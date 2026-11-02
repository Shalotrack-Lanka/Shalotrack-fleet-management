<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: complaints (list, thread, file, reply) and saved places (list, add, delete). */
class ComplaintsAndSavedPlacesTest extends TestCase
{
    use SignedInFleetUser;

    private const XSS = '<script>window.__pwn=1</script>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function complaintsApi(array $over = []): void
    {
        $this->fakeApi($over + [
            'Customers/me'        => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*' => $this->ok(['data' => [['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio']]]),
            'Complaints/mine'     => $this->ok(['data' => [['complaintId' => self::UUID, 'vehicleNumber' => self::XSS, 'category' => 1, 'status' => 'Open']]]),
        ]);
    }

    // ---- complaints: pages ------------------------------------------------------------------------

    public function test_the_list_shows_owned_vehicles_for_the_picker_and_escapes_customer_text(): void
    {
        $this->complaintsApi();

        $res = $this->asCustomer()->get('/complaints')->assertOk()->assertSee('CAB-1234');

        $this->assertStringNotContainsString(self::XSS, $res->getContent());
        $this->assertStringContainsString('&lt;script&gt;', $res->getContent());
        // Vehicles are looked up for THIS customer id (from the profile), never from the request.
        $this->assertNotNull($this->sentTo('GET', '/api/Vehicles/customer/' . self::UUID2));
    }

    public function test_the_list_explains_an_api_outage_and_signs_out_on_401(): void
    {
        $this->complaintsApi(['Complaints/mine' => Http::response([], 500)]);
        $this->asCustomer()->get('/complaints')->assertOk()->assertSee('Could not load complaints');

        // A different customer: the profile is cached for 30 s per signed-in user, so the first answer would be reused.
        $this->complaintsApi(['Customers/me' => Http::response([], 401)]);
        $this->asCustomer('uid-2')->get('/complaints')->assertRedirect('/login?expired=1');
    }

    public function test_a_complaint_thread_opens_and_a_missing_one_goes_back_to_the_list(): void
    {
        $this->fakeApi(['Complaints/' . self::UUID => $this->ok(['data' => ['complaintId' => self::UUID, 'category' => 1, 'status' => 'Open', 'replies' => []]])]);
        $this->asCustomer()->get('/complaints/' . self::UUID)->assertOk();

        $this->fakeApi(['Complaints/*' => Http::response([], 404)]);
        $this->asCustomer()->get('/complaints/' . self::UUID)->assertRedirect('/complaints')->assertSessionHas('error', 'Complaint not found.');
    }

    public function test_a_thread_failure_is_plain_and_a_401_signs_out(): void
    {
        $this->fakeApi(['Complaints/*' => Http::response([], 500)]);
        $this->asCustomer()->get('/complaints/' . self::UUID)->assertOk()->assertSee('Could not load this complaint');

        $this->fakeApi(['Complaints/*' => Http::response([], 401)]);
        $this->asCustomer()->get('/complaints/' . self::UUID)->assertRedirect('/login?expired=1');
    }

    // ---- complaints: file + reply -------------------------------------------------------------------

    public function test_a_complaint_is_forwarded_with_only_the_three_known_fields(): void
    {
        $this->fakeApi(['Complaints' => $this->ok(['data' => ['complaintId' => self::UUID]])]);

        $this->asCustomer()->postJson('/complaints', [
            'vehicleId' => self::UUID, 'category' => '2', 'description' => 'GPS is offline', 'status' => 'Resolved', 'customerId' => self::UUID2,
        ])->assertOk()->assertJsonPath('data.complaintId', self::UUID);

        $req = $this->sentTo('POST', '/api/Complaints');
        $this->assertNotNull($req);
        $this->assertSame(['vehicleId', 'category', 'description'], array_map('lcfirst', array_keys($req->data())));
        $this->assertSame(2, $req->data()['category'] ?? $req->data()['Category'] ?? null);
    }

    public static function badComplaints(): array
    {
        return [
            'no vehicle'        => [['vehicleId' => '']],
            'vehicle not uuid'  => [['vehicleId' => '1; DROP TABLE']],
            'no category'       => [['category' => '']],
            'category too high' => [['category' => 4]],
            'category negative' => [['category' => -1]],
            'category is text'  => [['category' => 'noise']],
            'no description'    => [['description' => '']],
            'description long'  => [['description' => str_repeat('a', 2001)]],
            'description array' => [['description' => ['x']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badComplaints')]
    public function test_a_bad_complaint_is_refused_before_the_api_is_called(array $over): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/complaints', $over + ['vehicleId' => self::UUID, 'category' => 1, 'description' => 'ok'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_limits_of_a_complaint_are_allowed(): void
    {
        $this->fakeApi(['Complaints' => $this->ok(['data' => []])]);

        foreach ([0, 3] as $category) {
            $this->asCustomer()->postJson('/complaints', ['vehicleId' => self::UUID, 'category' => $category, 'description' => str_repeat('a', 2000)])->assertOk();
        }
    }

    public function test_filing_against_a_vehicle_that_is_not_yours_says_so_in_plain_words(): void
    {
        $this->fakeApi(['Complaints' => Http::response(['message' => 'NOT_OWNER'], 403)]);

        $res = $this->asCustomer()->postJson('/complaints', ['vehicleId' => self::UUID, 'category' => 1, 'description' => 'x'])
            ->assertStatus(422)->assertJsonPath('message', 'You can only file a complaint against a vehicle you own.');

        $this->assertStringNotContainsString('NOT_OWNER', $res->getContent());
    }

    public function test_a_reply_is_forwarded_and_a_closed_thread_is_explained(): void
    {
        $this->fakeApi(['Complaints/' . self::UUID . '/reply' => $this->ok(['data' => ['replyId' => 'r1']])]);
        $this->asCustomer()->postJson('/complaints/' . self::UUID . '/reply', ['message' => 'Any update?', 'isStaff' => true])->assertOk();
        $this->assertSentBody('POST', '/reply', ['message' => 'Any update?']);

        $this->fakeApi(['Complaints/*' => Http::response([], 409)]);
        $this->asCustomer()->postJson('/complaints/' . self::UUID . '/reply', ['message' => 'Hello'])
            ->assertStatus(422)->assertJsonPath('message', 'This complaint is closed and can no longer receive replies.');
    }

    public function test_a_blank_or_oversized_reply_is_refused_before_the_api_is_called(): void
    {
        Http::fake();

        foreach (['', str_repeat('a', 2001), ['x']] as $bad) {
            $this->asCustomer()->postJson('/complaints/' . self::UUID . '/reply', ['message' => $bad])->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    // ---- saved places -------------------------------------------------------------------------------

    public function test_saved_places_are_listed_and_names_are_escaped(): void
    {
        $this->fakeApi(['SavedPlaces' => $this->ok(['data' => [['placeId' => self::UUID, 'name' => self::XSS, 'latitude' => 6.9, 'longitude' => 79.8]]])]);

        $res = $this->asCustomer()->get('/saved-places')->assertOk();

        $this->assertStringNotContainsString(self::XSS, $res->getContent());
    }

    public function test_saved_places_fail_softly_and_a_401_signs_out(): void
    {
        $this->fakeApi(['SavedPlaces' => Http::response([], 500)]);
        $this->asCustomer()->get('/saved-places')->assertOk()->assertSee('Could not load saved places');

        $this->fakeApi(['SavedPlaces' => Http::response([], 401)]);
        $this->asCustomer()->get('/saved-places')->assertRedirect('/login?expired=1');
    }

    public function test_a_place_is_forwarded_with_numbers_and_nothing_else(): void
    {
        $this->fakeApi(['SavedPlaces' => $this->ok(['data' => ['placeId' => self::UUID]])]);

        $this->asCustomer()->postJson('/saved-places', ['name' => 'Home', 'latitude' => '6.9271', 'longitude' => '79.8612', 'customerId' => self::UUID2])->assertOk();

        $this->assertSentBody('POST', '/api/SavedPlaces', ['name' => 'Home', 'latitude' => 6.9271, 'longitude' => 79.8612]);
    }

    public static function badPlaces(): array
    {
        return [
            'no name'         => [['name' => '']],
            'name too long'   => [['name' => str_repeat('a', 101)]],
            'latitude 90.1'   => [['latitude' => 90.1]],
            'latitude -90.1'  => [['latitude' => -90.1]],
            'longitude 180.1' => [['longitude' => 180.1]],
            'longitude -181'  => [['longitude' => -181]],
            'latitude text'   => [['latitude' => 'north']],
            'no longitude'    => [['longitude' => '']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badPlaces')]
    public function test_a_bad_place_is_refused_before_the_api_is_called(array $over): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/saved-places', $over + ['name' => 'Home', 'latitude' => 6.9, 'longitude' => 79.8])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_place_boundaries_are_allowed_and_delete_hits_that_place(): void
    {
        $this->fakeApi(['SavedPlaces*' => $this->ok()]);

        $this->asCustomer()->postJson('/saved-places', ['name' => str_repeat('a', 100), 'latitude' => -90, 'longitude' => 180])->assertOk();
        $this->asCustomer()->deleteJson('/saved-places/' . self::UUID)->assertOk();

        $this->assertNotNull($this->sentTo('DELETE', '/api/SavedPlaces/' . self::UUID));
    }

    public function test_place_failures_are_plain(): void
    {
        $this->fakeApi(['SavedPlaces*' => Http::response(['message' => 'DB_CONSTRAINT_FAIL'], 500)]);

        $res = $this->asCustomer()->postJson('/saved-places', ['name' => 'Home', 'latitude' => 6.9, 'longitude' => 79.8])
            ->assertStatus(422)->assertJsonPath('message', 'Failed to save place.');
        $this->assertStringNotContainsString('DB_CONSTRAINT', $res->getContent());

        $this->asCustomer()->deleteJson('/saved-places/' . self::UUID)->assertStatus(422)->assertJsonPath('message', 'Failed to delete place.');
    }
}
