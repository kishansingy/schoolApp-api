<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatPermission;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // ── Permission config (admin only) ────────────────────────────────────────

    public function getPermissions()
    {
        return response()->json(ChatPermission::all());
    }

    public function savePermissions(Request $request)
    {
        $rows = $request->validate(['permissions' => 'required|array',
            'permissions.*.from_role' => 'required|string',
            'permissions.*.to_role'   => 'required|string',
            'permissions.*.enabled'   => 'required|boolean',
        ])['permissions'];

        foreach ($rows as $row) {
            ChatPermission::updateOrCreate(
                ['from_role' => $row['from_role'], 'to_role' => $row['to_role']],
                ['enabled'   => $row['enabled']]
            );
        }

        return response()->json(['message' => 'Saved.']);
    }

    // ── Check if current user can chat with a target user ─────────────────────

    private function canChat(User $me, User $target): bool
    {
        // Admin can chat with anyone
        if ($me->hasRole('admin')) return true;

        $myRole     = $me->getRoleNames()->first();
        $targetRole = $target->getRoleNames()->first();

        return ChatPermission::where(function ($q) use ($myRole, $targetRole) {
            $q->where('from_role', $myRole)->where('to_role', $targetRole);
        })->orWhere(function ($q) use ($myRole, $targetRole) {
            $q->where('from_role', $targetRole)->where('to_role', $myRole);
        })->where('enabled', true)->exists();
    }

    // ── List users I can chat with ────────────────────────────────────────────

    public function allowedUsers(Request $request)
    {
        $me     = $request->user();
        $myRole = $me->getRoleNames()->first();

        // Admin can chat with everyone
        if ($me->hasRole('admin')) {
            $users = User::with('roles')
                ->where('id', '!=', $me->id)
                ->get()
                ->map(fn($u) => [
                    'id'   => $u->id,
                    'name' => $u->name,
                    'role' => $u->getRoleNames()->first() ?? 'viewer',
                ]);
            return response()->json($users);
        }

        // Roles I can chat with (bidirectional)
        $allowedRoles = ChatPermission::where('enabled', true)
            ->where(function ($q) use ($myRole) {
                $q->where('from_role', $myRole)->orWhere('to_role', $myRole);
            })
            ->get()
            ->flatMap(fn($p) => [$p->from_role, $p->to_role])
            ->unique()
            ->reject(fn($r) => $r === $myRole)
            ->values();

        $users = User::with('roles')
            ->where('id', '!=', $me->id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles->toArray()))
            ->get()
            ->map(fn($u) => [
                'id'   => $u->id,
                'name' => $u->name,
                'role' => $u->getRoleNames()->first(),
            ]);

        return response()->json($users);
    }

    // ── Get or create conversation ────────────────────────────────────────────

    public function getOrCreateConversation(Request $request, int $targetUserId)
    {
        $me     = $request->user();
        $target = User::findOrFail($targetUserId);

        if (!$this->canChat($me, $target)) {
            return response()->json(['message' => 'Not allowed to chat with this user.'], 403);
        }

        [$one, $two] = $me->id < $target->id ? [$me->id, $target->id] : [$target->id, $me->id];

        $conv = ChatConversation::firstOrCreate(
            ['user_one_id' => $one, 'user_two_id' => $two]
        );

        // Ensure relations are loaded after firstOrCreate
        $conv->load(['userOne.roles', 'userTwo.roles']);

        return response()->json($this->formatConversation($conv, $me->id));
    }

    // ── List my conversations ─────────────────────────────────────────────────

    public function conversations(Request $request)
    {
        $me = $request->user();

        $convs = ChatConversation::with(['userOne.roles', 'userTwo.roles'])
            ->where('user_one_id', $me->id)
            ->orWhere('user_two_id', $me->id)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn($c) => $this->formatConversation($c, $me->id));

        return response()->json($convs);
    }

    // ── Get messages in a conversation ────────────────────────────────────────

    public function messages(Request $request, int $conversationId)
    {
        $me   = $request->user();
        $conv = ChatConversation::findOrFail($conversationId);

        if ($conv->user_one_id !== $me->id && $conv->user_two_id !== $me->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Mark incoming messages as read
        ChatMessage::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $since = $request->query('since'); // for polling — only fetch new messages
        $query = ChatMessage::where('conversation_id', $conversationId)->orderBy('created_at');
        if ($since) $query->where('id', '>', $since);

        return response()->json($query->get(['id', 'sender_id', 'message', 'read_at', 'created_at']));
    }

    // ── Send a message ────────────────────────────────────────────────────────

    public function sendMessage(Request $request, int $conversationId)
    {
        $me   = $request->user();
        $conv = ChatConversation::findOrFail($conversationId);

        if ($conv->user_one_id !== $me->id && $conv->user_two_id !== $me->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate(['message' => 'required|string|max:2000']);

        $msg = ChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $me->id,
            'message'         => $data['message'],
        ]);

        $conv->update(['last_message_at' => now()]);

        return response()->json($msg, 201);
    }

    // ── Unread count (for badge) ──────────────────────────────────────────────

    public function unreadCount(Request $request)
    {
        $me = $request->user();
        $count = ChatMessage::whereHas('conversation', function ($q) use ($me) {
            $q->where('user_one_id', $me->id)->orWhere('user_two_id', $me->id);
        })->where('sender_id', '!=', $me->id)->whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatConversation(ChatConversation $conv, int $myId): array
    {
        $other   = $conv->user_one_id === $myId ? $conv->userTwo : $conv->userOne;
        $lastMsg = ChatMessage::where('conversation_id', $conv->id)->latest()->first();
        $unread  = ChatMessage::where('conversation_id', $conv->id)
            ->where('sender_id', '!=', $myId)->whereNull('read_at')->count();

        return [
            'id'              => $conv->id,
            'other_user'      => ['id' => $other?->id, 'name' => $other?->name, 'role' => $other?->getRoleNames()->first()],
            'last_message'    => $lastMsg?->message,
            'last_message_at' => $conv->last_message_at,
            'unread_count'    => $unread,
        ];
    }
}
