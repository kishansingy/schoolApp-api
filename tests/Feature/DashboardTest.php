<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $user = $this->makeUser('admin');
        $this->getJson('/api/dashboard', $this->authHeaders($user))->assertOk();
    }

    public function test_teacher_can_access_dashboard(): void
    {
        $user = $this->makeUser('teacher');
        $this->getJson('/api/dashboard', $this->authHeaders($user))->assertOk();
    }

    public function test_student_can_access_dashboard(): void
    {
        $user = $this->makeUser('student');
        $this->getJson('/api/dashboard', $this->authHeaders($user))->assertOk();
    }

    public function test_parent_can_access_dashboard(): void
    {
        $user = $this->makeUser('parent');
        $this->getJson('/api/dashboard', $this->authHeaders($user))->assertOk();
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_dashboard_returns_role_specific_data(): void
    {
        $admin = $this->makeUser('admin');
        $res   = $this->getJson('/api/dashboard', $this->authHeaders($admin));
        $res->assertOk()->assertJsonStructure(['role']);
        $this->assertEquals('admin', $res->json('role'));
    }
}
