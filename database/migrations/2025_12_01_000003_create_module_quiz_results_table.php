<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates the module_quiz_results table to track
     * user quiz results at the module level. This provides a quick way
     * to check if a user has passed a module's quiz without querying
     * the quiz_attempts table.
     */
    public function up(): void
    {
        Schema::create('module_quiz_results', function (Blueprint $table) {
            $table->id();
            
            // User who took the quiz
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // Module the quiz belongs to
            $table->foreignId('module_id')
                  ->constrained('course_modules')
                  ->onDelete('cascade');
            
            // The quiz that was taken
            $table->foreignId('quiz_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // Link to the specific attempt
            $table->foreignId('quiz_attempt_id')
                  ->constrained('quiz_attempts')
                  ->onDelete('cascade');
            
            // Whether the user passed this attempt
            $table->boolean('passed')->default(false);
            
            // Score as percentage (0.00 - 100.00)
            $table->decimal('score_percentage', 5, 2)->default(0);
            
            // Points earned in this attempt
            $table->integer('points_earned')->default(0);
            
            // Total possible points
            $table->integer('total_points')->default(0);
            
            // When the quiz was completed
            $table->timestamp('completed_at')->nullable();
            
            // Time taken to complete (in seconds)
            $table->integer('time_taken_seconds')->nullable();
            
            $table->timestamps();
            
            // === INDEXES ===
            // Unique constraint: one result per user per module per attempt
            $table->unique(['user_id', 'module_id', 'quiz_attempt_id'], 'module_quiz_results_unique');
            
            // Index for quick lookup of user's module quiz status
            $table->index(['user_id', 'module_id', 'passed'], 'module_quiz_results_user_module_passed');
            
            // Index for finding all results for a module
            $table->index(['module_id', 'passed'], 'module_quiz_results_module_passed');
            
            // Index for analytics queries
            $table->index(['quiz_id', 'passed'], 'module_quiz_results_quiz_passed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_quiz_results');
    }
};