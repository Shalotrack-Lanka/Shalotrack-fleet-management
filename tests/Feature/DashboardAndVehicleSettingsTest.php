<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: the dashboard page and the per-vehicle settings the portal proxies: live links, reminders, alert limits, weekly summary. */
class DashboardAndVehicleSettingsTest extends TestCase
{
    use SignedInFleetUser;

    private const XSS = '<img src=x onerror=window.__pwn=1>';
    private const TOKEN = 'AbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_AbCde';   // 43 url-safe characters

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    // ---- dashboard ----------------------------------------------------------------------------------

    private function dashboardApi(array $dash, array $over = []): void
    {
        $this->fakeApi($over + [
            'Customers/me'          => $this->ok(['data' => ['customerId' => self::UUID2, 'fullName' => 'Nimal']]),
            'Customers/*/dashboard' => $this->ok(['data' => $dash]),
        ]);
    }

    public function test_the_dashboard_loads_for_the_signed_in_customer_and_remembers_who_they_are(): void
    {
        $this->dashboardApi(['vehicles' => [['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1234']]]);

        $this->asCustomer()->get('/dashboard')->assertOk()->assertSessionHas('customer_id', self::UUID2)->assertSessionHas('customer_name', 'Nimal');

        $this->assertNotNull($this->sentTo('GET', '/api/Customers/' . self::UUID2 . '/dashboard'));
    }

    public function test_a_dashboard_with_no_vehicles_or_a_strange_shape_still_renders(): void
    {
        foreach ([[], ['vehicles' => null], ['vehicles' => 'oops']] as $i => $shape) {
            $this->dashboardApi($shape);
            $this->asCustomer('uid-' . $i)->get('/dashboard')->assertOk();
        }
    }

    public function test_hostile_vehicle_text_is_escaped_on_the_dashboard(): void
    {
        $this->dashboardApi(['vehicles' => [['vehicleId' => self::UUID, 'vehicleNumber' => self::XSS, 'make' => self::XSS]]]);

        $res = $this->asCustomer()->get('/dashboard')->assertOk();

        $this->assertStringNotContainsString(self::XSS, $res->getContent());
    }

    public function test_a_profile_without_an_id_shows_an_error_and_never_asks_for_a_dashboard(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok(['data' => []])]);

        $this->asCustomer()->get('/dashboard')->assertOk()->assertSee('Could not load your profile');

        $this->assertNull($this->sentTo('GET', '/dashboard'));
    }

    public function test_dashboard_failures_each_get_their_own_plain_message(): void
    {
        $this->dashboardApi([], ['Customers/*/dashboard' => Http::response([], 500)]);
        $this->asCustomer('a')->get('/dashboard')->assertOk()->assertSee('Could not connect to the server');

        $this->dashboardApi([], ['Customers/*/dashboard' => Http::response(['code' => 'SUBSCRIPTION_RENEWAL_REQUIRED'], 402)]);
        $this->asCustomer('b')->get('/dashboard')->assertOk()->assertSee('subscription has expired')->assertSessionHas('renewal_required', true);

        $this->dashboardApi([], ['Customers/*/dashboard' => Http::response([], 401)]);
        $this->asCustomer('c')->get('/dashboard')->assertRedirect('/login?expired=1');
    }

    // ---- live links (owner side) ---------------------------------------------------------------------

    public function test_a_link_is_created_for_one_to_twenty_four_hours_and_the_url_is_built_here(): void
    {
        $this->fakeApi(['LiveShare/vehicle/*' => $this->ok(['data' => ['linkId' => self::UUID2, 'token' => self::TOKEN, 'createdAt' => '2026-10-28T10:00:00Z', 'expiresAt' => '2026-10-28T12:00:00Z', 'extra' => 'x']])]);

        $res = $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => 2])->assertStatus(201);

        $this->assertSame('http://localhost/live/' . self::TOKEN, $res->json('link.url'));
        $this->assertSame(['linkId', 'createdAt', 'expiresAt', 'url'], array_keys($res->json('link')));   // no token field, no extras
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertSentBody('POST', '/api/LiveShare/vehicle/' . self::UUID, ['durationHours' => 2]);
    }

    public static function badDurations(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'over a day' => [25], 'text' => ['soon'], 'decimal' => [1.5], 'missing' => [null], 'array' => [[2]]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badDurations')]
    public function test_a_bad_duration_never_reaches_the_api(mixed $hours): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => $hours])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_limits_one_and_twenty_four_hours_are_allowed(): void
    {
        $this->fakeApi(['LiveShare/vehicle/*' => $this->ok(['data' => ['linkId' => self::UUID2, 'token' => self::TOKEN]])]);

        foreach ([1, 24] as $h) {
            $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => $h])->assertStatus(201);
        }
    }

    public static function malformedTokens(): array
    {
        return ['missing' => [null], 'short' => ['abc'], 'too long' => [str_repeat('a', 44)], 'bad characters' => [str_repeat('a', 42) . '/'], 'spaces' => [str_repeat('a', 42) . ' '], 'not text' => [[1]]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedTokens')]
    public function test_a_token_that_is_not_the_agreed_shape_is_never_turned_into_a_link(mixed $token): void
    {
        $this->fakeApi(['LiveShare/vehicle/*' => $this->ok(['data' => ['linkId' => self::UUID2, 'token' => $token]])]);

        $res = $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => 2])->assertStatus(502);

        $this->assertSame('Could not create the link.', $res->json('message'));
        $this->assertStringNotContainsString('/live/', $res->getContent());
    }

    public function test_listing_links_never_shows_a_token_even_if_the_api_sent_one(): void
    {
        $this->fakeApi(['LiveShare/vehicle/*' => $this->ok(['data' => [['linkId' => self::UUID2, 'token' => self::TOKEN, 'tokenHash' => 'abc', 'createdAt' => '2026-10-28T10:00:00Z', 'expiresAt' => '2026-10-28T12:00:00Z']]])]);

        $res = $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/live-links')->assertOk();

        $this->assertSame([['linkId' => self::UUID2, 'createdAt' => '2026-10-28T10:00:00Z', 'expiresAt' => '2026-10-28T12:00:00Z']], $res->json('links'));
        $this->assertStringNotContainsString(self::TOKEN, $res->getContent());
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
    }

    public function test_stopping_a_link_calls_the_api_for_that_link(): void
    {
        $this->fakeApi(['LiveShare/*' => $this->ok()]);

        $this->asCustomer()->deleteJson('/api/live-links/' . self::UUID2)->assertOk();

        $this->assertNotNull($this->sentTo('DELETE', '/api/LiveShare/' . self::UUID2));
    }

    public static function liveLinkFailures(): array
    {
        return [
            'subscription' => [402, 402, 'Renew your subscription to share this vehicle.'],
            'not found'    => [404, 404, 'Vehicle or link not found.'],
            'cap reached'  => [409, 409, 'You already have the maximum number of active links. Stop one first.'],
            'rate limited' => [429, 429, 'Too many requests. Please wait a moment.'],
            'server error' => [500, 502, 'Could not create the link.'],
            'forbidden'    => [403, 502, 'Could not create the link.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('liveLinkFailures')]
    public function test_live_link_failures_are_plain(int $apiStatus, int $expected, string $message): void
    {
        $this->fakeApi(['LiveShare/vehicle/*' => Http::response([], $apiStatus)]);

        $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => 2])
            ->assertStatus($expected)->assertJsonPath('message', $message);
    }

    public function test_a_401_while_sharing_signs_the_customer_out(): void
    {
        $this->fakeApi(['LiveShare/*' => Http::response([], 401)]);

        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/live-links')->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');

        $this->assertNull(session('firebase_token'));
    }

    // ---- reminders ----------------------------------------------------------------------------------

    public function test_reminders_are_listed_with_only_the_fields_the_card_needs(): void
    {
        $this->fakeApi(['VehicleReminders/vehicle/*' => $this->ok(['data' => [['reminderId' => self::UUID2, 'vehicleId' => self::UUID, 'type' => 1, 'dueDate' => '2026-12-01', 'daysLeft' => 34, 'notes' => 'Renew', 'secret' => 'x']]])]);

        $res = $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/reminders')->assertOk();

        $this->assertSame([['reminderId' => self::UUID2, 'type' => 1, 'dueDate' => '2026-12-01', 'daysLeft' => 34, 'notes' => 'Renew']], $res->json('reminders'));
    }

    public function test_a_reminder_is_trimmed_and_a_blank_note_is_sent_as_nothing(): void
    {
        $this->fakeApi(['VehicleReminders/vehicle/*' => $this->ok(['data' => ['reminderId' => self::UUID2, 'type' => 0, 'dueDate' => '2026-12-01']])]);

        $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/reminders', ['type' => 0, 'dueDate' => '2026-12-01', 'notes' => '   '])->assertOk();
        $this->assertSentBody('PUT', '/api/VehicleReminders/vehicle/' . self::UUID, ['type' => 0, 'dueDate' => '2026-12-01', 'notes' => null]);

        $this->fakeApi(['VehicleReminders/vehicle/*' => $this->ok(['data' => []])]);
        $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/reminders', ['type' => 2, 'dueDate' => '2026-12-01', 'notes' => '  Oil change  '])->assertOk();
        $this->assertSame('Oil change', $this->sentTo('PUT', '/api/VehicleReminders/vehicle/' . self::UUID)->data()['notes']);
    }

    public static function badReminders(): array
    {
        return [
            'type 3'           => [['type' => 3]],
            'type negative'    => [['type' => -1]],
            'type text'        => [['type' => 'licence']],
            'no type'          => [['type' => '']],
            'date dd/mm/yyyy'  => [['dueDate' => '01/12/2026']],
            'date with time'   => [['dueDate' => '2026-12-01 10:00']],
            'impossible date'  => [['dueDate' => '2026-02-30']],
            'no date'          => [['dueDate' => '']],
            'note too long'    => [['notes' => str_repeat('a', 201)]],
            'note is an array' => [['notes' => ['x']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badReminders')]
    public function test_a_bad_reminder_never_reaches_the_api(array $over): void
    {
        Http::fake();

        $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/reminders', $over + ['type' => 1, 'dueDate' => '2026-12-01', 'notes' => 'ok'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_reminder_failures_are_plain_and_do_not_echo_the_note(): void
    {
        $this->fakeApi(['VehicleReminders/*' => Http::response(['message' => 'bad note: <script>'], 400)]);
        $res = $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/reminders', ['type' => 1, 'dueDate' => '2026-12-01'])
            ->assertStatus(400)->assertJsonPath('message', 'That reminder is not valid. Check the date and note.');
        $this->assertStringNotContainsString('<script>', $res->getContent());

        foreach ([403, 404] as $s) {
            $this->fakeApi(['VehicleReminders/*' => Http::response([], $s)]);
            $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/reminders')->assertStatus($s)->assertJsonPath('message', 'Reminders are only available for vehicles you own.');
        }

        $this->fakeApi(['VehicleReminders/*' => Http::response([], 500)]);
        $this->asCustomer()->deleteJson('/api/reminders/' . self::UUID2)->assertStatus(502)->assertJsonPath('message', 'Could not remove the reminder.');
    }

    public function test_a_reminder_is_deleted_by_its_own_id_and_401_signs_out(): void
    {
        $this->fakeApi(['VehicleReminders/*' => $this->ok()]);
        $this->asCustomer()->deleteJson('/api/reminders/' . self::UUID2)->assertOk();
        $this->assertNotNull($this->sentTo('DELETE', '/api/VehicleReminders/' . self::UUID2));

        $this->fakeApi(['VehicleReminders/*' => Http::response([], 401)]);
        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/reminders')->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');
    }

    // ---- alert settings (speed limit, idle alert) ----------------------------------------------------

    public function test_alert_settings_are_shaped_with_safe_defaults(): void
    {
        $this->fakeApi(['VehicleAlertSettings/vehicle/*' => $this->ok(['data' => ['speedLimitKmh' => '90', 'idleAlertEnabled' => 1, 'extra' => 'x']])]);

        $res = $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/alert-settings')->assertOk();

        $this->assertSame(['speedLimitKmh' => 90, 'idleAlertEnabled' => true, 'idleAlertMinutes' => 10, 'defaultSpeedLimitKmh' => 80, 'minSpeedLimitKmh' => 20, 'maxSpeedLimitKmh' => 200, 'minIdleMinutes' => 3, 'maxIdleMinutes' => 120], $res->json('settings'));
    }

    public function test_alert_limits_are_forwarded_exactly(): void
    {
        $this->fakeApi(['VehicleAlertSettings/vehicle/*' => $this->ok(['data' => []])]);

        $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/alert-settings', ['speedLimitKmh' => 100, 'idleAlertEnabled' => true, 'idleAlertMinutes' => 15])->assertOk();

        $req = $this->sentTo('PUT', '/api/VehicleAlertSettings/vehicle/' . self::UUID);
        $this->assertNotNull($req);
        $this->assertSame([100, true, 15], [$req->data()['speedLimitKmh'], $req->data()['idleAlertEnabled'], $req->data()['idleAlertMinutes']]);
    }

    public function test_idle_minutes_are_not_needed_when_the_idle_alert_is_off(): void
    {
        $this->fakeApi(['VehicleAlertSettings/vehicle/*' => $this->ok(['data' => []])]);

        $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/alert-settings', ['speedLimitKmh' => 80, 'idleAlertEnabled' => false])->assertOk();
    }

    public static function badAlertSettings(): array
    {
        return [
            'speed 19'            => [['speedLimitKmh' => 19]],
            'speed 201'           => [['speedLimitKmh' => 201]],
            'speed text'          => [['speedLimitKmh' => 'fast']],
            'speed decimal'       => [['speedLimitKmh' => 80.5]],
            'no speed'            => [['speedLimitKmh' => '']],
            'idle flag is text'   => [['idleAlertEnabled' => 'maybe']],
            'no idle flag'        => [['idleAlertEnabled' => null]],
            'idle 2 min'          => [['idleAlertMinutes' => 2]],
            'idle 121 min'        => [['idleAlertMinutes' => 121]],
            'idle on, no minutes' => [['idleAlertEnabled' => true, 'idleAlertMinutes' => null]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badAlertSettings')]
    public function test_bad_alert_settings_never_reach_the_api(array $over): void
    {
        Http::fake();

        $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/alert-settings', $over + ['speedLimitKmh' => 80, 'idleAlertEnabled' => true, 'idleAlertMinutes' => 10])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_alert_setting_limits_themselves_are_allowed(): void
    {
        $this->fakeApi(['VehicleAlertSettings/vehicle/*' => $this->ok(['data' => []])]);

        foreach ([[20, 3], [200, 120]] as [$speed, $idle]) {
            $this->asCustomer()->putJson('/api/vehicles/' . self::UUID . '/alert-settings', ['speedLimitKmh' => $speed, 'idleAlertEnabled' => true, 'idleAlertMinutes' => $idle])->assertOk();
        }
    }

    public function test_alert_setting_failures_are_plain(): void
    {
        foreach ([[400, 'Those values are not valid. Check the speed limit and idle time.'], [403, 'Alert settings are only available for vehicles you own.'], [404, 'Alert settings are only available for vehicles you own.'], [429, 'Too many requests. Please wait a moment.']] as [$s, $msg]) {
            $this->fakeApi(['VehicleAlertSettings/*' => Http::response(['message' => 'INTERNAL_CODE'], $s)]);
            $res = $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/alert-settings')->assertStatus($s)->assertJsonPath('message', $msg);
            $this->assertStringNotContainsString('INTERNAL_CODE', $res->getContent());
        }

        $this->fakeApi(['VehicleAlertSettings/*' => Http::response([], 500)]);
        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/alert-settings')->assertStatus(502)->assertJsonPath('message', 'Alert settings unavailable.');

        $this->fakeApi(['VehicleAlertSettings/*' => Http::response([], 401)]);
        $this->asCustomer()->getJson('/api/vehicles/' . self::UUID . '/alert-settings')->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');
    }

    // ---- weekly summary ------------------------------------------------------------------------------

    public function test_the_weekly_summary_defaults_to_on_when_the_api_says_nothing(): void
    {
        $this->fakeApi(['WeeklySummary/settings' => $this->ok(['data' => []])]);
        $this->asCustomer()->getJson('/api/weekly-summary')->assertOk()->assertJson(['success' => true, 'enabled' => true]);

        $this->fakeApi(['WeeklySummary/settings' => $this->ok(['data' => ['enabled' => false]])]);
        $this->asCustomer()->getJson('/api/weekly-summary')->assertOk()->assertJson(['enabled' => false]);
    }

    public function test_the_weekly_summary_switch_is_a_boolean_and_is_forwarded_as_one(): void
    {
        $this->fakeApi(['WeeklySummary/settings' => $this->ok(['data' => ['enabled' => false]])]);

        $this->asCustomer()->putJson('/api/weekly-summary', ['enabled' => false, 'customerId' => self::UUID2])->assertOk()->assertJson(['enabled' => false]);

        $this->assertSentBody('PUT', '/api/WeeklySummary/settings', ['enabled' => false]);
    }

    public function test_a_non_boolean_weekly_summary_switch_is_refused(): void
    {
        Http::fake();

        foreach ([null, 'maybe', ['x'], 5] as $bad) {
            $this->asCustomer()->putJson('/api/weekly-summary', ['enabled' => $bad])->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    public function test_weekly_summary_failures_are_plain(): void
    {
        $this->fakeApi(['WeeklySummary/settings' => Http::response([], 429)]);
        $this->asCustomer()->putJson('/api/weekly-summary', ['enabled' => true])->assertStatus(429)->assertJsonPath('message', 'Too many requests. Please wait a moment.');

        $this->fakeApi(['WeeklySummary/settings' => Http::response([], 500)]);
        $this->asCustomer()->getJson('/api/weekly-summary')->assertStatus(502)->assertJsonPath('message', 'Setting unavailable.');

        $this->fakeApi(['WeeklySummary/settings' => Http::response([], 401)]);
        $this->asCustomer()->getJson('/api/weekly-summary')->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');
    }
}
