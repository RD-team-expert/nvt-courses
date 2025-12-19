<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

$userId = 131;
$courseId = 29;

echo "=== Testing Fix for User $userId, Course $courseId ===\n\n";

// Get session
$session = DB::table('learning_sessions')
    ->where('user_id', $userId)
    ->where('course_online_id', $courseId)
    ->first();

if ($session) {
    echo "Session ID: {$session->id}\n";
    echo "Start: {$session->session_start}\n";
    echo "End: " . ($session->session_end ?: 'NULL (active session)') . "\n";
    echo "Stored Duration: {$session->total_duration_minutes} minutes\n";
    echo "Active Playback Time: {$session->active_playback_time} seconds\n\n";
    
    // Test the new logic
    $activePlaybackTime = $session->active_playback_time;
    
    echo "=== Testing Duration Calculation ===\n\n";
    
    // Priority 1: active_playback_time
    if ($activePlaybackTime && $activePlaybackTime > 0) {
        $minutes = round($activePlaybackTime / 60, 2);
        echo "✅ Using active_playback_time: {$minutes} minutes\n";
    } else {
        echo "❌ No active_playback_time available\n";
        
        // Priority 2: Calculate from start/end
        if ($session->session_end) {
            $start = Carbon::parse($session->session_start);
            $end = Carbon::parse($session->session_end);
            $minutes = $start->diffInMinutes($end);
            echo "✅ Using calculated duration (start to end): {$minutes} minutes\n";
        } else {
            // For active sessions, calculate up to now (capped at 120 min)
            $start = Carbon::parse($session->session_start);
            $now = Carbon::now();
            $minutes = $start->diffInMinutes($now);
            $cappedMinutes = min($minutes, 120);
            echo "⚠️  Active session - calculating up to now: {$minutes} minutes (capped at {$cappedMinutes} minutes)\n";
        }
    }
    
    // Check assignment completion time
    $assignment = DB::table('course_online_assignments')
        ->where('user_id', $userId)
        ->where('course_online_id', $courseId)
        ->first();
    
    if ($assignment && $assignment->completed_at) {
        echo "\n=== Assignment Info ===\n";
        echo "Completed at: {$assignment->completed_at}\n";
        
        // Calculate duration from session start to assignment completion
        $start = Carbon::parse($session->session_start);
        $completed = Carbon::parse($assignment->completed_at);
        $durationToCompletion = $start->diffInMinutes($completed);
        echo "Duration from session start to completion: {$durationToCompletion} minutes\n";
    }
}
