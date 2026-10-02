<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_parents', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('relation', ['father', 'mother', 'guardian']);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('sch_student_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('sch_parents')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'parent_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_student_parent');
        Schema::dropIfExists('sch_parents');
    }
};
