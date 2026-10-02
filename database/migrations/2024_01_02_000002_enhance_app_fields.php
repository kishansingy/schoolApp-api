<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->boolean('active')->default(true);
            $table->boolean('display')->default(false);       // display value for reference
            $table->boolean('function_field')->default(false);
            $table->integer('max_length')->nullable();        // for string fields
            $table->text('attributes')->nullable();           // key=value pairs
            // Choice List
            $table->json('choices')->nullable();              // [{label, value}]
            // Dependent Field
            $table->foreignId('dependent_field_id')->nullable()->constrained('app_fields')->nullOnDelete();
            $table->string('dependent_value')->nullable();
            // Calculated Value
            $table->boolean('calculated')->default(false);
            $table->text('calculated_value')->nullable();     // formula/expression
            // Reference Specification
            $table->string('reference_qualifier')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->dropForeign(['dependent_field_id']);
            $table->dropColumn([
                'active','display','function_field','max_length','attributes',
                'choices','dependent_field_id','dependent_value',
                'calculated','calculated_value','reference_qualifier',
            ]);
        });
    }
};
