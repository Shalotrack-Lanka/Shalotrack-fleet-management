<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F3: create / edit / delete a vehicle and link / unlink a GPS device.
 * The security rules: the owner of a new vehicle is ALWAYS the signed-in customer, the browser cannot add
 * fields the portal did not intend to forward, and a bad IMEI costs no API call.
 */
class VehicleWriteTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function valid(array $over = []): array
    {
        return $over + ['vehicleNumber' => 'cab-1234', 'make' => 'Toyota', 'model' => 'Axio', 'year' => 2018];
    }

    // ------------------------------------------------------------------ create

    public function test_create_forwards_only_the_known_fields_with_the_plate_in_capitals(): void
    {
        $this->fakeApi(['Vehicles' => $this->ok(['success' => true, 'data' => ['vehicleId' => self::UUID]])]);

        $this->asCustomer('u', ['customer_id' => self::UUID2])
            ->postJson('/vehicles', $this->valid(['color' => 'White', 'fuelType' => 'Petrol']))
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.vehicleId', self::UUID);

        $this->assertSentBody('POST', '/api/Vehicles', [
            'CustomerId' => self::UUID2, 'VehicleNumber' => 'CAB-1234', 'Make' => 'Toyota', 'Model' => 'Axio', 'Year' => 2018,
            'Color' => 'White', 'VehicleType' => null, 'FuelType' => 'Petrol', 'ChassisNumber' => null, 'EngineNumber' => null,
        ]);
    }

    public function test_the_owner_comes_from_the_session_never_from_the_browser(): void
    {
        $this->fakeApi(['Vehicles' => $this->ok()]);

        // The attacker names somebody else's customer id in the request.
        $this->asCustomer('attacker', ['customer_id' => self::UUID])
            ->postJson('/vehicles', $this->valid(['customerId' => self::UUID2]))
            ->assertOk();

        $this->assertSame(self::UUID, $this->sentTo('POST', '/api/Vehicles')->data()['CustomerId']);
        $this->assertStringNotContainsString(self::UUID2, json_encode($this->sentTo('POST', '/api/Vehicles')->data()));
    }

    public function test_without_a_cached_customer_id_it_is_looked_up_from_the_api_profile_not_the_request(): void
    {
        $this->fakeApi([
            'Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]),
            'Vehicles'     => $this->ok(),
        ]);

        $this->asCustomer()->postJson('/vehicles', $this->valid(['customerId' => self::UUID2]))->assertOk();

        $this->assertSame(self::UUID, $this->sentTo('POST', '/api/Vehicles')->data()['CustomerId']);
        $this->assertSame(self::UUID, session('customer_id'), 'remembered for the next write');
    }

    public function test_a_profile_without_an_id_stops_the_create_instead_of_sending_an_empty_owner(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok(['data' => []]), 'Vehicles' => $this->ok()]);

        $this->asCustomer()->postJson('/vehicles', $this->valid())->assertStatus(422)->assertJsonPath('success', false);

        $this->assertNull($this->sentTo('POST', '/api/Vehicles'));
    }

    public function test_extra_browser_fields_are_not_forwarded_on_create_or_update(): void
    {
        $this->fakeApi(['Vehicles*' => $this->ok()]);
        $extra = ['isDemoVehicle' => true, 'subscriptionStatus' => 'Active', 'imei' => '123456789012345', 'IsActive' => true];

        $this->asCustomer('u', ['customer_id' => self::UUID])->postJson('/vehicles', $this->valid($extra))->assertOk();
        $this->asCustomer('u', ['customer_id' => self::UUID])->putJson('/vehicles/' . self::UUID2, $this->valid($extra))->assertOk();

        foreach (['POST' => '/api/Vehicles', 'PUT' => '/api/Vehicles/' . self::UUID2] as $method => $path) {
            $keys = array_keys($this->sentTo($method, $path)->data());
            foreach (['isDemoVehicle', 'IsDemoVehicle', 'subscriptionStatus', 'SubscriptionStatus', 'imei', 'Imei', 'IsActive'] as $bad) {
                $this->assertNotContains($bad, $keys, "$method must not forward $bad");
            }
        }
    }

    public static function invalidVehicles(): array
    {
        return [
            'no plate'            => [['vehicleNumber' => ''], 'vehicleNumber'],
            'plate too long'      => [['vehicleNumber' => str_repeat('A', 21)], 'vehicleNumber'],
            'no make'             => [['make' => ''], 'make'],
            'make too long'       => [['make' => str_repeat('a', 51)], 'make'],
            'no model'            => [['model' => ''], 'model'],
            'year before 1900'    => [['year' => 1899], 'year'],
            'year after 2100'     => [['year' => 2101], 'year'],
            'year is text'        => [['year' => 'abcd'], 'year'],
            'year is a decimal'   => [['year' => 2018.5], 'year'],
            'colour too long'     => [['color' => str_repeat('a', 31)], 'color'],
            'chassis too long'    => [['chassisNumber' => str_repeat('1', 101)], 'chassisNumber'],
            'engine too long'     => [['engineNumber' => str_repeat('1', 101)], 'engineNumber'],
            'plate is an array'   => [['vehicleNumber' => ['x']], 'vehicleNumber'],
        ];
    }

    /** @dataProvider invalidVehicles */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidVehicles')]
    public function test_create_rejects_bad_input_before_calling_the_api(array $over, string $field): void
    {
        Http::fake();

        $this->asCustomer('u', ['customer_id' => self::UUID])->postJson('/vehicles', $this->valid($over))
            ->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    /** @dataProvider invalidVehicles */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidVehicles')]
    public function test_update_rejects_the_same_bad_input(array $over, string $field): void
    {
        Http::fake();

        $this->asCustomer()->putJson('/vehicles/' . self::UUID, $this->valid($over))->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    public function test_a_customer_id_that_is_not_a_uuid_is_rejected_on_create(): void
    {
        Http::fake();

        $this->asCustomer('u', ['customer_id' => self::UUID])->postJson('/vehicles', $this->valid(['customerId' => 'not-a-uuid']))
            ->assertStatus(422)->assertJsonValidationErrors('customerId');

        Http::assertNothingSent();
    }

    public function test_boundary_values_that_are_allowed_are_accepted(): void
    {
        $this->fakeApi(['Vehicles' => $this->ok()]);

        $this->asCustomer('u', ['customer_id' => self::UUID])->postJson('/vehicles', $this->valid([
            'vehicleNumber' => str_repeat('A', 20), 'make' => str_repeat('a', 50), 'year' => 1900,
            'color' => str_repeat('a', 30), 'chassisNumber' => str_repeat('1', 100),
        ]))->assertOk();
        $this->asCustomer('u', ['customer_id' => self::UUID])->postJson('/vehicles', $this->valid(['year' => 2100]))->assertOk();
    }

    // ------------------------------------------------------------------ update / delete

    public function test_update_sends_the_changes_to_that_vehicle_and_never_the_owner(): void
    {
        $this->fakeApi(['Vehicles/*' => $this->ok()]);

        $this->asCustomer()->putJson('/vehicles/' . self::UUID, $this->valid(['customerId' => self::UUID2]))
            ->assertOk()->assertJsonPath('message', 'Vehicle updated successfully.');

        $this->assertSentBody('PUT', '/api/Vehicles/' . self::UUID, [
            'VehicleNumber' => 'CAB-1234', 'Make' => 'Toyota', 'Model' => 'Axio', 'Year' => 2018,
            'Color' => null, 'VehicleType' => null, 'FuelType' => null, 'ChassisNumber' => null, 'EngineNumber' => null,
        ]);
    }

    public function test_delete_calls_the_api_for_that_vehicle_only(): void
    {
        $this->fakeApi(['Vehicles/*' => $this->ok()]);

        $this->asCustomer()->deleteJson('/vehicles/' . self::UUID)->assertOk()->assertJsonPath('message', 'Vehicle deleted successfully.');

        $this->assertNotNull($this->sentTo('DELETE', '/api/Vehicles/' . self::UUID));
        Http::assertSentCount(1);
    }

    public function test_deleting_someone_elses_vehicle_is_refused_by_the_api_and_shown_as_no_access(): void
    {
        $this->fakeApi(['Vehicles/*' => Http::response(['message' => 'nope'], 403)]);

        $this->asCustomer()->deleteJson('/vehicles/' . self::UUID)
            ->assertStatus(422)->assertJsonPath('code', 'FORBIDDEN')->assertJsonPath('message', "You don't have access to do that.");
    }

    // ------------------------------------------------------------------ link / unlink a GPS device

    public static function badImeis(): array
    {
        return [
            'letters'           => ['12345678901234A'],
            '14 digits'         => ['12345678901234'],
            '18 digits'         => ['123456789012345678'],
            'empty'             => [''],
            'sql-ish'           => ["123456789012345'; DROP TABLE x;--"],
            'only separators'   => ['--- --- --- ---'],
            'path traversal'    => ['../../Customers/me'],
        ];
    }

    /** @dataProvider badImeis */
    #[\PHPUnit\Framework\Attributes\DataProvider('badImeis')]
    public function test_a_bad_imei_never_causes_an_api_lookup(string $imei): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/link-device', ['imei' => $imei])->assertStatus(422);

        Http::assertNothingSent();
    }

    public static function goodImeis(): array
    {
        return [
            '15 digits'          => ['123456789012345', '123456789012345'],
            'with spaces'        => ['1234 5678 9012 345', '123456789012345'],
            'with dashes'        => ['12-345678-901234-5', '123456789012345'],
            '16 digits (sw ver)' => ['1234567890123456', '1234567890123456'],
            '17 digits'          => ['12345678901234567', '12345678901234567'],
            'padded'             => ['  123456789012345  ', '123456789012345'],
        ];
    }

    /** @dataProvider goodImeis */
    #[\PHPUnit\Framework\Attributes\DataProvider('goodImeis')]
    public function test_a_good_imei_is_cleaned_to_digits_then_looked_up_then_assigned(string $typed, string $digits): void
    {
        $this->fakeApi([
            'GpsDevices/lookup/*'        => $this->ok(['data' => ['deviceId' => self::UUID2]]),
            'DeviceAssignments/assign'   => $this->ok(['data' => ['assignmentId' => 'a1']]),
        ]);

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/link-device', ['imei' => $typed])
            ->assertOk()->assertJsonPath('message', 'GPS device linked successfully.');

        $this->assertNotNull($this->sentTo('GET', '/api/GpsDevices/lookup/' . $digits), 'lookup must use digits only');
        $this->assertSentBody('POST', '/api/DeviceAssignments/assign', ['VehicleId' => self::UUID, 'DeviceId' => self::UUID2]);
    }

    public function test_an_unknown_imei_says_so_and_nothing_is_assigned(): void
    {
        $this->fakeApi(['GpsDevices/lookup/*' => Http::response(['message' => 'x'], 404), 'DeviceAssignments/assign' => $this->ok()]);

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/link-device', ['imei' => '123456789012345'])
            ->assertStatus(422)->assertJsonPath('message', 'No GPS device found with that IMEI.');

        $this->assertNull($this->sentTo('POST', '/api/DeviceAssignments/assign'));
    }

    public function test_a_lookup_without_a_device_id_assigns_nothing(): void
    {
        $this->fakeApi(['GpsDevices/lookup/*' => $this->ok(['data' => []]), 'DeviceAssignments/assign' => $this->ok()]);

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/link-device', ['imei' => '123456789012345'])
            ->assertStatus(404)->assertJsonPath('success', false);

        $this->assertNull($this->sentTo('POST', '/api/DeviceAssignments/assign'));
    }

    public function test_a_device_that_belongs_to_someone_else_is_reported_as_unavailable(): void
    {
        $this->fakeApi([
            'GpsDevices/lookup/*'      => $this->ok(['data' => ['deviceId' => self::UUID2]]),
            'DeviceAssignments/assign' => Http::response(['message' => 'taken'], 403),
        ]);

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/link-device', ['imei' => '123456789012345'])
            ->assertStatus(422)->assertJsonPath('message', 'This device is not available for linking.');
    }

    public function test_unlink_needs_a_real_assignment_id_and_uses_a_patch(): void
    {
        $this->fakeApi(['DeviceAssignments/*' => $this->ok()]);

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/unlink-device', ['assignmentId' => 'not-a-uuid'])
            ->assertStatus(422)->assertJsonValidationErrors('assignmentId');
        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/unlink-device', [])->assertStatus(422);
        Http::assertNothingSent();

        $this->asCustomer()->postJson('/vehicles/' . self::UUID . '/unlink-device', ['assignmentId' => self::UUID2])
            ->assertOk()->assertJsonPath('message', 'GPS device unlinked successfully.');
        $this->assertNotNull($this->sentTo('PATCH', '/api/DeviceAssignments/' . self::UUID2 . '/unassign'));
    }
}
