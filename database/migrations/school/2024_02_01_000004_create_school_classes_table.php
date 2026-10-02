<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('sch_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('sch_classes')->cascadeOnDelete();
            $table->string('name', 10);
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->integer('capacity')->default(40);
            $table->timestamps();
        });

        Schema::create('sch_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->enum('type', ['theory', 'practical', 'both'])->default('theory');
            $table->foreignId('class_id')->constrained('sch_classes')->cascadeOnDelete();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_subjects');
        Schema::dropIfExists('sch_sections');
        Schema::dropIfExists('sch_classes');
    }
};
