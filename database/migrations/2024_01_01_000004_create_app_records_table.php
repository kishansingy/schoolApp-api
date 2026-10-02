<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Stores all dynamic records as JSON data
        Schema::create('app_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->json('data'); // { field_name: value, ... }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_records');
    }
};
