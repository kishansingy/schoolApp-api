<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AccountsServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SalaryPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    public function __construct(
        private readonly AccountsServiceInterface $accountsService
    ) {}

    // ── Summary & Ledger ──────────────────────────────────────────────────────

    public function summary(): JsonResponse
    {
        return response()->json($this->accountsService->summary());
    }

    public function ledger(Request $request): JsonResponse
    {
        return response()->json($this->accountsService->ledger($request->year ?? date('Y')));
    }

    public function staffList(): JsonResponse
    {
        return response()->json($this->accountsService->staffList());
    }

    // ── Expense Categories ────────────────────────────────────────────────────

    public function categories(): JsonResponse
    {
        return response()->json(ExpenseCategory::orderBy('name')->get());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string', 'description' => 'nullable|string']);
        return response()->json(ExpenseCategory::create($data), 201);
    }

    public function destroyCategory(ExpenseCategory $category): \Illuminate\Http\Response
    {
        $category->delete();
        return response()->noContent();
    }

    // ── Expenses ──────────────────────────────────────────────────────────────

    public function expenses(Request $request): JsonResponse
    {
        $q = Expense::with('category')->orderBy('expense_date', 'desc');
        if ($request->month)     $q->where('month', $request->month);
        if ($request->category)  $q->where('category_id', $request->category);
        if ($request->status)    $q->where('status', $request->status);
        if ($request->date_from) $q->where('expense_date', '>=', $request->date_from);
        if ($request->date_to)   $q->where('expense_date', '<=', $request->date_to);
        return response()->json($q->get());
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id'   => 'nullable|exists:expense_categories,id',
            'title'         => 'required|string',
            'description'   => 'nullable|string',
            'amount'        => 'required|numeric|min:0',
            'expense_date'  => 'required|date',
            'payment_mode'  => 'required|in:cash,bank,upi,cheque',
            'paid_to'       => 'nullable|string',
            'reference_no'  => 'nullable|string',
            'status'        => 'in:draft,approved',
            'academic_year' => 'nullable|string',
        ]);
        $data['month']      = substr($data['expense_date'], 0, 7);
        $data['voucher_no'] = $this->accountsService->nextVoucher('EXP');
        return response()->json(Expense::create($data), 201);
    }

    public function updateExpense(Request $request, Expense $expense): JsonResponse
    {
        $data = $request->validate([
            'category_id'   => 'nullable|exists:expense_categories,id',
            'title'         => 'required|string',
            'description'   => 'nullable|string',
            'amount'        => 'required|numeric|min:0',
            'expense_date'  => 'required|date',
            'payment_mode'  => 'required|in:cash,bank,upi,cheque',
            'paid_to'       => 'nullable|string',
            'reference_no'  => 'nullable|string',
            'status'        => 'in:draft,approved',
            'academic_year' => 'nullable|string',
        ]);
        $data['month'] = substr($data['expense_date'], 0, 7);
        $expense->update($data);
        return response()->json($expense->load('category'));
    }

    public function destroyExpense(Expense $expense): \Illuminate\Http\Response
    {
        $expense->delete();
        return response()->noContent();
    }

    // ── Salary Payments ───────────────────────────────────────────────────────

    public function salaries(Request $request): JsonResponse
    {
        $q = SalaryPayment::orderBy('payment_date', 'desc');
        if ($request->month)  $q->where('month', $request->month);
        if ($request->status) $q->where('status', $request->status);
        return response()->json($q->get());
    }

    public function storeSalary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'staff_record_id' => 'required|integer',
            'staff_name'      => 'required|string',
            'staff_no'        => 'nullable|string',
            'month'           => 'required|string',
            'basic_salary'    => 'required|numeric|min:0',
            'allowances'      => 'nullable|numeric|min:0',
            'deductions'      => 'nullable|numeric|min:0',
            'payment_date'    => 'required|date',
            'payment_mode'    => 'required|in:cash,bank,upi,cheque',
            'reference_no'    => 'nullable|string',
            'status'          => 'in:pending,paid',
        ]);
        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['basic_salary'] + $data['allowances'] - $data['deductions'];
        $data['voucher_no'] = $this->accountsService->nextVoucher('SAL');
        return response()->json(SalaryPayment::create($data), 201);
    }

    public function updateSalary(Request $request, SalaryPayment $salary): JsonResponse
    {
        $data = $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'allowances'   => 'nullable|numeric|min:0',
            'deductions'   => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:cash,bank,upi,cheque',
            'reference_no' => 'nullable|string',
            'status'       => 'in:pending,paid',
        ]);
        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['basic_salary'] + $data['allowances'] - $data['deductions'];
        $salary->update($data);
        return response()->json($salary);
    }

    public function destroySalary(SalaryPayment $salary): \Illuminate\Http\Response
    {
        $salary->delete();
        return response()->noContent();
    }
}
