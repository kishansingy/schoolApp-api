<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineTest;
use App\Models\OnlineTestAttempt;
use App\Models\OnlineTestAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineTestController extends Controller
{
    // ── Admin: list all tests ─────────────────────────────────────────────────
    public function index()
    {
        return OnlineTest::withCount('questions')->latest()->get();
    }

    // ── Admin: create test ────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'instructions'    => 'nullable|string',
            'subject_id'      => 'nullable|integer',
            'class_id'        => 'nullable|integer',
            'total_time'      => 'required|integer|min:1',
            'max_marks'       => 'required|integer|min:1',
            'status'          => 'in:draft,published,closed',
            'available_from'  => 'nullable|date',
            'available_until' => 'nullable|date',
        ]);
        $data['created_by'] = $request->user()->id;
        return response()->json(OnlineTest::create($data), 201);
    }

    // ── Admin: get full test with sections + questions ────────────────────────
    public function show(OnlineTest $onlineTest)
    {
        return $onlineTest->load(['sections.questions']);
    }

    // ── Admin: update test ────────────────────────────────────────────────────
    public function update(Request $request, OnlineTest $onlineTest)
    {
        $onlineTest->update($request->only([
            'title', 'instructions', 'subject_id', 'class_id',
            'total_time', 'max_marks', 'status', 'available_from', 'available_until',
        ]));
        return $onlineTest->fresh()->load(['sections.questions']);
    }

    public function destroy(OnlineTest $onlineTest)
    {
        $onlineTest->delete();
        return response()->noContent();
    }

    // ── Admin: save sections + questions in one call ──────────────────────────
    public function saveSections(Request $request, OnlineTest $onlineTest)
    {
        DB::transaction(function () use ($request, $onlineTest) {
            $onlineTest->sections()->delete(); // cascades to questions via FK

            foreach ($request->input('sections', []) as $sIdx => $sec) {
                $section = $onlineTest->sections()->create([
                    'name'               => $sec['name'],
                    'time_limit'         => $sec['time_limit']         ?? 0,
                    'marks_per_question' => $sec['marks_per_question'] ?? 1,
                    'order'              => $sIdx,
                ]);

                foreach ($sec['questions'] ?? [] as $qIdx => $q) {
                    $onlineTest->questions()->create([
                        'section_id'    => $section->id,
                        'question_text' => $q['question_text'],
                        'question_type' => $q['question_type'] ?? 'mcq',
                        'option_a'      => $q['option_a']      ?? null,
                        'option_b'      => $q['option_b']      ?? null,
                        'option_c'      => $q['option_c']      ?? null,
                        'option_d'      => $q['option_d']      ?? null,
                        'correct_answer'=> $q['correct_answer'] ?? null,
                        'marks'         => $q['marks']          ?? $sec['marks_per_question'] ?? 1,
                        'order'         => $qIdx,
                    ]);
                }
            }
        });

        return $onlineTest->fresh()->load(['sections.questions']);
    }

    // ── Student: list published tests ─────────────────────────────────────────
    public function published()
    {
        return OnlineTest::where('status', 'published')
            ->withCount('questions')
            ->latest()->get();
    }

    // ── Student: start attempt ────────────────────────────────────────────────
    public function startAttempt(Request $request, OnlineTest $onlineTest)
    {
        // Only published tests can be attempted
        if ($onlineTest->status !== 'published') {
            return response()->json(['message' => 'This test is not available.'], 403);
        }

        $userId = $request->user()->id;

        // Check existing in-progress attempt
        $attempt = OnlineTestAttempt::where('test_id', $onlineTest->id)
            ->where('student_id', $userId)
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt) {
            // Check already submitted
            $done = OnlineTestAttempt::where('test_id', $onlineTest->id)
                ->where('student_id', $userId)
                ->whereIn('status', ['submitted', 'timed_out'])
                ->first();
            if ($done) {
                return response()->json(['message' => 'Already attempted.', 'attempt_id' => $done->id], 409);
            }

            $attempt = OnlineTestAttempt::create([
                'test_id'    => $onlineTest->id,
                'student_id' => $userId,
                'started_at' => now(),
                'max_marks'  => $onlineTest->max_marks,
                'status'     => 'in_progress',
            ]);
        }

        // Return test with sections + questions (no correct answers)
        $test = $onlineTest->load(['sections.questions']);
        // Strip correct answers from questions
        $test->sections->each(function ($sec) {
            $sec->questions->each(function ($q) {
                unset($q->correct_answer);
            });
        });

        // Load saved answers so far
        $savedAnswers = OnlineTestAnswer::where('attempt_id', $attempt->id)
            ->pluck('selected_answer', 'question_id');

        return response()->json([
            'attempt'       => $attempt,
            'test'          => $test,
            'saved_answers' => $savedAnswers,
        ]);
    }

    // ── Student: save answer (auto-save per question) ─────────────────────────
    public function saveAnswer(Request $request, OnlineTestAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);

        OnlineTestAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $request->input('question_id')],
            ['selected_answer' => $request->input('selected_answer')]
        );

        // Also update current section index
        if ($request->has('current_section_index')) {
            $attempt->update(['current_section_index' => $request->input('current_section_index')]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Student: submit attempt ───────────────────────────────────────────────
    public function submitAttempt(Request $request, OnlineTestAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);

        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Already submitted.'], 409);
        }

        return response()->json($this->gradeAttempt($attempt, $request->input('timed_out', false)));
    }

    // ── Student: get result ───────────────────────────────────────────────────
    public function result(Request $request, OnlineTestAttempt $attempt)
    {
        $this->authorizeAttempt($request, $attempt);

        $attempt->load(['test.sections.questions', 'answers']);

        // Attach correct answers + student answer to each question
        $attempt->test->sections->each(function ($sec) use ($attempt) {
            $sec->questions->each(function ($q) use ($attempt) {
                $ans = $attempt->answers->firstWhere('question_id', $q->id);
                $q->student_answer  = $ans?->selected_answer;
                $q->is_correct      = $ans?->is_correct ?? false;
                $q->marks_awarded   = $ans?->marks_awarded ?? 0;
            });
        });

        return response()->json($attempt);
    }

    // ── Admin: all attempts for a test ────────────────────────────────────────
    public function attempts(OnlineTest $onlineTest)
    {
        return $onlineTest->attempts()->with('answers')->latest()->get();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function gradeAttempt(OnlineTestAttempt $attempt, bool $timedOut = false): OnlineTestAttempt
    {
        $questions = $attempt->test->load('questions')->questions;
        $answers   = $attempt->answers()->get()->keyBy('question_id');

        $totalMarks = 0;

        foreach ($questions as $q) {
            $ans = $answers->get($q->id);
            if (!$ans) continue;

            $correct = strtolower(trim($ans->selected_answer ?? '')) === strtolower(trim($q->correct_answer ?? ''));
            $awarded = $correct ? (float) $q->marks : 0;
            $totalMarks += $awarded;

            $ans->update(['is_correct' => $correct, 'marks_awarded' => $awarded]);
        }

        $maxMarks   = $questions->sum('marks');
        $percentage = $maxMarks > 0 ? round(($totalMarks / $maxMarks) * 100, 2) : 0;

        $attempt->update([
            'submitted_at' => now(),
            'total_marks'  => $totalMarks,
            'max_marks'    => $maxMarks,
            'percentage'   => $percentage,
            'status'       => $timedOut ? 'timed_out' : 'submitted',
        ]);

        return $attempt->fresh();
    }

    private function authorizeAttempt(Request $request, OnlineTestAttempt $attempt): void
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && $attempt->student_id !== $user->id) {
            abort(403);
        }
    }
}
