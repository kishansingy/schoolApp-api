<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            // standard | header | detail | footer
            $table->string('table_type')->default('standard')->after('label');
            // For detail/footer tables: which header table they belong to
            $table->foreignId('detail_of_table_id')
                  ->nullable()
                  ->after('table_type')
                  ->constrained('app_tables')
                  ->nullOnDelete();
        });

        // detail records need to reference their parent header record
        Schema::table('app_records', function (Blueprint $table) {
            $table->foreignId('parent_record_id')
                  ->nullable()
                  ->after('app_table_id')
                  ->constrained('app_records')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_records', function (Blueprint $table) {
            $table->dropForeign(['parent_record_id']);
            $table->dropColumn('parent_record_id');
        });
        Schema::table('app_tables', function (Blueprint $table) {
            $table->dropForeign(['detail_of_table_id']);
            $table->dropColumn(['table_type', 'detail_of_table_id']);
        });
    }
};
