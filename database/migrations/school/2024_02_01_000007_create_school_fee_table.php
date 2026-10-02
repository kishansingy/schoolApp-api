<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('sch_classes')->cascadeOnDelete();
            $table->string('fee_type', 100);
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['monthly', 'quarterly', 'annually', 'one_time'])->default('monthly');
            $table->string('academic_year', 20);
            $table->timestamps();
        });

        Schema::create('sch_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained('sch_fee_structures')->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('fine', 10, 2)->default(0);
            $table->date('payment_date');
            $table->string('receipt_no')->unique();
            $table->enum('payment_mode', ['cash', 'cheque', 'online', 'bank_transfer'])->default('cash');
            $table->enum('status', ['paid', 'partial', 'pending'])->default('paid');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_fee_payments');
        Schema::dropIfExists('sch_fee_structures');
    }
};
