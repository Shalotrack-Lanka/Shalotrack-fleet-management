<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: vehicle statistics, the comparison picker, and "frequent places". */
class StatsAndPlacesTest extends TestCase
{
    use SignedInFleetUser;

    private const XSS = '<script>window.__pwn=1</script>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function fleetApi(array $over = []): void
    {
        $this->fakeApi($over + [
            'Customers/me'                 => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*'          => $this->ok(['data' => [
                ['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio', 'hasGpsDevice' => true],
                ['vehicleId' => self::UUID2, 'vehicleNumber' => 'NO-GPS', 'hasGpsDevice' => false],
                ['vehicleNumber' => 'NO-ID', 'hasGpsDevice' => true],
                ['vehicleId' => 'c0000000-0000-4000-8000-000000000001', 'vehicleNumber' => self::XSS, 'make' => self::XSS, 'hasGpsDevice' => true, 'isDemoVehicle' => true],
            ]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => [['status' => 'Accepted', 'vehicleId' => 'b0000000-0000-4000-8000-000000000001', 'vehicleNumber' => 'SHARED-1', 'otherPartyName' => 'Kamal']]]),
        ]);
    }

    // ---- stats page + compare picker -------------------------------------------------------------------------

    public function test_the_stats_page_lists_owned_and_shared_vehicles_and_escapes_text(): void
    {
        $this->fleetApi();

        $res = $this->asCustomer()->get('/stats')->assertOk();

        $this->assertContains('SHARED-1', array_column($res->viewData('vehicles'), 'vehicleNumber'));
        $this->assertStringNotContainsString(self::XSS, $res->getContent());
    }

    public function test_the_compare_picker_offers_only_gps_vehicles_with_an_id_and_only_the_fields_it_needs(): void
    {
        $this->fleetApi();

        $vehicles = $this->asCustomer()->get('/stats/compare')->assertOk()->viewData('vehicles');

        $plates = array_column($vehicles, 'plate');
        $this->assertContains('CAB-1234', $plates);
        $this->assertContains('SHARED-1', $plates);
        $this->assertNotContains('NO-GPS', $plates);
        $this->assertNotContains('NO-ID', $plates);
        foreach ($vehicles as $v) {
            $this->assertSame(['id', 'plate', 'name', 'demo', 'shared'], array_keys($v));
        }
        $shared = collect($vehicles)->firstWhere('plate', 'SHARED-1');
        $this->assertTrue($shared['shared']);
        $this->assertTrue(collect($vehicles)->firstWhere('id', 'c0000000-0000-4000-8000-000000000001')['demo']);
    }

    public function test_stats_and_compare_pages_fail_softly_and_sign_out_on_401(): void
    {
        foreach (['/stats', '/stats/compare'] as $i => $url) {
            $this->fleetApi(['Vehicles/customer/*' => Http::response([], 500)]);
            $this->asCustomer('a' . $i)->get($url)->assertOk()->assertSee('Could not load vehicles');

            $this->fleetApi(['Vehicles/customer/*' => Http::response([], 401)]);
            $this->asCustomer('b' . $i)->get($url)->assertRedirect('/login?expired=1');
        }
    }

    // ---- stats data ------------------------------------------------------------------------------------------

    public static function periods(): array
    {
        return [['today', 'today'], ['week', 'week'], ['month', 'month'], ['all', 'all'], ['', 'today'], ['year', 'today'], ['week; drop', 'today'], ['WEEK', 'today']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('periods')]
    public function test_only_the_four_known_periods_are_asked_for(string $asked, string $sent): void
    {
        $this->fakeApi(['VehicleStats/*' => $this->ok(['data' => ['totalKm' => 12]])]);

        $this->asCustomer()->getJson('/stats/' . self::UUID . '/data?period=' . urlencode($asked))->assertOk()->assertJsonPath('data.totalKm', 12);

        parse_str(parse_url($this->sentTo('GET', '/api/VehicleStats/' . self::UUID)->url(), PHP_URL_QUERY), $q);
        $this->assertSame($sent, $q['period']);
    }

    public function test_stats_are_cached_per_customer_for_a_short_time_and_never_shared_between_customers(): void
    {
        $this->fakeApi(['VehicleStats/*' => $this->ok(['data' => ['totalKm' => 1]])]);

        $this->asCustomer('uid-A')->getJson('/stats/' . self::UUID . '/data?period=week')->assertOk();
        $this->asCustomer('uid-A')->getJson('/stats/' . self::UUID . '/data?period=week')->assertOk();
        Http::assertSentCount(1);                       // second view of the same page: one API call in total

        $this->asCustomer('uid-B')->getJson('/stats/' . self::UUID . '/data?period=week')->assertOk();
        Http::assertSentCount(2);                       // another customer never reads uid-A's cached numbers
    }

    public function test_an_error_is_never_cached(): void
    {
        $this->fakeApi(['VehicleStats/*' => Http::response([], 500)]);
        $this->asCustomer()->getJson('/stats/' . self::UUID . '/data?period=week')->assertOk()->assertJsonPath('success', false);

        $this->fakeApi(['VehicleStats/*' => $this->ok(['data' => ['totalKm' => 7]])]);
        $this->asCustomer()->getJson('/stats/' . self::UUID . '/data?period=week')->assertOk()->assertJsonPath('data.totalKm', 7);
    }

    public static function statsFailures(): array
    {
        return [
            'subscription expired' => [402, [], 'Subscription expired — renew to view statistics.'],
            'not your vehicle'     => [403, [], 'You do not have access to this vehicle.'],
            'no such vehicle'      => [404, [], 'Vehicle not found.'],
            'rate limited'         => [429, [], 'Too many requests — please wait a moment and try again.'],
            'plain explanation'    => [400, ['message' => 'That period is too large.'], 'That period is too large.'],
            'machine code hidden'  => [400, ['message' => 'PERIOD_TOO_LARGE'], 'Could not load statistics. Please try again.'],
            'server error'         => [500, ['message' => 'stack trace Foo.cs'], 'Could not load statistics. Please try again.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statsFailures')]
    public function test_stats_failures_keep_http_200_so_the_page_can_show_the_message(int $apiStatus, array $body, string $message): void
    {
        $this->fakeApi(['VehicleStats/*' => Http::response($body, $apiStatus)]);

        $res = $this->asCustomer()->getJson('/stats/' . self::UUID . '/data?period=week')->assertOk()
            ->assertJsonPath('success', false)->assertJsonPath('message', $message);

        $this->assertStringNotContainsString('Foo.cs', $res->getContent());
    }

    public function test_a_stats_timeout_and_an_expired_token_are_handled(): void
    {
        $this->fakeApi(['VehicleStats/*' => fn () => throw new ConnectionException('timeout')]);
        $this->asCustomer('t1')->getJson('/stats/' . self::UUID . '/data?period=all')->assertOk()
            ->assertJsonPath('message', 'Loading took too long. Try a shorter period, or try again in a moment.');

        $this->fakeApi(['VehicleStats/*' => Http::response([], 401)]);
        $this->asCustomer('t2')->getJson('/stats/' . self::UUID . '/data?period=all')->assertStatus(401)->assertJsonPath('expired', true);
    }

    // ---- frequent places -------------------------------------------------------------------------------------

    public function test_the_places_page_lists_gps_vehicles_in_a_reduced_shape(): void
    {
        $this->fleetApi();

        $vehicles = $this->asCustomer()->get('/places')->assertOk()->viewData('vehicles');

        $this->assertContains('CAB-1234', array_column($vehicles, 'plate'));
        $this->assertNotContains('NO-GPS', array_column($vehicles, 'plate'));
        $this->assertSame(['id', 'plate', 'name', 'demo', 'shared'], array_keys($vehicles[0]));
    }

    public function test_the_places_page_fails_softly_and_signs_out_on_401(): void
    {
        $this->fleetApi(['Vehicles/customer/*' => Http::response([], 500)]);
        $this->asCustomer('a')->get('/places')->assertOk()->assertSee('Could not load vehicles');

        $this->fleetApi(['Vehicles/customer/*' => Http::response([], 401)]);
        $this->asCustomer('b')->get('/places')->assertRedirect('/login?expired=1');
    }

    private function trip(float $lat, float $lng, string $end = '2026-10-20T10:00:00Z', int $mins = 20, array $over = []): array
    {
        return $over + ['endLatitude' => $lat, 'endLongitude' => $lng, 'endTime' => $end, 'durationMinutes' => $mins];
    }

    public function test_repeat_destinations_become_a_place_and_a_saved_place_names_it(): void
    {
        $this->fakeApi([
            'GpsTracking/trips*' => $this->ok(['data' => [
                $this->trip(6.9271, 79.8612, '2026-10-20T10:00:00Z', 20),
                $this->trip(6.9272, 79.8613, '2026-10-22T10:00:00Z', 30),    // ~15 m away: same place
                $this->trip(7.2906, 80.6337, '2026-10-21T10:00:00Z', 10),    // visited once: dropped
            ]]),
            'SavedPlaces'        => $this->ok(['data' => [['name' => 'Office', 'latitude' => 6.9271, 'longitude' => 79.8612]]]),
        ]);

        $res = $this->asCustomer()->getJson('/places/' . self::UUID . '/data?period=30')->assertOk();

        $this->assertSame(3, $res->json('tripCount'));
        $this->assertCount(1, $res->json('places'));
        $place = $res->json('places.0');
        $this->assertSame(2, $place['visits']);
        $this->assertSame(50, $place['totalMinutes']);
        $this->assertSame('Office', $place['savedName']);
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
    }

    public static function placePeriods(): array
    {
        return [['30', 30], ['90', 90], ['7', 30], ['', 30], ['abc', 30], ['90; x', 30]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('placePeriods')]
    public function test_only_thirty_or_ninety_days_can_be_asked_for(string $asked, int $days): void
    {
        $this->fakeApi(['GpsTracking/trips*' => $this->ok(['data' => []]), 'SavedPlaces' => $this->ok(['data' => []])]);

        $res = $this->asCustomer()->getJson('/places/' . self::UUID . '/data?period=' . urlencode($asked))->assertOk();

        $this->assertSame($days, $res->json('days'));
        parse_str(parse_url($this->sentTo('GET', '/api/GpsTracking/trips')->url(), PHP_URL_QUERY), $q);
        $span = (new \DateTimeImmutable($q['to']))->diff(new \DateTimeImmutable($q['from']));
        $this->assertEqualsWithDelta($days, $span->days, 1);
    }

    public function test_a_saved_places_outage_never_breaks_the_list(): void
    {
        $this->fakeApi(['GpsTracking/trips*' => $this->ok(['data' => [$this->trip(6.9, 79.8), $this->trip(6.9, 79.8, '2026-10-21T10:00:00Z')]]), 'SavedPlaces' => Http::response([], 500)]);

        $res = $this->asCustomer()->getJson('/places/' . self::UUID . '/data')->assertOk();

        $this->assertSame(2, $res->json('places.0.visits'));
        $this->assertNull($res->json('places.0.savedName'));
    }

    public function test_places_failures_are_plain(): void
    {
        foreach ([[403, 403, 'You do not have access to this vehicle.'], [402, 402, 'Subscription expired. Renew to see this vehicle.'], [500, 422, 'Could not load trips. Please try again.']] as [$api, $status, $msg]) {
            $this->fakeApi(['GpsTracking/trips*' => Http::response(['message' => 'internal'], $api)]);
            $this->asCustomer()->getJson('/places/' . self::UUID . '/data')->assertStatus($status)->assertJsonPath('message', $msg);
        }

        $this->fakeApi(['GpsTracking/trips*' => Http::response([], 401)]);
        $this->asCustomer()->getJson('/places/' . self::UUID . '/data')->assertStatus(401)->assertJsonPath('expired', true);
    }
}
