<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F6: subscription renewals - the only place a customer's money is involved.
 * Rules under test: the customer never sends a price; nothing is ever marked paid here; a bank slip is judged by its
 * CONTENT (never its name or browser-declared type), is capped at 2 MB, and is only streamed on to the API.
 */
class RenewalTest extends TestCase
{
    use SignedInFleetUser;

    private const XSS = '<script>window.__pwn=1</script>';

    // Smallest real files of each allowed type.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function renewalsApi(array $over = []): void
    {
        $this->fakeApi($over + [
            'Customers/me'        => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*' => $this->ok(['data' => [
                ['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio'],
                ['vehicleId' => self::UUID2, 'vehicleNumber' => 'DEMO-1', 'isDemoVehicle' => true],
            ]]),
            'Renewals/packages'   => $this->ok(['data' => [
                ['duration' => 'OneYear', 'priceLkr' => 12000, 'name' => '1 year'],
                ['duration' => 'SixMonths', 'priceLkr' => 0],
                ['duration' => '', 'priceLkr' => 5000],
                ['priceLkr' => 5000],
            ]]),
            'Renewals'            => $this->ok(['data' => [['renewalRequestId' => self::UUID, 'vehicleNumber' => self::XSS, 'status' => 'Pending']]]),
        ]);
    }

    private static function bytes(string $kind): string
    {
        return match ($kind) {
            'jpg' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9",
            'png' => base64_decode(self::PNG),
            'pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF",
        };
    }

    // ---- the page ---------------------------------------------------------------------------------------

    public function test_the_page_hides_demo_vehicles_and_unpriced_packages_and_escapes_text(): void
    {
        $this->renewalsApi();

        $res = $this->asCustomer('uid-1', ['renewal_required' => true])->get('/renewals')->assertOk()->assertSee('CAB-1234')->assertDontSee('DEMO-1');

        $this->assertStringNotContainsString(self::XSS, $res->getContent());
        $this->assertNull(session('renewal_required'), 'visiting the page clears the renew-now nudge');
    }

    public function test_a_price_list_outage_does_not_hide_the_history(): void
    {
        $this->renewalsApi(['Renewals/packages' => Http::response([], 500)]);

        $this->asCustomer()->get('/renewals')->assertOk()->assertDontSee('Could not load renewals');
    }

    public function test_a_401_anywhere_on_the_page_signs_out(): void
    {
        $this->renewalsApi(['Renewals/packages' => Http::response([], 401)]);
        $this->asCustomer('a')->get('/renewals')->assertRedirect('/login?expired=1');

        $this->renewalsApi(['Renewals' => Http::response([], 401)]);
        $this->asCustomer('b')->get('/renewals')->assertRedirect('/login?expired=1');
    }

    public function test_a_server_error_shows_a_plain_message(): void
    {
        $this->renewalsApi(['Renewals' => Http::response([], 500)]);

        $this->asCustomer()->get('/renewals')->assertOk()->assertSee('Could not load renewals');
    }

    // ---- create a request --------------------------------------------------------------------------------

    public function test_a_request_carries_the_vehicle_and_duration_but_never_a_price(): void
    {
        $this->renewalsApi(['Renewals' => $this->ok(['data' => ['renewalRequestId' => self::UUID]])]);

        $this->asCustomer()->postJson('/renewals', [
            'vehicleId' => self::UUID, 'duration' => 'OneYear', 'paymentReference' => '  REF-77  ',
            'amount' => 1, 'priceLkr' => 1, 'status' => 'Paid',
        ])->assertOk()->assertJsonPath('data.renewalRequestId', self::UUID);

        $this->assertSentBody('POST', '/api/Renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear', 'paymentReference' => 'REF-77']);
    }

    public function test_a_blank_reference_is_left_out_entirely(): void
    {
        $this->renewalsApi(['Renewals' => $this->ok(['data' => []])]);

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'SixYears', 'paymentReference' => '   '])->assertOk();

        $this->assertSentBody('POST', '/api/Renewals', ['vehicleId' => self::UUID, 'duration' => 'SixYears']);
    }

    public static function durations(): array
    {
        return array_map(fn ($d) => [$d], ['ThreeMonths', 'SixMonths', 'OneYear', 'TwoYears', 'ThreeYears', 'SixYears']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('durations')]
    public function test_every_documented_duration_is_accepted(string $duration): void
    {
        $this->renewalsApi(['Renewals' => $this->ok(['data' => []])]);

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => $duration])->assertOk();
    }

    public static function badRenewals(): array
    {
        return [
            'no vehicle'        => [['vehicleId' => '']],
            'vehicle not uuid'  => [['vehicleId' => '../1']],
            'no duration'       => [['duration' => '']],
            'unknown duration'  => [['duration' => 'Forever']],
            'duration ordinal'  => [['duration' => '2']],
            'wrong case'        => [['duration' => 'oneyear']],
            'reference long'    => [['paymentReference' => str_repeat('a', 101)]],
            'reference array'   => [['paymentReference' => ['x']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badRenewals')]
    public function test_a_bad_request_never_reaches_the_api(array $over): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/renewals', $over + ['vehicleId' => self::UUID, 'duration' => 'OneYear'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public static function renewalFailures(): array
    {
        return [
            'not your vehicle'  => [403, 403, 'You can only renew vehicles you own.'],
            'not found'         => [404, 404, 'That renewal request was not found.'],
            'server error'      => [500, 502, "Couldn't create the renewal request."],
            'rate limited'      => [429, 502, "Couldn't create the renewal request."],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('renewalFailures')]
    public function test_failures_are_plain_and_never_echo_5xx_text(int $apiStatus, int $expected, string $message): void
    {
        $this->renewalsApi(['Renewals' => Http::response(['message' => 'NullReferenceException at Renewal.cs:44'], $apiStatus)]);

        $res = $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear'])->assertStatus($expected)->assertJsonPath('message', $message);

        $this->assertStringNotContainsString('NullReference', $res->getContent());
    }

    public function test_the_apis_own_customer_message_is_shown_for_a_duplicate_open_request(): void
    {
        $this->renewalsApi(['Renewals' => Http::response(['message' => 'You already have an open renewal for this vehicle.'], 409)]);

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear'])
            ->assertStatus(422)->assertJsonPath('message', 'You already have an open renewal for this vehicle.');
    }

    public function test_a_400_without_words_falls_back_to_the_plain_default(): void
    {
        $this->renewalsApi(['Renewals' => Http::response([], 400)]);

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear'])
            ->assertStatus(422)->assertJsonPath('message', "Couldn't create the renewal request.");
    }

    public function test_a_401_while_renewing_signs_the_customer_out(): void
    {
        $this->renewalsApi(['Renewals' => Http::response([], 401)]);

        $this->asCustomer()->postJson('/renewals', ['vehicleId' => self::UUID, 'duration' => 'OneYear'])->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');

        $this->assertNull(session('firebase_token'));
    }

    // ---- the bank slip ------------------------------------------------------------------------------------

    public static function goodSlips(): array
    {
        return [
            'jpeg' => ['jpg', 'image/jpeg', 'slip.jpg'],
            'png'  => ['png', 'image/png', 'slip.png'],
            'pdf'  => ['pdf', 'application/pdf', 'slip.pdf'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('goodSlips')]
    public function test_a_slip_is_forwarded_with_a_name_and_type_chosen_here_from_its_bytes(string $kind, string $mime, string $name): void
    {
        $this->renewalsApi(['Renewals/*' => $this->ok(['data' => ['status' => 'SlipUploaded']])]);

        // The browser claims a dangerous name and type; neither may be forwarded.
        $file = UploadedFile::fake()->createWithContent('evil.php.' . $kind, self::bytes($kind));
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $file], ['Accept' => 'application/json'])->assertOk();

        $req = $this->sentTo('POST', '/api/Renewals/' . self::UUID . '/slip');
        $this->assertNotNull($req);
        $this->assertTrue($req->isMultipart());
        $part = collect($req->data())->firstWhere('name', 'file');
        $this->assertSame($name, $part['filename']);
        $this->assertSame(self::bytes($kind), $part['contents']);
        $this->assertSame($mime, $part['headers']['Content-Type'] ?? null);
        $this->assertStringNotContainsString('evil', json_encode($req->data(), JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public static function disguisedFiles(): array
    {
        return [
            // not starting with the PHP tag at byte 0: on some Windows machines antivirus removes such a temp file mid-test
            'php code'        => ["  <?php echo 1; ?>"],
            'gif with code'   => ['GIF89a<?php echo 1;'],
            'plain letters'   => [str_repeat('A', 200)],
            'windows program' => ["MZ\x90\x00\x03"],
        ];
    }

    /** One upload per test (not a loop): Windows holds on to a fake upload's temp file until the test ends. */
    #[\PHPUnit\Framework\Attributes\DataProvider('disguisedFiles')]
    public function test_a_file_dressed_up_as_an_image_is_refused_by_what_it_contains(string $payload): void
    {
        Http::fake();

        $file = UploadedFile::fake()->createWithContent('slip.jpg', $payload);
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $file], ['Accept' => 'application/json'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_a_slip_over_two_megabytes_is_refused_and_exactly_two_is_allowed(): void
    {
        $this->renewalsApi(['Renewals/*' => $this->ok(['data' => []])]);

        $over = UploadedFile::fake()->createWithContent('big.pdf', self::bytes('pdf') . str_repeat('A', 2 * 1024 * 1024));
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $over], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('file');
        $this->assertNull($this->sentTo('POST', '/slip'));

        $bytes = self::bytes('pdf');
        $ok = UploadedFile::fake()->createWithContent('ok.pdf', $bytes . str_repeat('A', 2 * 1024 * 1024 - strlen($bytes) - 1024));
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $ok], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_no_file_an_empty_file_or_several_files_are_refused(): void
    {
        Http::fake();

        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', [], ['Accept' => 'application/json'])->assertStatus(422);
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => UploadedFile::fake()->createWithContent('e.pdf', '')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => 'just text'], ['Accept' => 'application/json'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_a_slip_for_a_malformed_renewal_id_is_a_404_before_anything_happens(): void
    {
        Http::fake();

        foreach (['abc', str_repeat('g', 36), str_repeat('a', 35)] as $id) {
            $this->asCustomer()->post("/renewals/$id/slip", ['file' => UploadedFile::fake()->createWithContent('s.pdf', self::bytes('pdf'))], ['Accept' => 'application/json'])->assertStatus(404);
            $this->asCustomer()->postJson("/renewals/$id/cancel")->assertStatus(404);
        }

        Http::assertNothingSent();
    }

    public function test_a_slip_is_never_written_to_this_servers_disk(): void
    {
        $this->renewalsApi(['Renewals/*' => $this->ok(['data' => []])]);
        $before = glob(storage_path('app/*/*')) ?: [];

        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => UploadedFile::fake()->createWithContent('s.pdf', self::bytes('pdf'))], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame($before, glob(storage_path('app/*/*')) ?: []);
    }

    public function test_slip_failures_are_plain(): void
    {
        $file = fn () => UploadedFile::fake()->createWithContent('s.pdf', self::bytes('pdf'));

        $this->renewalsApi(['Renewals/*' => Http::response(['message' => 'Only pending requests accept a slip.'], 409)]);
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $file()], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('message', 'Only pending requests accept a slip.');

        $this->renewalsApi(['Renewals/*' => Http::response(['message' => 'boom'], 500)]);
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $file()], ['Accept' => 'application/json'])
            ->assertStatus(502)->assertJsonPath('message', "Couldn't upload the slip. Please try again.");

        $this->renewalsApi(['Renewals/*' => Http::response([], 401)]);
        $this->asCustomer()->post('/renewals/' . self::UUID . '/slip', ['file' => $file()], ['Accept' => 'application/json'])->assertStatus(401);
    }

    // ---- cancel ------------------------------------------------------------------------------------------

    public function test_cancelling_calls_the_api_for_that_request_only(): void
    {
        $this->renewalsApi(['Renewals/*' => $this->ok()]);

        $this->asCustomer()->postJson('/renewals/' . self::UUID . '/cancel')->assertOk()->assertJsonPath('message', 'Renewal request cancelled.');

        $this->assertNotNull($this->sentTo('PATCH', '/api/Renewals/' . self::UUID . '/cancel'));
    }

    public function test_cancel_failures_are_plain(): void
    {
        foreach ([[404, 404, 'That renewal request was not found.'], [403, 403, 'You can only renew vehicles you own.'], [500, 502, "Couldn't cancel the request."]] as [$api, $status, $msg]) {
            $this->renewalsApi(['Renewals/*' => Http::response([], $api)]);
            $this->asCustomer()->postJson('/renewals/' . self::UUID . '/cancel')->assertStatus($status)->assertJsonPath('message', $msg);
        }
    }
}
