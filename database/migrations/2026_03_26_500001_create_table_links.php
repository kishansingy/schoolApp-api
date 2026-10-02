<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Link configuration (admin sets this up once) ──────────────────────
        Schema::create('table_links', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. "Sales Order → Invoice"
            $table->foreignId('source_table_id')->constrained('app_tables')->cascadeOnDelete();
            $table->foreignId('target_table_id')->constrained('app_tables')->cascadeOnDelete();

            // Header field map: [{from, to}] — copied once per pull
            $table->json('header_field_map')->nullable();

            // Line/detail field map: [{from, to}] — copied per line item
            $table->json('line_field_map')->nullable();

            // Source detail table (e.g. order_items) — null = no line items
            $table->foreignId('source_detail_table_id')->nullable()->constrained('app_tables')->nullOnDelete();
            // Target detail table (e.g. invoice_items)
            $table->foreignId('target_detail_table_id')->nullable()->constrained('app_tables')->nullOnDelete();

            // Quantity tracking
            $table->string('qty_field')->nullable();         // field name holding quantity, e.g. "quantity"
            $table->boolean('track_qty')->default(false);    // enable partial qty tracking

            // Status stamping on source after full transfer
            $table->string('source_status_field')->nullable();   // e.g. "status"
            $table->string('source_status_done_value')->nullable(); // e.g. "invoiced"
            $table->string('source_status_partial_value')->nullable(); // e.g. "partial"

            // Which source field value means "eligible" (e.g. status=confirmed)
            $table->string('source_filter_field')->nullable();
            $table->string('source_filter_value')->nullable();

            $table->string('pull_button_label')->default('Pull to Invoice');
            $table->boolean('allow_multi_select')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ── Queue: one row per source record (or per line item if track_qty) ──
        Schema::create('table_link_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_link_id')->constrained('table_links')->cascadeOnDelete();
            $table->foreignId('source_record_id')->constrained('app_records')->cascadeOnDelete();
            // For line-level tracking: which detail record
            $table->unsignedBigInteger('source_detail_record_id')->nullable();

            // Quantity tracking
            $table->decimal('qty_original', 12, 4)->default(0);
            $table->decimal('qty_transferred', 12, 4)->default(0);
            $table->decimal('qty_pending', 12, 4)->default(0);

            // Status: pending | partial | done
            $table->string('status')->default('pending');

            // Which target record consumed this (last pull)
            $table->unsignedBigInteger('last_target_record_id')->nullable();

            $table->timestamps();

            $table->index(['table_link_id', 'status']);
            $table->index(['source_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_link_queue');
        Schema::dropIfExists('table_links');
    }
};
