<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            // The target table this record can be "promoted" into
            $table->unsignedBigInteger('promote_to_table_id')->nullable()->after('parent_id');
            // JSON field map: [{ "from": "source_field", "to": "target_field" }, ...]
            $table->json('promote_field_map')->nullable()->after('promote_to_table_id');
            // Label shown on the promote button, e.g. "Confirm Admission"
            $table->string('promote_label')->nullable()->after('promote_field_map');
            // Which field+value marks a record as already promoted, e.g. status=confirmed
            $table->string('promote_status_field')->nullable()->after('promote_label');
            $table->string('promote_status_value')->nullable()->after('promote_status_field');
        });
    }

    public function down(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->dropColumn([
                'promote_to_table_id',
                'promote_field_map',
                'promote_label',
                'promote_status_field',
                'promote_status_value',
            ]);
        });
    }
};
