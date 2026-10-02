<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_menus', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('icon')->default('📄');
            $table->integer('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('app_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('app_menus')->cascadeOnDelete();
            $table->string('label');
            $table->string('icon')->default('📄');
            $table->unsignedBigInteger('app_table_id')->nullable();
            $table->string('custom_url')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_menu_items');
        Schema::dropIfExists('app_menus');
    }
};
