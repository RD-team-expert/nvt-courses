<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_content_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('content_id')->constrained('module_content')->onDelete('cascade');
            $table->foreignId('course_online_id')->constrained('course_online')->onDelete('cascade');
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->foreignId('video_id')->nullable()->constrained('videos')->onDelete('cascade')->comment('For backward compatibility');
            $table->enum('content_type', ['video', 'pdf'])->default('video');

            // Progress tracking fields
            $table->integer('watch_time')->default(0)->comment('Video watch time in seconds');
            $table->integer('total_duration')->nullable()->comment('Video total duration in seconds');
            $table->integer('pdf_pages_viewed')->default(0)->comment('PDF pages viewed');
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->decimal('playback_position', 8, 2)->default(0.00)->comment('Video resume position in seconds');
            $table->boolean('task_completed')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'course_online_id']);
            $table->index(['content_id', 'user_id']);
            $table->index(['module_id', 'user_id']);
            $table->index(['is_completed', 'task_completed']);
            $table->index('video_id'); // For backward compatibility
            $table->unique(['user_id', 'content_id'], 'unique_user_content_progress');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_content_progress');
    }
};
