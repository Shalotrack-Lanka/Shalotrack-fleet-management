<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: the profile page and the profile update. */
class ProfileTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function profile(array $over = []): array
    {
        return ['data' => $over + ['customerId' => self::UUID, 'fullName' => 'Ridmi P', 'phoneNumber' => '0771234567', 'address' => 'Colombo']];
    }

    private function valid(array $over = []): array
    {
        return $over + ['fullName' => 'Ridmi Perera', 'phoneNumber' => '077 123 4567', 'address' => 'No 1, Galle Road'];
    }

    public function test_the_page_shows_the_profile_from_the_api(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile())]);

        $this->asCustomer()->get('/profile')->assertOk()->assertSee('Ridmi P')->assertSee('0771234567');
    }

    public function test_a_name_with_html_is_shown_as_text_not_run(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile(['fullName' => '<script>alert(1)</script>']))]);

        $this->asCustomer()->get('/profile')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_when_the_api_is_down_the_page_says_so_instead_of_crashing(): void
    {
        $this->fakeApi(['Customers/me' => Http::response([], 500)]);

        $this->asCustomer()->get('/profile')->assertOk()->assertSee('Could not load profile');
    }

    public function test_a_401_from_the_api_sends_the_customer_to_sign_in_again(): void
    {
        $this->fakeApi(['Customers/me' => Http::response([], 401)]);

        $this->asCustomer()->get('/profile')->assertRedirect('/login?expired=1');

        $this->assertNull(session('firebase_token'));
    }

    public function test_a_stranger_cannot_open_or_change_a_profile(): void
    {
        Http::fake();

        $this->get('/profile')->assertRedirect('/login');
        $this->putJson('/profile', $this->valid())->assertStatus(401);
        Http::assertNothingSent();
    }

    // ---- update --------------------------------------------------------------------------------------------

    public function test_an_update_sends_only_the_three_allowed_fields_in_the_api_format(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/' . self::UUID => $this->ok($this->profile(['fullName' => 'Ridmi Perera']))]);

        $this->asCustomer()->putJson('/profile', $this->valid(['customerId' => self::UUID2, 'role' => 'Admin', 'email' => 'x@y.z']))
            ->assertOk()->assertJson(['success' => true, 'message' => 'Profile updated successfully.']);

        $this->assertSentBody('PUT', '/api/Customers/' . self::UUID, ['FullName' => 'Ridmi Perera', 'PhoneNumber' => '077 123 4567', 'Address' => 'No 1, Galle Road']);
    }

    public function test_the_customer_id_comes_from_the_api_never_from_the_browser(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', $this->valid(['customerId' => self::UUID2]))->assertOk();

        $this->assertNull($this->sentTo('PUT', '/api/Customers/' . self::UUID2));
        $this->assertNotNull($this->sentTo('PUT', '/api/Customers/' . self::UUID));
    }

    public function test_the_header_name_follows_the_update_and_address_is_optional(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', ['fullName' => 'New Name', 'phoneNumber' => '0771234567'])->assertOk();

        $this->assertSame('New Name', session('customer_name'));
        $this->assertSame(['FullName' => 'New Name', 'PhoneNumber' => '0771234567', 'Address' => null], $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data());
    }

    public function test_a_missing_profile_is_a_clean_404_and_nothing_is_written(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok(['data' => []])]);

        $this->asCustomer()->putJson('/profile', $this->valid())->assertStatus(404)->assertJson(['success' => false, 'message' => 'Profile not found.']);

        $this->assertNull(session('customer_name'));
    }

    public static function badUpdates(): array
    {
        return [
            'no name'          => [['fullName' => ''], 'fullName'],
            'name too long'    => [['fullName' => str_repeat('a', 151)], 'fullName'],
            'name is array'    => [['fullName' => ['x']], 'fullName'],
            'no phone'         => [['phoneNumber' => ''], 'phoneNumber'],
            'phone letters'    => [['phoneNumber' => '07712abcde'], 'phoneNumber'],
            'phone too short'  => [['phoneNumber' => '12345'], 'phoneNumber'],
            'phone too long'   => [['phoneNumber' => str_repeat('1', 21)], 'phoneNumber'],
            'phone with html'  => [['phoneNumber' => '<b>0771234567</b>'], 'phoneNumber'],
            'phone starts +-'  => [['phoneNumber' => '-0771234567'], 'phoneNumber'],
            'phone starts ('   => [['phoneNumber' => '(011) 234-5678'], 'phoneNumber'],   // known strictness: must start with a digit or +
            'address too long' => [['address' => str_repeat('a', 301)], 'address'],
            'address is array' => [['address' => ['x']], 'address'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badUpdates')]
    public function test_bad_input_is_refused_before_the_api_is_called(array $over, string $field): void
    {
        Http::fake();

        $this->asCustomer()->putJson('/profile', $this->valid($over))->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    public function test_the_phone_message_gives_an_example(): void
    {
        Http::fake();

        $res = $this->asCustomer()->putJson('/profile', $this->valid(['phoneNumber' => 'abc']))->assertStatus(422);

        $this->assertStringContainsString('077 123 4567', $res->json('errors.phoneNumber.0'));
    }

    public static function goodPhones(): array
    {
        return [['0771234567'], ['+94771234567'], ['077 123 4567'], ['011.234.5678'], ['1234567']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('goodPhones')]
    public function test_common_phone_formats_are_accepted(string $phone): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', $this->valid(['phoneNumber' => $phone]))->assertOk();
    }

    public function test_an_api_refusal_is_a_clear_message_and_the_session_name_is_not_changed(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/' . self::UUID => Http::response(['message' => 'SQL_CONSTRAINT'], 409)]);

        $res = $this->asCustomer()->putJson('/profile', $this->valid())->assertStatus(422);

        $this->assertSame('CONFLICT', $res->json('code'));
        $this->assertStringNotContainsString('SQL_CONSTRAINT', $res->getContent());
        $this->assertNull(session('customer_name'));
    }
}
