<?php

namespace Tests\Feature;

use App\Services\FirebaseTokenVerifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Web sign-up: /register only keeps the details; /register/password lets the browser attach an optional
 * email + password; POST /register/complete creates the profile with the FRESH token.
 * There is no email verification (owner decision, 2 Nov 2026): the phone OTP is the only identity check.
 */
class RegistrationFlowTest extends TestCase
{
    private const API = 'https://api.test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['shalotrack.api_base_url' => self::API, 'shalotrack.firebase_project_id' => 'proj']);
    }

    private function signedIn(array $extra = []): static
    {
        return $this->withSession([
            'firebase_token'            => 'phone-login-token',
            'firebase_uid'              => 'u1',
            'firebase_phone'            => '+94714915401',
            'firebase_token_expires_at' => time() + 3000,
        ] + $extra);
    }

    private function pending(array $over = []): array
    {
        return ['pending_profile' => $over + [
            'fullName' => 'Charith Silva', 'email' => 'Charith@Example.com',
            'nicNumber' => '198648697512', 'address' => 'Haldaduwana',
        ]];
    }

    private function verifierReturns(array $payload): void
    {
        $this->mock(FirebaseTokenVerifier::class, fn ($m) => $m->shouldReceive('verify')->andReturn($payload));
    }

    private function goodPayload(array $over = []): array
    {
        return $over + ['sub' => 'u1', 'email' => 'charith@example.com', 'exp' => time() + 3000];
    }

    public function test_register_only_keeps_the_details_and_does_not_call_the_api(): void
    {
        Http::fake();

        $this->signedIn()->post('/register', [
            'fullName' => 'Charith Silva', 'email' => 'charith@example.com',
            'nicNumber' => '198648697512', 'address' => 'Haldaduwana',
        ])->assertRedirect('/register/password')
          ->assertSessionHas('pending_profile.email', 'charith@example.com')
          ->assertSessionMissing('pending_verification_email');

        Http::assertNothingSent();
    }

    public static function badRegistrationNames(): array
    {
        return [['Charith123'], ['12345'], ['<b>Charith</b>'], ['Charith@Silva'], ['C'], ['Charith 😀']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badRegistrationNames')]
    public function test_registration_refuses_a_name_with_numbers_or_symbols(string $name): void
    {
        Http::fake();

        $this->signedIn()->post('/register', [
            'fullName' => $name, 'email' => 'charith@example.com', 'nicNumber' => '198648697512', 'address' => 'Haldaduwana',
        ])->assertSessionHasErrors('fullName')->assertSessionMissing('pending_profile');

        Http::assertNothingSent();
    }

    public function test_registration_accepts_a_sinhala_name_and_tidies_spaces(): void
    {
        Http::fake();

        $this->signedIn()->post('/register', [
            'fullName' => '  නුවන්   අකලංක ', 'email' => 'charith@example.com', 'nicNumber' => '198648697512', 'address' => 'Haldaduwana',
        ])->assertRedirect('/register/password')->assertSessionHas('pending_profile.fullName', 'නුවන් අකලංක');
    }

    public static function badRegistrationAddresses(): array
    {
        return [['Main @ Street'], ['Main_Street 5'], ['Main; Street'], ['<b>Colombo</b>'], ['12345'], ['Colombo 😀']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badRegistrationAddresses')]
    public function test_registration_refuses_an_address_with_other_characters(string $address): void
    {
        Http::fake();

        $this->signedIn()->post('/register', [
            'fullName' => 'Charith Silva', 'email' => 'charith@example.com', 'nicNumber' => '198648697512', 'address' => $address,
        ])->assertSessionHasErrors('address')->assertSessionMissing('pending_profile');

        Http::assertNothingSent();
    }

    public function test_registration_accepts_and_tidies_a_normal_address(): void
    {
        Http::fake();

        $this->signedIn()->post('/register', [
            'fullName' => 'Charith Silva', 'email' => 'charith@example.com', 'nicNumber' => '198648697512', 'address' => "123/A,\n Main Street,  Colombo 07",
        ])->assertRedirect('/register/password')->assertSessionHas('pending_profile.address', '123/A, Main Street, Colombo 07');
    }

    public function test_password_page_without_details_goes_back_to_the_form(): void
    {
        $this->signedIn()->get('/register/password')->assertRedirect('/register');
    }

    public function test_password_page_shows_the_email_for_the_login(): void
    {
        $this->signedIn($this->pending())->get('/register/password')->assertOk()->assertSee('Charith@Example.com');
    }

    public function test_password_page_needs_a_session(): void
    {
        $this->get('/register/password')->assertRedirect('/login');
    }

    public function test_profile_is_created_with_the_fresh_token(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response(['success' => true, 'data' => ['customerId' => 'c1']], 200)]);

        $this->signedIn($this->pending())
            ->postJson('/register/complete', ['idToken' => 'fresh-token'])
            ->assertOk()->assertJson(['success' => true, 'redirect' => '/dashboard'])
            ->assertSessionHas('firebase_token', 'fresh-token')
            ->assertSessionMissing('pending_profile');

        Http::assertSent(function (Request $r) {
            return $r->method() === 'POST' && $r->url() === self::API . '/api/Customers'
                && $r->hasHeader('Authorization', 'Bearer fresh-token')   // not the phone-login token
                && $r['Email'] === 'Charith@Example.com' && $r['FullName'] === 'Charith Silva'
                && $r['PhoneNumber'] === '+94714915401' && $r['NicNumber'] === '198648697512';
        });
    }

    public function test_email_is_not_verified_and_a_token_without_the_claim_still_works(): void
    {
        // Owner decision (2 Nov 2026): no email verification. A token with email_verified=false, or with no
        // email at all (customer skipped the password), must still create the profile.
        $this->verifierReturns(['sub' => 'u1', 'email_verified' => false, 'exp' => time() + 3000]);
        Http::fake([self::API . '/api/Customers' => Http::response(['success' => true, 'data' => ['customerId' => 'c1']], 200)]);

        $this->signedIn($this->pending())
            ->postJson('/register/complete', ['idToken' => 't'])
            ->assertOk()->assertJson(['success' => true, 'redirect' => '/dashboard'])
            ->assertSessionMissing('pending_profile');

        Http::assertSentCount(1);
    }

    public function test_token_of_another_account_is_refused(): void
    {
        $this->verifierReturns($this->goodPayload(['sub' => 'someone-else']));
        Http::fake();

        $this->signedIn($this->pending())->postJson('/register/complete', ['idToken' => 't'])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_an_account_email_that_is_not_the_typed_one_is_refused(): void
    {
        $this->verifierReturns($this->goodPayload(['email' => 'other@example.com']));
        Http::fake();

        $this->signedIn($this->pending())->postJson('/register/complete', ['idToken' => 't'])
            ->assertStatus(422)->assertJson(['restart' => true]);
        Http::assertNothingSent();
    }

    public function test_profile_that_already_exists_counts_as_success(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response(['message' => 'Profile already exists.', 'errors' => ['This account already has a customer profile.']], 409)]);

        $this->signedIn($this->pending())->postJson('/register/complete', ['idToken' => 't'])
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_duplicate_nic_gets_a_clear_message_and_a_way_back(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response(['message' => 'NIC already exists.', 'errors' => ['A customer with this NIC already exists.']], 409)]);

        $this->signedIn($this->pending())->postJson('/register/complete', ['idToken' => 't'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'restart' => true])
            ->assertJsonPath('message', 'An account with this NIC number already exists. Check the NIC, or sign in with the phone number you used before.');
    }

    public function test_api_outage_gives_a_reference_and_keeps_the_details_for_a_retry(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response('boom', 500)]);

        $this->signedIn($this->pending())->postJson('/register/complete', ['idToken' => 't'])
            ->assertStatus(422)->assertJsonStructure(['message', 'reference'])
            ->assertSessionHas('pending_profile');   // still there: the page retries on its own
    }

    public function test_mark_verified_needs_a_session(): void
    {
        $this->postJson('/register/complete', ['idToken' => 't'])->assertStatus(401);
    }

    public function test_register_form_is_prefilled_when_the_customer_comes_back_to_fix_a_detail(): void
    {
        Http::fake([self::API . '/api/Customers/me' => Http::response([], 404)]);

        $this->signedIn($this->pending())->get('/register')->assertOk()
            ->assertSee('198648697512', false)->assertSee('Charith Silva', false);
    }
}