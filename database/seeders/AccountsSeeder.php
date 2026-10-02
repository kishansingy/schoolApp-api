<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SalaryPayment;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class AccountsSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $cats = [
            ['name' => 'Utilities',       'description' => 'Electricity, water, internet'],
            ['name' => 'Maintenance',     'description' => 'Building and equipment repairs'],
            ['name' => 'Stationery',      'description' => 'Office and classroom supplies'],
            ['name' => 'Events',          'description' => 'School events and functions'],
            ['name' => 'Transport',       'description' => 'Vehicle fuel and maintenance'],
            ['name' => 'Miscellaneous',   'description' => 'Other expenses'],
        ];
        foreach ($cats as $c) ExpenseCategory::firstOrCreate(['name' => $c['name']], $c);

        $catMap = ExpenseCategory::pluck('id', 'name');

        // Sample expenses for last 3 months
        $expenses = [
            ['category_id' => $catMap['Utilities'],    'title' => 'Electricity Bill - Jan', 'amount' => 12500, 'expense_date' => '2026-01-05', 'payment_mode' => 'bank',  'paid_to' => 'BESCOM',         'status' => 'approved'],
            ['category_id' => $catMap['Utilities'],    'title' => 'Internet Bill - Jan',    'amount' => 2500,  'expense_date' => '2026-01-10', 'payment_mode' => 'upi',   'paid_to' => 'Airtel',         'status' => 'approved'],
            ['category_id' => $catMap['Maintenance'],  'title' => 'Classroom Painting',     'amount' => 18000, 'expense_date' => '2026-01-15', 'payment_mode' => 'cheque','paid_to' => 'Ravi Painters',  'status' => 'approved'],
            ['category_id' => $catMap['Stationery'],   'title' => 'Exam Answer Sheets',     'amount' => 4500,  'expense_date' => '2026-01-20', 'payment_mode' => 'cash',  'paid_to' => 'Paper Mart',     'status' => 'approved'],
            ['category_id' => $catMap['Utilities'],    'title' => 'Electricity Bill - Feb', 'amount' => 11800, 'expense_date' => '2026-02-05', 'payment_mode' => 'bank',  'paid_to' => 'BESCOM',         'status' => 'approved'],
            ['category_id' => $catMap['Events'],       'title' => 'Annual Day Decoration',  'amount' => 25000, 'expense_date' => '2026-02-14', 'payment_mode' => 'cash',  'paid_to' => 'Event Supplies', 'status' => 'approved'],
            ['category_id' => $catMap['Transport'],    'title' => 'Bus Fuel - Feb',         'amount' => 8500,  'expense_date' => '2026-02-28', 'payment_mode' => 'cash',  'paid_to' => 'HP Petrol Bunk', 'status' => 'approved'],
            ['category_id' => $catMap['Utilities'],    'title' => 'Electricity Bill - Mar', 'amount' => 13200, 'expense_date' => '2026-03-05', 'payment_mode' => 'bank',  'paid_to' => 'BESCOM',         'status' => 'approved'],
            ['category_id' => $catMap['Maintenance'],  'title' => 'Projector Repair',       'amount' => 3500,  'expense_date' => '2026-03-10', 'payment_mode' => 'cash',  'paid_to' => 'Tech Service',   'status' => 'approved'],
            ['category_id' => $catMap['Miscellaneous'],'title' => 'First Aid Supplies',     'amount' => 1200,  'expense_date' => '2026-03-15', 'payment_mode' => 'cash',  'paid_to' => 'Medical Store',  'status' => 'approved'],
        ];

        $vNum = 1;
        foreach ($expenses as $e) {
            $e['voucher_no']    = 'EXP-' . str_pad($vNum++, 5, '0', STR_PAD_LEFT);
            $e['month']         = substr($e['expense_date'], 0, 7);
            $e['academic_year'] = '2025-2026';
            Expense::firstOrCreate(['voucher_no' => $e['voucher_no']], $e);
        }

        // Salary payments — pull from staff table
        $staffTable = AppTable::where('name', 'staff')->first();
        $staffList  = $staffTable
            ? AppRecord::where('app_table_id', $staffTable->id)->get()->map(fn($r) => [
                'id'       => $r->id,
                'name'     => trim(($r->data['first_name'] ?? '') . ' ' . ($r->data['last_name'] ?? '')),
                'staff_no' => $r->data['staff_no'] ?? '',
                'salary'   => (float)($r->data['salary'] ?? 30000),
            ])->toArray()
            : [];

        $sNum = 1;
        foreach (['2026-01', '2026-02', '2026-03'] as $month) {
            foreach ($staffList as $staff) {
                $basic      = $staff['salary'];
                $allowances = round($basic * 0.1);
                $deductions = round($basic * 0.05);
                SalaryPayment::firstOrCreate(
                    ['voucher_no' => 'SAL-' . str_pad($sNum, 5, '0', STR_PAD_LEFT)],
                    [
                        'voucher_no'      => 'SAL-' . str_pad($sNum, 5, '0', STR_PAD_LEFT),
                        'staff_record_id' => $staff['id'],
                        'staff_name'      => $staff['name'],
                        'staff_no'        => $staff['staff_no'],
                        'month'           => $month,
                        'basic_salary'    => $basic,
                        'allowances'      => $allowances,
                        'deductions'      => $deductions,
                        'net_salary'      => $basic + $allowances - $deductions,
                        'payment_date'    => $month . '-28',
                        'payment_mode'    => 'bank',
                        'status'          => 'paid',
                    ]
                );
                $sNum++;
            }
        }

        $this->command->info('Accounts seeded.');
    }
}
