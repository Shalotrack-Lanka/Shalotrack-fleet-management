<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\Feature\Concerns\FirebaseTestTokens;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F6: signing in and out with a real (signed) Firebase token: what is stored, what is refused, where the customer lands,
 * and the "keep me signed in" cookie. Google and the C# API are faked.
 */
class LoginSessionTest extends TestCase
{
    use FirebaseTestTokens;
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        $this->googleKeys();
    }

    /** Add API answers on top of the Google key fake (does not replace it). */
    private function api(array $routes): void
    {
        Http::fake(collect($routes)->mapWithKeys(fn ($v, $k) => ['api.test/api/' . $k => $v])->all());
    }

    private function signIn(array $body = [], array $claims = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/login', $body + ['token' => $this->token($claims)]);
    }

    // ---- what a good sign-in does ------------------------------------------------------------------------

    public function test_a_good_token_signs_the_customer_in_and_goes_to_the_dashboard(): void
    {
        $this->api(['Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]), 'Account/deletion' => $this->ok(['data' => ['pending' => false]])]);
        $token = $this->token();

        $this->postJson('/login', ['token' => $token])->assertOk()->assertExactJson(['success' => true, 'redirect' => '/dashboard']);

        $this->assertSame($token, session('firebase_token'));
        $this->assertSame(self::UID, session('firebase_uid'));
        $this->assertSame('+94771234567', session('firebase_phone'));
        $this->assertGreaterThan(time() + 2000, session('firebase_token_expires_at'));
        $this->assertNull(session('deletion_pending'));
    }

    /**
     * The array session driver used in tests replaces unknown ids by itself, so a behavioural test cannot prove the
     * rotation. This guard reads the source instead: the id must be rotated BEFORE any login data is written.
     */
    public function test_the_login_code_rotates_the_session_id_before_it_stores_anything(): void
    {
        $m = new \ReflectionMethod(\App\Http\Controllers\AuthController::class, 'login');
        $src = implode('', array_slice(file($m->getFileName()), $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));

        $rotate = strpos($src, 'Session::regenerate()');
        $store  = strpos($src, "Session::put('firebase_token'");

        $this->assertNotFalse($rotate, 'login() must call Session::regenerate()');
        $this->assertLessThan($store, $rotate, 'the id must be rotated before the token is stored');
    }

    public function test_the_browser_never_receives_the_token_back(): void
    {
        $this->api(['Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]), 'Account/deletion' => $this->ok(['data' => ['pending' => false]])]);
        $token = $this->token();

        $res = $this->postJson('/login', ['token' => $token, 'refresh_token' => str_repeat('r', 40)]);

        $this->assertStringNotContainsString($token, $res->getContent());
        $this->assertStringNotContainsString(str_repeat('r', 40), $res->getContent());
    }

    public static function destinations(): array
    {
        return [
            'has a profile'             => ['ok', 'ok', '/dashboard'],
            'no profile yet'            => [404, 'ok', '/register'],
            'profile call fails'        => [500, 'ok', '/dashboard'],
            'account locked (403)'      => [403, 'ok', '/dashboard'],
            'deletion scheduled'        => ['ok', 'pending', '/account/deletion'],
            'deletion status fails'     => ['ok', 500, '/dashboard'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('destinations')]
    public function test_the_customer_lands_on_the_right_page(string|int $profile, string|int $deletion, string $target): void
    {
        $this->api([
            'Customers/me'     => $profile === 'ok' ? $this->ok(['data' => ['customerId' => self::UUID]]) : Http::response([], $profile),
            'Account/deletion' => match (true) { $deletion === 'ok' => $this->ok(['data' => ['pending' => false]]), $deletion === 'pending' => $this->ok(['data' => ['pending' => true]]), default => Http::response([], $deletion) },
        ]);

        $this->signIn()->assertOk()->assertJsonPath('redirect', $target);

        $this->assertSame($deletion === 'pending' ? true : null, session('deletion_pending'));
    }

    public function test_a_normal_form_post_redirects_instead_of_returning_json(): void
    {
        $this->api(['Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]), 'Account/deletion' => $this->ok(['data' => ['pending' => false]])]);

        $this->post('/login', ['token' => $this->token()])->assertRedirect('/dashboard');
    }

    // ---- what a bad sign-in does -------------------------------------------------------------------------

    public static function badTokens(): array
    {
        return [
            'tampered'      => ['tampered'],
            'wrong key'     => ['attacker'],
            'expired'       => ['expired'],
            'other project' => ['other-project'],
            'garbage'       => ['garbage'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badTokens')]
    public function test_a_bad_token_gets_one_generic_answer_and_no_session(string $kind): void
    {
        Http::fake();
        $this->googleKeys();
        $token = match ($kind) {
            'tampered'      => (function () { [$h, $p, $s] = explode('.', $this->token()); return $h . '.' . self::b64(json_encode(['sub' => 'x'] + json_decode(base64_decode(strtr($p, '-_', '+/')), true))) . '.' . $s; })(),
            'attacker'      => $this->token([], [], 'attacker'),
            'expired'       => $this->token(['exp' => time() - 5]),
            'other-project' => $this->token(['aud' => 'someone-else', 'iss' => 'https://securetoken.google.com/someone-else']),
            'garbage'       => 'not.a.jwt',
        };

        $res = $this->postJson('/login', ['token' => $token])->assertStatus(401)->assertExactJson(['success' => false, 'message' => 'Authentication failed. Please try again.']);

        $this->assertNull(session('firebase_token'));
        $this->assertStringNotContainsString('signature', strtolower($res->getContent()));   // never says WHICH check failed
    }

    public function test_a_failed_form_login_goes_back_with_a_generic_error(): void
    {
        $this->from('/login')->post('/login', ['token' => 'not.a.jwt'])->assertRedirect('/login')->assertSessionHasErrors(['phone' => 'Authentication failed. Please try again.']);
    }

    public static function badBodies(): array
    {
        return [
            'no token'            => [['token' => '']],
            'token is an array'   => [['token' => ['a']]],
            'token is a number'   => [['token' => 12345]],
            'refresh too short'   => [['refresh_token' => str_repeat('r', 19)]],
            'refresh too long'    => [['refresh_token' => str_repeat('r', 2049)]],
            'refresh is an array' => [['refresh_token' => ['x']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badBodies')]
    public function test_a_malformed_request_is_refused_before_any_check_runs(array $over): void
    {
        Http::fake();

        $this->postJson('/login', $over + ['token' => 'x.y.z'])->assertStatus(422);

        Http::assertNothingSent();
        $this->assertNull(session('firebase_token'));
    }

    // ---- keep me signed in -------------------------------------------------------------------------------

    public function test_a_refresh_token_creates_one_private_cookie_and_none_means_no_cookie(): void
    {
        $this->api(['Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]), 'Account/deletion' => $this->ok(['data' => ['pending' => false]])]);

        $res = $this->signIn(['refresh_token' => str_repeat('r', 40)])->assertOk();

        $cookie = $res->getCookie(\App\Services\RememberLogin::cookieName());
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertNull($cookie->getDomain(), 'host-only: never shared with sibling subdomains');
        $this->assertSame('/', $cookie->getPath());
        $value = json_decode($cookie->getValue(), true);
        $this->assertSame([str_repeat('r', 40), self::UID], [$value['rt'], $value['uid']]);
        $this->assertEqualsWithDelta(time(), $value['iat'], 5);
        $this->assertEqualsWithDelta(time() + 90 * 86400, $cookie->getExpiresTime(), 120);   // gone after 90 days
    }

    public function test_no_refresh_token_means_no_remember_cookie(): void
    {
        $this->api(['Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]), 'Account/deletion' => $this->ok(['data' => ['pending' => false]])]);

        $this->signIn()->assertOk()->assertCookieMissing(\App\Services\RememberLogin::cookieName());
    }

    // ---- the login page -----------------------------------------------------------------------------------

    public function test_a_signed_in_customer_is_sent_to_the_dashboard_from_the_login_page(): void
    {
        $this->asCustomer()->get('/login')->assertRedirect('/dashboard');
    }

    public function test_the_expired_notice_also_forgets_the_remembered_login(): void
    {
        $res = $this->withCookie(\App\Services\RememberLogin::cookieName(), json_encode(['rt' => str_repeat('r', 30), 'uid' => self::UID, 'iat' => time()]))
            ->get('/login?expired=1')->assertOk();

        $res->assertCookieExpired(\App\Services\RememberLogin::cookieName());
        $this->assertNull(session('firebase_token'));
    }

    // ---- sign out ------------------------------------------------------------------------------------------

    public function test_signing_out_ends_the_session_and_the_remembered_login(): void
    {
        $res = $this->asCustomer('uid-1', ['customer_id' => self::UUID])
            ->withCookie(\App\Services\RememberLogin::cookieName(), json_encode(['rt' => str_repeat('r', 30), 'uid' => self::UID, 'iat' => time()]))
            ->post('/logout')->assertRedirect('/login');

        $res->assertCookieExpired(\App\Services\RememberLogin::cookieName());
        $this->assertNull(session('firebase_token'));
        $this->assertNull(session('customer_id'));
    }

    public function test_signing_out_is_post_only(): void
    {
        $this->asCustomer()->get('/logout')->assertStatus(405);

        $this->assertNotNull(session('firebase_token'));
    }

    public function test_after_signing_out_a_protected_page_asks_for_a_login(): void
    {
        $this->asCustomer()->post('/logout');

        $this->get('/dashboard')->assertRedirect('/login');
    }
}
