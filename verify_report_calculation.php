<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFY REPORT CALCULATION FOR FRANK ===\n\n";

$user = DB::table('users')->where('email', 'frank@nvt360.com')->first();
$assignment = DB::table('course_online_assignments')
    ->where('user_id', $user->id)
    ->first();

echo "User: {$user->name}\n";
echo "Assignment ID: {$assignment->id}\n";
echo "Course ID: {$assignment->course_online_id}\n\n";

// Simulate what the report controller does
echo "--- SIMULATING REPORT CONTROLLER LOGIC ---\n\n";

// Get sessions (from progressReport method)
$sessions = DB::table('learning_sessions')
    ->where('user_id', $user->id)
    ->where('course_online_id', $assignment->course_online_id)
    ->select('id', 'session_start', 'session_end', 'total_duration_minutes', 'attention_score', 'is_suspicious_activity', 'active_playback_time', 'content_id')
    ->get();

echo "Sessions found: " . $sessions->count() . "\n\n";

// Process sessions (from processSessionsWithSimulatedAttention method)
$totalCalculatedMinutes = 0;

foreach ($sessions as $session) {
    echo "Processing Session {$session->id}:\n";
    
    // Get full session data
    $fullSessionData = DB::table('learning_sessions')
        ->where('id', $session->id)
        ->select('video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage', 'content_id', 'active_playback_time')
        ->first();
    
    $contentId = $fullSessionData->content_id ?? null;
    $activePlaybackTime = $fullSessionData->active_playback_time ?? null;
    
    echo "  Content ID: {$contentId}\n";
    echo "  Active Playback Time: {$activePlaybackTime} seconds\n";
    
    // Calculate duration (from getActualSessionDuration method)
    // Priority 1: Use active_playback_time if available
    if ($activePlaybackTime && $activePlaybackTime > 0) {
        $calculatedDuration = round($activePlaybackTime / 60, 2);
        echo "  Using active_playback_time: {$calculatedDuration} minutes\n";
    } else {
        $calculatedDuration = 0;
        echo "  No active_playback_time, duration = 0\n";
    }
    
    $totalCalculatedMinutes += $calculatedDuration;
    echo "  Running Total: {$totalCalculatedMinutes} minutes\n\n";
}

echo "--- FINAL REPORT VALUES ---\n";
echo "Total Learning Time: {$totalCalculatedMinutes} minutes\n";
echo "Formatted: " . ($totalCalculatedMinutes < 60 ? round($totalCalculatedMinutes) . ' min' : floor($totalCalculatedMinutes / 60) . 'h ' . round($totalCalculatedMinutes % 60) . 'm') . "\n\n";

// Get progress data
$progressData = DB::table('user_content_progress')
    ->where('user_id', $user->id)
    ->where('course_online_id', $assignment->course_online_id)
    ->selectRaw('
        COUNT(*) as total_content_accessed,
        COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_content,
        COALESCE(SUM(CASE WHEN watch_time > 0 THEN watch_time ELSE 0 END), 0) as total_watch_time
    ')
    ->first();

echo "--- PROGRESS DATA ---\n";
echo "Total Content Accessed: {$progressData->total_content_accessed}\n";
echo "Completed Content: {$progressData->completed_content}\n";
echo "Total Watch Time: {$progressData->total_watch_time} seconds\n\n";

// Calculate progress percentage
$totalContent = DB::table('module_content')
    ->join('course_modules', 'module_content.module_id', '=', 'course_modules.id')
    ->where('course_modules.course_online_id', $assignment->course_online_id)
    ->count();

$completedContent = $progressData->completed_content;

echo "--- PROGRESS CALCULATION ---\n";
echo "Total Content in Course: {$totalContent}\n";
echo "Completed Content: {$completedContent}\n";

if ($totalContent > 0) {
    $progressPercentage = ($completedContent / $totalContent) * 100;
    echo "Progress: " . round($progressPercentage, 2) . "%\n";
} else {
    echo "Progress: 0% (no content)\n";
}

echo "\n--- MATCHES REPORT? ---\n";
echo "Expected Learning Time: ~10-12 minutes ✅\n";
echo "Calculated Learning Time: " . round($totalCalculatedMinutes) . " minutes\n";
echo "Expected Progress: 0% ✅\n";
echo "Calculated Progress: " . round($progressPercentage ?? 0) . "%\n";
