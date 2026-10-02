<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Stores form sections and field ordering per table
        Schema::create('form_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->string('section_name')->default('Default');
            $table->integer('section_order')->default(0);
            $table->foreignId('app_field_id')->constrained('app_fields')->cascadeOnDelete();
            $table->integer('field_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_layouts');
    }
};
