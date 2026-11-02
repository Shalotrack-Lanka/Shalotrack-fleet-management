<?php

namespace Tests\Feature;

use App\Support\ApiErrors;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\FirebaseTestTokens;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F8: two leaks closed.
 *  1. LiveShare and Renewal used to show the C# API's raw error text to the customer on 400/409/422.
 *     Now only plain customer-safe sentences pass; machine codes, paths, URLs, SQL and markup are replaced by a safe default.
 *  2. The login logged the full phone number and Firebase uid at info level. Now they are masked.
 */
class ApiMessageLeakTest extends TestCase
{
    use FirebaseTestTokens;
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    /** API texts that must never reach a customer. */
    public static function unsafe(): array
    {
        return [
            'machine code'  => ['SQL_CONSTRAINT_VIOLATION'],
            'sql words'     => ['insert into vehicle_shares failed: sql error'],
            'exception'     => ['System.Data.SqlClient.SqlException thrown'],
            'path'          => ['Error in /app/Repo/ShareRepo.cs line 42'],
            'url'           => ['see https://internal.example/stack'],
            'markup'        => ['<script>alert(1)</script>'],
            'angle bracket' => ['value < 5 > 2'],
            'too long'      => [str_repeat('a ', 150)],
            'empty'         => [''],
        ];
    }

    /** Plain sentences the API is allowed to show. */
    public static function safe(): array
    {
        return [
            ['You already have an open renewal for this vehicle.'],
            ['Only pending requests accept a slip.'],
            ['That vehicle is not on your account.'],
        ];
    }

    // ---- the shared rule -----------------------------------------------------------------------------------

    #[DataProvider('unsafe')]
    public function test_unsafe_texts_are_not_plain_messages(string $m): void
    {
        $this->assertFalse(ApiErrors::isPlainMessage($m));
    }

    #[DataProvider('safe')]
    public function test_plain_sentences_are_allowed(string $m): void
    {
        $this->assertTrue(ApiErrors::isPlainMessage($m));
    }

    // ---- live links ----------------------------------------------------------------------------------------

    public static function liveStatuses(): array
    {
        return [
            '400' => [400, 'That request is not valid.'],
            '409' => [409, 'You already have the maximum number of active links. Stop one first.'],
        ];
    }

    #[DataProvider('liveStatuses')]
    public function test_live_link_errors_never_show_unsafe_api_text(int $status, string $default): void
    {
        foreach (self::unsafe() as $label => [$text]) {
            $this->fakeApi(['LiveShare/vehicle/*' => Http::response(['message' => $text], $status)]);

            $res = $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => 2])->assertStatus($status);

            $this->assertSame($default, $res->json('message'), "leaked for: $label");
        }
    }

    #[DataProvider('liveStatuses')]
    public function test_live_link_errors_still_show_a_plain_api_sentence(int $status, string $default): void
    {
        $this->fakeApi(['LiveShare/vehicle/*' => Http::response(['message' => 'This vehicle already has two links.'], $status)]);

        $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => 2])
            ->assertStatus($status)->assertJsonPath('message', 'This vehicle already has two links.');
    }

    // ---- renewals ------------------------------------------------------------------------------------------

    private function renewalApi(\GuzzleHttp\Promise\PromiseInterface $createAnswer): void
    {
        $this->fakeApi([
            'Customers/me'        => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*' => $this->ok(['data' => [['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1234']]]),
            'Renewals/packages'   => $this->ok(['data' => [['duration' => 'OneYear', 'priceLkr' => 12000, 'name' => '1 year']]]),
            'Renewals'            => $createAnswer,
        ]);
    }

    public static function renewalStatuses(): array
    {
        return [[400], [409], [413], [415], [422]];
    }

    #[DataProvider('renewalStatuses')]
    public function test_renewal_errors_never_show_unsafe_api_text(int $status): void
    {
        foreach (self::unsafe() as $label => [$text]) {
            $this->renewalApi(Http::response(['message' => $text], $status));

            $res = $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear']);

            $this->assertStringNotContainsString($text === '' ? "\0" : $text, $res->getContent(), "leaked for: $label");
            $this->assertSame(422, $res->getStatusCode());
        }
    }

    public function test_a_plain_renewal_sentence_is_still_shown(): void
    {
        $this->renewalApi(Http::response(['message' => 'You already have an open renewal for this vehicle.'], 409));

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear'])
            ->assertStatus(422)->assertJsonPath('message', 'You already have an open renewal for this vehicle.');
    }

    public function test_renewal_failures_are_logged_with_personal_data_masked(): void
    {
        Log::spy();
        $this->renewalApi(Http::response(['message' => 'No renewal for +94 77 123 4567 / nimal@example.com'], 409));

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear']);

        Log::shouldHaveReceived('error')->withArgs(function ($msg, $ctx = []) {
            return str_contains($msg, 'RenewalController')
                && ! str_contains(json_encode($ctx), 'nimal')
                && ! str_contains(json_encode($ctx), '123 4567');
        })->atLeast()->once();
    }

    // ---- login logs ----------------------------------------------------------------------------------------

    public function test_a_successful_login_does_not_write_the_phone_or_uid_to_the_log(): void
    {
        $this->googleKeys();
        Http::fake([
            'api.test/api/Customers/me'     => Http::response(['data' => ['customerId' => self::UUID]], 200),
            'api.test/api/Account/deletion' => Http::response(['data' => ['pending' => false]], 200),
        ]);
        Log::spy();

        $this->postJson('/login', ['token' => $this->token()])->assertOk();

        Log::shouldHaveReceived('info')->withArgs(function ($msg, $ctx = []) {
            return $msg === 'FirebaseAuth: Login successful'
                && ! str_contains(json_encode($ctx), '+94771234567')
                && ! str_contains(json_encode($ctx), self::UID)
                && ($ctx['phone'] ?? '') === '+94******567';
        })->once();
    }
}
