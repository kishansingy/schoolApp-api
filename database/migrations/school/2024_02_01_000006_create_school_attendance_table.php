<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'holiday'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'date']);
        });

        Schema::create('sch_staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('sch_staff')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'leave'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'date']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_staff_attendance');
        Schema::dropIfExists('sch_student_attendance');
    }
};
