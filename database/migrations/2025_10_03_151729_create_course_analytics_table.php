<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_online_id')->constrained('course_online')->onDelete('cascade');

            // Basic enrollment metrics
            $table->integer('total_enrollments')->default(0);
            $table->integer('active_learners')->default(0);
            $table->integer('completed_learners')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0.00)->comment('Percentage');
            $table->decimal('dropout_rate', 5, 2)->default(0.00)->comment('Percentage');

            // Time-based metrics
            $table->integer('average_completion_time_hours')->nullable()->comment('Average hours to complete');
            $table->integer('average_session_duration_minutes')->default(0)->comment('Average session length');
            $table->integer('total_learning_hours')->default(0)->comment('Combined learning time');

            // Content engagement metrics
            $table->decimal('average_video_completion_rate', 5, 2)->default(0.00)->comment('Avg video completion %');
            $table->foreignId('most_skipped_content_id')->nullable()->constrained('module_content')->onDelete('set null');
            $table->foreignId('most_replayed_content_id')->nullable()->constrained('module_content')->onDelete('set null');

            // Assessment metrics
            $table->decimal('average_task_score', 5, 2)->nullable()->comment('Average task score');
            $table->integer('cheating_incidents_count')->default(0)->comment('Suspicious activity count');

            // Maintenance
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique('course_online_id');
            $table->index('completion_rate');
            $table->index('last_calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_analytics');
    }
};
