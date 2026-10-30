<?php

namespace Tests\Feature;

use App\Services\ReverseGeocoder;
use App\Support\LocalTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F7: the last untested branches: address lookup edge cases, the Reports page, e-mail check refusals, register page, time helpers. */
class GapFillTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        Cache::flush();
        config(['services.geocoder.url' => 'https://geo.test']);
    }

    // ---- ReverseGeocoder::format ---------------------------------------------------------------------------

    public static function nominatim(): array
    {
        return [
            'road, area, town'        => [['address' => ['road' => 'Galle Rd', 'suburb' => 'Bambalapitiya', 'city' => 'Colombo']], 'Galle Rd, Bambalapitiya, Colombo'],
            'duplicates removed'      => [['address' => ['road' => 'Colombo', 'city' => 'Colombo']], 'Colombo'],
            'footway used as road'    => [['address' => ['footway' => 'Park Path', 'village' => 'Ella']], 'Park Path, Ella'],
            'blank parts skipped'     => [['address' => ['road' => '  ', 'town' => 'Kandy']], 'Kandy'],
            'no parts, uses name'     => [['address' => [], 'name' => ' Temple of the Tooth '], 'Temple of the Tooth'],
            'no name, uses display'   => [['address' => [], 'display_name' => 'Fort, Colombo, Western Province, Sri Lanka'], 'Fort, Colombo'],
            'display_name with a gap' => [['display_name' => ' ,Fort, Colombo'], 'Fort'],
            'nothing usable'          => [['address' => [], 'name' => '', 'display_name' => ''], null],
            'provider error'          => [['error' => 'Unable to geocode'], null],
            'not an array'            => ['oops', null],
            'address not an array'    => [['address' => 'x', 'name' => 'Pettah'], 'Pettah'],
        ];
    }

    #[DataProvider('nominatim')]
    public function test_the_provider_answer_becomes_a_short_readable_address(mixed $json, ?string $expected): void
    {
        $this->assertSame($expected, ReverseGeocoder::format($json));
    }

    // ---- ReverseGeocoder::resolve --------------------------------------------------------------------------

    public static function badCoords(): array
    {
        return [[91, 0], [-91, 0], [0, 181], [0, -181], [NAN, 0], [0, NAN]];
    }

    #[DataProvider('badCoords')]
    public function test_impossible_coordinates_never_reach_the_provider(float $lat, float $lng): void
    {
        Http::fake();
        $fromCache = null;

        $this->assertNull(app(ReverseGeocoder::class)->resolve($lat, $lng, $fromCache));

        Http::assertNothingSent();
        $this->assertTrue($fromCache);
    }

    public function test_a_found_address_is_remembered_and_the_second_call_skips_the_provider(): void
    {
        Http::fake(['geo.test/*' => Http::response(['address' => ['road' => 'Main St', 'city' => 'Galle']], 200)]);
        $geo = app(ReverseGeocoder::class);

        $first = $geo->resolve(6.0329, 80.2168, $a);
        $second = $geo->resolve(6.03291, 80.21681, $b);   // same 4-decimal cell

        $this->assertSame('Main St, Galle', $first);
        $this->assertSame($first, $second);
        $this->assertSame([false, true], [$a, $b]);
        Http::assertSentCount(1);
        $this->assertSame('Main St, Galle', $geo->peek(6.0329, 80.2168));
    }

    public function test_the_request_follows_the_providers_usage_rules(): void
    {
        Http::fake(['geo.test/*' => Http::response(['name' => 'X'], 200)]);

        app(ReverseGeocoder::class)->resolve(6.9, 79.8);

        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://geo.test/reverse?')
            && $r->hasHeader('User-Agent') && $r->header('User-Agent')[0] !== ''
            && ($r->data()['lat'] ?? null) === '6.900000' && ($r->data()['lon'] ?? null) === '79.800000');
    }

    public static function providerFailures(): array
    {
        return [['status 500'], ['status 429'], ['status 404'], ['network down']];
    }

    #[DataProvider('providerFailures')]
    public function test_a_provider_failure_gives_no_address_and_a_short_memory_of_the_miss(string $kind): void
    {
        $calls = 0;
        Http::fake(['geo.test/*' => function () use ($kind, &$calls) {
            $calls++;
            return $kind === 'network down'
                ? throw new \Illuminate\Http\Client\ConnectionException('timeout')
                : Http::response([], (int) substr($kind, -3));
        }]);
        $geo = app(ReverseGeocoder::class);

        $this->assertNull($geo->resolve(6.5, 80.5, $a));
        $this->assertNull($geo->resolve(6.5, 80.5, $b));   // remembered miss: no second call

        $this->assertSame([false, true], [$a, $b]);
        $this->assertSame(1, $calls);
        $this->assertNull($geo->peek(6.5, 80.5));
    }

    public function test_a_broken_cache_never_breaks_the_lookup(): void
    {
        Http::fake(['geo.test/*' => Http::response(['name' => 'Pettah'], 200)]);
        Cache::shouldReceive('get')->andThrow(new \RuntimeException('redis down'));
        Cache::shouldReceive('put')->andThrow(new \RuntimeException('redis down'));

        $this->assertSame('Pettah', app(ReverseGeocoder::class)->resolve(6.9, 79.8));
        $this->assertNull(app(ReverseGeocoder::class)->peek(6.9, 79.8));
    }

    // ---- Reports page --------------------------------------------------------------------------------------

    public function test_the_reports_page_lists_the_customers_trackable_vehicles(): void
    {
        $this->fakeApi([
            'Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]]),
            'Vehicles/customer/*' => $this->ok(['data' => [['vehicleId' => self::UUID2, 'vehicleNumber' => 'CAB-1234', 'hasGpsDevice' => true]]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => []]),
        ]);

        $this->asCustomer()->get('/reports')->assertOk()->assertSee('CAB-1234');
    }

    public function test_the_reports_page_survives_a_missing_profile_id(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok(['data' => []])]);

        $this->asCustomer()->get('/reports')->assertOk();
    }

    public function test_the_reports_page_shows_a_retry_message_on_api_failure_and_signs_out_on_401(): void
    {
        $this->fakeApi(['Customers/me' => Http::response(['message' => 'Npgsql 10.0.0.5'], 500)]);
        $this->asCustomer()->get('/reports')->assertOk()->assertSee('Could not load vehicles. Please refresh.')->assertDontSee('10.0.0.5');

        $this->fakeApi(['Customers/me' => Http::response([], 401)]);
        $this->asCustomer()->get('/reports')->assertRedirect('/login?expired=1');
        $this->assertNull(session('firebase_token'));
    }

    // ---- e-mail check refusals + register page -------------------------------------------------------------

    public function test_the_email_check_needs_a_signed_in_session(): void
    {
        Http::fake();

        $this->postJson('/email/mark-verified', ['idToken' => 'x'])->assertStatus(401)->assertJson(['success' => false, 'expired' => true]);

        Http::assertNothingSent();
    }

    public static function badIdTokens(): array
    {
        return ['missing' => [null], 'empty' => [''], 'too long' => [str_repeat('a', 4097)]];
    }

    #[DataProvider('badIdTokens')]
    public function test_a_missing_or_oversized_id_token_is_refused_before_anything_is_checked(?string $token): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/email/mark-verified', $token === null ? [] : ['idToken' => $token])
            ->assertStatus(422)->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_the_register_page_needs_a_login_and_goes_to_the_dashboard_when_a_profile_exists(): void
    {
        Http::fake();
        $this->get('/register')->assertRedirect('/login');

        $this->fakeApi(['Customers/me' => $this->ok(['data' => ['customerId' => self::UUID]])]);
        $this->asCustomer()->get('/register')->assertRedirect('/dashboard');
    }

    public function test_the_register_page_opens_only_when_the_profile_is_really_missing(): void
    {
        $this->fakeApi(['Customers/me' => Http::response([], 404)]);
        $this->asCustomer('uid-new', ['firebase_phone' => '+94771234567', 'pending_profile' => ['fullName' => 'Nimal']])->get('/register')
            ->assertOk()->assertSee('Nimal')->assertSee('+94771234567');

        $this->fakeApi(['Customers/me' => Http::response([], 500)]);
        $this->asCustomer('uid-other')->get('/register')->assertRedirect('/dashboard');   // an API fault must not show the sign-up form
    }

    // ---- time helpers --------------------------------------------------------------------------------------

    public function test_time_helpers_accept_carbon_and_php_dates_and_show_colombo_time(): void
    {
        $this->assertSame('28 Oct 2026, 08:00', LocalTime::format(Carbon::parse('2026-10-28 02:30:00', 'UTC')));
        $this->assertSame('28 Oct 2026, 08:00', LocalTime::format(new \DateTimeImmutable('2026-10-28 02:30:00', new \DateTimeZone('UTC'))));
        $this->assertSame('28 Oct 2026, 08:00', LocalTime::format('2026-10-28T08:00:00+05:30'));
    }

    public function test_ago_reads_naturally_and_falls_back_for_bad_input(): void
    {
        Carbon::setTestNow('2026-10-28 12:00:00');
        $this->assertSame('5 minutes ago', LocalTime::ago(now()->subMinutes(5)->toIso8601ZuluString()));
        $this->assertSame('—', LocalTime::ago(null));
        $this->assertSame('n/a', LocalTime::ago('', 'n/a'));
        Carbon::setTestNow();
    }
}
