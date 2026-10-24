<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Format checks on top of type/length validation:
 *  - NIC must be a real Sri Lankan NIC (9 digits + V/X, or 12 digits) and is stored in one canonical form,
 *    so " 861234567v " and "861234567V" cannot register as two customers.
 *  - the profile phone number must look like a phone number (same rule as emergency contacts).
 */
class InputFormatValidationTest extends TestCase
{
    private const API = 'https://api.test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['shalotrack.api_base_url' => self::API, 'shalotrack.firebase_project_id' => 'proj']);
    }

    private function signedIn(): static
    {
        return $this->withSession([
            'firebase_token' => 't', 'firebase_uid' => 'u1', 'firebase_phone' => '+94714915401',
            'firebase_token_expires_at' => time() + 3000,
        ]);
    }

    private function register(string $nic)
    {
        return $this->signedIn()->post('/register', [
            'fullName' => 'Charith Silva', 'email' => 'charith@example.com',
            'nicNumber' => $nic, 'address' => 'Colombo',
        ]);
    }

    public function test_new_and_old_nic_formats_are_accepted(): void
    {
        Http::fake();
        $this->register('198648697512')->assertRedirect('/email/verify')->assertSessionHas('pending_profile.nicNumber', '198648697512');
    }

    public function test_old_nic_is_stored_upper_case_without_spaces(): void
    {
        Http::fake();
        $this->register(' 861234567v ')->assertRedirect('/email/verify')->assertSessionHas('pending_profile.nicNumber', '861234567V');
    }

    #[DataProvider('badNics')]
    public function test_invalid_nic_is_rejected(string $nic): void
    {
        Http::fake();
        $this->register($nic)->assertSessionHasErrors('nicNumber')->assertSessionMissing('pending_profile');
        Http::assertNothingSent();
    }

    public static function badNics(): array
    {
        return [
            'too short'      => ['12345'],
            'letters'        => ['ABCDEFGHIJ'],
            'wrong letter'   => ['861234567Z'],
            '11 digits'      => ['19864869751'],
            'script'         => ['<script>1</script>'],
            'sql-ish'        => ["1' OR '1'='1"],
        ];
    }

    public function test_profile_rejects_a_phone_number_that_is_not_a_phone_number(): void
    {
        Http::fake();
        $this->signedIn()->putJson('/profile', [
            'fullName' => 'Charith Silva', 'phoneNumber' => 'call me maybe', 'address' => 'Colombo',
        ])->assertStatus(422)->assertJsonValidationErrors('phoneNumber');
        Http::assertNothingSent();
    }
}
