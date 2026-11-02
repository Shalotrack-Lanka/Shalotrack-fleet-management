<?php

namespace Tests\Feature;

use App\Support\EmailTypoGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The email is not verified (owner decision, 2 Nov 2026), so a domain typo must be caught at the form.
 * The guard only suggests; "Keep what I typed" always goes through.
 */
class EmailTypoGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['shalotrack.api_base_url' => 'https://api.test', 'shalotrack.firebase_project_id' => 'proj']);
    }

    public static function typos(): array
    {
        return [
            'gamil'    => ['suwen@gamil.com', 'suwen@gmail.com'],
            'gmial'    => ['a.b@gmial.com', 'a.b@gmail.com'],
            'gmai'     => ['x@gmai.com', 'x@gmail.com'],
            'hotmial'  => ['x@hotmial.com', 'x@hotmail.com'],
            'yaho'     => ['x@yaho.com', 'x@yahoo.com'],
            'upper'    => ['X@GAMIL.COM', 'X@gmail.com'],
            'outlok'   => ['x@outlok.com', 'x@outlook.com'],
        ];
    }

    #[DataProvider('typos')]
    public function test_a_likely_typo_gets_a_suggestion(string $typed, string $expected): void
    {
        $this->assertSame($expected, EmailTypoGuard::suggest($typed));
    }

    public static function fine(): array
    {
        return [
            'gmail' => ['a@gmail.com'], 'company' => ['a@shalotrack.com'], 'sltnet' => ['a@sltnet.lk'],
            'mail.com is real' => ['a@mail.com'], 'aol' => ['a@aol.com'], 'short unrelated' => ['a@gm.lk'],
            'no at sign' => ['nonsense'], 'empty local' => ['@gmail.com'],
        ];
    }

    #[DataProvider('fine')]
    public function test_a_normal_address_is_left_alone(string $email): void
    {
        $this->assertNull(EmailTypoGuard::suggest($email));
    }

    private function form(array $over = []): array
    {
        return $over + ['fullName' => 'Suwen J', 'email' => 'suwen@gamil.com', 'nicNumber' => '198648697512', 'address' => 'Colombo'];
    }

    private function signedIn(): static
    {
        return $this->withSession(['firebase_token' => 't', 'firebase_uid' => 'u1', 'firebase_phone' => '+94714915401', 'firebase_token_expires_at' => time() + 3000]);
    }

    public function test_the_form_asks_once_and_offers_both_buttons(): void
    {
        $this->signedIn()->post('/register', $this->form())
            ->assertSessionHasErrors(['email' => 'Did you mean suwen@gmail.com?'])
            ->assertSessionHas('email_suggestion', 'suwen@gmail.com')
            ->assertSessionMissing('pending_profile');
    }

    public function test_keep_what_i_typed_goes_through(): void
    {
        $this->signedIn()->post('/register', $this->form(['keepEmail' => '1']))
            ->assertRedirect('/register/password')
            ->assertSessionHas('pending_profile.email', 'suwen@gamil.com');
    }

    public function test_the_corrected_address_goes_through(): void
    {
        $this->signedIn()->post('/register', $this->form(['email' => 'suwen@gmail.com']))
            ->assertRedirect('/register/password')
            ->assertSessionHas('pending_profile.email', 'suwen@gmail.com');
    }

    public function test_an_address_that_is_not_an_address_is_still_refused(): void
    {
        $this->signedIn()->post('/register', $this->form(['email' => 'not-an-email', 'keepEmail' => '1']))
            ->assertSessionHasErrors('email')->assertSessionMissing('pending_profile');
    }

    public function test_dns_check_is_off_under_tests_and_on_by_default_elsewhere(): void
    {
        $this->assertFalse(config('shalotrack.email_dns_check'));   // tests have no network
        $src = file_get_contents(base_path('config/shalotrack.php'));
        $this->assertStringContainsString("env('APP_ENV') !== 'testing'", $src);
    }

    // ---- the domain check (resolver faked: no network in tests) ----

    protected function tearDown(): void
    {
        \App\Support\EmailDomainCheck::$resolver = null;
        parent::tearDown();
    }

    public function test_a_dead_domain_is_refused_while_dns_works(): void
    {
        config(['shalotrack.email_dns_check' => true]);
        \App\Support\EmailDomainCheck::$resolver = fn (string $d) => $d === 'gmail.com' || $d === 'shalotrack.com';

        $this->signedIn()->post('/register', $this->form(['email' => 'a@no-such-domain-xyz.com', 'keepEmail' => '1']))
            ->assertSessionHasErrors(['email' => 'That email domain cannot receive mail. Check the address after the @.'])
            ->assertSessionMissing('pending_profile');
    }

    public function test_a_real_domain_passes_the_dns_check(): void
    {
        config(['shalotrack.email_dns_check' => true]);
        \App\Support\EmailDomainCheck::$resolver = fn (string $d) => $d === 'gmail.com' || $d === 'shalotrack.com';

        $this->signedIn()->post('/register', $this->form(['email' => 'a@shalotrack.com']))
            ->assertRedirect('/register/password');
    }

    public function test_if_dns_itself_is_down_nobody_is_blocked(): void
    {
        config(['shalotrack.email_dns_check' => true]);
        \App\Support\EmailDomainCheck::$resolver = fn (string $d) => false;   // even gmail.com fails: resolver outage

        $this->signedIn()->post('/register', $this->form(['email' => 'a@shalotrack.com']))
            ->assertRedirect('/register/password');
    }
}