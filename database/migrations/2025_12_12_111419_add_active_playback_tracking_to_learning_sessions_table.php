<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            // Add active_playback_time column (INT, default 0)
            // Active playback time in seconds (excludes loading, buffering, pauses)
            $table->integer('active_playback_time')->default(0)
                ->after('video_watch_time')
                ->comment('Active playback time in seconds (excludes loading, buffering, pauses)');
            
            // Add is_within_allowed_time column (BOOLEAN, default TRUE)
            // Whether session stayed within allowed time (Duration × 2)
            $table->boolean('is_within_allowed_time')->default(true)
                ->after('active_playback_time')
                ->comment('Whether session stayed within allowed time (Duration × 2)');
            
            // Add video_events column (JSON, nullable)
            // JSON array of video events (pause, resume, rewind with timestamps)
            $table->json('video_events')->nullable()
                ->after('is_within_allowed_time')
                ->comment('JSON array of video events (pause, resume, rewind with timestamps)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'active_playback_time',
                'is_within_allowed_time',
                'video_events'
            ]);
        });
    }
};
