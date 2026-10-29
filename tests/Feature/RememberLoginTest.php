<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FirebaseTestTokens;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F6: "keep me signed in" for up to 90 days. A stolen or stale cookie must never open a session it should not,
 * and the 90 days must never slide. Google's token endpoint, its keys and the C# API are faked.
 */
class RememberLoginTest extends TestCase
{
    use FirebaseTestTokens;
    use SignedInFleetUser;

    private const RT = 'refresh-token-0123456789-abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        $this->googleKeys();
    }

    private function cookie(array $over = []): string
    {
        return json_encode($over + ['rt' => self::RT, 'uid' => self::UID, 'iat' => time() - 3600]);
    }

    /** Google's refresh endpoint + the API pages a restored session touches. */
    private function google(\GuzzleHttp\Promise\PromiseInterface|callable|null $refresh = null): void
    {
        Http::fake([
            'securetoken.googleapis.com/*' => $refresh ?? Http::response(['id_token' => $this->token(), 'refresh_token' => 'rotated-refresh-token-0123456789'], 200),
            'api.test/api/Account/deletion' => Http::response(['data' => ['pending' => false]], 200),
            'api.test/api/Customers/me'     => Http::response(['data' => ['customerId' => self::UUID]], 200),
            'api.test/api/Customers/*'      => Http::response(['data' => ['vehicles' => []]], 200),
        ]);
    }

    private function refreshCalls(): int
    {
        return Http::recorded(fn ($req) => str_contains($req->url(), 'securetoken.googleapis.com'))->count();
    }

    public function test_a_new_session_is_quietly_signed_in_from_the_cookie_and_the_cookie_is_renewed(): void
    {
        $this->google();
        $iat = time() - 86400;

        $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie(['iat' => $iat]))->get('/dashboard')->assertOk();

        $this->assertNotNull(session('firebase_token'));
        $this->assertSame(self::UID, session('firebase_uid'));
        $new = json_decode($res->getCookie(\App\Services\RememberLogin::cookieName())->getValue(), true);
        $this->assertSame('rotated-refresh-token-0123456789', $new['rt']);           // Google's rotated token is kept
        $this->assertSame($iat, $new['iat']);                                         // the 90 days did NOT restart
        $this->assertEqualsWithDelta($iat + 90 * 86400, $res->getCookie(\App\Services\RememberLogin::cookieName())->getExpiresTime(), 120);
    }

    public function test_the_refresh_call_carries_the_cookies_token_and_our_key_only(): void
    {
        config(['services.firebase.api_key' => 'KEY&evil=1']);
        $this->google();

        $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertOk();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'securetoken.googleapis.com/v1/token?key=KEY%26evil%3D1')
            && $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === self::RT);
    }

    public function test_a_session_whose_token_is_about_to_end_is_renewed_before_it_fails(): void
    {
        $this->google();

        $this->withSession(['firebase_token' => 'old', 'firebase_uid' => self::UID, 'firebase_token_expires_at' => time() + 100])
            ->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertOk();

        $this->assertNotSame('old', session('firebase_token'));
        $this->assertSame(1, $this->refreshCalls());
    }

    public function test_a_healthy_session_never_calls_google(): void
    {
        $this->google();

        $this->asCustomer()->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertOk();

        $this->assertSame(0, $this->refreshCalls());
    }

    public function test_with_no_cookie_a_stranger_is_simply_asked_to_sign_in(): void
    {
        $this->google();

        $this->get('/dashboard')->assertRedirect('/login');

        $this->assertSame(0, $this->refreshCalls());
    }

    public function test_a_restored_session_learns_that_a_deletion_is_scheduled(): void
    {
        Http::fake([
            'securetoken.googleapis.com/*'  => Http::response(['id_token' => $this->token(), 'refresh_token' => 'rotated-refresh-token-0123456789'], 200),
            'api.test/api/Account/deletion' => Http::response(['data' => ['pending' => true]], 200),
        ]);

        $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertRedirect('/account/deletion');

        $this->assertTrue(session('deletion_pending'));
    }

    public function test_the_login_page_also_restores_a_returning_customer(): void
    {
        $this->google();

        $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/login')->assertRedirect('/dashboard');
    }

    // ---- cookies that must NOT work ----------------------------------------------------------------------

    public static function unusableCookies(): array
    {
        $now = time();
        return [
            'not json'             => ['this is not json'],
            'json list'            => ['[1,2,3]'],
            'no token'             => [json_encode(['uid' => 'u', 'iat' => $now])],
            'token too short'      => [json_encode(['rt' => str_repeat('r', 19), 'uid' => 'u', 'iat' => $now])],
            'token too long'       => [json_encode(['rt' => str_repeat('r', 2049), 'uid' => 'u', 'iat' => $now])],
            'no uid'               => [json_encode(['rt' => str_repeat('r', 30), 'iat' => $now])],
            'start is text'        => [json_encode(['rt' => str_repeat('r', 30), 'uid' => 'u', 'iat' => 'yesterday'])],
            'start is a float'     => [json_encode(['rt' => str_repeat('r', 30), 'uid' => 'u', 'iat' => 1.5])],
            'older than 90 days'   => [json_encode(['rt' => str_repeat('r', 30), 'uid' => 'u', 'iat' => $now - 91 * 86400])],
            'start in the future'  => [json_encode(['rt' => str_repeat('r', 30), 'uid' => 'u', 'iat' => $now + 4000])],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unusableCookies')]
    public function test_a_damaged_or_stale_cookie_is_dropped_without_calling_google(string $value): void
    {
        $this->google();

        $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), $value)->get('/dashboard')->assertRedirect('/login');

        $res->assertCookieExpired(\App\Services\RememberLogin::cookieName());
        $this->assertSame(0, $this->refreshCalls());
        $this->assertNull(session('firebase_token'));
    }

    public function test_exactly_ninety_days_old_is_still_allowed_and_one_second_more_is_not(): void
    {
        $this->google();
        $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie(['iat' => time() - 90 * 86400 + 5]))->get('/dashboard')->assertOk();

        $this->google();
        $this->flushSession();
        $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie(['iat' => time() - 90 * 86400 - 5]))->get('/dashboard')->assertRedirect('/login');
    }

    public function test_when_google_refuses_the_login_is_over_and_the_cookie_is_removed(): void
    {
        foreach ([400, 401, 403] as $status) {
            $this->google(Http::response(['error' => ['message' => 'TOKEN_EXPIRED']], $status));

            $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertRedirect('/login');

            $res->assertCookieExpired(\App\Services\RememberLogin::cookieName());
        }
    }

    public function test_when_google_is_busy_or_unreachable_the_cookie_is_kept_for_next_time(): void
    {
        foreach ([[Http::response([], 500)], [Http::response([], 503)], [Http::response([], 429)], [fn () => throw new \Illuminate\Http\Client\ConnectionException('down')]] as [$answer]) {
            $this->google($answer);

            $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertRedirect('/login');

            $res->assertCookieMissing(\App\Services\RememberLogin::cookieName());   // neither refreshed nor deleted
        }
    }

    public function test_a_refreshed_token_that_fails_verification_is_not_trusted(): void
    {
        foreach ([$this->token([], [], 'attacker'), $this->token(['exp' => time() - 5]), $this->token(['aud' => 'other'])] as $bad) {
            $this->google(Http::response(['id_token' => $bad, 'refresh_token' => 'x'], 200));

            $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertRedirect('/login');

            $res->assertCookieExpired(\App\Services\RememberLogin::cookieName());
            $this->assertNull(session('firebase_token'));
        }
    }

    public function test_a_cookie_cannot_be_used_to_become_someone_else(): void
    {
        // The cookie says user A, but Google (or whoever answers) returns a valid token for user B.
        $this->google(Http::response(['id_token' => $this->token(['sub' => 'user-b']), 'refresh_token' => 'x'], 200));

        $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie(['uid' => 'user-a']))->get('/dashboard')->assertRedirect('/login');

        $res->assertCookieExpired(\App\Services\RememberLogin::cookieName());
        $this->assertNull(session('firebase_token'));
        $this->assertNull(session('firebase_uid'));
    }

    public function test_a_response_without_a_token_does_not_sign_anyone_in(): void
    {
        $this->google(Http::response(['unexpected' => true], 200));

        $this->withCookie(\App\Services\RememberLogin::cookieName(), $this->cookie())->get('/dashboard')->assertRedirect('/login');

        $this->assertNull(session('firebase_token'));
    }

    public function test_a_cookie_that_is_not_encrypted_by_us_is_ignored_in_the_normal_path(): void
    {
        // A cookie planted by a script or another site is not encrypted with our key: Laravel drops it before we read it.
        $this->google();

        $this->withUnencryptedCookie('st_remember', $this->cookie())->get('/dashboard')->assertRedirect('/login');

        $this->assertSame(0, $this->refreshCalls());
    }
}
