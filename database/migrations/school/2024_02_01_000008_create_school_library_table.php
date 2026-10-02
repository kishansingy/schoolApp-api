<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author', 150)->nullable();
            $table->string('isbn', 30)->nullable()->unique();
            $table->string('publisher', 150)->nullable();
            $table->year('publish_year')->nullable();
            $table->string('category', 100)->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->timestamps();
        });

        Schema::create('sch_book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('sch_books')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('sch_students')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('sch_staff')->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->enum('status', ['issued', 'returned', 'overdue'])->default('issued');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sch_book_issues');
        Schema::dropIfExists('sch_books');
    }
};
