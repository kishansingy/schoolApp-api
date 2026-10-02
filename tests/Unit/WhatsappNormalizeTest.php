<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Tests the phone normalisation logic extracted from WhatsappController.
 * We test it via the send-message endpoint in dev mode (no real API call).
 */
class WhatsappNormalizeTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function sendWith(string $phone): \Illuminate\Testing\TestResponse
    {
        $user = $this->makeUser('teacher');
        return $this->postJson('/api/whatsapp/send-message', [
            'to'      => $phone,
            'message' => 'Test',
        ], $this->authHeaders($user));
    }

    public function test_10_digit_number_gets_91_prefix(): void
    {
        $res = $this->sendWith('9876543210');
        $res->assertOk();
        $this->assertDatabaseHas('whatsapp_logs', ['to_number' => '919876543210']);
    }

    public function test_number_starting_with_0_gets_91_prefix(): void
    {
        $res = $this->sendWith('09876543210');
        $res->assertOk();
        $this->assertDatabaseHas('whatsapp_logs', ['to_number' => '919876543210']);
    }

    public function test_number_with_country_code_kept_as_is(): void
    {
        $res = $this->sendWith('919876543210');
        $res->assertOk();
        $this->assertDatabaseHas('whatsapp_logs', ['to_number' => '919876543210']);
    }

    public function test_number_with_spaces_and_dashes_normalised(): void
    {
        $res = $this->sendWith('+91 98765-43210');
        $res->assertOk();
        $this->assertDatabaseHas('whatsapp_logs', ['to_number' => '919876543210']);
    }

    public function test_empty_phone_returns_422(): void
    {
        $user = $this->makeUser('teacher');
        $this->postJson('/api/whatsapp/send-message', [
            'to' => '', 'message' => 'Hi',
        ], $this->authHeaders($user))->assertStatus(422);
    }
}
