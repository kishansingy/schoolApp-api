<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $parent;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin   = $this->makeUser('admin');
        $this->teacher = $this->makeUser('teacher');
        $this->parent  = $this->makeUser('parent');
        $this->student = $this->makeUser('student');
    }

    private function makeConversation(User $a, User $b): ChatConversation
    {
        return ChatConversation::create([
            'user_one_id' => min($a->id, $b->id),
            'user_two_id' => max($a->id, $b->id),
        ]);
    }

    // ── Conversations ─────────────────────────────────────────────────────────

    public function test_user_can_list_their_conversations(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);

        $res = $this->getJson('/api/chat/conversations', $this->authHeaders($this->teacher));
        $res->assertOk()->assertJsonCount(1);
    }

    public function test_user_only_sees_own_conversations(): void
    {
        $this->makeConversation($this->teacher, $this->parent);

        // student has no conversations
        $this->getJson('/api/chat/conversations', $this->authHeaders($this->student))
            ->assertOk()->assertJsonCount(0);
    }

    public function test_get_or_create_conversation(): void
    {
        $res = $this->getJson("/api/chat/with/{$this->parent->id}", $this->authHeaders($this->teacher));
        $res->assertOk()->assertJsonStructure(['id', 'user_one_id', 'user_two_id']);
        $this->assertDatabaseCount('chat_conversations', 1);
    }

    public function test_get_or_create_returns_existing_conversation(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);

        $res = $this->getJson("/api/chat/with/{$this->parent->id}", $this->authHeaders($this->teacher));
        $res->assertOk()->assertJsonPath('id', $conv->id);
        $this->assertDatabaseCount('chat_conversations', 1); // no duplicate
    }

    // ── Messages ──────────────────────────────────────────────────────────────

    public function test_user_can_send_message(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);

        $res = $this->postJson("/api/chat/conversations/{$conv->id}/messages", [
            'message' => 'Hello parent!',
        ], $this->authHeaders($this->teacher));

        $res->assertStatus(201)->assertJsonPath('message', 'Hello parent!');
        $this->assertDatabaseHas('chat_messages', ['conversation_id' => $conv->id, 'message' => 'Hello parent!']);
    }

    public function test_user_can_list_messages(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);
        ChatMessage::create(['conversation_id' => $conv->id, 'sender_id' => $this->teacher->id, 'message' => 'Hi']);
        ChatMessage::create(['conversation_id' => $conv->id, 'sender_id' => $this->parent->id,  'message' => 'Hello']);

        $this->getJson("/api/chat/conversations/{$conv->id}/messages", $this->authHeaders($this->teacher))
            ->assertOk()->assertJsonCount(2);
    }

    public function test_outsider_cannot_send_to_conversation(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);

        $this->postJson("/api/chat/conversations/{$conv->id}/messages", [
            'message' => 'Intruder!',
        ], $this->authHeaders($this->student))->assertStatus(403);
    }

    public function test_send_message_requires_message_field(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);
        $this->postJson("/api/chat/conversations/{$conv->id}/messages", [], $this->authHeaders($this->teacher))
            ->assertStatus(422)->assertJsonValidationErrors(['message']);
    }

    // ── Unread count ──────────────────────────────────────────────────────────

    public function test_unread_count_returns_correct_number(): void
    {
        $conv = $this->makeConversation($this->teacher, $this->parent);
        // parent sends 2 messages, teacher hasn't read them (read_at is null)
        ChatMessage::create(['conversation_id' => $conv->id, 'sender_id' => $this->parent->id, 'message' => 'Msg1', 'read_at' => null]);
        ChatMessage::create(['conversation_id' => $conv->id, 'sender_id' => $this->parent->id, 'message' => 'Msg2', 'read_at' => null]);

        $res = $this->getJson('/api/chat/unread', $this->authHeaders($this->teacher));
        $res->assertOk()->assertJsonPath('count', 2);
    }

    // ── Admin: permissions ────────────────────────────────────────────────────

    public function test_admin_can_get_chat_permissions(): void
    {
        $this->getJson('/api/chat/permissions', $this->authHeaders($this->admin))->assertOk();
    }

    public function test_non_admin_cannot_get_chat_permissions(): void
    {
        $this->getJson('/api/chat/permissions', $this->authHeaders($this->teacher))->assertStatus(403);
    }

    public function test_admin_can_save_chat_permissions(): void
    {
        $res = $this->postJson('/api/chat/permissions', [
            'permissions' => [
                ['from_role' => 'teacher', 'to_role' => 'parent',  'enabled' => true],
                ['from_role' => 'parent',  'to_role' => 'teacher', 'enabled' => true],
            ],
        ], $this->authHeaders($this->admin));

        $res->assertOk();
        $this->assertDatabaseHas('chat_permissions', ['from_role' => 'teacher', 'to_role' => 'parent', 'enabled' => 1]);
    }

    public function test_unauthenticated_cannot_access_chat(): void
    {
        $this->getJson('/api/chat/conversations')->assertStatus(401);
    }
}
