<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * Changing the sign-in phone number: code to the NEW number, then verify.
 * The C# API does the real enforcement (fresh sign-in, one-time code, number not taken); these tests pin what the
 * Fleet portal must do on its side so a mistake here cannot weaken that: what it forwards, what it refuses before
 * calling the API, what it tells the customer, and how hard it is throttled.
 */
class PhoneChangeTest extends TestCase
{
    use SignedInFleetUser;

    private const REQ = '/api/Customers/me/phone-change/request';
    private const VER = '/api/Customers/me/phone-change/verify';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function me(string $phone = '0771234567'): array
    {
        return ['data' => ['customerId' => self::UUID, 'fullName' => 'Ridmi P', 'phoneNumber' => $phone]];
    }

    private function fakeOk(): void
    {
        $this->fakeApi([
            'Customers/me/phone-change/request' => $this->ok(['success' => true, 'data' => ['resendAfterSeconds' => 45]]),
            'Customers/me/phone-change/verify'  => $this->ok(['success' => true, 'data' => ['phoneNumber' => '+94719876543']]),
            'Customers/me'                      => $this->ok($this->me()),
        ]);
    }

    // ---- who may call it ------------------------------------------------------------------------------

    public function test_a_stranger_cannot_start_or_finish_a_phone_change(): void
    {
        Http::fake();

        $this->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertStatus(401);
        $this->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '123456'])->assertStatus(401);

        Http::assertNothingSent();
    }

    // ---- step 1: request ------------------------------------------------------------------------------

    public function test_the_code_is_requested_for_the_normalised_new_number(): void
    {
        $this->fakeOk();

        $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => '071 987 6543'])
            ->assertOk()
            ->assertJson(['success' => true, 'phoneNumber' => '+94719876543', 'resendAfterSeconds' => 45])
            ->assertJsonPath('message', 'We sent a 6-digit code to +94 71 987 6543.');

        $this->assertSentBody('POST', self::REQ, ['PhoneNumber' => '+94719876543']);
    }

    public static function equivalentSpellings(): array
    {
        return [['0719876543'], ['+94719876543'], ['0094 71 987 6543'], ['94719876543'], ['(0)71-987-6543']];
    }

    #[DataProvider('equivalentSpellings')]
    public function test_every_spelling_of_the_number_is_sent_in_one_canonical_form(string $typed): void
    {
        $this->fakeOk();

        $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => $typed])->assertOk();

        $this->assertSentBody('POST', self::REQ, ['PhoneNumber' => '+94719876543']);
    }

    public static function notMobiles(): array
    {
        return [['abc'], ['0112345678'], ['12345'], ['<b>0719876543</b>'], ['+919876543210'], ['0551234567'], [str_repeat('7', 24)]];
    }

    #[DataProvider('notMobiles')]
    public function test_a_number_that_is_not_a_sri_lankan_mobile_never_reaches_the_api(string $typed): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => $typed])
            ->assertStatus(422)->assertJson(['success' => false, 'code' => 'INVALID_PHONE']);

        Http::assertNothingSent();
    }

    public function test_missing_or_non_text_input_is_refused(): void
    {
        Http::fake();

        // a different customer each time: the limiter is per customer and these are three separate cases
        $this->asCustomer('u-a')->postJson('/profile/phone/request', [])->assertStatus(422)->assertJsonValidationErrors('phoneNumber');
        $this->asCustomer('u-b')->postJson('/profile/phone/request', ['phoneNumber' => ['x']])->assertStatus(422)->assertJsonValidationErrors('phoneNumber');
        $this->asCustomer('u-c')->postJson('/profile/phone/request', ['phoneNumber' => str_repeat('1', 26)])->assertStatus(422)->assertJsonValidationErrors('phoneNumber');

        Http::assertNothingSent();
    }

    public function test_asking_for_the_number_you_already_have_costs_no_sms(): void
    {
        $this->fakeOk();

        $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => '+94 77 123 4567'])
            ->assertStatus(422)->assertJson(['code' => 'SAME_NUMBER']);

        $this->assertNull($this->sentTo('POST', self::REQ));
    }

    public function test_the_resend_wait_is_kept_within_sane_limits(): void
    {
        $this->fakeApi([
            'Customers/me/phone-change/request' => $this->ok(['success' => true, 'data' => ['resendAfterSeconds' => 1]]),
            'Customers/me'                      => $this->ok($this->me()),
        ]);
        $this->asCustomer('r-1')->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertJson(['resendAfterSeconds' => 30]);

        $this->fakeApi([
            'Customers/me/phone-change/request' => $this->ok(['success' => true, 'data' => ['resendAfterSeconds' => 99999]]),
            'Customers/me'                      => $this->ok($this->me()),
        ]);
        $this->asCustomer('r-2')->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertJson(['resendAfterSeconds' => 300]);

        $this->fakeApi(['Customers/me/phone-change/request' => $this->ok(['success' => true]), 'Customers/me' => $this->ok($this->me())]);
        $this->asCustomer('r-3')->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertJson(['resendAfterSeconds' => 60]);
    }

    public function test_a_stale_sign_in_tells_the_customer_to_sign_in_again(): void
    {
        $this->fakeApi([
            'Customers/me/phone-change/request' => Http::response(['code' => 'REAUTH_REQUIRED'], 403),
            'Customers/me'                      => $this->ok($this->me()),
        ]);

        $res = $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertStatus(403);

        $this->assertSame('REAUTH_REQUIRED', $res->json('code'));
        $this->assertStringContainsString('sign in again', $res->json('message'));
    }

    public function test_a_number_in_use_is_a_calm_generic_message_and_leaks_nothing(): void
    {
        $this->fakeApi([
            'Customers/me/phone-change/request' => Http::response(['message' => 'PHONE_TAKEN by customer 22222222'], 409),
            'Customers/me'                      => $this->ok($this->me()),
        ]);

        $res = $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertStatus(422);

        $this->assertSame('That number cannot be used. Try a different number.', $res->json('message'));
        $this->assertStringNotContainsString('PHONE_TAKEN', $res->getContent());
        $this->assertStringNotContainsString('22222222', $res->getContent());
    }

    public function test_the_sms_provider_being_down_is_a_friendly_failure_not_a_crash(): void
    {
        $this->fakeApi([
            'Customers/me/phone-change/request' => Http::response(['message' => 'Text.lk gateway exploded: key=abc'], 500),
            'Customers/me'                      => $this->ok($this->me()),
        ]);

        $res = $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertStatus(422);

        $this->assertSame('Could not send the code. Please try again.', $res->json('message'));
        $this->assertStringNotContainsString('exploded', $res->getContent());
    }

    public function test_an_expired_session_during_the_request_signs_the_customer_out(): void
    {
        $this->fakeApi(['Customers/me' => Http::response([], 401)]);

        $this->asCustomer()->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])
            ->assertStatus(401)->assertJson(['expired' => true]);

        $this->assertNull(session('firebase_token'));
    }

    // ---- step 2: verify -------------------------------------------------------------------------------

    public function test_a_right_code_changes_the_number_and_the_page_is_told_the_new_one(): void
    {
        $this->fakeOk();

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '123456'])
            ->assertOk()->assertJson(['success' => true, 'phoneNumber' => '+94719876543', 'message' => 'Your phone number was changed.']);

        $this->assertSentBody('POST', self::VER, ['PhoneNumber' => '+94719876543', 'Code' => '123456']);
    }

    public function test_the_page_shows_what_the_api_saved_not_what_the_browser_claimed(): void
    {
        $this->fakeApi(['Customers/me/phone-change/verify' => $this->ok(['success' => true, 'data' => ['phoneNumber' => '+94711112222']])]);

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '123456'])
            ->assertJson(['phoneNumber' => '+94711112222']);
    }

    public static function badCodes(): array
    {
        return [
            'too short' => ['12345'], 'too long' => ['1234567'], 'letters' => ['12a456'], 'spaces' => ['123 45'],
            'html' => ['<b>1</b>'], 'empty' => [''], 'unicode digits' => ['١٢٣٤٥٦'],
        ];
    }

    #[DataProvider('badCodes')]
    public function test_a_code_that_is_not_exactly_six_digits_never_reaches_the_api(string $code): void
    {
        Http::fake();

        $res = $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => $code]);

        $this->assertContains($res->status(), [422]);
        Http::assertNothingSent();
    }

    public function test_surrounding_whitespace_on_the_code_is_trimmed_not_a_failure(): void
    {
        $this->fakeOk();

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => " 123456\n"])->assertOk();

        $this->assertSentBody('POST', self::VER, ['PhoneNumber' => '+94719876543', 'Code' => '123456']);
    }

    public function test_a_code_sent_as_an_array_or_number_is_refused(): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => ['123456']])->assertStatus(422);
        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => ['x'], 'code' => '123456'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_verifying_for_a_number_that_is_not_a_mobile_never_reaches_the_api(): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0112345678', 'code' => '123456'])
            ->assertStatus(422)->assertJson(['code' => 'INVALID_PHONE']);

        Http::assertNothingSent();
    }

    public function test_a_wrong_or_expired_code_is_one_clear_message(): void
    {
        $this->fakeApi(['Customers/me/phone-change/verify' => Http::response(['message' => 'OTP_MISMATCH attempts=4'], 400)]);

        $res = $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '000000'])->assertStatus(422);

        $this->assertSame('That code is not right or has expired. Request a new one.', $res->json('message'));
        $this->assertStringNotContainsString('attempts', $res->getContent());
    }

    public function test_a_locked_out_code_is_reported_as_too_many_requests(): void
    {
        $this->fakeApi(['Customers/me/phone-change/verify' => Http::response([], 429)]);

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '000000'])
            ->assertStatus(429)->assertJson(['code' => 'RATE_LIMITED']);
    }

    public function test_a_stale_sign_in_is_reported_on_verify_too(): void
    {
        $this->fakeApi(['Customers/me/phone-change/verify' => Http::response(['code' => 'REAUTH_REQUIRED'], 403)]);

        $this->asCustomer()->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '123456'])
            ->assertStatus(403)->assertJson(['code' => 'REAUTH_REQUIRED']);
    }

    public function test_after_a_change_the_cached_profile_is_dropped_so_the_old_number_is_not_shown(): void
    {
        $this->fakeOk();
        $key = 'api:' . hash('sha256', 'uid-1|/api/Customers/me|');
        cache()->put($key, ['data' => ['phoneNumber' => 'OLD']], 600);

        $this->asCustomer('uid-1')->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => '123456'])->assertOk();

        $this->assertNull(cache()->get($key));
    }

    // ---- throttling -----------------------------------------------------------------------------------

    public function test_code_requests_are_limited_per_customer(): void
    {
        RateLimiter::clear('phone-change-request-m:u:uid-throttle');
        $this->fakeOk();

        $codes = [];
        for ($i = 0; $i < 4; $i++) {
            $codes[] = $this->asCustomer('uid-throttle')->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->status();
        }

        $this->assertSame([200, 200, 429, 429], $codes, 'two per minute');
    }

    public function test_the_limit_is_per_customer_so_one_cannot_block_another(): void
    {
        $this->fakeOk();
        for ($i = 0; $i < 3; $i++) {
            $this->asCustomer('uid-spammer')->postJson('/profile/phone/request', ['phoneNumber' => '0719876543']);
        }

        $this->asCustomer('uid-other')->postJson('/profile/phone/request', ['phoneNumber' => '0719876543'])->assertOk();
    }

    public function test_guessing_codes_is_limited(): void
    {
        $this->fakeApi(['Customers/me/phone-change/verify' => Http::response(['message' => 'bad'], 400)]);

        $statuses = [];
        for ($i = 0; $i < 7; $i++) {
            $statuses[] = $this->asCustomer('uid-guesser')->postJson('/profile/phone/verify', ['phoneNumber' => '0719876543', 'code' => sprintf('%06d', $i)])->status();
        }

        $this->assertSame([422, 422, 422, 422, 422, 429, 429], $statuses, 'five a minute');
    }

    // ---- the page -------------------------------------------------------------------------------------

    public function test_the_page_offers_a_verified_change_and_no_longer_an_inline_phone_editor(): void
    {
        $this->fakeApi(['Customers/me' => $this->ok($this->me())]);

        $html = $this->asCustomer()->get('/profile')->assertOk()->getContent();

        $this->assertStringContainsString('id="phone-change-open"', $html);
        $this->assertStringContainsString('autocomplete="one-time-code"', $html);
        $this->assertStringNotContainsString("startEdit('phoneNumber')", $html);
        $this->assertStringNotContainsString('id="input-phoneNumber"', $html);
        $this->assertStringContainsString('/profile/phone/request', $html);
        $this->assertStringContainsString('/profile/phone/verify', $html);
    }
}
