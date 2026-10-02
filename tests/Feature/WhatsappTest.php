<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappLog;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin   = $this->makeUser('admin');
        $this->teacher = $this->makeUser('teacher');
        $this->parent  = $this->makeUser('parent');
    }

    private function fakeWhatsappSuccess(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test123']],
            ], 200),
        ]);
    }

    private function fakeWhatsappFailure(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid phone number'],
            ], 400),
        ]);
    }

    // ── Config ────────────────────────────────────────────────────────────────

    public function test_config_endpoint_returns_configured_status(): void
    {
        $this->getJson('/api/whatsapp/config', $this->authHeaders($this->admin))
            ->assertOk()
            ->assertJsonStructure(['configured', 'school_number', 'school_name']);
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    public function test_admin_can_create_template(): void
    {
        $res = $this->postJson('/api/whatsapp/templates', [
            'name'     => 'Fee Reminder',
            'category' => 'fee',
            'body'     => 'Dear {{name}}, your fee is due.',
        ], $this->authHeaders($this->admin));

        $res->assertOk()->assertJsonPath('name', 'Fee Reminder');
        $this->assertDatabaseHas('whatsapp_templates', ['name' => 'Fee Reminder']);
    }

    public function test_non_admin_cannot_create_template(): void
    {
        $this->postJson('/api/whatsapp/templates', [
            'name' => 'Hack', 'category' => 'x', 'body' => 'x',
        ], $this->authHeaders($this->teacher))->assertStatus(403);
    }

    public function test_anyone_can_list_active_templates(): void
    {
        WhatsappTemplate::create(['name' => 'T1', 'category' => 'general', 'body' => 'Hello', 'active' => true]);
        WhatsappTemplate::create(['name' => 'T2', 'category' => 'general', 'body' => 'Hi',    'active' => false]);

        $res = $this->getJson('/api/whatsapp/templates', $this->authHeaders($this->teacher));
        $res->assertOk()->assertJsonCount(1); // only active
    }

    public function test_admin_can_update_template(): void
    {
        $t = WhatsappTemplate::create(['name' => 'Old', 'category' => 'fee', 'body' => 'Old body']);
        $this->putJson("/api/whatsapp/templates/{$t->id}", [
            'name' => 'New', 'category' => 'fee', 'body' => 'New body',
        ], $this->authHeaders($this->admin))->assertOk()->assertJsonPath('name', 'New');
    }

    public function test_admin_can_delete_template(): void
    {
        $t = WhatsappTemplate::create(['name' => 'Del', 'category' => 'fee', 'body' => 'body']);
        $this->deleteJson("/api/whatsapp/templates/{$t->id}", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('whatsapp_templates', ['id' => $t->id]);
    }

    // ── Send message (mobile) ─────────────────────────────────────────────────

    public function test_teacher_can_send_message_in_dev_mode(): void
    {
        // No token configured → dev mode returns success
        $res = $this->postJson('/api/whatsapp/send-message', [
            'to'             => '9876543210',
            'message'        => 'Hello from teacher',
            'recipient_name' => 'Test Parent',
            'recipient_type' => 'parents',
        ], $this->authHeaders($this->teacher));

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('whatsapp_logs', [
            'to_number'      => '919876543210',
            'sent_by_user_id' => $this->teacher->id,
        ]);
    }

    public function test_parent_can_send_message_to_school(): void
    {
        config(['services.whatsapp.school_number' => '919000000000']);

        $res = $this->postJson('/api/whatsapp/contact-school', [
            'message' => 'My child is sick today.',
        ], $this->authHeaders($this->parent));

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('whatsapp_logs', [
            'to_number'       => '919000000000',
            'recipient_type'  => 'school',
            'sent_by_user_id' => $this->parent->id,
        ]);
    }

    public function test_contact_school_fails_when_number_not_configured(): void
    {
        config(['services.whatsapp.school_number' => null]);

        $this->postJson('/api/whatsapp/contact-school', [
            'message' => 'Hello',
        ], $this->authHeaders($this->parent))->assertStatus(422);
    }

    public function test_send_message_validates_required_fields(): void
    {
        $this->postJson('/api/whatsapp/send-message', [], $this->authHeaders($this->teacher))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to', 'message']);
    }

    public function test_send_message_validates_max_length(): void
    {
        $this->postJson('/api/whatsapp/send-message', [
            'to'      => '9876543210',
            'message' => str_repeat('x', 1001),
        ], $this->authHeaders($this->teacher))->assertStatus(422);
    }

    // ── Logs ─────────────────────────────────────────────────────────────────

    public function test_my_logs_returns_only_current_user_logs(): void
    {
        WhatsappLog::create(['to_number' => '91111', 'message' => 'Mine',   'status' => 'sent', 'sent_by_user_id' => $this->teacher->id]);
        WhatsappLog::create(['to_number' => '91222', 'message' => 'Others', 'status' => 'sent', 'sent_by_user_id' => $this->admin->id]);

        $res = $this->getJson('/api/whatsapp/my-logs', $this->authHeaders($this->teacher));
        $res->assertOk()->assertJsonCount(1);
        $this->assertEquals('Mine', $res->json('0.message'));
    }

    public function test_admin_can_view_all_logs(): void
    {
        WhatsappLog::create(['to_number' => '91111', 'message' => 'A', 'status' => 'sent']);
        WhatsappLog::create(['to_number' => '91222', 'message' => 'B', 'status' => 'failed']);

        $this->getJson('/api/whatsapp/logs', $this->authHeaders($this->admin))
            ->assertOk()->assertJsonCount(2);
    }

    public function test_admin_can_view_stats(): void
    {
        WhatsappLog::create(['to_number' => '91111', 'message' => 'A', 'status' => 'sent']);
        WhatsappLog::create(['to_number' => '91222', 'message' => 'B', 'status' => 'failed']);

        $res = $this->getJson('/api/whatsapp/stats', $this->authHeaders($this->admin));
        $res->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('sent', 1)
            ->assertJsonPath('failed', 1);
    }

    public function test_unauthenticated_cannot_send_message(): void
    {
        $this->postJson('/api/whatsapp/send-message', ['to' => '9876543210', 'message' => 'Hi'])
            ->assertStatus(401);
    }
}
