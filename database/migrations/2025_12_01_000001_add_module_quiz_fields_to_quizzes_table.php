<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds module-level quiz support to the quizzes table,
     * enabling Coursera-like quiz functionality where each module can have
     * its own quiz that users must pass before proceeding.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // === MODULE QUIZ SUPPORT ===
            // Link quiz to a specific module (nullable for course-level quizzes)
            $table->unsignedBigInteger('module_id')->nullable()->after('course_online_id');
            
            // Flag to identify module quizzes vs course-level quizzes
            $table->boolean('is_module_quiz')->default(false)->after('module_id');
            
            // Whether passing this quiz is required to unlock the next module
            $table->boolean('required_to_proceed')->default(true)->after('is_module_quiz');
            
            // === ATTEMPT CONFIGURATION ===
            // Maximum number of attempts allowed (admin configurable)
            $table->integer('max_attempts')->default(3)->after('required_to_proceed');
            
            // Hours to wait between retry attempts (0 = no delay)
            $table->integer('retry_delay_hours')->default(0)->after('max_attempts');
            
            // === CORRECT ANSWERS DISPLAY ===
            // When to show correct answers to users
            // Options: 'never', 'after_pass', 'after_max_attempts', 'always'
            $table->enum('show_correct_answers', [
                'never',           // Never show correct answers
                'after_pass',      // Show only after user passes
                'after_max_attempts', // Show after all attempts used
                'always'           // Always show after submission
            ])->default('after_pass')->after('retry_delay_hours');
            
            // === INDEXES ===
            $table->index('module_id', 'quizzes_module_id_index');
            $table->index('is_module_quiz', 'quizzes_is_module_quiz_index');
            
            // === FOREIGN KEY ===
            $table->foreign('module_id')
                  ->references('id')
                  ->on('course_modules')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['module_id']);
            
            // Drop indexes
            $table->dropIndex('quizzes_module_id_index');
            $table->dropIndex('quizzes_is_module_quiz_index');
            
            // Drop columns
            $table->dropColumn([
                'module_id',
                'is_module_quiz',
                'required_to_proceed',
                'max_attempts',
                'retry_delay_hours',
                'show_correct_answers',
            ]);
        });
    }
};