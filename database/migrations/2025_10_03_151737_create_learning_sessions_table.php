<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_online_id')->constrained('course_online')->onDelete('cascade');
            $table->foreignId('content_id')->nullable()->constrained('module_content')->onDelete('cascade');

            // Session timing
            $table->timestamp('session_start')->useCurrent();
            $table->timestamp('session_end')->nullable();
            $table->integer('total_duration_minutes')->default(0)->comment('Actual time spent');

            // Video-specific tracking
            $table->integer('video_watch_time')->default(0)->comment('Actual seconds watched');
            $table->integer('video_total_duration')->nullable()->comment('Video length in seconds');
            $table->integer('video_skip_count')->default(0)->comment('Number of skips/jumps');
            $table->integer('video_replay_count')->default(0)->comment('Number of replays');
            $table->decimal('video_completion_percentage', 5, 2)->default(0.00)->comment('Real completion %');

            // User activity tracking
            $table->integer('clicks_count')->default(0)->comment('Total clicks during session');
            $table->integer('pause_count')->default(0)->comment('Number of pauses');
            $table->integer('seek_count')->default(0)->comment('Video scrubbing events');
            $table->integer('fullscreen_count')->default(0)->comment('Fullscreen toggles');
            $table->integer('speed_changes')->default(0)->comment('Playback speed changes');

            // Engagement analysis
            $table->boolean('is_suspicious_activity')->default(false)->comment('Flagged as potential cheating');
            $table->integer('cheating_score')->default(0)->comment('0-100 suspicion score');
            $table->integer('attention_score')->default(0)->comment('0-100 engagement score');

            $table->timestamps();

            // Indexes for analytics queries
            $table->index(['user_id', 'course_online_id', 'session_start']);
            $table->index(['course_online_id', 'session_start']);
            $table->index(['is_suspicious_activity', 'cheating_score']);
            $table->index(['attention_score', 'session_start']);
            $table->index('content_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_sessions');
    }
};
