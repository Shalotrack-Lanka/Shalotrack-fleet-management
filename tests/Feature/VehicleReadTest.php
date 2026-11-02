<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: reading vehicles: the list (owned + accepted shares), the detail, location and the health card. */
class VehicleReadTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        Cache::flush();
    }

    private function profile(): \GuzzleHttp\Promise\PromiseInterface
    {
        return $this->ok(['data' => ['customerId' => self::UUID]]);
    }

    private function owned(array $over = []): array
    {
        return $over + ['vehicleId' => self::UUID2, 'vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio', 'year' => 2018, 'hasGpsDevice' => true];
    }

    private function share(array $over = []): array
    {
        return $over + ['shareId' => 's-1', 'vehicleId' => 'v-shared', 'vehicleNumber' => 'KY-9999', 'status' => 'Accepted', 'otherPartyName' => 'Nimal'];
    }

    // ---- list ---------------------------------------------------------------------------------------------

    public function test_the_list_shows_owned_vehicles_and_only_accepted_shares(): void
    {
        $this->fakeApi([
            'Customers/me' => $this->profile(),
            'Vehicles/customer/*' => $this->ok(['data' => [$this->owned()]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => [$this->share(), $this->share(['shareId' => 's-2', 'vehicleNumber' => 'PENDING-1', 'status' => 'Pending']), $this->share(['shareId' => 's-3', 'vehicleNumber' => 'REVOKED-1', 'status' => 'Revoked'])]]),
        ]);

        $this->asCustomer()->get('/vehicles')->assertOk()->assertSee('CAB-1234')->assertSee('KY-9999')->assertDontSee('PENDING-1')->assertDontSee('REVOKED-1');

        $this->assertSame(self::UUID, session('customer_id'));
        $this->assertNotNull($this->sentTo('GET', '/api/Vehicles/customer/' . self::UUID));
    }

    public function test_html_in_a_vehicle_name_is_shown_as_text(): void
    {
        $this->fakeApi([
            'Customers/me' => $this->profile(),
            'Vehicles/customer/*' => $this->ok(['data' => [$this->owned(['vehicleNumber' => '<img src=x onerror=alert(1)>'])]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => []]),
        ]);

        $this->asCustomer()->get('/vehicles')->assertOk()->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_no_profile_id_shows_a_clear_message(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok(['data' => []])]);

        $this->asCustomer()->get('/vehicles')->assertOk()->assertSee('Could not load your profile.');

        $this->assertNull($this->sentTo('GET', '/api/Vehicles/customer/'));
    }

    public function test_an_api_error_shows_a_retry_message_not_a_stack_trace(): void
    {
        $this->fakeApi(['Customers/me' => $this->profile(), 'Vehicles/customer/*' => Http::response(['message' => 'System.Data.SqlClient'], 500)]);

        $this->asCustomer()->get('/vehicles')->assertOk()->assertSee('Could not load vehicles. Please refresh.')->assertDontSee('SqlClient');
    }

    public function test_odd_api_shapes_do_not_crash_the_list(): void
    {
        $this->fakeApi(['Customers/me' => $this->profile(), 'Vehicles/customer/*' => $this->ok(['data' => 'oops']), 'VehicleShares/shared-with-me' => $this->ok(['data' => null])]);

        $this->asCustomer()->get('/vehicles')->assertOk();
    }

    // ---- detail -------------------------------------------------------------------------------------------

    public function test_the_detail_page_shows_the_vehicle(): void
    {
        $this->fakeApi(['Vehicles/' . self::UUID2 => $this->ok(['data' => $this->owned()])]);

        $this->asCustomer()->get('/vehicles/' . self::UUID2)->assertOk()->assertSee('CAB-1234');
    }

    public function test_an_unknown_vehicle_is_a_plain_404(): void
    {
        $this->fakeApi(['Vehicles/*' => Http::response(['message' => 'nope'], 404)]);

        $this->asCustomer()->get('/vehicles/' . self::UUID2)->assertNotFound();
    }

    public function test_a_server_error_shows_the_page_with_a_message(): void
    {
        $this->fakeApi(['Vehicles/*' => Http::response([], 500)]);

        $this->asCustomer()->get('/vehicles/' . self::UUID2)->assertOk()->assertSee('Could not load vehicle details.');
    }

    // ---- json reads ----------------------------------------------------------------------------------------

    public function test_the_json_vehicle_and_location_pass_the_api_answer_through(): void
    {
        $this->fakeApi(['Vehicles/*' => $this->ok(['data' => $this->owned()]), 'CurrentLocations/vehicle/*' => $this->ok(['data' => ['latitude' => 6.9, 'longitude' => 79.8]])]);

        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2)->assertOk()->assertJsonPath('data.vehicleNumber', 'CAB-1234');
        $this->asCustomer()->getJson('/api/CurrentLocations/vehicle/' . self::UUID2)->assertOk()->assertJsonPath('data.latitude', 6.9);
    }

    public static function failures(): array
    {
        return [[403, 403], [404, 404], [400, 502], [422, 502], [500, 502], [503, 502]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failures')]
    public function test_json_read_failures_become_403_404_or_502_with_a_short_message(int $api, int $http): void
    {
        $this->fakeApi(['*' => Http::response(['message' => 'Npgsql at 10.0.0.5'], $api)]);

        $res = $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2)->assertStatus($http)->assertJsonPath('success', false);

        $this->assertSame('Could not load vehicle.', $res->json('message'));
        $this->assertStringNotContainsString('10.0.0.5', $res->getContent());
    }

    public function test_a_401_on_a_json_read_ends_the_session(): void
    {
        $this->fakeApi(['*' => Http::response([], 401)]);

        $this->asCustomer()->getJson('/api/CurrentLocations/vehicle/' . self::UUID2)->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');

        $this->assertNull(session('firebase_token'));
    }

    // ---- health ---------------------------------------------------------------------------------------------

    public function test_health_returns_only_the_summary(): void
    {
        $now = now()->toIso8601ZuluString();
        $this->fakeApi(['DeviceStatus/vehicle/*' => $this->ok(['data' => ['isOnline' => true, 'lastSeen' => $now, 'lastHeartbeat' => $now, 'batteryLevel' => 15,
            'gpsSignal' => 4, 'powerStatus' => 0, 'imei' => '123456789012345', 'deviceId' => 'dev-secret']])]);

        $res = $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk()->assertJsonPath('level', 'warn')->assertJsonPath('headline', 'Tracker battery is low');

        $this->assertStringNotContainsString('123456789012345', $res->getContent());
        $this->assertStringNotContainsString('dev-secret', $res->getContent());
    }

    public function test_no_device_or_no_permission_hides_the_card_rather_than_erroring(): void
    {
        foreach ([403, 404] as $status) {
            Cache::flush();
            $this->fakeApi(['DeviceStatus/vehicle/*' => Http::response([], $status)]);

            $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk()->assertExactJson(['available' => false]);
        }
    }

    public function test_an_empty_record_hides_the_card(): void
    {
        $this->fakeApi(['DeviceStatus/vehicle/*' => $this->ok(['data' => null])]);

        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk()->assertExactJson(['available' => false]);
    }

    public function test_a_server_error_on_health_is_a_502(): void
    {
        $this->fakeApi(['DeviceStatus/vehicle/*' => Http::response([], 500)]);

        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertStatus(502)->assertJsonPath('message', 'Health unavailable.');
    }

    public function test_health_is_cached_for_a_short_time_so_polling_does_not_hammer_the_api(): void
    {
        $this->fakeApi(['DeviceStatus/vehicle/*' => $this->ok(['data' => ['isOnline' => true, 'lastSeen' => now()->toIso8601ZuluString()]])]);

        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk();
        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk();

        $this->assertCount(1, Http::recorded());
    }

    public function test_one_customers_cached_health_is_never_served_to_another(): void
    {
        $this->fakeApi(['DeviceStatus/vehicle/*' => $this->ok(['data' => ['isOnline' => true, 'lastSeen' => now()->toIso8601ZuluString()]])]);
        $this->asCustomer('uid-A')->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk();

        $this->fakeApi(['DeviceStatus/vehicle/*' => Http::response([], 404)]);   // B may not see this vehicle
        $this->asCustomer('uid-B')->getJson('/api/vehicles/' . self::UUID2 . '/health')->assertOk()->assertExactJson(['available' => false]);
    }
}
