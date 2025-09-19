<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('capacity')->default(20);
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('notes')->nullable(); // Optional admin notes
            $table->timestamps();

            // Add indexes for performance
            $table->index(['course_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // Add check constraint using raw SQL (works for MySQL and PostgreSQL)
        DB::statement('ALTER TABLE course_availabilities ADD CONSTRAINT check_date_order CHECK (start_date < end_date)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the check constraint first
        DB::statement('ALTER TABLE course_availabilities DROP CONSTRAINT IF EXISTS check_date_order');

        Schema::dropIfExists('course_availabilities');
    }
};
