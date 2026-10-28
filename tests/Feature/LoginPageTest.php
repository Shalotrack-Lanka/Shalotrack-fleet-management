<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The login page doubles as the sign-up page (phone code, then /register for new numbers).
 * "Get started" on the landing page must say so.
 *
 * Phone codes are sent by the C# API (Text.lk) and the browser signs in with the Firebase custom token the API
 * returns (PR #96). Firebase phone auth and reCAPTCHA are gone from this page. The tests below pin what that
 * design promises users: a rate-limit answer (429) reads as "wait", a send failure (502) is a different message,
 * the resend button is locked while a code is on its way, and the page talks to the configured API host without
 * cookies.
 *
 * These are source checks on the rendered page (the repo has no browser-test setup); they guard against
 * accidental regressions, they do not run the JavaScript.
 */
class LoginPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();   // no built assets needed to check the markup
        config(['shalotrack.api_base_url' => 'https://api.test']);
    }

    public function test_login_page_says_welcome_back_by_default(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('New to ShaloTrack?')
            ->assertDontSee('Create your account');
    }

    public function test_get_started_link_opens_the_create_account_wording(): void
    {
        $this->get('/login?new=1')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('then set up your account', false)
            ->assertDontSee('Welcome back');
    }

    public function test_landing_get_started_button_points_to_new_account_mode(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="/login?new=1"', $html);
        $this->assertMatchesRegularExpression('#href="/login\?new=1"[^>]*>Get started</a>#', $html);
        // The other entry points are for people who already have an account and stay as they were.
        $this->assertStringContainsString('Sign in with your phone</a>', $html);
    }

    public function test_login_script_turns_a_429_into_a_wait_message_and_locks_the_resend_button(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('r.status === 429', $html);
        $this->assertStringContainsString('Too many attempts. Please wait a few minutes and try again.', $html);
        $this->assertStringContainsString("kind: 'warn'", $html);          // amber card: wait, do not retry
        $this->assertStringContainsString('function startCooldown', $html);
        // The lock survives a page reload so a refresh cannot be used to hammer the SMS endpoint.
        $this->assertStringContainsString("sessionStorage.setItem('otp_cooldown_until'", $html);
        $this->assertStringContainsString("sessionStorage.getItem('otp_cooldown_until'", $html);
    }

    public function test_a_send_failure_is_not_reported_as_too_many_requests(): void
    {
        // The API answers 502 when the SMS provider could not deliver: a different problem from the rate limit.
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertMatchesRegularExpression("/r\.status === 502\) return \{\s*msg: apiMsg \|\| '([^']+)'/", $html);
        preg_match("/r\.status === 502\) return \{\s*msg: apiMsg \|\| '([^']+)'/", $html, $m);
        $this->assertStringContainsString('could not send the code', $m[1]);
        $this->assertStringNotContainsString('Too many', $m[1]);
    }

    public function test_login_page_uses_the_api_for_codes_and_no_longer_loads_recaptcha(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        // New flow: ask the API for a code, verify it, sign in with the Firebase custom token it returns.
        $this->assertStringContainsString('/api/auth/otp/request', $html);
        $this->assertStringContainsString('/api/auth/otp/verify', $html);
        $this->assertStringContainsString('signInWithCustomToken', $html);

        // Old flow must be gone, not just unused: dead reCAPTCHA code is attack surface and a third-party script.
        foreach (['grecaptcha', 'RecaptchaVerifier', 'signInWithPhoneNumber', 'recaptcha-container'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, "$gone should no longer be on the login page");
        }
    }

    public function test_login_page_calls_the_configured_api_host_without_cookies(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        // API host comes from config through @json (escaped), never concatenated from request data.
        $this->assertStringContainsString('const API_BASE = ' . json_encode('https://api.test'), $html);
        // The API is authenticated by the OTP itself; ambient cookies must never ride along cross-origin.
        $this->assertStringContainsString("credentials: 'omit'", $html);
    }
}