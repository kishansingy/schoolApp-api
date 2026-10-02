<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->boolean('whatsapp_broadcast')->default(false)->after('allow_configuration');
            $table->string('whatsapp_phone_field')->nullable()->after('whatsapp_broadcast');
        });
    }

    public function down(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_broadcast', 'whatsapp_phone_field']);
        });
    }
};
