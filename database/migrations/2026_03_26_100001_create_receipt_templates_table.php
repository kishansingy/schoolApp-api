<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('app_table_id')->nullable(); // linked table
            $table->text('html_template');   // HTML with {{field_name}} placeholders
            $table->string('paper_size')->default('A4');  // A4, A5, Letter
            $table->string('orientation')->default('portrait'); // portrait, landscape
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_templates');
    }
};
