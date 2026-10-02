<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Who can initiate chat with whom (role-to-role config)
        Schema::create('sch_chat_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('from_role', 50); // e.g. teacher
            $table->string('to_role', 50);   // e.g. parent
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['from_role', 'to_role']);
        });

        // One conversation between two users
        Schema::create('sch_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_one_id');
            $table->unsignedBigInteger('user_two_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->unique(['user_one_id', 'user_two_id']);
            $table->foreign('user_one_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_two_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Messages within a conversation
        Schema::create('sch_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                  ->constrained('sch_chat_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sch_chat_messages');
        Schema::dropIfExists('sch_chat_conversations');
        Schema::dropIfExists('sch_chat_permissions');
    }
};
