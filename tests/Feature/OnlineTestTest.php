<?php

namespace Tests\Feature;

use App\Models\OnlineTest;
use App\Models\OnlineTestSection;
use App\Models\OnlineTestQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineTestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin   = $this->makeUser('admin');
        $this->student = $this->makeUser('student');
        $this->teacher = $this->makeUser('teacher');
    }

    private function makePublishedTest(): OnlineTest
    {
        $test = OnlineTest::create([
            'title'      => 'Math Test',
            'total_time' => 30,
            'max_marks'  => 10,
            'status'     => 'published',
            'created_by' => $this->admin->id,
        ]);
        $section = OnlineTestSection::create([
            'test_id' => $test->id,
            'name'    => 'Section 1',
            'order'   => 1,
        ]);
        OnlineTestQuestion::create([
            'test_id'        => $test->id,
            'section_id'     => $section->id,
            'question_text'  => 'What is 2+2?',
            'question_type'  => 'mcq',
            'option_a'       => '3',
            'option_b'       => '4',
            'option_c'       => '5',
            'correct_answer' => 'B',
            'marks'          => 1,
            'order'          => 1,
        ]);
        return $test;
    }

    // ── Admin CRUD ────────────────────────────────────────────────────────────

    public function test_admin_can_create_test(): void
    {
        $res = $this->postJson('/api/online-tests', [
            'title'      => 'Science Quiz',
            'total_time' => 20,
            'max_marks'  => 10,
            'status'     => 'draft',
        ], $this->authHeaders($this->admin));

        $res->assertStatus(201)->assertJsonPath('title', 'Science Quiz');
        $this->assertDatabaseHas('online_tests', ['title' => 'Science Quiz']);
    }

    public function test_non_admin_cannot_create_test(): void
    {
        $this->postJson('/api/online-tests', [
            'title' => 'Hack', 'total_time' => 10, 'max_marks' => 5, 'status' => 'draft',
        ], $this->authHeaders($this->student))->assertStatus(403);
    }

    public function test_admin_can_list_all_tests(): void
    {
        OnlineTest::create(['title' => 'T1', 'total_time' => 10, 'max_marks' => 5, 'status' => 'draft',     'created_by' => $this->admin->id]);
        OnlineTest::create(['title' => 'T2', 'total_time' => 20, 'max_marks' => 10, 'status' => 'published', 'created_by' => $this->admin->id]);

        $this->getJson('/api/online-tests', $this->authHeaders($this->admin))
            ->assertOk()->assertJsonCount(2);
    }

    public function test_admin_can_update_test(): void
    {
        $test = OnlineTest::create(['title' => 'Old', 'total_time' => 10, 'max_marks' => 5, 'status' => 'draft', 'created_by' => $this->admin->id]);
        $this->putJson("/api/online-tests/{$test->id}", ['title' => 'Updated', 'total_time' => 15, 'max_marks' => 5, 'status' => 'draft'], $this->authHeaders($this->admin))
            ->assertOk()->assertJsonPath('title', 'Updated');
    }

    public function test_admin_can_delete_test(): void
    {
        $test = OnlineTest::create(['title' => 'Del', 'total_time' => 5, 'max_marks' => 5, 'status' => 'draft', 'created_by' => $this->admin->id]);
        $this->deleteJson("/api/online-tests/{$test->id}", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('online_tests', ['id' => $test->id]);
    }

    // ── Student: published tests ──────────────────────────────────────────────

    public function test_student_can_see_published_tests(): void
    {
        OnlineTest::create(['title' => 'Draft',     'total_time' => 10, 'max_marks' => 5, 'status' => 'draft',     'created_by' => $this->admin->id]);
        OnlineTest::create(['title' => 'Published', 'total_time' => 20, 'max_marks' => 10, 'status' => 'published', 'created_by' => $this->admin->id]);

        $res = $this->getJson('/api/online-tests/published', $this->authHeaders($this->student));
        $res->assertOk()->assertJsonCount(1);
        $this->assertEquals('Published', $res->json('0.title'));
    }

    public function test_student_can_start_attempt(): void
    {
        $test = $this->makePublishedTest();
        $res  = $this->postJson("/api/online-tests/{$test->id}/attempt", [], $this->authHeaders($this->student));
        $res->assertStatus(200)->assertJsonStructure(['attempt', 'test']);
    }

    public function test_student_cannot_start_draft_test(): void
    {
        $test = OnlineTest::create(['title' => 'Draft', 'total_time' => 10, 'max_marks' => 5, 'status' => 'draft', 'created_by' => $this->admin->id]);
        $this->postJson("/api/online-tests/{$test->id}/attempt", [], $this->authHeaders($this->student))
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_tests(): void
    {
        $this->getJson('/api/online-tests/published')->assertStatus(401);
    }
}
