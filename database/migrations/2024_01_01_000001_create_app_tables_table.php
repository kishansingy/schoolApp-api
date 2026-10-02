<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. incident
            $table->string('label');          // e.g. Incident
            $table->foreignId('parent_id')->nullable()->constrained('app_tables')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_tables');
    }
};
