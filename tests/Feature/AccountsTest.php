<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin   = $this->makeUser('admin');
        $this->teacher = $this->makeUser('teacher');
    }

    // ── Summary & ledger (any authenticated) ─────────────────────────────────

    public function test_authenticated_user_can_get_summary(): void
    {
        $this->getJson('/api/accounts/summary', $this->authHeaders($this->teacher))->assertOk();
    }

    public function test_authenticated_user_can_get_categories(): void
    {
        $this->getJson('/api/accounts/categories', $this->authHeaders($this->teacher))->assertOk();
    }

    public function test_unauthenticated_cannot_access_accounts(): void
    {
        $this->getJson('/api/accounts/summary')->assertStatus(401);
    }

    // ── Categories (admin only) ───────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $res = $this->postJson('/api/accounts/categories', [
            'name' => 'Utilities',
        ], $this->authHeaders($this->admin));

        $res->assertOk()->assertJsonPath('name', 'Utilities');
        $this->assertDatabaseHas('expense_categories', ['name' => 'Utilities']);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $this->postJson('/api/accounts/categories', [
            'name' => 'Hack',
        ], $this->authHeaders($this->teacher))->assertStatus(403);
    }

    public function test_admin_can_delete_category(): void
    {
        $cat = ExpenseCategory::create(['name' => 'Del Cat']);
        $this->deleteJson("/api/accounts/categories/{$cat->id}", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('expense_categories', ['id' => $cat->id]);
    }

    // ── Expenses (admin only) ─────────────────────────────────────────────────

    public function test_admin_can_create_expense(): void
    {
        $cat = ExpenseCategory::create(['name' => 'Rent']);

        $res = $this->postJson('/api/accounts/expenses', [
            'category_id'  => $cat->id,
            'title'        => 'Monthly rent',
            'amount'       => 5000,
            'expense_date' => '2026-03-01',
            'payment_mode' => 'bank',
        ], $this->authHeaders($this->admin));

        $res->assertOk()->assertJsonPath('amount', 5000);
        $this->assertDatabaseHas('expenses', ['amount' => 5000]);
    }

    public function test_non_admin_cannot_create_expense(): void
    {
        $this->postJson('/api/accounts/expenses', [
            'title' => 'x', 'amount' => 100, 'expense_date' => '2026-01-01', 'payment_mode' => 'cash',
        ], $this->authHeaders($this->teacher))->assertStatus(403);
    }

    public function test_admin_can_update_expense(): void
    {
        $cat     = ExpenseCategory::create(['name' => 'Misc']);
        $expense = Expense::create([
            'category_id' => $cat->id, 'title' => 'Old', 'amount' => 100,
            'expense_date' => '2026-01-01', 'payment_mode' => 'cash', 'month' => '2026-01', 'voucher_no' => 'EXP-00001',
        ]);

        $this->putJson("/api/accounts/expenses/{$expense->id}", [
            'category_id' => $cat->id, 'title' => 'Updated', 'amount' => 200,
            'expense_date' => '2026-01-01', 'payment_mode' => 'cash',
        ], $this->authHeaders($this->admin))->assertOk()->assertJsonPath('amount', 200);
    }

    public function test_admin_can_delete_expense(): void
    {
        $cat     = ExpenseCategory::create(['name' => 'Misc']);
        $expense = Expense::create([
            'category_id' => $cat->id, 'title' => 'Del', 'amount' => 50,
            'expense_date' => '2026-01-01', 'payment_mode' => 'cash', 'month' => '2026-01', 'voucher_no' => 'EXP-00002',
        ]);

        $this->deleteJson("/api/accounts/expenses/{$expense->id}", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    // ── Salaries (admin only) ─────────────────────────────────────────────────

    public function test_admin_can_create_salary(): void
    {
        $res = $this->postJson('/api/accounts/salaries', [
            'staff_record_id' => 1,
            'staff_name'      => 'John Doe',
            'basic_salary'    => 30000,
            'month'           => '2026-03',
            'payment_date'    => '2026-03-31',
            'payment_mode'    => 'bank',
        ], $this->authHeaders($this->admin));

        $res->assertOk()->assertJsonPath('basic_salary', 30000);
    }

    public function test_non_admin_cannot_access_salaries(): void
    {
        $this->getJson('/api/accounts/salaries', $this->authHeaders($this->teacher))->assertStatus(403);
    }
}
