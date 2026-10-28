<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F3: create / edit / delete a geofence. Coordinates and radius are checked before the API is called. */
class GeofenceWriteTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function valid(array $over = []): array
    {
        return $over + ['name' => 'Home', 'latitude' => 6.9271, 'longitude' => 79.8612, 'radiusMeters' => 200];
    }

    public function test_create_forwards_exactly_the_expected_fields_with_alerts_on_by_default(): void
    {
        $this->fakeApi(['Geofences' => $this->ok(['data' => ['geofenceId' => self::UUID]])]);

        $this->asCustomer()->postJson('/geofences', $this->valid())->assertOk()->assertJsonPath('data.geofenceId', self::UUID);

        $this->assertSentBody('POST', '/api/Geofences', [
            'Name' => 'Home', 'Latitude' => 6.9271, 'Longitude' => 79.8612, 'RadiusMeters' => 200,
            'VehicleId' => null, 'AlertOnEnter' => true, 'AlertOnExit' => true,
        ]);
    }

    public function test_a_scoped_fence_with_alerts_switched_off_is_respected(): void
    {
        $this->fakeApi(['Geofences' => $this->ok()]);

        $this->asCustomer()->postJson('/geofences', $this->valid(['vehicleId' => self::UUID, 'alertOnEnter' => false, 'alertOnExit' => false]))->assertOk();

        $sent = $this->sentTo('POST', '/api/Geofences')->data();
        $this->assertSame(self::UUID, $sent['VehicleId']);
        $this->assertFalse($sent['AlertOnEnter']);
        $this->assertFalse($sent['AlertOnExit']);
    }

    public function test_extra_browser_fields_are_not_forwarded(): void
    {
        $this->fakeApi(['Geofences*' => $this->ok()]);

        $this->asCustomer()->postJson('/geofences', $this->valid(['customerId' => self::UUID2, 'isActive' => false, 'createdBy' => 'admin']))->assertOk();

        $keys = array_keys($this->sentTo('POST', '/api/Geofences')->data());
        $this->assertEqualsCanonicalizing(['Name', 'Latitude', 'Longitude', 'RadiusMeters', 'VehicleId', 'AlertOnEnter', 'AlertOnExit'], $keys);
    }

    public static function invalidFences(): array
    {
        return [
            'no name'               => [['name' => ''], 'name'],
            'name too long'         => [['name' => str_repeat('a', 101)], 'name'],
            'latitude above 90'     => [['latitude' => 90.0001], 'latitude'],
            'latitude below -90'    => [['latitude' => -91], 'latitude'],
            'latitude is text'      => [['latitude' => 'north'], 'latitude'],
            'no latitude'           => [['latitude' => null], 'latitude'],
            'longitude above 180'   => [['longitude' => 180.5], 'longitude'],
            'longitude below -180'  => [['longitude' => -181], 'longitude'],
            'radius under 50'       => [['radiusMeters' => 49], 'radiusMeters'],
            'radius over 50 km'     => [['radiusMeters' => 50001], 'radiusMeters'],
            'radius is a decimal'   => [['radiusMeters' => 100.5], 'radiusMeters'],
            'radius is text'        => [['radiusMeters' => 'big'], 'radiusMeters'],
            'vehicle not a uuid'    => [['vehicleId' => '1 OR 1=1'], 'vehicleId'],
            'alert flag is text'    => [['alertOnEnter' => 'maybe'], 'alertOnEnter'],
        ];
    }

    /** @dataProvider invalidFences */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFences')]
    public function test_create_rejects_bad_input_before_calling_the_api(array $over, string $field): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/geofences', $this->valid($over))->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    /** @dataProvider invalidFences */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFences')]
    public function test_update_rejects_the_same_bad_input(array $over, string $field): void
    {
        Http::fake();

        $this->asCustomer()->putJson('/geofences/' . self::UUID, $this->valid($over))->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    public function test_the_limits_themselves_are_allowed(): void
    {
        $this->fakeApi(['Geofences' => $this->ok()]);

        foreach ([['latitude' => 90, 'longitude' => 180, 'radiusMeters' => 50000], ['latitude' => -90, 'longitude' => -180, 'radiusMeters' => 50]] as $edge) {
            $this->asCustomer()->postJson('/geofences', $this->valid($edge))->assertOk();
        }
    }

    public function test_update_targets_that_fence_and_can_switch_it_off(): void
    {
        $this->fakeApi(['Geofences/*' => $this->ok()]);

        $this->asCustomer()->putJson('/geofences/' . self::UUID, $this->valid(['isActive' => false]))->assertOk();

        $sent = $this->sentTo('PUT', '/api/Geofences/' . self::UUID)->data();
        $this->assertFalse($sent['IsActive']);
        $this->assertSame(true, $sent['AlertOnEnter']);
    }

    public function test_update_without_isactive_keeps_the_fence_active(): void
    {
        $this->fakeApi(['Geofences/*' => $this->ok()]);

        $this->asCustomer()->putJson('/geofences/' . self::UUID, $this->valid())->assertOk();

        $this->assertTrue($this->sentTo('PUT', '/api/Geofences/' . self::UUID)->data()['IsActive']);
    }

    public function test_delete_calls_the_api_for_that_fence(): void
    {
        $this->fakeApi(['Geofences/*' => $this->ok()]);

        $this->asCustomer()->deleteJson('/geofences/' . self::UUID)->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($this->sentTo('DELETE', '/api/Geofences/' . self::UUID));
    }
}
