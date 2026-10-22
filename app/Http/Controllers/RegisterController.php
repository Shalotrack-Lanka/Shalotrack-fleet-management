<?php

namespace Tests\Feature;

use App\Services\FirebaseTokenVerifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression: "Failed to create your profile" on every web sign-up.
 * The API refuses POST /api/Customers unless the bearer token already says email_verified = true, but the
 * web flow created the profile FIRST and verified the email afterwards, so it could never succeed.
 * Now: /register only keeps the details; the profile is created after the email is verified,
 * using the fresh token.
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
        ], 'pending_verification_email' => 'Charith@Example.com', 'email_verified' => false];
    }

    private function verifierReturns(array $payload): void
    {
        $this->mock(FirebaseTokenVerifier::class, fn ($m) => $m->shouldReceive('verify')->andReturn($payload));
    }

    private function goodPayload(array $over = []): array
    {
        return $over + ['sub' => 'u1', 'email_verified' => true, 'email' => 'charith@example.com', 'exp' => time() + 3000];
    }

    public function test_register_only_keeps_the_details_and_does_not_call_the_api(): void
    {
        Http::fake();

        $this->signedIn()->post('/register', [
            'fullName' => 'Charith Silva', 'email' => 'charith@example.com',
            'nicNumber' => '198648697512', 'address' => 'Haldaduwana',
        ])->assertRedirect('/email/verify')
          ->assertSessionHas('pending_profile.email', 'charith@example.com')
          ->assertSessionHas('email_verified', false);

        Http::assertNothingSent();
    }

    public function test_verify_page_without_details_goes_back_to_the_form(): void
    {
        $this->signedIn()->get('/email/verify')->assertRedirect('/register');
    }

    public function test_verify_page_shows_the_email_being_verified(): void
    {
        $this->signedIn($this->pending())->get('/email/verify')->assertOk()->assertSee('Charith@Example.com');
    }

    public function test_verify_page_needs_a_session(): void
    {
        $this->get('/email/verify')->assertRedirect('/login');
    }

    public function test_profile_is_created_after_verification_with_the_fresh_token(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response(['success' => true, 'data' => ['customerId' => 'c1']], 200)]);

        $this->signedIn($this->pending())
            ->postJson('/email/mark-verified', ['idToken' => 'fresh-verified-token'])
            ->assertOk()->assertJson(['success' => true, 'redirect' => '/dashboard'])
            ->assertSessionHas('email_verified', true)
            ->assertSessionHas('firebase_token', 'fresh-verified-token')
            ->assertSessionMissing('pending_profile');

        Http::assertSent(function (Request $r) {
            return $r->method() === 'POST' && $r->url() === self::API . '/api/Customers'
                && $r->hasHeader('Authorization', 'Bearer fresh-verified-token')   // not the phone-login token
                && $r['Email'] === 'Charith@Example.com' && $r['FullName'] === 'Charith Silva'
                && $r['PhoneNumber'] === '+94714915401' && $r['NicNumber'] === '198648697512';
        });
    }

    public function test_unverified_token_is_refused_and_nothing_is_created(): void
    {
        $this->verifierReturns($this->goodPayload(['email_verified' => false]));
        Http::fake();

        $this->signedIn($this->pending())
            ->postJson('/email/mark-verified', ['idToken' => 't'])
            ->assertStatus(422)->assertJson(['success' => false])
            ->assertSessionHas('email_verified', false);

        Http::assertNothingSent();
    }

    public function test_token_of_another_account_is_refused(): void
    {
        $this->verifierReturns($this->goodPayload(['sub' => 'someone-else']));
        Http::fake();

        $this->signedIn($this->pending())->postJson('/email/mark-verified', ['idToken' => 't'])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_a_verified_email_that_is_not_the_typed_one_is_refused(): void
    {
        $this->verifierReturns($this->goodPayload(['email' => 'other@example.com']));
        Http::fake();

        $this->signedIn($this->pending())->postJson('/email/mark-verified', ['idToken' => 't'])
            ->assertStatus(422)->assertJson(['restart' => true]);
        Http::assertNothingSent();
    }

    public function test_profile_that_already_exists_counts_as_success(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response(['message' => 'Profile already exists.', 'errors' => ['This account already has a customer profile.']], 409)]);

        $this->signedIn($this->pending())->postJson('/email/mark-verified', ['idToken' => 't'])
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_duplicate_nic_gets_a_clear_message_and_a_way_back(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response(['message' => 'NIC already exists.', 'errors' => ['A customer with this NIC already exists.']], 409)]);

        $this->signedIn($this->pending())->postJson('/email/mark-verified', ['idToken' => 't'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'restart' => true])
            ->assertJsonPath('message', 'An account with this NIC number already exists. Check the NIC, or sign in with the phone number you used before.');
    }

    public function test_api_outage_gives_a_reference_and_keeps_the_details_for_a_retry(): void
    {
        $this->verifierReturns($this->goodPayload());
        Http::fake([self::API . '/api/Customers' => Http::response('boom', 500)]);

        $this->signedIn($this->pending())->postJson('/email/mark-verified', ['idToken' => 't'])
            ->assertStatus(422)->assertJsonStructure(['message', 'reference'])
            ->assertSessionHas('pending_profile');   // still there: the page retries on its own
    }

    public function test_mark_verified_needs_a_session(): void
    {
        $this->postJson('/email/mark-verified', ['idToken' => 't'])->assertStatus(401);
    }

    public function test_register_form_is_prefilled_when_the_customer_comes_back_to_fix_a_detail(): void
    {
        Http::fake([self::API . '/api/Customers/me' => Http::response([], 404)]);

        $this->signedIn($this->pending())->get('/register')->assertOk()
            ->assertSee('198648697512', false)->assertSee('Charith Silva', false);
    }
}