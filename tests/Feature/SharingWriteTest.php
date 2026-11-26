<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F3: sharing a vehicle with another customer by phone number.
 * The API answers an invite the same way whether or not the number has a ShaloTrack account. Each invite still
 * pushes to a real person, so junk numbers never cost an API call, and invites are capped per minute and per day,
 * per signed-in customer.
 */
class SharingWriteTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    public static function phoneFormats(): array
    {
        return [
            'local with 0'         => ['0771234567', '+94771234567'],
            'local without 0'      => ['771234567', '+94771234567'],
            'with country code'    => ['94771234567', '+94771234567'],
            'international'        => ['+94771234567', '+94771234567'],
            'spaces'               => ['077 123 4567', '+94771234567'],
            'dashes'               => ['077-123-4567', '+94771234567'],
            'brackets and spaces'  => ['+94 (77) 123 4567', '+94771234567'],
        ];
    }

    /** @dataProvider phoneFormats */
    #[\PHPUnit\Framework\Attributes\DataProvider('phoneFormats')]
    public function test_the_phone_number_is_sent_to_the_api_in_international_format(string $typed, string $expected): void
    {
        $this->fakeApi(['VehicleShares/invite' => $this->ok(['data' => ['shareId' => self::UUID2]])]);

        $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID, 'phoneNumber' => $typed])
            ->assertOk()->assertJsonPath('data.shareId', self::UUID2);

        $this->assertSentBody('POST', '/api/VehicleShares/invite', ['VehicleId' => self::UUID, 'PhoneNumber' => $expected]);
    }

    public static function junkPhones(): array
    {
        return [
            'letters only'      => ['abcdefg'],
            'too short'         => ['12345'],
            'eight digits'      => ['12345678'],
            'too long'          => ['+1234567890123456'],
            'symbols'           => ['+++---'],
            'foreign number'    => ['+44 7911 123456'],
            'sri lankan landline' => ['011 234 5678'],
        ];
    }

    /** @dataProvider junkPhones */
    #[\PHPUnit\Framework\Attributes\DataProvider('junkPhones')]
    public function test_a_junk_number_is_refused_without_asking_the_api_whether_it_has_an_account(string $phone): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID, 'phoneNumber' => $phone])
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_PHONE');

        Http::assertNothingSent();
    }

    public function test_the_invite_needs_a_vehicle_uuid_and_a_phone_number(): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/sharing', ['vehicleId' => 'abc', 'phoneNumber' => '0771234567'])->assertStatus(422)->assertJsonValidationErrors('vehicleId');
        $this->asCustomer()->postJson('/sharing', ['phoneNumber' => '0771234567'])->assertStatus(422)->assertJsonValidationErrors('vehicleId');
        $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID])->assertStatus(422)->assertJsonValidationErrors('phoneNumber');
        $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID, 'phoneNumber' => str_repeat('1', 26)])->assertStatus(422)->assertJsonValidationErrors('phoneNumber');

        Http::assertNothingSent();
    }

    public function test_a_missing_vehicle_gets_a_plain_message_and_the_api_text_is_not_passed_through(): void
    {
        $this->fakeApi(['VehicleShares/invite' => Http::response(['message' => 'VEHICLE_NOT_FOUND:abc'], 404)]);

        $res = $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID, 'phoneNumber' => '0771234567'])->assertStatus(422);

        $this->assertSame('That vehicle is no longer available. Please refresh the page.', $res->json('message'));
        $this->assertStringNotContainsString('VEHICLE_NOT_FOUND', $res->getContent());
        $this->assertStringNotContainsString('account', $res->getContent());
    }

    public function test_a_duplicate_invite_shows_the_apis_plain_words(): void
    {
        $this->fakeApi(['VehicleShares/invite' => Http::response(['message' => "You've already shared this vehicle with this number."], 409)]);

        $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID, 'phoneNumber' => '0771234567'])
            ->assertStatus(422)->assertJsonPath('message', "You've already shared this vehicle with this number.");
    }

    public function test_a_409_with_a_machine_code_is_not_passed_through(): void
    {
        $this->fakeApi(['VehicleShares/invite' => Http::response(['message' => 'DUPLICATE_SHARE'], 409)]);

        $this->asCustomer()->postJson('/sharing', ['vehicleId' => self::UUID, 'phoneNumber' => '0771234567'])
            ->assertStatus(422)->assertJsonPath('message', "You've already shared this vehicle with this number.");
    }

    public function test_extra_browser_fields_are_not_forwarded_on_an_invite(): void
    {
        $this->fakeApi(['VehicleShares/invite' => $this->ok()]);

        $this->asCustomer()->postJson('/sharing', [
            'vehicleId' => self::UUID, 'phoneNumber' => '0771234567', 'status' => 'Accepted', 'ownerId' => self::UUID2,
        ])->assertOk();

        $this->assertEqualsCanonicalizing(['VehicleId', 'PhoneNumber'], array_keys($this->sentTo('POST', '/api/VehicleShares/invite')->data()));
    }

    // ------------------------------------------------------------------ the invite limiter

    public function test_a_customer_can_send_5_invites_a_minute_then_is_slowed_down(): void
    {
        $this->fakeApi(['VehicleShares/invite' => $this->ok()]);
        $body = ['vehicleId' => self::UUID, 'phoneNumber' => '0771234567'];

        for ($i = 1; $i <= 5; $i++) {
            $this->asCustomer('uid-A')->postJson('/sharing', $body)->assertOk();
        }
        $this->asCustomer('uid-A')->postJson('/sharing', $body)->assertStatus(429);

        Http::assertSentCount(5);   // the 6th never reached the API
    }

    public function test_the_invite_limit_is_per_customer_not_shared(): void
    {
        $this->fakeApi(['VehicleShares/invite' => $this->ok()]);
        $body = ['vehicleId' => self::UUID, 'phoneNumber' => '0771234567'];

        for ($i = 1; $i <= 5; $i++) {
            $this->asCustomer('uid-A')->postJson('/sharing', $body)->assertOk();
        }
        $this->asCustomer('uid-A')->postJson('/sharing', $body)->assertStatus(429);

        $this->asCustomer('uid-B')->postJson('/sharing', $body)->assertOk();   // a different customer is unaffected
    }

    public function test_the_invite_limit_cannot_be_dodged_by_changing_the_forwarded_ip(): void
    {
        // Behind Cloudflare + ALB the client IP header can be forged. The limit follows the signed-in user.
        $this->fakeApi(['VehicleShares/invite' => $this->ok()]);
        $body = ['vehicleId' => self::UUID, 'phoneNumber' => '0771234567'];

        for ($i = 1; $i <= 5; $i++) {
            $this->asCustomer('uid-A')->withHeaders(['X-Forwarded-For' => "203.0.113.$i"])->postJson('/sharing', $body)->assertOk();
        }
        $this->asCustomer('uid-A')->withHeaders(['X-Forwarded-For' => '198.51.100.99'])->postJson('/sharing', $body)->assertStatus(429);
    }

    // ------------------------------------------------------------------ accept / decline / revoke

    public function test_accept_sends_accept_true_to_that_share(): void
    {
        $this->fakeApi(['VehicleShares/*' => $this->ok(['data' => ['status' => 'Accepted']])]);

        $this->asCustomer()->postJson('/sharing/' . self::UUID . '/accept')->assertOk()->assertJsonPath('data.status', 'Accepted');

        $this->assertSentBody('POST', '/api/VehicleShares/' . self::UUID . '/respond', ['Accept' => true]);
    }

    public function test_decline_sends_accept_false_and_returns_no_data(): void
    {
        $this->fakeApi(['VehicleShares/*' => $this->ok(['data' => ['secret' => 'x']])]);

        $res = $this->asCustomer()->postJson('/sharing/' . self::UUID . '/decline')->assertOk();

        $this->assertSame(['success' => true], $res->json());
        $this->assertSentBody('POST', '/api/VehicleShares/' . self::UUID . '/respond', ['Accept' => false]);
    }

    public function test_revoke_deletes_that_share(): void
    {
        $this->fakeApi(['VehicleShares/*' => $this->ok()]);

        $this->asCustomer()->deleteJson('/sharing/' . self::UUID)->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($this->sentTo('DELETE', '/api/VehicleShares/' . self::UUID));
    }

    public function test_responding_to_a_share_that_is_not_yours_is_refused_by_the_api_and_reported_safely(): void
    {
        $this->fakeApi(['VehicleShares/*' => Http::response(['message' => 'Not your invite'], 403)]);

        $this->asCustomer()->postJson('/sharing/' . self::UUID . '/accept')
            ->assertStatus(422)->assertJsonPath('code', 'FORBIDDEN');
    }
}
