<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->string('auto_number_prefix')->nullable()->after('calculated_value');
            $table->string('auto_number_suffix')->nullable()->after('auto_number_prefix');
            $table->integer('auto_number_padding')->nullable()->after('auto_number_suffix');
            $table->integer('auto_number_base')->nullable()->after('auto_number_padding');
        });
    }

    public function down(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->dropColumn(['auto_number_prefix','auto_number_suffix','auto_number_padding','auto_number_base']);
        });
    }
};
