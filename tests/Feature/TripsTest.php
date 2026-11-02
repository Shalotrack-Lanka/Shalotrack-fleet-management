<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: trip history, route playback, last-known position and the trip PDF. */
class TripsTest extends TestCase
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
        $owned = fn ($id, $plate, $gps, $demo = false) => ['vehicleId' => $id, 'vehicleNumber' => $plate, 'make' => 'Toyota', 'model' => 'Axio', 'hasGpsDevice' => $gps, 'isDemoVehicle' => $demo];

        $this->fakeApi($over + [
            'Customers/me'              => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*'       => $this->ok(['data' => [
                $owned('a0000000-0000-4000-8000-000000000001', 'NO-GPS', false),
                $owned('a0000000-0000-4000-8000-000000000002', 'DEMO', true, true),
                $owned('a0000000-0000-4000-8000-000000000003', 'OWNED-GPS', true),
            ]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => [
                ['status' => 'Accepted', 'vehicleId' => 'b0000000-0000-4000-8000-000000000001', 'vehicleNumber' => 'SHARED-1', 'otherPartyName' => 'Kamal'],
                ['status' => 'Pending',  'vehicleId' => 'b0000000-0000-4000-8000-000000000002', 'vehicleNumber' => 'PENDING-1'],
                ['status' => 'Accepted', 'vehicleId' => 'A0000000-0000-4000-8000-000000000003', 'vehicleNumber' => 'DUPLICATE-OF-OWNED'],
            ]]),
        ]);
    }

    // ---- the page ---------------------------------------------------------------------------------------

    public function test_the_list_puts_gps_vehicles_first_demo_last_and_owned_before_shared(): void
    {
        $this->fleetApi();

        $res = $this->asCustomer()->get('/trips')->assertOk();

        $plates = array_column($res->viewData('vehicles'), 'vehicleNumber');
        $this->assertSame(['OWNED-GPS', 'SHARED-1', 'DEMO', 'NO-GPS'], $plates);
    }

    public function test_only_accepted_shares_are_listed_and_a_vehicle_is_never_listed_twice(): void
    {
        $this->fleetApi();

        $plates = array_column($this->asCustomer()->get('/trips')->viewData('vehicles'), 'vehicleNumber');

        $this->assertNotContains('PENDING-1', $plates);
        $this->assertNotContains('DUPLICATE-OF-OWNED', $plates);
        $this->assertCount(count(array_unique($plates)), $plates);
    }

    public function test_a_shares_outage_still_shows_the_owned_vehicles(): void
    {
        $this->fleetApi(['VehicleShares/shared-with-me' => Http::response([], 500)]);

        $plates = array_column($this->asCustomer()->get('/trips')->assertOk()->viewData('vehicles'), 'vehicleNumber');

        $this->assertContains('OWNED-GPS', $plates);
        $this->assertNotContains('SHARED-1', $plates);
    }

    public function test_a_401_on_the_shares_call_signs_the_customer_out(): void
    {
        $this->fleetApi(['VehicleShares/shared-with-me' => Http::response([], 401)]);

        $this->asCustomer()->get('/trips')->assertRedirect('/login?expired=1');
    }

    public function test_the_page_explains_an_outage_and_escapes_vehicle_text(): void
    {
        $this->fleetApi(['Vehicles/customer/*' => Http::response([], 500)]);
        $this->asCustomer('a')->get('/trips')->assertOk()->assertSee('Could not load vehicles');

        $this->fleetApi(['Vehicles/customer/*' => $this->ok(['data' => [['vehicleId' => self::UUID, 'vehicleNumber' => self::XSS, 'hasGpsDevice' => true]]])]);
        $res = $this->asCustomer('b')->get('/trips')->assertOk();
        $this->assertStringNotContainsString(self::XSS, $res->getContent());
    }

    // ---- points + summary ---------------------------------------------------------------------------------

    public static function historyEndpoints(): array
    {
        return ['points' => ['points', '/api/GpsTracking', 1000], 'summary' => ['summary', '/api/GpsTracking/trips', null]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('historyEndpoints')]
    public function test_a_colombo_wall_clock_range_is_sent_to_the_api_as_utc(string $name, string $apiPath, ?int $pageSize): void
    {
        $this->fakeApi(['GpsTracking*' => $this->ok(['data' => []])]);

        $this->asCustomer()->getJson('/trips/' . self::UUID . "/$name?from=2026-10-28T08:00&to=2026-10-28T20:00")->assertOk()->assertJsonPath('success', true);

        $req = $this->sentTo('GET', $apiPath);
        parse_str(parse_url($req->url(), PHP_URL_QUERY), $q);
        $this->assertSame('2026-10-28T02:30:00Z', $q['from']);   // 08:00 in Colombo
        $this->assertSame('2026-10-28T14:30:00Z', $q['to']);
        $this->assertSame(self::UUID, $q['vehicleId']);
        if ($pageSize) {
            $this->assertSame((string) $pageSize, $q['pageSize']);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('historyEndpoints')]
    public function test_an_explicit_utc_range_is_kept_as_it_is(string $name, string $apiPath): void
    {
        $this->fakeApi(['GpsTracking*' => $this->ok(['data' => []])]);

        $this->asCustomer()->getJson('/trips/' . self::UUID . "/$name?from=2026-10-28T02:30:00Z&to=2026-10-28T14:30:00Z")->assertOk();

        parse_str(parse_url($this->sentTo('GET', $apiPath)->url(), PHP_URL_QUERY), $q);
        $this->assertSame('2026-10-28T02:30:00Z', $q['from']);
        $this->assertSame('2026-10-28T14:30:00Z', $q['to']);
    }

    public static function badRanges(): array
    {
        return [
            'no from'         => [['from' => '']],
            'no to'           => [['to' => '']],
            'not a date'      => [['from' => 'yesterday-ish!']],
            'to before from'  => [['from' => '2026-10-28T20:00', 'to' => '2026-10-28T08:00']],
            'to equals from'  => [['from' => '2026-10-28T08:00', 'to' => '2026-10-28T08:00']],
            'array'           => [['from' => ['2026-10-28']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badRanges')]
    public function test_a_bad_range_is_refused_before_the_api_is_called(array $over): void
    {
        Http::fake();

        foreach (['points', 'summary'] as $name) {
            $this->asCustomer()->getJson('/trips/' . self::UUID . "/$name?" . http_build_query($over + ['from' => '2026-10-28T08:00', 'to' => '2026-10-28T20:00']))->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    public function test_points_report_how_many_there_are_and_tolerate_a_strange_shape(): void
    {
        $this->fakeApi(['GpsTracking*' => $this->ok(['data' => [['latitude' => 6.9, 'longitude' => 79.8], ['latitude' => 6.91, 'longitude' => 79.81]]])]);
        $this->asCustomer()->getJson('/trips/' . self::UUID . '/points?from=2026-10-28T08:00&to=2026-10-28T09:00')->assertOk()->assertJsonPath('count', 2);

        $this->fakeApi(['GpsTracking*' => $this->ok(['data' => 'oops'])]);
        $this->asCustomer()->getJson('/trips/' . self::UUID . '/points?from=2026-10-28T08:00&to=2026-10-28T09:00')->assertOk()->assertJsonPath('count', 0);
    }

    public static function historyFailures(): array
    {
        return [
            'subscription expired' => [402, [], 402, 'Subscription expired — renew to view trip history.'],
            'not your vehicle'     => [403, [], 403, 'You do not have access to this vehicle.'],
            'no such vehicle'      => [404, [], 404, 'Vehicle not found.'],
            'rate limited'         => [429, [], 429, 'Too many requests — please wait a moment and try again.'],
            'plain range message'  => [400, ['message' => 'The range cannot be longer than 90 days.'], 422, 'The range cannot be longer than 90 days.'],
            'machine code hidden'  => [400, ['message' => 'RANGE_TOO_LARGE'], 422, 'Could not load trip data. Please try again.'],
            'server error'         => [500, ['message' => 'stack trace at Foo.cs'], 422, 'Could not load trip data. Please try again.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('historyFailures')]
    public function test_history_failures_are_honest_and_safe(int $apiStatus, array $body, int $expected, string $message): void
    {
        $this->fakeApi(['GpsTracking*' => Http::response($body, $apiStatus)]);

        $res = $this->asCustomer()->getJson('/trips/' . self::UUID . '/points?from=2026-10-28T08:00&to=2026-10-28T09:00')
            ->assertStatus($expected)->assertJsonPath('message', $message);

        $this->assertStringNotContainsString('Foo.cs', $res->getContent());
    }

    public function test_a_slow_api_is_a_timeout_message_not_an_error_page(): void
    {
        $this->fakeApi(['GpsTracking*' => fn () => throw new ConnectionException('cURL error 28')]);

        $this->asCustomer()->getJson('/trips/' . self::UUID . '/summary?from=2026-10-28T08:00&to=2026-10-28T09:00')
            ->assertStatus(422)->assertJsonPath('timeout', true)->assertJsonPath('success', false);
    }

    public function test_a_401_in_history_tells_the_page_to_sign_in_again(): void
    {
        $this->fakeApi(['GpsTracking*' => Http::response([], 401)]);

        $this->asCustomer()->getJson('/trips/' . self::UUID . '/points?from=2026-10-28T08:00&to=2026-10-28T09:00')->assertStatus(401)->assertJsonPath('expired', true);
    }

    // ---- last known position -------------------------------------------------------------------------------

    public function test_the_last_known_position_is_returned_and_never_cached(): void
    {
        $this->fakeApi(['CurrentLocations/vehicle/*' => $this->ok(['data' => ['latitude' => 6.9, 'longitude' => 79.8, 'speedKmh' => 0]])]);

        $res = $this->asCustomer()->getJson('/trips/' . self::UUID . '/location')->assertOk()->assertJsonPath('data.latitude', 6.9);

        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
    }

    public function test_a_vehicle_with_no_fix_yet_is_a_normal_empty_answer(): void
    {
        $this->fakeApi(['CurrentLocations/vehicle/*' => Http::response([], 404)]);

        $this->asCustomer()->getJson('/trips/' . self::UUID . '/location')->assertOk()->assertJson(['success' => true, 'data' => null]);
    }

    public function test_location_failures_are_plain(): void
    {
        foreach ([[403, 403, 'You do not have access to this vehicle.'], [402, 402, 'Subscription expired — renew to use live tracking.'], [500, 422, 'Could not load the last known location.']] as [$api, $status, $msg]) {
            $this->fakeApi(['CurrentLocations/vehicle/*' => Http::response(['message' => 'internal'], $api)]);
            $this->asCustomer()->getJson('/trips/' . self::UUID . '/location')->assertStatus($status)->assertJsonPath('message', $msg);
        }

        $this->fakeApi(['CurrentLocations/vehicle/*' => Http::response([], 401)]);
        $this->asCustomer()->getJson('/trips/' . self::UUID . '/location')->assertStatus(401)->assertJsonPath('expired', true);
    }

    public function test_a_vehicle_id_that_is_not_a_uuid_never_reaches_the_location_api(): void
    {
        Http::fake();

        $this->asCustomer()->getJson('/trips/not-a-uuid/location')->assertNotFound();

        Http::assertNothingSent();
    }

    // ---- the trip PDF ----------------------------------------------------------------------------------------

    private function tripSummary(array $over = []): array
    {
        return ['data' => $over + [
            'trips' => [
                ['distanceKm' => 10.5, 'durationMinutes' => 30, 'maxSpeed' => 80, 'avgSpeed' => 40, 'startTime' => '2026-10-28T02:30:00Z', 'endTime' => '2026-10-28T03:00:00Z'],
                ['distanceKm' => 4.5,  'durationMinutes' => 90, 'maxSpeed' => 60, 'avgSpeed' => 20, 'startTime' => '2026-10-28T05:00:00Z', 'endTime' => '2026-10-28T06:30:00Z'],
            ],
            'stops' => [],
        ]];
    }

    public function test_the_trip_report_is_a_real_pdf_for_a_vehicle_the_customer_can_see(): void
    {
        $this->fleetApi(['GpsTracking/trips*' => $this->ok($this->tripSummary())]);

        $res = $this->asCustomer()->get('/trips/a0000000-0000-4000-8000-000000000003/report?from=2026-10-28T08:00&to=2026-10-28T20:00')->assertOk();

        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
    }

    public function test_the_trip_report_defaults_to_today_in_sri_lanka_time(): void
    {
        $this->fleetApi(['GpsTracking/trips*' => $this->ok($this->tripSummary())]);

        $this->asCustomer()->get('/trips/' . self::UUID . '/report')->assertOk();

        parse_str(parse_url($this->sentTo('GET', '/api/GpsTracking/trips')->url(), PHP_URL_QUERY), $q);
        // "today so far" starts at local midnight = 18:30 UTC the evening before.
        $this->assertStringEndsWith('T18:30:00Z', $q['from']);
    }

    public function test_the_trip_report_never_loads_remote_files_or_runs_php(): void
    {
        $this->assertFalse((bool) config('dompdf.options.enable_remote', config('dompdf.options.isRemoteEnabled', false)));
        $this->assertFalse((bool) config('dompdf.options.enable_php', config('dompdf.options.isPhpEnabled', false)));
    }

    public function test_hostile_text_is_escaped_in_the_report_page(): void
    {
        $html = view('trips.report', [
            'vehicle' => ['vehicleNumber' => self::XSS, 'make' => self::XSS, 'model' => '"><img src=http://evil.test/x>', 'color' => self::XSS],
            'trips' => [], 'stops' => [], 'summary' => [], 'totalDistanceKm' => 0, 'totalDurationMin' => 0, 'maxSpeed' => 0, 'avgSpeed' => 0,
            'from' => '2026-10-28T02:30:00Z', 'to' => '2026-10-28T14:30:00Z', 'logoBase64' => '',
        ])->render();

        $this->assertStringNotContainsString(self::XSS, $html);
        $this->assertStringNotContainsString('<img src=http://evil.test', $html);
    }

    public function test_a_report_failure_is_a_plain_500_not_a_stack_trace(): void
    {
        $this->fleetApi(['GpsTracking/trips*' => Http::response(['message' => 'secret detail'], 500)]);

        $res = $this->asCustomer()->get('/trips/' . self::UUID . '/report?from=2026-10-28T08:00&to=2026-10-28T20:00')->assertStatus(500);

        $this->assertStringNotContainsString('secret detail', $res->getContent());
        $this->assertStringNotContainsString('Stack trace', $res->getContent());
    }

    public function test_a_report_with_a_bad_date_is_refused(): void
    {
        Http::fake();

        $this->asCustomer()->getJson('/trips/' . self::UUID . '/report?from=not-a-date')->assertStatus(422);

        Http::assertNothingSent();
    }
}
