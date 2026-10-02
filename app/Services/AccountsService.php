<?php

namespace App\Services;

use App\Contracts\AccountsServiceInterface;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\SalaryPayment;
use Illuminate\Support\Facades\DB;

class AccountsService implements AccountsServiceInterface
{
    public function summary(): array
    {
        $currentMonth = date('Y-m');

        $feeTable = AppTable::where('name', 'fee_payments')->value('id');
        $feeThisMonth = $feeTable ? (float) DB::selectOne("
            SELECT COALESCE(SUM(JSON_EXTRACT(data,'$.amount_paid')),0) AS total
            FROM app_records
            WHERE app_table_id = ?
            AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'paid'
            AND DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')),'%Y-%m') = ?
        ", [$feeTable, $currentMonth])->total : 0;

        $salaryThisMonth = (float) DB::selectOne(
            "SELECT COALESCE(SUM(net_salary),0) AS total FROM salary_payments WHERE status='paid' AND month=?",
            [$currentMonth]
        )->total;

        $expensesThisMonth = (float) DB::selectOne(
            "SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE status='approved' AND month=?",
            [$currentMonth]
        )->total;

        return [
            'fee_income_this_month'  => $feeThisMonth,
            'salary_this_month'      => $salaryThisMonth,
            'expenses_this_month'    => $expensesThisMonth,
            'net_this_month'         => $feeThisMonth - $salaryThisMonth - $expensesThisMonth,
            'pending_salaries'       => SalaryPayment::where('status', 'pending')->count(),
        ];
    }

    public function ledger(string $year): array
    {
        $feeTable = AppTable::where('name', 'fee_payments')->value('id');

        $feeRows = $feeTable ? DB::select("
            SELECT DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')),'%Y-%m') AS month,
                   SUM(JSON_EXTRACT(data,'$.amount_paid')) AS income
            FROM app_records
            WHERE app_table_id = ?
              AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'paid'
              AND DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')),'%Y') = ?
            GROUP BY month
        ", [$feeTable, $year]) : [];

        $salaryRows  = DB::select("SELECT month, SUM(net_salary) AS salary_total FROM salary_payments WHERE status='paid' AND month LIKE ? GROUP BY month", ["{$year}-%"]);
        $expenseRows = DB::select("SELECT month, SUM(amount) AS expense_total FROM expenses WHERE status='approved' AND month LIKE ? GROUP BY month", ["{$year}-%"]);

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $months[$key] = ['month' => $key, 'fee_income' => 0, 'salary_total' => 0, 'other_expenses' => 0, 'total_expenses' => 0, 'net' => 0];
        }

        foreach ($feeRows    as $r) { if (isset($months[$r->month])) $months[$r->month]['fee_income']     = (float) $r->income; }
        foreach ($salaryRows  as $r) { if (isset($months[$r->month])) $months[$r->month]['salary_total']   = (float) $r->salary_total; }
        foreach ($expenseRows as $r) { if (isset($months[$r->month])) $months[$r->month]['other_expenses'] = (float) $r->expense_total; }

        foreach ($months as &$row) {
            $row['total_expenses'] = $row['salary_total'] + $row['other_expenses'];
            $row['net']            = $row['fee_income'] - $row['total_expenses'];
        }

        $totals = array_reduce(array_values($months), function ($carry, $row) {
            foreach (['fee_income', 'salary_total', 'other_expenses', 'total_expenses', 'net'] as $k) {
                $carry[$k] = ($carry[$k] ?? 0) + $row[$k];
            }
            return $carry;
        }, []);
        $totals['month'] = 'TOTAL';

        return ['rows' => array_values($months), 'totals' => $totals];
    }

    public function staffList(): array
    {
        $table = AppTable::where('name', 'staff')->first();
        if (!$table) return [];

        return AppRecord::where('app_table_id', $table->id)
            ->where(fn($q) => $q->whereNull('data->status')
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'active'"))
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'staff_no'    => $r->data['staff_no']    ?? '',
                'name'        => trim(($r->data['first_name'] ?? '') . ' ' . ($r->data['last_name'] ?? '')),
                'designation' => $r->data['designation'] ?? '',
                'salary'      => $r->data['salary']      ?? 0,
            ])
            ->toArray();
    }

    public function nextVoucher(string $prefix): string
    {
        $table = $prefix === 'EXP' ? 'expenses' : 'salary_payments';
        $last  = DB::table($table)->where('voucher_no', 'like', "{$prefix}-%")->max('voucher_no');
        $num   = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;
        return $prefix . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
