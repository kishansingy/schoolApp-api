<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('form_layouts', function (Blueprint $table) {
            $table->unsignedTinyInteger('col_span')->default(2)->after('field_order');
            // 1=full(12), 2=half(6), 3=third(4), 4=quarter(3)
        });
    }
    public function down(): void
    {
        Schema::table('form_layouts', function (Blueprint $table) {
            $table->dropColumn('col_span');
        });
    }
};
