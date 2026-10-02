<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['boys', 'girls', 'mixed']);
            $table->integer('capacity')->default(100);
            $table->unsignedBigInteger('warden_staff_id')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sch_hostel_allotments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->foreignId('hostel_id')->constrained('sch_hostels')->cascadeOnDelete();
            $table->string('room_no', 20)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->enum('status', ['active', 'vacated'])->default('active');
            $table->timestamps();
        });

        Schema::create('sch_notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('audience', ['all', 'students', 'parents', 'staff', 'teachers'])->default('all');
            $table->date('publish_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('sch_timetable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sch_sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('sch_subjects')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('sch_staff')->cascadeOnDelete();
            $table->enum('day', ['monday','tuesday','wednesday','thursday','friday','saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_timetable');
        Schema::dropIfExists('sch_notices');
        Schema::dropIfExists('sch_hostel_allotments');
        Schema::dropIfExists('sch_hostels');
    }
};
