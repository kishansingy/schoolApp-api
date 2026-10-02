<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_staff', function (Blueprint $table) {
            $table->id();
            $table->string('staff_no')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('staff_type', ['teaching', 'non_teaching']);
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('qualification', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sch_staff'); }
};
