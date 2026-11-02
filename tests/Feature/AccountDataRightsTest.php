<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F6: the customer's own data rights - "download my data" (PDF / JSON) and "delete my account".
 *
 * The C# API owns the data and the rules (confirm word, recent sign-in, 30-day grace). The portal must:
 *   - never invent or keep the data itself (nothing is stored here),
 *   - turn each API answer into a safe, plain message (no raw API text, no machine codes),
 *   - lock the rest of the portal while a deletion is scheduled.
 */
class AccountDataRightsTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function exportPayload(): array
    {
        return ['data' => [
            'profile'  => ['fullName' => 'Nimal <b>Perera</b>', 'email' => 'n@example.lk', 'phoneNumber' => '+94771234567'],
            'vehicles' => [['vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio']],
            'alerts'   => [],
        ]];
    }

    // ---- download my data ----------------------------------------------------------------------

    public function test_json_export_is_a_private_download_of_exactly_what_the_api_returned(): void
    {
        $this->fakeApi(['Account/export' => $this->ok($this->exportPayload())]);

        $res = $this->asCustomer()->get('/api/account/export?format=json')->assertOk();

        $this->assertStringContainsString('application/json', $res->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/^attachment; filename="shalotrack-my-data-\d{4}-\d{2}-\d{2}\.json"$/', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $res->headers->get('Cache-Control'));
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertEquals($this->exportPayload()['data'], json_decode($res->getContent(), true));
    }

    public function test_pdf_is_the_default_and_is_a_real_pdf_download(): void
    {
        $this->fakeApi(['Account/export' => $this->ok($this->exportPayload())]);

        $res = $this->asCustomer()->get('/api/account/export')->assertOk();

        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/shalotrack-my-data-\d{4}-\d{2}-\d{2}\.pdf/', $res->headers->get('Content-Disposition'));
    }

    public function test_the_export_asks_the_api_for_the_signed_in_customer_only(): void
    {
        $this->fakeApi(['Account/export' => $this->ok($this->exportPayload())]);

        // A forged id in the query must go nowhere: the API resolves the owner from the token.
        $this->asCustomer('uid-9')->get('/api/account/export?format=json&customerId=' . self::UUID2)->assertOk();

        $req = $this->sentTo('GET', '/api/Account/export');
        $this->assertNotNull($req);
        $this->assertSame('Bearer tok-uid-9', $req->header('Authorization')[0]);
        $this->assertStringNotContainsString(self::UUID2, $req->url());
    }

    public function test_an_unknown_format_is_refused_before_the_api_is_called(): void
    {
        Http::fake();

        $this->asCustomer()->getJson('/api/account/export?format=xml')->assertStatus(422);
        $this->asCustomer()->getJson('/api/account/export?format[]=json')->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_an_export_with_no_data_is_a_clean_502_not_an_empty_file(): void
    {
        $this->fakeApi(['Account/export' => $this->ok(['success' => true])]);

        $this->asCustomer()->getJson('/api/account/export?format=json')
            ->assertStatus(502)->assertJsonPath('message', 'Export unavailable.');
    }

    public static function exportFailures(): array
    {
        return [
            'rate limited'  => [429, 429, 'You can download your data up to 3 times an hour. Please try again later.'],
            'server error'  => [500, 502, 'Could not prepare your data. Please try again.'],
            'forbidden'     => [403, 502, 'Could not prepare your data. Please try again.'],
            'not found'     => [404, 502, 'Could not prepare your data. Please try again.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('exportFailures')]
    public function test_export_failures_are_plain_and_never_leak_api_text(int $apiStatus, int $expected, string $message): void
    {
        $this->fakeApi(['Account/export' => Http::response(['message' => 'SQL error near users_table at /var/www/app.cs:99'], $apiStatus)]);

        $res = $this->asCustomer()->getJson('/api/account/export?format=json')
            ->assertStatus($expected)->assertJsonPath('message', $message);

        $this->assertStringNotContainsString('SQL', $res->getContent());
        $this->assertStringNotContainsString('app.cs', $res->getContent());
    }

    public function test_an_expired_token_during_export_signs_the_customer_out(): void
    {
        $this->fakeApi(['Account/export' => Http::response([], 401)]);

        $this->asCustomer('uid-1', ['customer_id' => self::UUID])->getJson('/api/account/export?format=json')
            ->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');

        $this->assertNull(session('firebase_token'));
        $this->assertNull(session('customer_id'));
    }

    // ---- deletion page + status -----------------------------------------------------------------

    public function test_the_deletion_page_shows_the_date_in_sri_lanka_time_and_the_days_left(): void
    {
        // 20:00 UTC on 12 Nov is already 13 Nov 01:30 in Colombo.
        $this->fakeApi(['Account/deletion' => $this->ok(['data' => ['pending' => true, 'scheduledFor' => '2026-11-12T20:00:00Z', 'daysLeft' => 5]])]);

        $this->asCustomer()->get('/account/deletion')
            ->assertOk()->assertSee('13 Nov 2026')->assertSee('5 days left');
    }

    public function test_a_date_without_a_zone_is_already_sri_lanka_time_and_one_day_is_singular(): void
    {
        $this->fakeApi(['Account/deletion' => $this->ok(['data' => ['pending' => true, 'scheduledFor' => '2026-11-12 20:00:00', 'daysLeft' => 1]])]);

        $this->asCustomer()->get('/account/deletion')
            ->assertOk()->assertSee('12 Nov 2026')->assertSee('1 day left')->assertDontSee('1 days left');
    }

    public function test_no_pending_deletion_sends_the_customer_back_to_the_profile_and_lifts_the_lock(): void
    {
        $this->fakeApi(['Account/deletion' => $this->ok(['data' => ['pending' => false]])]);

        $this->asCustomer('uid-1', ['deletion_pending' => true])->get('/account/deletion')->assertRedirect('/profile');

        $this->assertNull(session('deletion_pending'));
    }

    public function test_the_deletion_page_signs_out_on_401_and_degrades_quietly_on_other_errors(): void
    {
        $this->fakeApi(['Account/deletion' => Http::response([], 401)]);
        $this->asCustomer()->get('/account/deletion')->assertRedirect('/login?expired=1');

        $this->fakeApi(['Account/deletion' => Http::response([], 500)]);
        $this->asCustomer()->get('/account/deletion')->assertRedirect('/profile');
    }

    public function test_the_status_endpoint_reports_only_a_yes_or_no(): void
    {
        $this->fakeApi(['Account/deletion' => $this->ok(['data' => ['pending' => true, 'scheduledFor' => '2026-11-12', 'internalNote' => 'x']])]);

        $res = $this->asCustomer()->getJson('/api/account/deletion')->assertOk();

        $this->assertSame(['success' => true, 'pending' => true], $res->json());
    }

    public function test_the_status_endpoint_fails_with_a_plain_message(): void
    {
        $this->fakeApi(['Account/deletion' => Http::response(['message' => 'boom'], 500)]);

        $this->asCustomer()->getJson('/api/account/deletion')->assertStatus(502)->assertJsonPath('message', 'Status unavailable.');
    }

    // ---- request deletion -----------------------------------------------------------------------

    public function test_the_confirm_word_is_required_and_bounded_before_the_api_is_called(): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/api/account/delete', [])->assertStatus(422);
        $this->asCustomer()->postJson('/api/account/delete', ['confirm' => str_repeat('D', 21)])->assertStatus(422);
        $this->asCustomer()->postJson('/api/account/delete', ['confirm' => ['DELETE']])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_a_confirmed_request_schedules_the_deletion_and_locks_the_session(): void
    {
        $this->fakeApi(['Account/delete' => $this->ok()]);

        $this->asCustomer()->postJson('/api/account/delete', ['confirm' => 'DELETE'])
            ->assertOk()->assertJson(['success' => true, 'redirect' => '/account/deletion']);

        $this->assertTrue(session('deletion_pending'));
        $this->assertSentBody('POST', '/api/Account/delete', ['confirm' => 'DELETE']);
    }

    public function test_a_stale_sign_in_is_told_to_sign_in_again_and_nothing_is_locked(): void
    {
        $this->fakeApi(['Account/delete' => Http::response(['code' => 'REAUTH_REQUIRED'], 403)]);

        $this->asCustomer()->postJson('/api/account/delete', ['confirm' => 'DELETE'])
            ->assertStatus(403)->assertJsonPath('code', 'REAUTH_REQUIRED');

        $this->assertNull(session('deletion_pending'));
    }

    public static function deletionFailures(): array
    {
        return [
            'wrong word'    => [400, 400, 'Type DELETE to confirm.'],
            'too many'      => [429, 429, 'Too many attempts. Please wait a while.'],
            'server error'  => [500, 502, 'Could not schedule the deletion. Please try again.'],
            'forbidden'     => [403, 502, 'Could not schedule the deletion. Please try again.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('deletionFailures')]
    public function test_deletion_failures_get_one_plain_message_and_no_lock(int $apiStatus, int $expected, string $message): void
    {
        $this->fakeApi(['Account/delete' => Http::response(['message' => 'ACCOUNT_SERVICE_FAULT'], $apiStatus)]);

        $res = $this->asCustomer()->postJson('/api/account/delete', ['confirm' => 'DELETE'])
            ->assertStatus($expected)->assertJsonPath('message', $message);

        $this->assertStringNotContainsString('ACCOUNT_SERVICE_FAULT', $res->getContent());
        $this->assertNull(session('deletion_pending'));
    }

    public function test_an_expired_token_while_deleting_signs_the_customer_out(): void
    {
        $this->fakeApi(['Account/delete' => Http::response([], 401)]);

        $this->asCustomer()->postJson('/api/account/delete', ['confirm' => 'DELETE'])
            ->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');

        $this->assertNull(session('firebase_token'));
    }

    // ---- cancel deletion ------------------------------------------------------------------------

    public function test_cancelling_lifts_the_lock_and_returns_to_the_dashboard(): void
    {
        $this->fakeApi(['Account/deletion/cancel' => $this->ok()]);

        $this->asCustomer('uid-1', ['deletion_pending' => true])->postJson('/api/account/deletion/cancel')
            ->assertOk()->assertJson(['success' => true, 'redirect' => '/dashboard']);

        $this->assertNull(session('deletion_pending'));
    }

    public function test_a_failed_cancel_keeps_the_lock(): void
    {
        $this->fakeApi(['Account/deletion/cancel' => Http::response([], 500)]);

        $this->asCustomer('uid-1', ['deletion_pending' => true])->postJson('/api/account/deletion/cancel')
            ->assertStatus(502)->assertJsonPath('message', 'Could not cancel. Please try again.');

        $this->assertTrue(session('deletion_pending'));
    }

    // ---- the lock on the rest of the portal ------------------------------------------------------

    public static function lockedPages(): array
    {
        return ['dashboard' => ['/dashboard'], 'vehicles' => ['/vehicles'], 'reports' => ['/reports'], 'profile' => ['/profile'], 'sharing' => ['/sharing']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lockedPages')]
    public function test_while_deletion_is_scheduled_every_page_goes_to_the_deletion_page(string $url): void
    {
        Http::fake();

        $this->asCustomer('uid-1', ['deletion_pending' => true])->get($url)->assertRedirect('/account/deletion');

        Http::assertNothingSent();
    }

    public function test_while_deletion_is_scheduled_ajax_calls_get_a_clear_403(): void
    {
        Http::fake();

        $this->asCustomer('uid-1', ['deletion_pending' => true])->postJson('/sos/' . self::UUID)
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_PENDING_DELETION');

        Http::assertNothingSent();
    }

    public function test_the_deletion_page_and_its_own_api_stay_reachable_during_the_lock(): void
    {
        $this->fakeApi(['Account/deletion' => $this->ok(['data' => ['pending' => true, 'scheduledFor' => '2026-11-12', 'daysLeft' => 3]]), 'Account/deletion/cancel' => $this->ok()]);

        $this->asCustomer('uid-1', ['deletion_pending' => true])->get('/account/deletion')->assertOk();
        $this->asCustomer('uid-1', ['deletion_pending' => true])->getJson('/api/account/deletion')->assertOk();
        $this->asCustomer('uid-1', ['deletion_pending' => true])->postJson('/api/account/deletion/cancel')->assertOk();
    }
}
