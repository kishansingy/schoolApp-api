<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            // Controls tab
            $table->boolean('extensible')->default(true);
            $table->boolean('live_feed')->default(false);
            $table->boolean('auto_number')->default(false);
            $table->string('auto_number_prefix')->nullable();
            $table->integer('auto_number_base')->default(1000);
            $table->integer('auto_number_padding')->default(7);
            $table->boolean('create_access_controls')->default(false);
            $table->string('user_role')->nullable();
            // Application Access tab
            $table->string('accessible_from')->default('all');
            $table->boolean('can_read')->default(true);
            $table->boolean('can_create')->default(true);
            $table->boolean('can_update')->default(true);
            $table->boolean('can_delete')->default(true);
            $table->boolean('allow_web_services')->default(true);
            $table->boolean('allow_configuration')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('app_tables', function (Blueprint $table) {
            $table->dropColumn([
                'extensible','live_feed','auto_number','auto_number_prefix',
                'auto_number_base','auto_number_padding','create_access_controls',
                'user_role','accessible_from','can_read','can_create','can_update',
                'can_delete','allow_web_services','allow_configuration',
            ]);
        });
    }
};
