<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expense categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // School expenses (electricity, maintenance, supplies, etc.)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_mode')->default('cash'); // cash, bank, upi, cheque
            $table->string('paid_to')->nullable();           // vendor/person name
            $table->string('reference_no')->nullable();      // cheque/transaction no
            $table->string('status')->default('approved');   // draft, approved
            $table->string('academic_year')->nullable();
            $table->string('month')->nullable();             // YYYY-MM
            $table->timestamps();
        });

        // Staff salary payments
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->unsignedBigInteger('staff_record_id'); // references app_records.id
            $table->string('staff_name');
            $table->string('staff_no')->nullable();
            $table->string('month');                        // YYYY-MM
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->date('payment_date');
            $table->string('payment_mode')->default('bank');
            $table->string('reference_no')->nullable();
            $table->string('status')->default('paid');      // pending, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
