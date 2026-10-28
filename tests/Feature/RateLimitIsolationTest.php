<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\ReportApiFixtures;
use Tests\TestCase;

/**
 * F4: rate limits must not get in the way of SOS, and must not be dodgeable.
 *
 * THE BUG THESE GUARD AGAINST: routes limited with the unnamed `throttle:N,M` form all share ONE counter per
 * client IP. Five ordinary map polls were enough to make the very first SOS press answer "too many requests"
 * (SOS allows 3 a minute). The same counter was keyed by IP, which can be forged behind Cloudflare and is shared by
 * every customer on one mobile-carrier address. Each feature now has its own named limiter, keyed by customer.
 */
class RateLimitIsolationTest extends TestCase
{
    use ReportApiFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00', 'Asia/Colombo'));
        $this->fakeReportApi();
        $this->fakeApi([
            'SOS/*'                => $this->ok(),
            'EmergencyContacts'    => $this->ok(['data' => []]),
            'CurrentLocations/*'   => $this->ok(['data' => ['latitude' => 6.9, 'longitude' => 79.8]]),
            'Customers/me'         => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*'  => $this->ok(['data' => [['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1', 'make' => '', 'model' => '']]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => []]),
            'VehicleStats/*'       => $this->ok(['data' => ['dailyBreakdown' => []]]),
            'GpsTracking/trips*'   => $this->ok(['data' => ['trips' => [], 'stops' => []]]),
            'Alerts/report*'       => $this->ok(['data' => ['alerts' => []]]),
        ]);
    }

    private function sos(string $uid = 'uid-A'): \Illuminate\Testing\TestResponse
    {
        return $this->asCustomer($uid)->postJson('/sos/' . self::UUID);
    }

    public function test_ordinary_page_activity_never_uses_up_the_sos_budget(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->asCustomer('uid-A')->getJson('/trips/' . self::UUID . '/location')->assertOk();   // the map polling in the background
        }

        $this->sos()->assertOk();
    }

    public function test_every_feature_has_its_own_budget(): void
    {
        $export = '/reports/export?type=km&format=csv&vehicleId=' . self::UUID . '&from=2026-10-28&to=2026-10-28';

        for ($i = 1; $i <= 12; $i++) {
            $this->asCustomer('uid-A')->get($export)->assertOk();
        }
        $this->asCustomer('uid-A')->getJson($export)->assertStatus(429);   // exports: 12 a minute

        $this->sos()->assertOk();                                                                   // other features unaffected
        $this->asCustomer('uid-A')->getJson('/trips/' . self::UUID . '/location')->assertOk();
    }

    public function test_the_two_export_pages_share_one_export_budget_and_nothing_else(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->asCustomer('uid-A')->get('/reports/export?type=km&format=csv&vehicleId=' . self::UUID . '&from=2026-10-28&to=2026-10-28')->assertOk();
            $this->asCustomer('uid-A')->get('/stats/' . self::UUID . '/export?period=week&format=csv')->assertOk();
        }

        $this->asCustomer('uid-A')->getJson('/stats/' . self::UUID . '/export?period=week&format=csv')->assertStatus(429);
    }

    public function test_sos_is_limited_to_3_a_minute_per_customer_even_when_the_ip_header_is_changed(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->asCustomer('uid-A')->withHeaders(['X-Forwarded-For' => "203.0.113.$i"])->postJson('/sos/' . self::UUID)->assertOk();
        }

        $this->asCustomer('uid-A')->withHeaders(['X-Forwarded-For' => '198.51.100.99'])->postJson('/sos/' . self::UUID)->assertStatus(429);
    }

    public function test_one_customers_sos_never_blocks_another_customer_on_the_same_address(): void
    {
        // Mobile carriers put thousands of customers behind one public address.
        for ($i = 1; $i <= 3; $i++) {
            $this->sos('uid-A')->assertOk();
        }
        $this->sos('uid-A')->assertStatus(429);

        $this->sos('uid-B')->assertOk();
    }

    public function test_csp_reports_cannot_starve_sign_in(): void
    {
        // Report-Only CSP makes browsers post a report for every violation; sign-in must still work afterwards.
        for ($i = 0; $i < 30; $i++) {
            $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode(['csp-report' => ['violated-directive' => 'script-src']]));
        }

        $this->assertNotSame(429, $this->postJson('/login', [])->getStatusCode());
    }

    public function test_no_route_uses_the_unnamed_shared_throttle(): void
    {
        $offenders = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $mw) {
                if (is_string($mw) && preg_match('/^throttle:\d+(,\d+)?(,|$)/', $mw)) {
                    $offenders[] = implode('|', $route->methods()) . ' /' . $route->uri() . '  ' . $mw;
                }
            }
        }

        $this->assertSame([], $offenders, "Use a named limiter (AppServiceProvider) - the numeric form shares one counter per IP:\n" . implode("\n", $offenders));
    }

    public function test_every_named_limiter_a_route_asks_for_actually_exists(): void
    {
        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'throttle:') && ! preg_match('/^throttle:\d/', $mw)) {   // numeric form is caught by the test above
                    $name = substr($mw, 9);
                    $this->assertNotNull(\Illuminate\Support\Facades\RateLimiter::limiter($name), "$mw on /{$route->uri()} has no limiter defined");
                }
            }
        }
    }

    // ------------------------------------------------------------------ the export message fix that rode along

    public function test_an_api_rejection_without_words_never_shows_a_machine_code_on_export(): void
    {
        $this->fakeReportApi(['stats' => Http::response([], 422), 'trips' => Http::response([], 422)]);

        $res = $this->asCustomer()->getJson('/reports/export?type=km&format=csv&vehicleId=' . self::UUID . '&from=2026-10-28&to=2026-10-28')->assertStatus(422);

        $this->assertSame('Invalid report request.', $res->json('message'));
        $this->assertStringNotContainsString('REQUEST_FAILED', $res->getContent());
    }

    public function test_a_plain_sentence_from_the_api_is_still_shown_on_export(): void
    {
        $this->fakeReportApi(['stats' => Http::response(['message' => 'This vehicle has no GPS device yet'], 400)]);

        $this->asCustomer()->getJson('/reports/export?type=km&format=csv&vehicleId=' . self::UUID . '&from=2026-10-28&to=2026-10-28')
            ->assertStatus(422)->assertJsonPath('message', 'This vehicle has no GPS device yet');
    }
}
