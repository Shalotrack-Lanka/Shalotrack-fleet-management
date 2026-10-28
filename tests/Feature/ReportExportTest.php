<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportExportController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\ReportApiFixtures;
use Tests\TestCase;

/**
 * F4: PDF and CSV downloads (/reports/export and /stats/{id}/export).
 *
 * What a customer downloads must match the API, be in Sri Lanka time, be safe to open in Excel, and be
 * limited to what the API will allow. Nothing the browser posts may change the numbers.
 */
class ReportExportTest extends TestCase
{
    use ReportApiFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00', 'Asia/Colombo'));
    }

    private function export(array $q = [], string $uid = 'uid-1'): \Illuminate\Testing\TestResponse
    {
        return $this->asCustomer($uid)->getJson('/reports/export?' . http_build_query($q + [
            'type' => 'km', 'format' => 'csv', 'vehicleId' => self::UUID, 'from' => '2026-10-28', 'to' => '2026-10-28',
        ]));
    }

    // ------------------------------------------------------------------ access

    public static function exportUrls(): array
    {
        return [
            'report export' => ['/reports/export?type=km&format=csv&vehicleId=11111111-1111-4111-8111-111111111111&from=2026-10-28&to=2026-10-28'],
            'stats export'  => ['/stats/11111111-1111-4111-8111-111111111111/export?period=week&format=csv'],
            'report view'   => ['/reports/view?type=km&vehicleId=11111111-1111-4111-8111-111111111111&from=2026-10-28&to=2026-10-28'],
        ];
    }

    /** @dataProvider exportUrls */
    #[\PHPUnit\Framework\Attributes\DataProvider('exportUrls')]
    public function test_a_stranger_gets_nothing_and_the_api_is_not_called(string $url): void
    {
        Http::fake();

        $this->getJson($url)->assertStatus(401);

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------------ validation

    public static function badReportQueries(): array
    {
        return [
            'unknown type'       => [['type' => 'passwords']],
            'unknown format'     => [['format' => 'xlsx']],
            'no format'          => [['format' => null]],
            'vehicle not a uuid' => [['vehicleId' => '1 OR 1=1']],
            'no vehicle'         => [['vehicleId' => null]],
            'from in dd/mm/yyyy' => [['from' => '28/10/2026']],
            'to with a time'     => [['to' => '2026-10-28 23:59:59']],
            'no from'            => [['from' => null]],
            'no to'              => [['to' => null]],
            'impossible date'    => [['from' => '2026-02-30']],
            'array instead'      => [['type' => ['km']]],
        ];
    }

    /** @dataProvider badReportQueries */
    #[\PHPUnit\Framework\Attributes\DataProvider('badReportQueries')]
    public function test_a_bad_report_request_is_rejected_before_the_api_is_called(array $over): void
    {
        Http::fake();

        $query = array_filter($over + [
            'type' => 'km', 'format' => 'csv', 'vehicleId' => self::UUID, 'from' => '2026-10-28', 'to' => '2026-10-28',
        ], fn ($v) => $v !== null);

        $this->asCustomer()->getJson('/reports/export?' . http_build_query($query))->assertStatus(422);

        Http::assertNothingSent();
    }

    public static function badStatsQueries(): array
    {
        return [
            'unknown period' => ['period=forever&format=csv'],
            'no period'      => ['format=csv'],
            'unknown format' => ['period=week&format=exe'],
        ];
    }

    /** @dataProvider badStatsQueries */
    #[\PHPUnit\Framework\Attributes\DataProvider('badStatsQueries')]
    public function test_a_bad_stats_export_request_is_rejected_before_the_api_is_called(string $query): void
    {
        Http::fake();

        $this->asCustomer()->getJson('/stats/' . self::UUID . '/export?' . $query)->assertStatus(422);

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------------ date range rules

    public function test_from_after_to_is_refused_with_a_plain_message(): void
    {
        $this->fakeReportApi();

        $this->export(['from' => '2026-10-28', 'to' => '2026-10-20'])
            ->assertStatus(422)->assertJsonPath('message', '"From" date cannot be after "To" date.');

        // (the vehicle-name lookup may run first; the expensive trip / stats / alert reads must not)
        $this->assertNull($this->sentTo('GET', '/api/GpsTracking/trips'));
        $this->assertNull($this->sentTo('GET', '/api/VehicleStats/' . self::UUID));
    }

    public function test_a_range_longer_than_90_days_is_refused_and_exactly_90_is_allowed(): void
    {
        $this->fakeReportApi();

        $this->export(['from' => '2026-07-30', 'to' => '2026-10-28'])      // 91 days
            ->assertStatus(422)->assertJsonPath('message', 'Reports are limited to 90 days at a time. Please choose a shorter range.');

        $this->export(['from' => '2026-07-31', 'to' => '2026-10-28'])->assertOk();   // 90 days
    }

    public function test_a_start_date_in_the_future_is_refused(): void
    {
        $this->fakeReportApi();

        $this->export(['from' => '2026-10-29', 'to' => '2026-10-30'])
            ->assertStatus(422)->assertJsonPath('message', 'The start date is in the future — there is no data yet.');
    }

    public function test_an_end_date_in_the_future_is_cut_back_to_now_before_asking_the_api(): void
    {
        $this->fakeReportApi();

        $this->export(['from' => '2026-10-20', 'to' => '2026-12-31'])->assertOk();

        $trips = $this->sentTo('GET', '/api/GpsTracking/trips');
        $this->assertNotNull($trips);
        $this->assertTrue(Carbon::parse($trips['to'])->lte(now()->addSecond()), 'the API must never be asked about the future: ' . $trips['to']);
    }

    public function test_the_api_is_asked_for_whole_sri_lanka_days_in_utc(): void
    {
        $this->fakeReportApi();

        $this->export(['from' => '2026-10-27', 'to' => '2026-10-27'])->assertOk();

        $trips = $this->sentTo('GET', '/api/GpsTracking/trips');
        $this->assertSame('2026-10-26T18:30:00Z', $trips['from']);   // 00:00 Colombo
        $this->assertSame('2026-10-27T18:29:59Z', $trips['to']);     // 23:59:59 Colombo
    }

    // ------------------------------------------------------------------ CSV content

    public function test_csv_download_headers(): void
    {
        $this->fakeReportApi();

        $res = $this->export()->assertOk();

        $this->assertSame('text/csv; charset=UTF-8', $res->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="shalotrack_CAB-1234_km_2026-10-28_2026-10-28.csv"', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
    }

    public function test_the_filename_can_never_carry_a_header_injection_from_the_plate(): void
    {
        $this->fakeReportApi(['vehicle' => ['vehicleId' => self::UUID, 'vehicleNumber' => "A\"B\r\nX-Injected: 1/../x", 'make' => '', 'model' => '']]);

        $res = $this->export()->assertOk();

        $disposition = $res->headers->get('Content-Disposition');
        $this->assertMatchesRegularExpression('/^attachment; filename="[A-Za-z0-9._-]+\.csv"$/', $disposition);
        $this->assertFalse($res->headers->has('X-Injected'));
    }

    public function test_the_download_helpers_clean_the_filename_themselves(): void
    {
        // Second layer: even if a caller passed a hostile name, the helper must never put it in a header.
        $csv = \App\Support\CsvExport::download("evil\"\r\nSet-Cookie: x=1/../a.csv", [['a']]);
        $this->assertMatchesRegularExpression('/^attachment; filename="[A-Za-z0-9._-]+"$/', $csv->headers->get('Content-Disposition'));
        $this->assertFalse($csv->headers->has('Set-Cookie'));

        $pdf = \App\Support\PdfExport::download('exports.alerts', [
            'reportTitle' => 't', 'vehicle' => ['plate' => 'P', 'name' => '', 'isDemo' => false, 'isShared' => false, 'owner' => null],
            'rangeLabel' => 'r', 'generatedAt' => 'g', 'report' => ['total' => 0, 'counts' => [], 'alerts' => []], 'cut' => null,
        ], "evil\"\r\nSet-Cookie: x=1/../a.pdf");
        $this->assertMatchesRegularExpression('/^attachment; filename="[A-Za-z0-9._-]+"$/', $pdf->headers->get('Content-Disposition'));
        $this->assertFalse($pdf->headers->has('Set-Cookie'));
    }

    public function test_trip_times_are_shown_in_sri_lanka_time(): void
    {
        $this->fakeReportApi();

        $rows = $this->csvRows($this->export()->streamedContent());
        $trip = collect($rows)->first(fn ($r) => ($r[0] ?? null) === '1' && ($r[8] ?? null) === 'Completed');

        // 20:00 UTC on 27 Oct is 01:30 on 28 Oct in Colombo (UTC+5:30), so the DATE changes too.
        $this->assertSame('2026-10-28', $trip[1]);
        $this->assertSame('01:30:00', $trip[2]);
        $this->assertSame('02:15:00', $trip[3]);
    }

    public function test_the_numbers_in_the_file_are_the_apis_numbers(): void
    {
        $this->fakeReportApi();

        $rows = $this->csvRows($this->export()->streamedContent());
        $byLabel = collect($rows)->filter(fn ($r) => count($r) >= 3 && is_string($r[0]))->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);

        $this->assertSame('12.5', $byLabel['Total distance']);
        $this->assertSame('2', $byLabel['Trips']);
        $this->assertSame('80', $byLabel['Max speed']);
        $this->assertSame('CAB-1234', collect($rows)->firstWhere(0, 'Vehicle')[1]);
    }

    public function test_numbers_posted_by_the_browser_cannot_change_the_file(): void
    {
        $this->fakeReportApi();

        $res = $this->asCustomer()->postJson('/stats/' . self::UUID . '/export?period=week&format=csv', [
            'distanceKm' => 99999, 'totalDistanceKm' => 99999, 'summary' => ['distanceKm' => 99999], 'chart' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $this->assertStringNotContainsString('99999', $res->streamedContent());
    }

    public function test_the_stats_export_asks_the_api_for_the_chosen_period(): void
    {
        $this->fakeReportApi();

        $this->asCustomer()->getJson('/stats/' . self::UUID . '/export?period=month&format=csv')->assertOk();

        $this->assertSame('month', $this->sentTo('GET', '/api/VehicleStats/' . self::UUID)['period']);
    }

    // ------------------------------------------------------------------ spreadsheet formula injection

    public function test_text_that_starts_like_a_formula_is_defused_in_every_csv_type(): void
    {
        $evil = ['=HYPERLINK("http://evil.test","click")', '+cmd|calc', '-2+3', '@SUM(A1)', "\tTABBED", "\rCR"];
        $this->fakeReportApi([
            'vehicle' => ['vehicleId' => self::UUID, 'vehicleNumber' => '=1+1', 'make' => '=cmd|', 'model' => '@x'],
            'alerts'  => $this->ok(['data' => ['totalCount' => 6, 'countsByType' => ['=evil' => 6], 'alerts' => array_map(
                fn ($m, $i) => ['triggeredAt' => '2026-10-27T20:1' . $i . ':00', 'alertType' => $m, 'message' => $m, 'latitude' => 6.9, 'longitude' => 79.8, 'isRead' => false],
                $evil, array_keys($evil)
            )]]),
        ]);

        foreach (['km', 'stops', 'alerts'] as $type) {
            $content = $this->export(['type' => $type])->assertOk()->streamedContent();
            foreach ($this->csvRows($content) as $row) {
                foreach ($row as $cell) {
                    if (is_numeric($cell) && ! str_starts_with((string) $cell, "'")) {
                        continue;   // a genuine number (e.g. longitude -79.86) is meant to stay a number
                    }
                    $this->assertDoesNotMatchRegularExpression('/^[=+\-@\t\r]/', (string) $cell, "$type: a cell would run as a formula in Excel: " . json_encode($cell));
                }
            }
        }
    }

    public function test_numbers_stay_numbers_so_negative_coordinates_still_sum_in_excel(): void
    {
        $this->fakeReportApi();

        $rows = $this->csvRows($this->export(['type' => 'stops'])->streamedContent());
        $stop = collect($rows)->first(fn ($r) => ($r[0] ?? null) === '1');

        $this->assertSame('-79.8612', $stop[7], 'a negative longitude must not be prefixed with an apostrophe');
    }

    public function test_the_csv_has_every_row_even_when_a_pdf_would_be_cut(): void
    {
        $alerts = array_map(fn ($i) => ['triggeredAt' => '2026-10-27T' . sprintf('%02d:%02d:00', intdiv($i, 60) % 24, $i % 60), 'alertType' => 'Overspeed',
            'message' => "n$i", 'latitude' => 1, 'longitude' => 2, 'isRead' => false], range(1, 300));
        $this->fakeReportApi(['alerts' => $this->ok(['data' => ['totalCount' => 300, 'countsByType' => ['Overspeed' => 300], 'alerts' => $alerts]])]);

        $rows = $this->csvRows($this->export(['type' => 'alerts'])->streamedContent());
        $numbered = collect($rows)->filter(fn ($r) => isset($r[0]) && ctype_digit((string) $r[0]) && isset($r[3]) && $r[3] === 'Overspeed');

        $this->assertCount(300, $numbered);
    }

    // ------------------------------------------------------------------ PDF

    public static function pdfTypes(): array
    {
        return ['km' => ['km'], 'stops' => ['stops'], 'alerts' => ['alerts']];
    }

    /** @dataProvider pdfTypes */
    #[\PHPUnit\Framework\Attributes\DataProvider('pdfTypes')]
    public function test_a_pdf_is_a_real_pdf_with_safe_headers(string $type): void
    {
        $this->fakeReportApi();

        $res = $this->export(['type' => $type, 'format' => 'pdf'])->assertOk();

        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $res->getContent());
        $this->assertMatchesRegularExpression('/^attachment; filename="shalotrack_CAB-1234_[A-Za-z0-9._-]+\.pdf"$/', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
    }

    public function test_the_stats_page_pdf_works_for_every_period(): void
    {
        $this->fakeReportApi();

        foreach (['today', 'week', 'month', 'all'] as $period) {
            $res = $this->asCustomer()->get('/stats/' . self::UUID . '/export?period=' . $period);
            $this->assertSame(200, $res->getStatusCode(), $period);
            $this->assertStringStartsWith('%PDF-', $res->getContent());
        }
    }

    public function test_hostile_text_in_a_pdf_does_not_break_it_or_reach_out_to_the_internet(): void
    {
        $payload = '<img src="http://169.254.169.254/latest/meta-data"><script>alert(1)</script><?php echo 1; ?>{{ 7*7 }}';
        $this->fakeReportApi([
            'vehicle' => ['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1', 'make' => $payload, 'model' => ''],
            'alerts'  => $this->ok(['data' => ['totalCount' => 1, 'countsByType' => [$payload => 1], 'alerts' => [[
                'triggeredAt' => '2026-10-27T20:10:00Z', 'alertType' => $payload, 'message' => $payload, 'latitude' => 1, 'longitude' => 2, 'isRead' => false]]]]),
        ]);

        $res = $this->export(['type' => 'alerts', 'format' => 'pdf'])->assertOk();

        $this->assertStringStartsWith('%PDF-', $res->getContent());
        Http::assertNotSent(fn ($req) => ! str_starts_with($req->url(), self::API));   // nothing but the API was ever contacted
    }

    public function test_pdf_views_never_print_unescaped_values_and_remote_loading_stays_off(): void
    {
        foreach (glob(resource_path('views/exports/*.blade.php')) as $view) {
            $this->assertStringNotContainsString('{!!', file_get_contents($view), basename($view) . ' prints a value without escaping');
        }

        $pdf = file_get_contents(app_path('Support/PdfExport.php'));
        $this->assertMatchesRegularExpression("/'isRemoteEnabled'\s*=>\s*false/", $pdf);
        $this->assertMatchesRegularExpression("/'isPhpEnabled'\s*=>\s*false/", $pdf);
    }

    public function test_a_pdf_is_capped_at_250_rows_to_protect_memory(): void
    {
        $method = new \ReflectionMethod(ReportExportController::class, 'capForPdf');
        $controller = app(ReportExportController::class);

        [$kept, $cut] = $method->invoke($controller, range(1, 251));
        $this->assertCount(250, $kept);
        $this->assertSame(['shown' => 250, 'total' => 251], $cut);
        $this->assertSame(1, $kept[0], 'newest rows are the ones kept');

        [$kept, $cut] = $method->invoke($controller, range(1, 250));
        $this->assertCount(250, $kept);
        $this->assertNull($cut);
    }

    // ------------------------------------------------------------------ failures

    public static function apiFailures(): array
    {
        return [
            'forbidden'   => [403, 404, 'That vehicle is not available to your account.'],
            'not found'   => [404, 404, 'That vehicle is not available to your account.'],
            'server error' => [500, 502, 'Could not generate the file. Please try again.'],
            'api 503'     => [503, 502, 'Could not generate the file. Please try again.'],
        ];
    }

    /** @dataProvider apiFailures */
    #[\PHPUnit\Framework\Attributes\DataProvider('apiFailures')]
    public function test_someone_elses_vehicle_looks_exactly_like_a_missing_one(int $apiStatus, int $httpStatus, string $message): void
    {
        $this->fakeReportApi(['stats' => Http::response(['message' => 'internal detail'], $apiStatus), 'trips' => Http::response([], $apiStatus), 'alerts' => Http::response([], $apiStatus)]);

        foreach (['km', 'stops', 'alerts'] as $type) {
            $res = $this->export(['type' => $type])->assertStatus($httpStatus);
            $this->assertSame($message, $res->json('message'), $type);
            $this->assertStringNotContainsString('internal detail', $res->getContent());
        }
    }

    public function test_an_expired_api_token_signs_the_customer_out(): void
    {
        $this->fakeReportApi(['stats' => Http::response([], 401), 'trips' => Http::response([], 401)]);

        $this->export()->assertStatus(401)->assertJsonPath('expired', true);

        $this->assertNull(session('firebase_token'));
    }
}
