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
        return $over + ['fullName' => 'Ridmi Perera', 'address' => 'No 1, Galle Road'];
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

    public function test_an_update_sends_only_the_allowed_fields_in_the_api_format(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/' . self::UUID => $this->ok($this->profile(['fullName' => 'Ridmi Perera']))]);

        $this->asCustomer()->putJson('/profile', $this->valid(['customerId' => self::UUID2, 'role' => 'Admin', 'email' => 'x@y.z']))
            ->assertOk()->assertJson(['success' => true, 'message' => 'Profile updated successfully.']);

        $this->assertSentBody('PUT', '/api/Customers/' . self::UUID, ['FullName' => 'Ridmi Perera', 'PhoneNumber' => '0771234567', 'Address' => 'No 1, Galle Road']);
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

        $this->asCustomer()->putJson('/profile', ['fullName' => 'New Name'])->assertOk();

        $this->assertSame('New Name', session('customer_name'));
        // the address was not touched, so the saved one is kept (it used to be wiped to null)
        $this->assertSame(['FullName' => 'New Name', 'PhoneNumber' => '0771234567', 'Address' => 'Colombo'], $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data());
    }

    public static function goodNames(): array
    {
        return [['Nuwan Akalanka'], ["Conan O'Brien"], ['Anne-Marie Silva'], ['නුවන් අකලංක'], ['நுவன் அகலங்க'], ['José Núñez']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('goodNames')]
    public function test_real_names_in_any_language_are_accepted(string $name): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', $this->valid(['fullName' => $name]))->assertOk();

        $this->assertSame($name, $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['FullName']);
    }

    public function test_extra_spaces_in_a_name_are_tidied_before_saving(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', $this->valid(['fullName' => '  Nuwan    Akalanka ']))->assertOk();

        $this->assertSame('Nuwan Akalanka', $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['FullName']);
    }

    public function test_the_name_error_message_is_plain_and_helpful(): void
    {
        Http::fake();

        $res = $this->asCustomer()->putJson('/profile', $this->valid(['fullName' => 'Nuwan123']))->assertStatus(422);

        $this->assertStringContainsString('no numbers', $res->json('errors.fullName.0'));
    }

    public static function goodAddresses(): array
    {
        return [['123/A, Main Street, Colombo 07'], ['No 12 Galle Road Kandy'], ['දන්කොටුව, කොළඹ'], ['கொழும்பு, 12/3'], ['Dankotuwa']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('goodAddresses')]
    public function test_addresses_with_letters_numbers_commas_and_slashes_are_accepted(string $address): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', ['address' => $address])->assertOk();

        $this->assertSame($address, $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['Address']);
    }

    public function test_line_breaks_in_an_address_become_single_spaces(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', ['address' => "No 5,\n  Main Street,\r\nColombo"])->assertOk();

        $this->assertSame('No 5, Main Street, Colombo', $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['Address']);
    }

    public function test_an_address_can_be_cleared(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', ['address' => ''])->assertOk();

        $this->assertNull($this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['Address']);
    }

    public function test_editing_one_field_keeps_the_other_as_saved_even_if_it_would_not_pass_todays_rules(): void
    {
        // An old profile: a name with digits and an address with a dot, saved before these rules existed.
        $old = $this->profile(['fullName' => 'Nuwan 2', 'address' => 'No. 5, Main St']);
        $this->fakeApi(['Customers/me' => $this->ok($old), 'Customers/*' => $this->ok($old)]);

        $this->asCustomer()->putJson('/profile', ['fullName' => 'Nuwan Akalanka'])->assertOk();
        $this->assertSame('No. 5, Main St', $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['Address']);

        $this->fakeApi(['Customers/me' => $this->ok($old), 'Customers/*' => $this->ok($old)]);
        $this->asCustomer()->putJson('/profile', ['address' => '5/A, Main Street'])->assertOk();
        $this->assertSame('Nuwan 2', $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['FullName']);
    }

    public function test_an_update_with_no_editable_field_is_refused(): void
    {
        Http::fake();

        $this->asCustomer()->putJson('/profile', ['phoneNumber' => '0779999999'])->assertStatus(422);

        Http::assertNothingSent();
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
            'name with digits' => [['fullName' => 'Nuwan123'], 'fullName'],
            'name only digits' => [['fullName' => '12345'], 'fullName'],
            'name with symbols'=> [['fullName' => 'Nuwan@Perera'], 'fullName'],
            'name with html'   => [['fullName' => '<b>Nuwan</b>'], 'fullName'],
            'name one letter'  => [['fullName' => 'A'], 'fullName'],
            'name with emoji'  => [['fullName' => 'Nuwan 😀'], 'fullName'],
            'address too long' => [['address' => str_repeat('a', 301)], 'address'],
            'address symbols'  => [['address' => 'No 5 @ Main #2'], 'address'],
            'address dot'      => [['address' => 'No. 5, Main Street'], 'address'],
            'address hyphen'   => [['address' => '5-A Main Street'], 'address'],
            'address html'     => [['address' => '<b>Main Street</b>'], 'address'],
            'address only num' => [['address' => '12345'], 'address'],
            'address emoji'    => [['address' => 'Colombo 😀'], 'address'],
            'address semicolon'=> [['address' => 'Colombo; DROP TABLE'], 'address'],
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

    public static function triedPhones(): array
    {
        return [['0779999999'], ['+94779999999'], ['abc'], [''], ['<b>1</b>']];
    }

    /** The phone number is the sign-in identity: PUT /profile can never change it, whatever the browser sends. */
    #[\PHPUnit\Framework\Attributes\DataProvider('triedPhones')]
    public function test_the_profile_update_can_never_change_the_phone_number(string $tried): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->profile()), 'Customers/*' => $this->ok($this->profile())]);

        $this->asCustomer()->putJson('/profile', $this->valid(['phoneNumber' => $tried]))->assertOk();

        $this->assertSame('0771234567', $this->sentTo('PUT', '/api/Customers/' . self::UUID)->data()['PhoneNumber']);
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
