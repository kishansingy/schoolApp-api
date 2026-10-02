<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saved message templates
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // payment_reminder, attendance_warning, fee_receipt, general, custom
            $table->text('body');       // message body with {{placeholders}}
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Log of every message sent
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_number');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_type')->nullable(); // student, parent, staff
            $table->unsignedBigInteger('recipient_record_id')->nullable();
            $table->string('template_name')->nullable();
            $table->text('message');
            $table->string('status')->default('sent');   // sent, failed, pending
            $table->string('wa_message_id')->nullable(); // WhatsApp message ID from API
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_templates');
    }
};
