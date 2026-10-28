<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F3: emergency contacts (people alerted when SOS is pressed). */
class EmergencyContactWriteTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    public function test_a_contact_is_forwarded_with_the_relationship_defaulting_to_other(): void
    {
        $this->fakeApi(['EmergencyContacts' => $this->ok(['data' => ['contactId' => self::UUID]])]);

        $this->asCustomer()->postJson('/emergency-contacts', ['name' => 'Amma', 'phoneNumber' => '077 123 4567'])
            ->assertOk()->assertJsonPath('data.contactId', self::UUID);

        $this->assertSentBody('POST', '/api/EmergencyContacts', ['Name' => 'Amma', 'PhoneNumber' => '077 123 4567', 'Relationship' => 'Other']);
    }

    public function test_a_given_relationship_is_kept_and_extra_fields_are_dropped(): void
    {
        $this->fakeApi(['EmergencyContacts' => $this->ok()]);

        $this->asCustomer()->postJson('/emergency-contacts', [
            'name' => 'Amma', 'phoneNumber' => '0771234567', 'relationship' => 'Mother', 'customerId' => self::UUID2, 'isVerified' => true,
        ])->assertOk();

        $this->assertSentBody('POST', '/api/EmergencyContacts', ['Name' => 'Amma', 'PhoneNumber' => '0771234567', 'Relationship' => 'Mother']);
    }

    public static function invalidContacts(): array
    {
        return [
            'no name'            => [['name' => ''], 'name'],
            'name too long'      => [['name' => str_repeat('a', 101)], 'name'],
            'no phone'           => [['phoneNumber' => ''], 'phoneNumber'],
            'letters in phone'   => [['phoneNumber' => '07712abcde'], 'phoneNumber'],
            'phone too short'    => [['phoneNumber' => '12345'], 'phoneNumber'],
            'phone too long'     => [['phoneNumber' => str_repeat('1', 21)], 'phoneNumber'],
            'script in phone'    => [['phoneNumber' => '<script>1</script>'], 'phoneNumber'],
            'relationship long'  => [['relationship' => str_repeat('a', 51)], 'relationship'],
            'name is an array'   => [['name' => ['x']], 'name'],
        ];
    }

    /** @dataProvider invalidContacts */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidContacts')]
    public function test_bad_input_is_rejected_before_the_api_is_called(array $over, string $field): void
    {
        Http::fake();

        $this->asCustomer()->postJson('/emergency-contacts', $over + ['name' => 'Amma', 'phoneNumber' => '0771234567'])
            ->assertStatus(422)->assertJsonValidationErrors($field);

        Http::assertNothingSent();
    }

    public static function acceptedPhones(): array
    {
        return [['0771234567'], ['+94771234567'], ['077 123 4567'], ['011 234 5678'], ['011-234-5678'], ['077.123.4567'], ['1234567']];
    }

    /** @dataProvider acceptedPhones */
    #[\PHPUnit\Framework\Attributes\DataProvider('acceptedPhones')]
    public function test_common_ways_of_writing_a_phone_number_are_accepted(string $phone): void
    {
        $this->fakeApi(['EmergencyContacts' => $this->ok()]);

        $this->asCustomer()->postJson('/emergency-contacts', ['name' => 'Amma', 'phoneNumber' => $phone])->assertOk();
    }

    public function test_the_plan_limit_is_explained_in_plain_words(): void
    {
        $this->fakeApi(['EmergencyContacts' => Http::response(['message' => 'PLAN_LIMIT_EXCEEDED'], 422)]);

        $res = $this->asCustomer()->postJson('/emergency-contacts', ['name' => 'Amma', 'phoneNumber' => '0771234567'])->assertStatus(422);

        $this->assertSame('Plan limit reached. You cannot add more emergency contacts on your current plan.', $res->json('message'));
        $this->assertStringNotContainsString('PLAN_LIMIT_EXCEEDED', $res->getContent());
    }

    public function test_delete_calls_the_api_for_that_contact(): void
    {
        $this->fakeApi(['EmergencyContacts/*' => $this->ok()]);

        $this->asCustomer()->deleteJson('/emergency-contacts/' . self::UUID)->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($this->sentTo('DELETE', '/api/EmergencyContacts/' . self::UUID));
    }

    public function test_deleting_a_contact_that_is_not_yours_does_not_reveal_whether_it_exists(): void
    {
        $this->fakeApi(['EmergencyContacts/*' => Http::response(['message' => 'x'], 404)]);

        $res = $this->asCustomer()->deleteJson('/emergency-contacts/' . self::UUID)->assertStatus(422);

        $this->assertSame('NOT_FOUND', $res->json('code'));
    }
}
