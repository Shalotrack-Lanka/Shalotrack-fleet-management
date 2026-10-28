<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\ReportApiFixtures;
use Tests\TestCase;

/**
 * F4: the on-screen report (GET /reports/view) that the Reports page draws.
 * The server converts everything to Sri Lanka time and pre-formats it, so the viewer's own browser time zone
 * must never matter.
 */
class ReportViewTest extends TestCase
{
    use ReportApiFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00', 'Asia/Colombo'));
    }

    private function viewReport(array $q = []): \Illuminate\Testing\TestResponse
    {
        return $this->asCustomer()->getJson('/reports/view?' . http_build_query($q + [
            'type' => 'km', 'vehicleId' => self::UUID, 'from' => '2026-10-28', 'to' => '2026-10-28',
        ]));
    }

    public function test_km_report_is_formatted_in_sri_lanka_time(): void
    {
        $this->fakeReportApi();

        $d = $this->viewReport()->assertOk()->assertJsonPath('type', 'km')->json('data');

        // 20:00 UTC on 27 Oct = 01:30 AM on 28 Oct in Colombo
        $this->assertSame(['date' => '28 Oct 2026', 'start' => '01:30 AM', 'end' => '02:15 AM', 'duration' => '45 min',
            'distanceKm' => 12.5, 'maxSpeed' => 80, 'avgSpeed' => 40, 'inProgress' => false], $d['trips'][0]);
        $this->assertSame('45 min', $d['summary']['drivingLabel']);
        $this->assertSame('1h', $d['summary']['ignitionLabel']);
        $this->assertSame(12.5, $d['summary']['distanceKm']);
        $this->assertSame(1, $d['totalDays']);
    }

    public function test_a_trip_still_running_has_no_end_time(): void
    {
        $this->fakeReportApi(['trips' => $this->ok(['data' => ['trips' => [[
            'startTime' => '2026-10-27T20:00:00Z', 'endTime' => '2026-10-27T20:10:00Z', 'durationMinutes' => 10, 'distanceKm' => 1,
            'maxSpeed' => 30, 'avgSpeed' => 20, 'inProgress' => true]], 'stops' => []]])]);

        $this->assertNull($this->viewReport()->assertOk()->json('data.trips.0.end'));
    }

    public function test_stops_report_shows_arrival_in_sri_lanka_time_and_an_open_stop_has_no_departure(): void
    {
        $this->fakeReportApi();

        $d = $this->viewReport(['type' => 'stops'])->assertOk()->json('data');

        $this->assertSame('02:15 AM', $d['stops'][0]['arrived']);
        $this->assertNull($d['stops'][0]['departed']);
        $this->assertTrue($d['stops'][0]['inProgress']);
        $this->assertSame(6.9271, $d['stops'][0]['lat']);
        $this->assertSame(1, $d['summary']['count']);
        $this->assertSame('5 min', $d['summary']['totalLabel']);
    }

    public function test_alert_times_without_an_offset_are_read_as_utc_not_as_the_servers_clock(): void
    {
        $this->fakeReportApi();

        $d = $this->viewReport(['type' => 'alerts'])->assertOk()->json('data');

        // "2026-10-27T20:10:00" (no Z) is 20:10 UTC = 01:40 AM Colombo
        $this->assertSame('28 Oct 2026', $d['alerts'][0]['date']);
        $this->assertSame('01:40 AM', $d['alerts'][0]['time']);
        $this->assertSame(['Overspeed' => 1], $d['counts']);
    }

    public function test_each_entry_carries_only_the_fields_the_page_draws(): void
    {
        $this->fakeReportApi(['alerts' => $this->ok(['data' => ['totalCount' => 1, 'countsByType' => [], 'alerts' => [[
            'triggeredAt' => '2026-10-27T20:10:00Z', 'alertType' => 'Overspeed', 'message' => 'm', 'latitude' => 1, 'longitude' => 2,
            'isRead' => true, 'deviceImei' => '860000000000001', 'userId' => 'secret-user', 'alertId' => 99]]]])]);

        $entry = $this->viewReport(['type' => 'alerts'])->assertOk()->json('data.alerts.0');

        $this->assertEqualsCanonicalizing(['date', 'time', 'type', 'message', 'lat', 'lng', 'isRead'], array_keys($entry));
    }

    public function test_an_empty_range_is_a_normal_empty_report_not_an_error(): void
    {
        $this->fakeReportApi(['trips' => $this->ok(['data' => ['trips' => [], 'stops' => []]]),
            'alerts' => $this->ok(['data' => ['totalCount' => 0, 'countsByType' => [], 'alerts' => []]])]);

        $this->assertSame([], $this->viewReport(['type' => 'stops'])->assertOk()->json('data.stops'));
        $this->assertSame(0, $this->viewReport(['type' => 'stops'])->json('data.summary.count'));
        $this->assertSame([], $this->viewReport(['type' => 'alerts'])->assertOk()->json('data.alerts'));
    }

    public static function badQueries(): array
    {
        return [
            'unknown type'   => [['type' => 'all']],
            'bad vehicle id' => [['vehicleId' => 'x']],
            'bad from'       => [['from' => '28-10-2026']],
            'no to'          => [['to' => '']],
        ];
    }

    /** @dataProvider badQueries */
    #[\PHPUnit\Framework\Attributes\DataProvider('badQueries')]
    public function test_bad_requests_are_rejected_before_the_api_is_called(array $over): void
    {
        Http::fake();

        $this->viewReport($over)->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_same_date_rules_apply_on_screen_as_in_the_downloads(): void
    {
        $this->fakeReportApi();

        $this->viewReport(['from' => '2026-10-28', 'to' => '2026-10-01'])->assertStatus(422)->assertJsonPath('message', '"From" date cannot be after "To" date.');
        $this->viewReport(['from' => '2026-07-30', 'to' => '2026-10-28'])->assertStatus(422);
        $this->viewReport(['from' => '2026-10-29', 'to' => '2026-10-29'])->assertStatus(422);
    }

    public function test_someone_elses_vehicle_looks_like_a_missing_one_and_server_errors_are_generic(): void
    {
        foreach ([403, 404] as $status) {
            $this->fakeReportApi(['stats' => Http::response([], $status), 'trips' => Http::response([], $status)]);
            $this->viewReport()->assertStatus(404)->assertJsonPath('message', 'That vehicle is not available to your account.');
        }

        $this->fakeReportApi(['stats' => Http::response(['message' => 'SQL_FAIL'], 500), 'trips' => Http::response([], 500)]);
        $res = $this->viewReport()->assertStatus(422);
        $this->assertSame('Failed to load report data. Please try again.', $res->json('message'));
        $this->assertStringNotContainsString('SQL_FAIL', $res->getContent());
    }

    public function test_an_expired_api_token_signs_the_customer_out(): void
    {
        $this->fakeReportApi(['stats' => Http::response([], 401), 'trips' => Http::response([], 401)]);

        $this->viewReport()->assertStatus(401)->assertJsonPath('expired', true);

        $this->assertNull(session('firebase_token'));
    }
}
