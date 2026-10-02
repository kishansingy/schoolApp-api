<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_exams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('academic_year', 20);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed'])->default('upcoming');
            $table->timestamps();
        });

        Schema::create('sch_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('sch_exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('sch_subjects')->cascadeOnDelete();
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->decimal('max_marks', 6, 2)->default(100);
            $table->string('grade', 5)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'student_id', 'subject_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_marks');
        Schema::dropIfExists('sch_exams');
    }
};
