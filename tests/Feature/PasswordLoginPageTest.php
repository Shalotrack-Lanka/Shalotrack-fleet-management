<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Email + password sign-in (no SMS per login). The password lives on the SAME Firebase account as the phone
 * number, so the API, profiles and the Android app are untouched; phone-code sign-in stays as the fallback.
 */
class PasswordLoginPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['shalotrack.firebase_project_id' => 'proj']);
    }

    public function test_login_page_offers_email_password_forgot_and_phone_fallback(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('id="step-email"', false)
            ->assertSee('id="step-forgot"', false)
            ->assertSee('id="step-setpw"', false)
            ->assertSee('id="step-phone"', false)       // phone code still there
            ->assertSee('signInWithEmailAndPassword', false)
            ->assertSee('sendPasswordResetEmail', false);
    }

    public function test_password_login_never_reveals_whether_an_email_exists(): void
    {
        $html = $this->get('/login')->getContent();
        foreach (['auth/user-not-found', 'auth/wrong-password', 'auth/invalid-credential'] as $code) {
            $this->assertStringContainsString("case '{$code}'", $html);
        }
        $this->assertStringContainsString('If an account exists for that email', $html);
        $this->assertStringNotContainsString('No account found', $html);
    }

    public function test_password_login_uses_the_same_server_login_as_the_phone_code(): void
    {
        $html = $this->get('/login')->getContent();
        $this->assertSame(1, substr_count($html, "fetch('/login'"));   // one shared postLogin()
        // Both sign-in paths (phone code via verifyOtp, email + password via emailSignIn) end in that one function.
        $this->assertSame(2, substr_count($html, 'await postLogin(cred.user)'));
    }

    public function test_signup_asks_for_a_password_before_the_profile_is_created(): void
    {
        $this->withSession([
            'firebase_token' => 't', 'firebase_uid' => 'u1', 'firebase_phone' => '+94714915401',
            'firebase_token_expires_at' => time() + 3000,
            'pending_profile' => ['fullName' => 'A B', 'email' => 'a@b.lk', 'nicNumber' => '199012345678', 'address' => 'Colombo'],
        ])->get('/email/verify')->assertOk()
            ->assertSee('id="password-box"', false)
            ->assertSee('linkWithCredential', false)
            ->assertSee('skipPassword', false);   // never blocks sign-up
    }
}