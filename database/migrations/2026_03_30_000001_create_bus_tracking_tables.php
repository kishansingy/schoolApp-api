<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Buses master list
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "Bus A", "Route 1"
            $table->string('number_plate')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->unsignedBigInteger('driver_user_id')->nullable(); // linked User with role=driver
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Which students are assigned to which bus
        Schema::create('bus_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id');
            $table->unsignedBigInteger('student_record_id'); // app_records id for student
            $table->string('pickup_stop')->nullable();
            $table->timestamps();

            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('cascade');
        });

        // Live location — one row per bus, updated in place
        Schema::create('bus_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id')->unique();
            $table->decimal('latitude',  10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 5, 2)->nullable();   // km/h
            $table->decimal('heading', 5, 2)->nullable(); // degrees
            $table->boolean('is_active')->default(true);  // driver sharing or not
            $table->timestamp('located_at')->nullable();
            $table->timestamps();

            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_locations');
        Schema::dropIfExists('bus_assignments');
        Schema::dropIfExists('buses');
    }
};
