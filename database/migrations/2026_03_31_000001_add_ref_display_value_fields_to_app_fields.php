<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            // Which field from the reference table to show as the label in the picker
            $table->string('ref_display_field')->nullable()->after('reference_qualifier');
            // Which field from the reference table to store as the saved value (default: id)
            $table->string('ref_value_field')->nullable()->after('ref_display_field');
        });
    }

    public function down(): void
    {
        Schema::table('app_fields', function (Blueprint $table) {
            $table->dropColumn(['ref_display_field', 'ref_value_field']);
        });
    }
};
