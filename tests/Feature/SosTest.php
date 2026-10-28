<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F4: the SOS button (POST /sos/{vehicleId}).
 *
 * SOS is the one action where a wrong answer can hurt someone, so these tests pin: it only goes to the API
 * for a real vehicle id, every trigger leaves an audit line, a broken contacts list never blocks the alarm, and
 * a customer is told clearly (and truthfully) when it did not go out.
 */
class SosTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function contacts(): array
    {
        return ['data' => [
            ['contactId' => 'c1', 'customerId' => self::UUID2, 'name' => 'Amma', 'phoneNumber' => '0771234567', 'relationship' => 'Mother', 'createdAt' => '2026-01-01', 'isVerified' => true],
            ['contactId' => 'c2', 'name' => 'Thaththa', 'phoneNumber' => '0719876543'],
        ]];
    }

    public function test_a_stranger_cannot_trigger_sos_and_the_api_is_not_called(): void
    {
        Http::fake();

        $this->postJson('/sos/' . self::UUID)->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');

        Http::assertNothingSent();
    }

    public function test_sos_is_post_only(): void
    {
        $this->asCustomer()->getJson('/sos/' . self::UUID)->assertStatus(405);
    }

    public function test_only_a_real_vehicle_uuid_can_be_sent_to_the_api(): void
    {
        Http::fake();

        foreach (['abc', '123', 'x%3Fy', self::UUID . 'z'] as $bad) {
            $this->asCustomer()->postJson('/sos/' . $bad)->assertNotFound();
        }

        Http::assertNothingSent();
    }

    public function test_trigger_calls_the_api_for_that_vehicle_with_the_customers_token_and_returns_the_contacts(): void
    {
        $this->fakeApi(['SOS/*' => $this->ok(), 'EmergencyContacts' => $this->ok($this->contacts())]);

        $res = $this->asCustomer('uid-9')->postJson('/sos/' . self::UUID)->assertOk()->assertJsonPath('success', true);

        $req = $this->sentTo('POST', '/api/SOS/' . self::UUID . '/trigger');
        $this->assertNotNull($req);
        $this->assertSame('Bearer tok-uid-9', $req->header('Authorization')[0]);
        $this->assertCount(2, $res->json('contacts'));
        $this->assertSame(['name' => 'Amma', 'phoneNumber' => '0771234567', 'relationship' => 'Mother'], $res->json('contacts.0'));
        $this->assertSame(['name' => 'Thaththa', 'phoneNumber' => '0719876543', 'relationship' => null], $res->json('contacts.1'));
    }

    public function test_only_name_phone_and_relationship_of_each_contact_reach_the_browser(): void
    {
        $this->fakeApi(['SOS/*' => $this->ok(), 'EmergencyContacts' => $this->ok($this->contacts())]);

        $raw = $this->asCustomer()->postJson('/sos/' . self::UUID)->getContent();

        foreach (['contactId', 'customerId', self::UUID2, 'createdAt', 'isVerified'] as $leak) {
            $this->assertStringNotContainsString($leak, $raw);
        }
    }

    public function test_every_trigger_is_written_to_the_audit_log_with_who_and_from_where(): void
    {
        Log::spy();
        $this->fakeApi(['SOS/*' => $this->ok(), 'EmergencyContacts' => $this->ok(['data' => []])]);

        $this->asCustomer('uid-1', ['customer_id' => self::UUID2])->postJson('/sos/' . self::UUID)->assertOk();

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) {
            return $message === 'SOS triggered from web portal'
                && ($context['vehicle_id'] ?? null) === self::UUID
                && ($context['customer_id'] ?? null) === self::UUID2
                && array_key_exists('ip', $context);
        })->once();
    }

    public function test_a_broken_contacts_list_never_blocks_the_alarm(): void
    {
        $this->fakeApi(['SOS/*' => $this->ok(), 'EmergencyContacts' => Http::response(['message' => 'down'], 500)]);

        $this->asCustomer()->postJson('/sos/' . self::UUID)
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('contacts', []);
    }

    public function test_when_the_alarm_itself_fails_no_audit_line_claims_it_was_sent_and_contacts_are_not_fetched(): void
    {
        Log::spy();
        $this->fakeApi(['SOS/*' => Http::response(['message' => 'x'], 500), 'EmergencyContacts' => $this->ok($this->contacts())]);

        $this->asCustomer()->postJson('/sos/' . self::UUID)->assertStatus(502);

        Log::shouldNotHaveReceived('warning', fn ($message) => $message === 'SOS triggered from web portal');
        $this->assertNull($this->sentTo('GET', '/api/EmergencyContacts'));
    }

    public static function failures(): array
    {
        return [
            'subscription expired' => [402, 402, "This vehicle's subscription has expired. Renew it to use SOS."],
            'not your vehicle'     => [403, 403, "SOS can't be sent for this vehicle."],
            'no such vehicle'      => [404, 404, "SOS can't be sent for this vehicle."],
            'server error'         => [500, 502, "Couldn't send SOS. Please try again."],
            'bad request'          => [400, 502, "Couldn't send SOS. Please try again."],
        ];
    }

    /** @dataProvider failures */
    #[\PHPUnit\Framework\Attributes\DataProvider('failures')]
    public function test_each_failure_tells_the_customer_the_alarm_did_not_go_out(int $apiStatus, int $httpStatus, string $message): void
    {
        $this->fakeApi(['SOS/*' => Http::response(['message' => 'SQL_ERROR at /srv/x'], $apiStatus)]);

        $res = $this->asCustomer()->postJson('/sos/' . self::UUID)->assertStatus($httpStatus);

        $this->assertFalse($res->json('success'));
        $this->assertSame($message, $res->json('message'));
        $this->assertStringNotContainsString('SQL_ERROR', $res->getContent());
    }

    public function test_an_expired_api_token_signs_the_customer_out(): void
    {
        $this->fakeApi(['SOS/*' => Http::response([], 401)]);

        $this->asCustomer()->postJson('/sos/' . self::UUID)->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');

        $this->assertNull(session('firebase_token'));
    }

    public function test_an_account_waiting_for_deletion_cannot_use_sos_from_the_web(): void
    {
        Http::fake();

        $this->asCustomer('uid-1', ['deletion_pending' => true])->postJson('/sos/' . self::UUID)
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_PENDING_DELETION');

        Http::assertNothingSent();
    }

    public function test_sos_is_limited_to_3_a_minute_for_a_customer_and_the_4th_never_reaches_the_api(): void
    {
        $this->fakeApi(['SOS/*' => $this->ok(), 'EmergencyContacts' => $this->ok(['data' => []])]);

        for ($i = 1; $i <= 3; $i++) {
            $this->asCustomer('uid-A')->postJson('/sos/' . self::UUID)->assertOk();
        }
        $this->asCustomer('uid-A')->postJson('/sos/' . self::UUID)->assertStatus(429);

        $sos = collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), '/api/SOS/'))->count();
        $this->assertSame(3, $sos);
    }
}
