<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sch_live_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->string('class_name')->nullable();
            $table->string('subject')->nullable();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['scheduled', 'live', 'ended'])->default('scheduled');
            $table->string('room_code', 64)->unique();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('sch_live_session_signals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->string('type'); // offer, answer, ice-candidate
            $table->longText('payload');
            $table->boolean('consumed')->default(false);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sch_live_sessions')->onDelete('cascade');
            $table->index(['session_id', 'to_user_id', 'consumed']);
        });

        Schema::create('sch_live_session_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sch_live_sessions')->onDelete('cascade');
            $table->index(['session_id', 'created_at']);
        });

        Schema::create('sch_live_session_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sch_live_sessions')->onDelete('cascade');
            $table->unique(['session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sch_live_session_participants');
        Schema::dropIfExists('sch_live_session_messages');
        Schema::dropIfExists('sch_live_session_signals');
        Schema::dropIfExists('sch_live_sessions');
    }
};
