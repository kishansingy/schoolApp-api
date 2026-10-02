<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->string('name');           // column key e.g. short_description
            $table->string('label');          // display label
            $table->string('type')->default('string'); // string, integer, boolean, text, date, datetime, reference
            $table->foreignId('reference_table_id')->nullable()->constrained('app_tables')->nullOnDelete();
            $table->boolean('mandatory')->default(false);
            $table->boolean('readonly')->default(false);
            $table->string('default_value')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['app_table_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_fields');
    }
};
