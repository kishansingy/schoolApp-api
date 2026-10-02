<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('table_dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->foreignId('target_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->string('label')->nullable();
            // JSON: [{ source_field: "class_id", target_field: "class_id" }, ...]
            $table->json('field_map');
            // The target field that links back to the source record (FK)
            $table->string('target_fk_field')->default('student_id');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_dependents');
    }
};
