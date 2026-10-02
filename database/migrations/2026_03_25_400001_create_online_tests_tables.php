<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tests ─────────────────────────────────────────────────────────────
        Schema::create('online_tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->integer('total_time')->default(60)->comment('minutes');
            $table->integer('max_marks')->default(100);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // ── Sections ──────────────────────────────────────────────────────────
        Schema::create('online_test_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->string('name');
            $table->integer('time_limit')->default(0)->comment('0 = no separate limit');
            $table->decimal('marks_per_question', 5, 2)->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->foreign('test_id')->references('id')->on('online_tests')->onDelete('cascade');
        });

        // ── Questions ─────────────────────────────────────────────────────────
        Schema::create('online_test_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->text('question_text');
            $table->string('question_type')->default('mcq')->comment('mcq, true_false, short');
            $table->text('option_a')->nullable();
            $table->text('option_b')->nullable();
            $table->text('option_c')->nullable();
            $table->text('option_d')->nullable();
            $table->string('correct_answer')->nullable()->comment('a, b, c, d or true/false');
            $table->decimal('marks', 5, 2)->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->foreign('test_id')->references('id')->on('online_tests')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('online_test_sections')->onDelete('set null');
        });

        // ── Attempts ──────────────────────────────────────────────────────────
        Schema::create('online_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('student_id')->comment('users.id');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('current_section_index')->default(0);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('max_marks', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->enum('status', ['in_progress', 'submitted', 'timed_out'])->default('in_progress');
            $table->timestamps();
            $table->foreign('test_id')->references('id')->on('online_tests')->onDelete('cascade');
        });

        // ── Answers ───────────────────────────────────────────────────────────
        Schema::create('online_test_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->string('selected_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('marks_awarded', 5, 2)->default(0);
            $table->timestamps();
            $table->foreign('attempt_id')->references('id')->on('online_test_attempts')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('online_test_questions')->onDelete('cascade');
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_test_answers');
        Schema::dropIfExists('online_test_attempts');
        Schema::dropIfExists('online_test_questions');
        Schema::dropIfExists('online_test_sections');
        Schema::dropIfExists('online_tests');
    }
};
