<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            // The approved template name in Meta (e.g. fee_payment_reminder)
            $table->string('meta_template_name')->nullable()->after('body');
            // Language code for the Meta template (default en_US)
            $table->string('meta_lang_code')->default('en_US')->after('meta_template_name');
            // JSON map: which local placeholder keys map to which positional Meta param index
            // e.g. {"1": "amount", "2": "name", "3": "due_date"}
            $table->json('meta_params_map')->nullable()->after('meta_lang_code');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_template_name', 'meta_lang_code', 'meta_params_map']);
        });
    }
};
