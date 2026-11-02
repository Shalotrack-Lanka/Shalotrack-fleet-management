<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * F2: the public, no-login live link (/live/{token} and /live/{token}/data).
 *
 * This is the only part of Fleet that anonymous people can reach, and the token in the URL is the only
 * secret. What these tests protect:
 *   - nothing but the allowlisted fields ever leaves the portal, even if the API starts returning more;
 *   - every failure looks the same to the viewer (no way to tell "never existed" from "expired");
 *   - no cookie / session is created for a stranger, nothing is cached or indexed or framed;
 *   - the token is never written to the logs;
 *   - a malformed token never reaches the API, and one link cannot be used to hammer the API.
 *
 * The C# API is faked (Http::fake); no network is used.
 */
class PublicLiveLinkTest extends TestCase
{
    private const TOKEN = 'AbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_AbCde';   // 43 chars, URL-safe alphabet

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->assertSame(43, strlen(self::TOKEN), 'fixture must match the route constraint');
    }

    /** A realistic API answer, plus fields that must NEVER reach the viewer. */
    private function apiPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'success' => true,
            'data' => [
                'plateNumber' => 'CAB-1234',
                'vehicleType' => 'Car',
                'expiresAt'   => '2026-10-28T10:00:00',          // no offset on purpose: API may omit it
                'serverTime'  => '2026-10-28T09:00:00Z',
                'position'    => [
                    'latitude' => 6.9271, 'longitude' => 79.8612, 'speedKmh' => 42.5, 'heading' => 90,
                    'isMoving' => true, 'lastUpdate' => '2026-10-28T08:59:30',
                    // leaks that must be dropped:
                    'deviceImei' => '860000000000001', 'batteryLevel' => 77,
                ],
                'trail' => [
                    ['latitude' => 6.92, 'longitude' => 79.86, 'time' => '2026-10-28T08:50:00', 'speedKmh' => 10, 'ignition' => true],
                    ['latitude' => 6.93, 'longitude' => 79.87, 'time' => '2026-10-28T08:55:00'],
                ],
                // leaks that must be dropped:
                'ownerPhone' => '+94771234567', 'ownerEmail' => 'owner@example.lk', 'userId' => 'uid-secret',
                'vehicleId' => 'veh-secret', 'imei' => '860000000000001', 'ownerName' => 'Private Person',
            ],
        ], $overrides);
    }

    private function fakeApi(array|int $answer): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());   // replace, never accumulate (see SignedInFleetUser)

        Http::fake(['api.test/api/public/live/*' => is_int($answer)
            ? Http::response(['error' => 'x'], $answer)
            : Http::response($answer, 200)]);
    }

    // ------------------------------------------------------------------ the HTML page

    public function test_page_opens_without_login_and_carries_the_token_only_as_a_data_attribute(): void
    {
        $res = $this->get('/live/' . self::TOKEN)->assertOk();

        $res->assertSee('data-token="' . self::TOKEN . '"', false);
        $res->assertSee('name="robots" content="noindex, nofollow"', false);
        $res->assertSee('name="referrer" content="strict-origin"', false);
    }

    public function test_page_and_data_set_the_privacy_headers(): void
    {
        $this->fakeApi($this->apiPayload());

        foreach (['/live/' . self::TOKEN, '/live/' . self::TOKEN . '/data'] as $url) {
            $res = $this->get($url)->assertOk();

            $this->assertSame('no-store, private', $res->headers->get('Cache-Control'), $url);
            $this->assertSame('noindex, nofollow', $res->headers->get('X-Robots-Tag'), $url);
            $this->assertSame('DENY', $res->headers->get('X-Frame-Options'), $url);
            $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'), $url);
            // The token is in the path: third parties (Google Maps) may only ever see the origin.
            $this->assertSame('strict-origin', $res->headers->get('Referrer-Policy'), $url);
        }
    }

    public function test_a_stranger_never_gets_a_cookie_or_a_session(): void
    {
        $this->fakeApi($this->apiPayload());

        foreach (['/live/' . self::TOKEN, '/live/' . self::TOKEN . '/data'] as $url) {
            $res = $this->get($url)->assertOk();
            $this->assertCount(0, $res->headers->getCookies(), "$url must not send Set-Cookie");
            $this->assertFalse($res->headers->has('Set-Cookie'), $url);
        }
    }

    public function test_malformed_tokens_are_rejected_before_anything_runs(): void
    {
        Http::fake();

        $bad = [
            str_repeat('a', 42),                    // too short
            str_repeat('a', 44),                    // too long
            str_repeat('a', 42) . '.',              // dot is not in the alphabet
            str_repeat('a', 42) . '%',              // percent
            str_repeat('a', 42) . '+',              // plain base64 plus (not URL-safe)
        ];
        foreach ($bad as $token) {
            $this->get('/live/' . $token)->assertNotFound();
            $this->get('/live/' . $token . '/data')->assertNotFound();
        }

        Http::assertNothingSent();   // a guessed or garbage token costs the API nothing
    }

    public function test_the_page_is_post_proof(): void
    {
        // Read-only: no write verbs are routed for the public link.
        $this->post('/live/' . self::TOKEN)->assertStatus(405);
        $this->delete('/live/' . self::TOKEN . '/data')->assertStatus(405);
    }

    // ------------------------------------------------------------------ the data feed

    public function test_data_is_rebuilt_from_an_allowlist_and_leaks_nothing_else(): void
    {
        $this->fakeApi($this->apiPayload());

        $json = $this->getJson('/live/' . self::TOKEN . '/data')->assertOk()->json();

        $this->assertTrue($json['success']);
        $d = $json['data'];

        $this->assertEqualsCanonicalizing(['plateNumber', 'vehicleType', 'expiresAt', 'serverTime', 'position', 'trail'], array_keys($d));
        $this->assertEqualsCanonicalizing(['latitude', 'longitude', 'speedKmh', 'heading', 'isMoving', 'lastUpdate'], array_keys($d['position']));
        foreach ($d['trail'] as $point) {
            $this->assertEqualsCanonicalizing(['latitude', 'longitude', 'time'], array_keys($point));
        }

        // Belt and braces: the secret values must not appear anywhere in the raw response body.
        $raw = $this->get('/live/' . self::TOKEN . '/data')->getContent();
        foreach (['+94771234567', 'owner@example.lk', 'uid-secret', 'veh-secret', '860000000000001', 'Private Person'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw, "$secret leaked");
        }

        $this->assertSame('CAB-1234', $d['plateNumber']);
        $this->assertSame(42.5, $d['position']['speedKmh']);
        $this->assertCount(2, $d['trail']);
    }

    public function test_timestamps_without_an_offset_are_returned_as_utc_with_a_z(): void
    {
        // Without the Z a browser reads "2026-10-28T08:59:30" as LOCAL time: wrong by the viewer's UTC offset.
        $this->fakeApi($this->apiPayload());

        $d = $this->getJson('/live/' . self::TOKEN . '/data')->json('data');

        $this->assertSame('2026-10-28T10:00:00Z', $d['expiresAt']);
        $this->assertSame('2026-10-28T08:59:30Z', $d['position']['lastUpdate']);
        $this->assertSame('2026-10-28T08:50:00Z', $d['trail'][0]['time']);
    }

    public function test_an_offset_timestamp_is_converted_not_just_relabelled(): void
    {
        $this->fakeApi($this->apiPayload(['data' => ['serverTime' => '2026-10-28T14:30:00+05:30']]));

        $this->assertSame('2026-10-28T09:00:00Z', $this->getJson('/live/' . self::TOKEN . '/data')->json('data.serverTime'));
    }

    public function test_garbage_timestamps_become_null_instead_of_an_error(): void
    {
        $this->fakeApi($this->apiPayload(['data' => ['expiresAt' => 'not-a-date']]));

        $this->getJson('/live/' . self::TOKEN . '/data')->assertOk()->assertJsonPath('data.expiresAt', null);
    }

    public function test_trail_false_leaves_the_trail_out_and_tells_the_api_not_to_send_it(): void
    {
        $this->fakeApi($this->apiPayload());

        $json = $this->getJson('/live/' . self::TOKEN . '/data?trail=false')->assertOk()->json('data');

        $this->assertArrayNotHasKey('trail', $json);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/public/live/' . self::TOKEN) && $req['trail'] === 'false');
    }

    public function test_the_token_is_url_encoded_when_forwarded_to_the_api(): void
    {
        $this->fakeApi($this->apiPayload());

        $this->getJson('/live/' . self::TOKEN . '/data')->assertOk();

        Http::assertSent(fn ($req) => str_starts_with($req->url(), 'https://api.test/api/public/live/' . rawurlencode(self::TOKEN)));
    }

    public function test_vehicle_type_is_capped_at_30_characters(): void
    {
        $this->fakeApi($this->apiPayload(['data' => ['vehicleType' => str_repeat('x', 500)]]));

        $this->assertSame(30, mb_strlen($this->getJson('/live/' . self::TOKEN . '/data')->json('data.vehicleType')));
    }

    public function test_a_non_string_vehicle_type_is_dropped(): void
    {
        // (a separate test: Http::fake keeps the first registered answer within one test)
        $payload = $this->apiPayload();
        $payload['data']['vehicleType'] = ['nested' => 'array'];
        $this->fakeApi($payload);

        $this->assertNull($this->getJson('/live/' . self::TOKEN . '/data')->json('data.vehicleType'));
    }

    // ------------------------------------------------------------------ failures look the same

    /** Every reason a link can be dead, as the API reports it. */
    public static function deadLinkAnswers(): array
    {
        return [
            'API 404 (unknown / expired / stopped)' => [404],
            '200 but no position (lapsed, no fix yet)' => [['success' => true, 'data' => ['plateNumber' => 'CAB-1234', 'position' => null]]],
            '200 with an empty body' => [['success' => true]],
        ];
    }

    /** @dataProvider deadLinkAnswers */
    #[\PHPUnit\Framework\Attributes\DataProvider('deadLinkAnswers')]
    public function test_every_dead_link_gets_the_identical_gone_answer(array|int $apiAnswer): void
    {
        $this->fakeApi($apiAnswer);

        $res = $this->getJson('/live/' . self::TOKEN . '/data')->assertNotFound();

        $this->assertSame(
            ['success' => false, 'gone' => true, 'message' => 'This link has expired or is no longer available.'],
            $res->json(),
            'unknown, expired, stopped and lapsed links must be indistinguishable'
        );
        $this->assertStringNotContainsString('CAB-1234', $res->getContent(), 'a dead link must not reveal the plate');
    }

    public function test_api_rate_limit_is_passed_on_as_busy_not_gone(): void
    {
        $this->fakeApi(429);

        $this->getJson('/live/' . self::TOKEN . '/data')
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonMissing(['gone' => true]);   // "busy" must not make the viewer think the link died
    }

    public function test_api_failure_is_a_generic_502_and_the_token_is_never_logged(): void
    {
        Log::spy();
        $this->fakeApi(500);

        $res = $this->getJson('/live/' . self::TOKEN . '/data')->assertStatus(502);

        $this->assertSame('Temporarily unavailable.', $res->json('message'));
        $this->assertStringNotContainsString(self::TOKEN, $res->getContent());

        Log::shouldHaveReceived('warning')->atLeast()->once();
        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) {
            return ! str_contains((string) $message . json_encode($context), self::TOKEN);
        });
    }

    public function test_error_responses_carry_the_privacy_headers_too(): void
    {
        foreach ([404, 429, 500] as $apiStatus) {
            $this->fakeApi($apiStatus);
            $res = $this->getJson('/live/' . self::TOKEN . '/data');

            $this->assertSame('no-store, private', $res->headers->get('Cache-Control'), "API $apiStatus");
            $this->assertSame('DENY', $res->headers->get('X-Frame-Options'), "API $apiStatus");
            $this->assertSame('noindex, nofollow', $res->headers->get('X-Robots-Tag'), "API $apiStatus");
        }
    }

    // ------------------------------------------------------------------ abuse limits

    public function test_one_link_is_limited_to_40_requests_a_minute_and_other_links_are_not_affected(): void
    {
        $this->fakeApi($this->apiPayload());
        $other = str_repeat('Z', 43);

        for ($i = 1; $i <= 40; $i++) {
            $this->getJson('/live/' . self::TOKEN . '/data')->assertOk();
        }
        $this->getJson('/live/' . self::TOKEN . '/data')->assertStatus(429);   // 41st on the same link

        $this->getJson('/live/' . $other . '/data')->assertOk();               // a different link is unaffected
    }

    public function test_page_and_data_share_the_same_per_link_budget(): void
    {
        $this->fakeApi($this->apiPayload());

        for ($i = 1; $i <= 40; $i++) {
            $this->get('/live/' . self::TOKEN)->assertOk();
        }
        $this->getJson('/live/' . self::TOKEN . '/data')->assertStatus(429);
    }

    public function test_the_route_stack_has_no_session_csrf_or_cookie_middleware(): void
    {
        foreach (['live.page', 'live.data'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $excluded = $route->excludedMiddleware();

            foreach ([
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            ] as $mw) {
                $this->assertContains($mw, $excluded, "$name must exclude " . class_basename($mw));
            }
            $this->assertContains('throttle:live-public', $route->gatherMiddleware(), "$name must stay rate limited");
        }
    }

    // ------------------------------------------------------------------ the page's own markup

    public function test_page_script_reads_the_token_from_the_dom_and_never_from_a_blade_echo_in_js(): void
    {
        $src = file_get_contents(resource_path('views/live/show.blade.php'));

        // The only Blade echo of the token is the data attribute (HTML-escaped by {{ }}).
        $this->assertSame(1, preg_match_all('/\{\{\s*\$token\s*\}\}|\{!!\s*\$token\s*!!\}/', $src));
        $this->assertStringContainsString('data-token="{{ $token }}"', $src);
        $this->assertStringNotContainsString('{!!', $src, 'no unescaped output on a public page');
        $this->assertStringContainsString('const TOKEN = document.body.dataset.token', $src);
    }
}
