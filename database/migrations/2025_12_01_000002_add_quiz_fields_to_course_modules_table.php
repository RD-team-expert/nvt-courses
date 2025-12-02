<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds quiz-related fields to the course_modules table
     * to track whether a module has a quiz and if it's required.
     */
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            // Flag indicating this module has an associated quiz
            $table->boolean('has_quiz')->default(false)->after('is_active');
            
            // Whether the quiz must be passed to proceed to next module
            $table->boolean('quiz_required')->default(true)->after('has_quiz');
            
            // Index for quick filtering of modules with quizzes
            $table->index('has_quiz', 'course_modules_has_quiz_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('course_modules_has_quiz_index');
            
            // Drop columns
            $table->dropColumn([
                'has_quiz',
                'quiz_required',
            ]);
        });
    }
};