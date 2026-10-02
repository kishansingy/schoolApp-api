<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('General');
            $table->text('description')->nullable();
            $table->string('icon')->default('📊');
            $table->enum('query_type', ['sql', 'table'])->default('sql');
            $table->text('sql_query')->nullable();          // raw SQL with :param placeholders
            $table->string('table_name')->nullable();       // for table-based reports
            $table->json('filters')->nullable();            // filter definitions
            $table->json('columns')->nullable();            // column definitions
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
    }
};
