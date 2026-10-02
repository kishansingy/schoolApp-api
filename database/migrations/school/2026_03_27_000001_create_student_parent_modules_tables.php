<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Events
        Schema::create('sch_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->date('end_date')->nullable();
            $table->string('venue', 200)->nullable();
            $table->enum('audience', ['all', 'students', 'parents', 'staff'])->default('all');
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('sch_event_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('sch_events')->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video']);
            $table->string('file_path');
            $table->string('caption', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Homework
        Schema::create('sch_homework', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('sch_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sch_sections')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('sch_subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('sch_staff')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('assigned_date');
            $table->date('submission_date');
            $table->string('attachment', 500)->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();
        });

        // 3. YouTube Channel
        Schema::create('sch_youtube_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('youtube_url', 500);
            $table->string('youtube_id', 50)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->enum('audience', ['all', 'students', 'parents', 'staff'])->default('all');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // 4. Remarks & Complaints
        Schema::create('sch_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('sch_staff')->cascadeOnDelete();
            $table->enum('type', ['remark', 'complaint']);
            $table->enum('remark_by', ['teacher', 'parent']);
            $table->text('message');
            $table->enum('visibility', ['parent_only', 'all'])->default('parent_only');
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sch_remarks');
        Schema::dropIfExists('sch_youtube_videos');
        Schema::dropIfExists('sch_homework');
        Schema::dropIfExists('sch_event_media');
        Schema::dropIfExists('sch_events');
    }
};
