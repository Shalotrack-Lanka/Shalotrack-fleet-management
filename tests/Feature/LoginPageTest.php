<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The login page doubles as the sign-up page (phone OTP, then /register for new numbers).
 * "Get started" on the landing page must say so, and the Firebase "too many SMS requests" block
 * (reported by this SDK as auth/error-code:-39) must get a clear message instead of "something went wrong".
 */
class LoginPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();   // no built assets needed to check the markup
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

    public function test_login_script_recognises_the_firebase_throttle_and_has_a_resend_cooldown(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString("auth/error-code:-39", $html);
        $this->assertStringContainsString('TOO_MANY_ATTEMPTS', $html);
        $this->assertStringContainsString('function startCooldown', $html);
        $this->assertStringContainsString('Too many code requests', $html);
    }
}