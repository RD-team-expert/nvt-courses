<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

$userId = 131;
$courseId = 29;

echo "=== Testing Updated Fix for User $userId, Course $courseId ===\n\n";

// Get session
$session = DB::table('learning_sessions')
    ->where('user_id', $userId)
    ->where('course_online_id', $courseId)
    ->first();

if ($session) {
    echo "Session ID: {$session->id}\n";
    echo "Start: {$session->session_start}\n";
    echo "End: " . ($session->session_end ?: 'NULL (active session)') . "\n";
    echo "Content ID: {$session->content_id}\n";
    echo "Stored Duration: {$session->total_duration_minutes} minutes\n";
    echo "Active Playback Time: {$session->active_playback_time} seconds\n\n";
    
    // Get video duration
    if ($session->content_id) {
        $content = DB::table('module_content')
            ->where('id', $session->content_id)
            ->first(['title', 'duration']);
        
        if ($content) {
            echo "Video: {$content->title}\n";
            echo "Video Duration: {$content->duration} seconds (" . round($content->duration / 60, 2) . " minutes)\n\n";
        }
    }
    
    echo "=== Testing New Duration Calculation Logic ===\n\n";
    
    $activePlaybackTime = $session->active_playback_time;
    $contentId = $session->content_id;
    
    // Priority 1: active_playback_time
    if ($activePlaybackTime && $activePlaybackTime > 0) {
        $minutes = round($activePlaybackTime / 60, 2);
        echo "✅ Priority 1 - Using active_playback_time: {$minutes} minutes\n";
    } else {
        echo "❌ Priority 1 - No active_playback_time available\n";
        
        // Priority 2: Calculate from start/end
        if ($session->session_end) {
            $start = Carbon::parse($session->session_start);
            $end = Carbon::parse($session->session_end);
            $minutes = $start->diffInMinutes($end);
            echo "✅ Priority 2 - Using calculated duration (start to end): {$minutes} minutes\n";
        } else {
            echo "❌ Priority 2 - No session end time\n";
            
            // Priority 3: Use video duration
            if ($contentId) {
                $videoDurationSeconds = DB::table('module_content')
                    ->where('id', $contentId)
                    ->value('duration');
                
                if ($videoDurationSeconds && $videoDurationSeconds > 0) {
                    $minutes = round($videoDurationSeconds / 60, 2);
                    echo "✅ Priority 3 - Using video duration: {$minutes} minutes\n";
                } else {
                    echo "❌ Priority 3 - Video duration is 0 or NULL\n";
                    
                    // Priority 4: Calculate up to now (capped)
                    $start = Carbon::parse($session->session_start);
                    $now = Carbon::now();
                    $minutes = $start->diffInMinutes($now);
                    $cappedMinutes = min($minutes, 60);
                    echo "⚠️  Priority 4 - Calculating up to now: {$minutes} minutes (capped at {$cappedMinutes} minutes)\n";
                }
            }
        }
    }
}
