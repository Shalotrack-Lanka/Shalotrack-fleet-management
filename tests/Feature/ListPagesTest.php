<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: the three list pages (sharing, emergency contacts, geofences): content, escaping, and every failure mode. */
class ListPagesTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    private function me(): \GuzzleHttp\Promise\PromiseInterface
    {
        return $this->ok(['data' => ['customerId' => self::UUID]]);
    }

    private function vehicles(): \GuzzleHttp\Promise\PromiseInterface
    {
        return $this->ok(['data' => [['vehicleId' => self::UUID2, 'vehicleNumber' => 'CAB-1234']]]);
    }

    // ---- sharing ---------------------------------------------------------------------------------------------

    private function sharingApi(array $over = []): array
    {
        return $over + [
            'Customers/me' => $this->me(), 'Vehicles/customer/*' => $this->vehicles(),
            'VehicleShares/my-shares' => $this->ok(['data' => [['shareId' => 'a', 'vehicleNumber' => 'MINE-1', 'otherPartyName' => 'Kamal', 'status' => 'Accepted']]]),
            'VehicleShares/shared-with-me' => $this->ok(['data' => [['shareId' => 'b', 'vehicleNumber' => 'THEIRS-2', 'otherPartyName' => 'Nimal', 'status' => 'Accepted']]]),
            'VehicleShares/pending-invites' => $this->ok(['data' => [['shareId' => 'c', 'vehicleNumber' => 'INVITE-3', 'otherPartyName' => 'Sunil', 'status' => 'Pending']]]),
        ];
    }

    public function test_the_sharing_page_shows_all_three_lists_and_the_vehicles_for_the_invite_form(): void
    {
        $this->fakeApi($this->sharingApi());

        $this->asCustomer()->get('/sharing')->assertOk()->assertSee('MINE-1')->assertSee('THEIRS-2')->assertSee('INVITE-3')->assertSee('CAB-1234');
    }

    public function test_names_from_other_people_are_shown_as_text(): void
    {
        $this->fakeApi($this->sharingApi(['VehicleShares/pending-invites' => $this->ok(['data' => [['shareId' => 'c', 'vehicleNumber' => 'X', 'otherPartyName' => '<script>alert(9)</script>', 'status' => 'Pending']]])]));

        $this->asCustomer()->get('/sharing')->assertOk()->assertDontSee('<script>alert(9)</script>', false);
    }

    public function test_a_profile_without_an_id_still_shows_the_page_with_no_vehicles(): void
    {
        $this->fakeApi($this->sharingApi(['Customers/me' => $this->ok(['data' => []])]));

        $this->asCustomer()->get('/sharing')->assertOk();

        $this->assertNull($this->sentTo('GET', '/api/Vehicles/customer/' . self::UUID));
    }

    public function test_odd_shapes_from_the_api_become_empty_lists(): void
    {
        $this->fakeApi($this->sharingApi(['VehicleShares/my-shares' => $this->ok(['data' => 'x']), 'VehicleShares/shared-with-me' => $this->ok(['data' => null]), 'VehicleShares/pending-invites' => $this->ok([])]));

        $this->asCustomer()->get('/sharing')->assertOk();
    }

    // ---- shared failure behaviour -----------------------------------------------------------------------------

    public static function pages(): array
    {
        return [
            'sharing'            => ['/sharing', 'Could not load sharing data. Please refresh.', 'VehicleShares/my-shares'],
            'emergency contacts' => ['/emergency-contacts', 'Could not load emergency contacts. Please refresh.', 'EmergencyContacts'],
            'geofences'          => ['/geofences', 'Could not load geofences. Please refresh.', 'Geofences*'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_a_server_error_shows_a_retry_message_and_nothing_internal(string $url, string $message, string $failing): void
    {
        $this->fakeApi([$failing => Http::response(['message' => 'Npgsql 10.0.0.5'], 500)] + $this->sharingApi() + ['*' => $this->ok(['data' => []])]);

        $this->asCustomer()->get($url)->assertOk()->assertSee($message)->assertDontSee('10.0.0.5');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_a_401_sends_the_customer_to_sign_in_and_clears_the_session(string $url, string $message, string $failing): void
    {
        $this->fakeApi(['*' => Http::response([], 401)]);

        $this->asCustomer()->get($url)->assertRedirect('/login?expired=1');

        $this->assertNull(session('firebase_token'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_a_stranger_gets_the_login_page(string $url): void
    {
        Http::fake();

        $this->get($url)->assertRedirect('/login');
        Http::assertNothingSent();
    }

    // ---- emergency contacts -----------------------------------------------------------------------------------

    public function test_contacts_are_listed_and_escaped(): void
    {
        $this->fakeApi(['EmergencyContacts' => $this->ok(['data' => [['contactId' => self::UUID, 'name' => 'Amma', 'phoneNumber' => '0771234567', 'relationship' => '<b>Mother</b>']]])]);

        $this->asCustomer()->get('/emergency-contacts')->assertOk()->assertSee('Amma')->assertSee('0771234567')->assertDontSee('<b>Mother</b>', false);
    }

    public function test_no_contacts_is_an_empty_page_not_an_error(): void
    {
        $this->fakeApi(['EmergencyContacts' => $this->ok(['data' => []])]);

        $this->asCustomer()->get('/emergency-contacts')->assertOk()->assertDontSee('Could not load');
    }

    // ---- geofences ---------------------------------------------------------------------------------------------

    public function test_geofences_are_listed_with_the_vehicles_for_the_scope_picker(): void
    {
        $this->fakeApi([
            'Geofences*' => $this->ok(['data' => [['geofenceId' => self::UUID, 'name' => 'Home Zone', 'latitude' => 6.9, 'longitude' => 79.8, 'radiusMeters' => 200]]]),
            'Customers/me' => $this->me(), 'Vehicles/customer/*' => $this->vehicles(),
        ]);

        $this->asCustomer()->get('/geofences')->assertOk()->assertSee('Home Zone')->assertSee('CAB-1234');
    }

    public function test_a_geofence_name_with_html_is_shown_as_text(): void
    {
        $this->fakeApi([
            'Geofences*' => $this->ok(['data' => [['geofenceId' => self::UUID, 'name' => '<script>alert(5)</script>', 'latitude' => 6.9, 'longitude' => 79.8, 'radiusMeters' => 200]]]),
            'Customers/me' => $this->me(), 'Vehicles/customer/*' => $this->vehicles(),
        ]);

        $this->asCustomer()->get('/geofences')->assertOk()->assertDontSee('<script>alert(5)</script>', false);
    }
}
