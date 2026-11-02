<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * F2: the Content-Security-Policy and Permissions-Policy that App\Http\Middleware\SecurityHeaders puts on every
 * web response.
 *
 * Today the policy ships as Report-Only (the portal still has inline handlers, so 'unsafe-inline' is needed
 * for scripts). These tests pin the parts that must hold either way, and the switch to enforcement, so a
 * careless edit cannot quietly weaken the policy.
 */
class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['shalotrack.api_base_url' => 'https://api.test', 'security.csp_enforce' => false]);
    }

    /** @return array<string,string> directive name => value */
    private function directives(string $header): array
    {
        $out = [];
        foreach (explode(';', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            [$name, $value] = array_pad(explode(' ', $part, 2), 2, '');
            $out[$name] = $value;
        }
        return $out;
    }

    public function test_default_is_report_only_so_nothing_breaks_on_rollout(): void
    {
        $res = $this->get('/login')->assertOk();

        $this->assertTrue($res->headers->has('Content-Security-Policy-Report-Only'));
        $this->assertFalse($res->headers->has('Content-Security-Policy'), 'must not enforce until CSP_ENFORCE=true');
    }

    public function test_csp_enforce_flag_switches_to_the_enforcing_header(): void
    {
        config(['security.csp_enforce' => true]);

        $res = $this->get('/login')->assertOk();

        $this->assertTrue($res->headers->has('Content-Security-Policy'));
        $this->assertFalse($res->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_the_policy_blocks_the_dangerous_basics(): void
    {
        $csp = $this->directives($this->get('/login')->headers->get('Content-Security-Policy-Report-Only'));

        $this->assertSame("'self'", $csp['default-src']);
        $this->assertSame("'none'", $csp['object-src']);          // no <object>/<embed>/plugins
        $this->assertSame("'self'", $csp['base-uri']);            // no <base> hijack of relative URLs
        $this->assertSame("'self'", $csp['form-action']);         // forms post only to us
        $this->assertSame("'self'", $csp['frame-ancestors']);     // no clickjacking by other sites
        $this->assertSame('/csp-report', $csp['report-uri']);
    }

    public function test_script_sources_are_a_short_reviewed_allowlist(): void
    {
        $csp = $this->directives($this->get('/login')->headers->get('Content-Security-Policy-Report-Only'));
        $hosts = array_values(array_filter(explode(' ', $csp['script-src']), fn ($s) => str_starts_with($s, 'http')));

        sort($hosts);
        $this->assertSame([
            'https://maps.googleapis.com', 'https://maps.gstatic.com', 'https://www.google.com', 'https://www.gstatic.com',
        ], $hosts, 'A new script host is a supply-chain decision: add it here only after review.');

        $this->assertStringNotContainsString('*', $csp['script-src'], 'no wildcard script hosts');
        $this->assertStringNotContainsString("'unsafe-eval'", $csp['script-src']);
        $this->assertStringNotContainsString('data:', $csp['script-src']);
    }

    public function test_connections_are_limited_to_our_api_and_google(): void
    {
        $csp = $this->directives($this->get('/login')->headers->get('Content-Security-Policy-Report-Only'));

        $this->assertStringContainsString('https://api.test', $csp['connect-src']);
        $this->assertStringContainsString('wss://api.test', $csp['connect-src']);   // live tracking socket
        $this->assertStringNotContainsString('*', $csp['connect-src'], 'a wildcard here lets injected script send data anywhere');
        $this->assertStringNotContainsString('http://', $csp['connect-src']);
    }

    public function test_the_api_host_in_the_policy_follows_configuration(): void
    {
        config(['shalotrack.api_base_url' => 'https://api.example.lk/']);   // trailing slash on purpose

        $connect = $this->directives($this->get('/login')->headers->get('Content-Security-Policy-Report-Only'))['connect-src'];

        $this->assertStringContainsString('https://api.example.lk ', $connect . ' ');
        $this->assertStringNotContainsString('api.example.lk/', $connect);
        $this->assertStringContainsString('wss://api.example.lk', $connect);
    }

    public function test_permissions_policy_allows_location_only_for_ourselves(): void
    {
        $pp = $this->get('/login')->headers->get('Permissions-Policy');

        $this->assertStringContainsString('geolocation=(self)', $pp);
        foreach (['camera=()', 'microphone=()', 'payment=()', 'usb=()'] as $off) {
            $this->assertStringContainsString($off, $pp);
        }
    }

    public function test_headers_are_on_the_landing_page_and_on_redirects_and_error_pages_too(): void
    {
        $this->get('/')->assertOk()->assertHeader('Permissions-Policy');
        // A signed-out visitor bounced to login still gets the policy (redirects are responses too).
        $this->get('/dashboard')->assertRedirect()->assertHeader('Content-Security-Policy-Report-Only');
        // A live link the API reports as gone is a 404 from a real route, so it goes through the web stack too.
        \Illuminate\Support\Facades\Http::fake(['api.test/*' => \Illuminate\Support\Facades\Http::response([], 404)]);
        $this->getJson('/live/' . str_repeat('a', 43) . '/data')->assertNotFound()->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_a_response_that_already_set_its_own_policy_is_not_overwritten(): void
    {
        // The middleware only fills gaps; a controller that sets a stricter policy keeps it.
        \Illuminate\Support\Facades\Route::middleware('web')->get('/__own-csp', fn () => response('x')
            ->header('Content-Security-Policy-Report-Only', "default-src 'none'"));

        $this->get('/__own-csp')->assertHeader('Content-Security-Policy-Report-Only', "default-src 'none'");
    }

    public function test_csp_report_endpoint_is_stateless_and_accepts_a_post_without_csrf(): void
    {
        // Browsers post violation reports without cookies or a CSRF token.
        $res = $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'],
            json_encode(['csp-report' => ['violated-directive' => 'script-src', 'blocked-uri' => 'https://evil.test']]));

        $this->assertLessThan(300, $res->getStatusCode());
        $this->assertCount(0, $res->headers->getCookies());
    }
}
