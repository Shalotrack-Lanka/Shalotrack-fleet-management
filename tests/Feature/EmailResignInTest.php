<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression: the password step showed "Your sign-in expired" with no way out when the browser had lost its
 * Firebase sign-in. The page now offers "Sign in again", which ends the sign-in but keeps the typed details.
 */
class EmailResignInTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['shalotrack.api_base_url' => 'https://api.test', 'shalotrack.firebase_project_id' => 'proj']);
    }

    private function state(): array
    {
        return [
            'firebase_token' => 't', 'firebase_uid' => 'u1', 'firebase_phone' => '+94714915401',
            'firebase_token_expires_at' => time() + 3000,
            'pending_profile' => ['fullName' => 'A B', 'email' => 'a@b.lk', 'nicNumber' => '199012345678', 'address' => 'Colombo'],
        ];
    }

    public function test_resign_in_ends_signin_but_keeps_details(): void
    {
        $this->withSession($this->state())->post('/register/resign-in')->assertRedirect('/login');

        $this->assertNull(session('firebase_token'));
        $this->assertNull(session('firebase_uid'));
        $this->assertSame('a@b.lk', session('pending_profile.email'));
    }

    public function test_login_page_is_shown_after_resign_in_not_bounced_to_dashboard(): void
    {
        $this->withSession($this->state())->post('/register/resign-in');
        $this->get('/login')->assertOk();
    }

    public function test_password_page_offers_sign_in_again(): void
    {
        $this->withSession($this->state())->get('/register/password')
            ->assertOk()
            ->assertSee('action="/register/resign-in"', false)
            ->assertSee('Sign in again');
    }

    public function test_register_form_is_prefilled_after_new_signin(): void
    {
        Http::fake(['https://api.test/*' => Http::response(['message' => 'Not found'], 404)]);
        $this->withSession($this->state())->get('/register')->assertOk()->assertSee('a@b.lk', false);
    }

    public function test_state_changing_routes_reject_get(): void
    {
        // A GET (link, <img>, prefetch) must never end a session. CSRF itself is
        // skipped under phpunit, so the guarantee tested here is POST-only.
        $this->withSession($this->state())->get('/register/resign-in')->assertStatus(405);
        $this->get('/logout-and-login')->assertStatus(405);
    }

    public function test_logout_and_login_posts_to_login(): void
    {
        $this->withSession($this->state())->post('/logout-and-login')->assertRedirect('/login');
        $this->assertNull(session('firebase_token'));
    }
}
