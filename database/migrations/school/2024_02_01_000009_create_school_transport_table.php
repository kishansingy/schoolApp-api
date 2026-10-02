<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 100);
            $table->string('route_no', 20)->unique();
            $table->text('stops')->nullable();
            $table->unsignedBigInteger('driver_staff_id')->nullable();
            $table->string('vehicle_no', 20)->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->integer('capacity')->default(40);
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sch_student_transport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('sch_students')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('sch_transport_routes')->cascadeOnDelete();
            $table->string('pickup_stop', 100)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_student_transport');
        Schema::dropIfExists('sch_transport_routes');
    }
};
