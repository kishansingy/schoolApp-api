<?php

use App\Models\AppTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add missing columns to sch_marks ───────────────────────────────
        Schema::table('sch_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('sch_marks', 'class_id'))
                $table->unsignedBigInteger('class_id')->nullable()->after('subject_id');
            if (!Schema::hasColumn('sch_marks', 'section_id'))
                $table->unsignedBigInteger('section_id')->nullable()->after('class_id');
            if (!Schema::hasColumn('sch_marks', 'written_date'))
                $table->date('written_date')->nullable()->after('section_id');
            if (!Schema::hasColumn('sch_marks', 'percentage'))
                $table->decimal('percentage', 5, 2)->nullable()->after('marks_obtained');
        });

        // ── 2. Create sch_exam_marks (header) ─────────────────────────────────
        if (!Schema::hasTable('sch_exam_marks')) {
            Schema::create('sch_exam_marks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_row_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->unsignedBigInteger('exam_id')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->string('academic_year', 20)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // ── 3. Create sch_exam_mark_details (detail) ──────────────────────────
        if (!Schema::hasTable('sch_exam_mark_details')) {
            Schema::create('sch_exam_mark_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_row_id')->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->date('written_date')->nullable();
                $table->decimal('max_marks', 6, 2)->default(100);
                $table->decimal('marks_obtained', 6, 2)->nullable();
                $table->decimal('percentage', 5, 2)->nullable();
                $table->string('grade', 5)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // ── 4. Link schema_table on app_tables ────────────────────────────────
        AppTable::where('name', 'marks')->update(['schema_table' => 'sch_marks']);
        AppTable::where('name', 'exam_marks')->update(['schema_table' => 'sch_exam_marks']);
        AppTable::where('name', 'exam_mark_details')->update(['schema_table' => 'sch_exam_mark_details']);
    }

    public function down(): void
    {
        Schema::dropIfExists('sch_exam_mark_details');
        Schema::dropIfExists('sch_exam_marks');

        Schema::table('sch_marks', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['class_id', 'section_id', 'written_date', 'percentage'],
                fn($col) => Schema::hasColumn('sch_marks', $col)
            ));
        });

        AppTable::whereIn('name', ['marks', 'exam_marks', 'exam_mark_details'])
            ->update(['schema_table' => null]);
    }
};
