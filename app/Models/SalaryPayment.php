<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'voucher_no', 'staff_record_id', 'staff_name', 'staff_no',
        'month', 'basic_salary', 'allowances', 'deductions', 'net_salary',
        'payment_date', 'payment_mode', 'reference_no', 'status',
    ];
}
