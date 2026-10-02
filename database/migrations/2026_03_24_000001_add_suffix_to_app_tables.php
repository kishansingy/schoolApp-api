<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->string('auto_number_suffix')->nullable()->after('auto_number_padding');
            $table->string('auto_number_field')->nullable()->after('auto_number_suffix'); // which field to fill
        });
    }

    public function down(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->dropColumn(['auto_number_suffix', 'auto_number_field']);
        });
    }
};
