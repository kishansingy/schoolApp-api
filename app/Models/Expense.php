<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'voucher_no', 'category_id', 'title', 'description',
        'amount', 'expense_date', 'payment_mode', 'paid_to',
        'reference_no', 'status', 'academic_year', 'month',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
