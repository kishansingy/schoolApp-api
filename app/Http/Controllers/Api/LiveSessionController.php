<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Models\LiveSessionMessage;
use App\Models\LiveSessionParticipant;
use App\Models\LiveSessionSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LiveSessionController extends Controller
{
    // ── Teacher / Admin: list sessions ───────────────────────────────────────
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LiveSession::with('teacher:id,name')
            ->withCount('participants');

        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        }

        return $query->orderBy('scheduled_at', 'desc')->get();
    }

    // ── Student / Parent: list live or upcoming sessions ─────────────────────
    public function published(Request $request)
    {
        return LiveSession::with('teacher:id,name')
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('scheduled_at')
            ->get();
    }

    // ── Teacher / Admin: create session ──────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'class_name'       => 'nullable|string|max:100',
            'subject'          => 'nullable|string|max:100',
            'scheduled_at'     => 'required|date',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
        ]);

        $data['teacher_id'] = $request->user()->id;
        $data['room_code']  = Str::random(12);
        $data['status']     = 'scheduled';

        return response()->json(LiveSession::create($data), 201);
    }

    // ── Teacher / Admin: update session ──────────────────────────────────────
    public function update(Request $request, LiveSession $liveSession)
    {
        $this->authorizeTeacher($request, $liveSession);

        $data = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'description'      => 'nullable|string',
            'class_name'       => 'nullable|string|max:100',
            'subject'          => 'nullable|string|max:100',
            'scheduled_at'     => 'sometimes|required|date',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
        ]);

        $liveSession->update($data);
        return $liveSession->fresh();
    }

    // ── Teacher: start session ────────────────────────────────────────────────
    public function start(Request $request, LiveSession $liveSession)
    {
        $this->authorizeTeacher($request, $liveSession);
        $liveSession->update(['status' => 'live']);
        return $liveSession->fresh();
    }

    // ── Teacher: end session ──────────────────────────────────────────────────
    public function end(Request $request, LiveSession $liveSession)
    {
        $this->authorizeTeacher($request, $liveSession);
        $liveSession->update(['status' => 'ended']);

        // Mark all participants as left
        LiveSessionParticipant::where('session_id', $liveSession->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        return $liveSession->fresh();
    }

    // ── Teacher / Admin: delete session ──────────────────────────────────────
    public function destroy(Request $request, LiveSession $liveSession)
    {
        $this->authorizeTeacher($request, $liveSession);
        $liveSession->delete();
        return response()->noContent();
    }

    // ── Any authenticated: get session details ────────────────────────────────
    public function show(LiveSession $liveSession)
    {
        return $liveSession->load('teacher:id,name');
    }

    // ── Join session (record participant) ─────────────────────────────────────
    public function join(Request $request, LiveSession $liveSession)
    {
        if ($liveSession->status === 'ended') {
            return response()->json(['message' => 'Session has ended.'], 403);
        }

        $userId = $request->user()->id;

        LiveSessionParticipant::updateOrCreate(
            ['session_id' => $liveSession->id, 'user_id' => $userId],
            ['joined_at' => now(), 'left_at' => null]
        );

        // Return current participants (active)
        $participants = LiveSessionParticipant::with('user:id,name')
            ->where('session_id', $liveSession->id)
            ->whereNull('left_at')
            ->get()
            ->map(fn($p) => ['id' => $p->user_id, 'name' => $p->user->name]);

        return response()->json([
            'session'      => $liveSession->load('teacher:id,name'),
            'participants' => $participants,
        ]);
    }

    // ── Leave session ─────────────────────────────────────────────────────────
    public function leave(Request $request, LiveSession $liveSession)
    {
        LiveSessionParticipant::where('session_id', $liveSession->id)
            ->where('user_id', $request->user()->id)
            ->update(['left_at' => now()]);

        return response()->noContent();
    }

    // ── Poll active participants ───────────────────────────────────────────────
    public function participants(LiveSession $liveSession)
    {
        $participants = LiveSessionParticipant::with('user:id,name')
            ->where('session_id', $liveSession->id)
            ->whereNull('left_at')
            ->get()
            ->map(fn($p) => ['id' => $p->user_id, 'name' => $p->user->name]);

        return response()->json($participants);
    }

    // ── WebRTC Signaling: send signal ─────────────────────────────────────────
    public function sendSignal(Request $request, LiveSession $liveSession)
    {
        $data = $request->validate([
            'to_user_id' => 'required|integer',
            'type'       => 'required|in:offer,answer,ice-candidate',
            'payload'    => 'required|string',
        ]);

        LiveSessionSignal::create([
            'session_id'   => $liveSession->id,
            'from_user_id' => $request->user()->id,
            'to_user_id'   => $data['to_user_id'],
            'type'         => $data['type'],
            'payload'      => $data['payload'],
            'consumed'     => false,
        ]);

        return response()->json(['ok' => true]);
    }

    // ── WebRTC Signaling: poll signals for me ─────────────────────────────────
    public function pollSignals(Request $request, LiveSession $liveSession)
    {
        $signals = LiveSessionSignal::where('session_id', $liveSession->id)
            ->where('to_user_id', $request->user()->id)
            ->where('consumed', false)
            ->get();

        // Mark as consumed
        $ids = $signals->pluck('id');
        if ($ids->isNotEmpty()) {
            LiveSessionSignal::whereIn('id', $ids)->update(['consumed' => true]);
        }

        return response()->json($signals->map(fn($s) => [
            'id'           => $s->id,
            'from_user_id' => $s->from_user_id,
            'type'         => $s->type,
            'payload'      => $s->payload,
        ]));
    }

    // ── Chat: send message ────────────────────────────────────────────────────
    public function sendMessage(Request $request, LiveSession $liveSession)
    {
        $data = $request->validate(['message' => 'required|string|max:1000']);

        $msg = LiveSessionMessage::create([
            'session_id' => $liveSession->id,
            'user_id'    => $request->user()->id,
            'message'    => $data['message'],
        ]);

        return response()->json([
            'id'         => $msg->id,
            'user_id'    => $msg->user_id,
            'user_name'  => $request->user()->name,
            'message'    => $msg->message,
            'created_at' => $msg->created_at,
        ], 201);
    }

    // ── Chat: poll messages ───────────────────────────────────────────────────
    public function messages(Request $request, LiveSession $liveSession)
    {
        $since = $request->query('since'); // last message id

        $query = LiveSessionMessage::with('user:id,name')
            ->where('session_id', $liveSession->id)
            ->orderBy('id');

        if ($since) {
            $query->where('id', '>', $since);
        }

        return response()->json($query->get()->map(fn($m) => [
            'id'         => $m->id,
            'user_id'    => $m->user_id,
            'user_name'  => $m->user->name,
            'message'    => $m->message,
            'created_at' => $m->created_at,
        ]));
    }

    // ── Helper ────────────────────────────────────────────────────────────────
    private function authorizeTeacher(Request $request, LiveSession $session): void
    {
        $user = $request->user();
        if ($user->hasRole('admin')) return;
        if ($session->teacher_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }
}
