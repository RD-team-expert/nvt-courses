<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find Frank's user
$frank = DB::table('users')->where('email', 'frank@nvt360.com')->first();

if (!$frank) {
    echo "Frank not found\n";
    exit;
}

echo "=== Frank's Learning Time Analysis ===\n\n";
echo "User ID: {$frank->id}\n";
echo "Name: {$frank->name}\n\n";

// Get all learning sessions
$sessions = DB::table('learning_sessions')
    ->where('user_id', $frank->id)
    ->get();

echo "Total Sessions: " . $sessions->count() . "\n\n";

$totalActivePlaybackTime = 0;
$totalCalculatedDuration = 0;

foreach ($sessions as $session) {
    echo "--- Session {$session->id} ---\n";
    echo "Course ID: {$session->course_online_id}\n";
    echo "Content ID: {$session->content_id}\n";
    echo "Start: {$session->session_start}\n";
    echo "End: " . ($session->session_end ?? 'NULL') . "\n";
    echo "Active Playback Time: " . ($session->active_playback_time ?? 0) . " minutes\n";
    
    // Calculate duration from start/end times
    if ($session->session_start && $session->session_end) {
        $start = new DateTime($session->session_start);
        $end = new DateTime($session->session_end);
        $diff = $end->getTimestamp() - $start->getTimestamp();
        $calculatedMinutes = round($diff / 60, 2);
        echo "Calculated Duration (from timestamps): {$calculatedMinutes} minutes\n";
        $totalCalculatedDuration += $calculatedMinutes;
    } else {
        echo "Calculated Duration: Cannot calculate (missing end time)\n";
    }
    
    $totalActivePlaybackTime += ($session->active_playback_time ?? 0);
    
    echo "Video Completion: " . ($session->video_completion_percentage ?? 0) . "%\n";
    echo "Attention Score: " . ($session->attention_score ?? 0) . "%\n";
    echo "\n";
}

echo "=== Summary ===\n";
echo "Total Active Playback Time (stored): {$totalActivePlaybackTime} minutes\n";
echo "Total Calculated Duration: {$totalCalculatedDuration} minutes\n";
echo "Total in Hours: " . round($totalActivePlaybackTime / 60, 2) . " hours\n";

// Check what the report controller calculates
echo "\n=== Report Calculation ===\n";

// Simulate the report controller logic
$assignmentQuery = DB::table('course_online_assignments')
    ->where('user_id', $frank->id);

$assignmentStats = $assignmentQuery
    ->selectRaw('
        COUNT(*) as total_assignments,
        COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_assignments,
        AVG(COALESCE(progress_percentage, 0)) as avg_progress
    ')
    ->first();

echo "Total Assignments: {$assignmentStats->total_assignments}\n";
echo "Completed Assignments: {$assignmentStats->completed_assignments}\n";
echo "Average Progress: " . round($assignmentStats->avg_progress, 2) . "%\n\n";

// Get session stats
$sessionQuery = DB::table('learning_sessions')->where('user_id', $frank->id);

$rawSessions = $sessionQuery->select(
    'id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
    'video_completion_percentage', 'content_id', 'active_playback_time'
)->get();

echo "Sessions found by report: " . $rawSessions->count() . "\n";

$totalRealMinutes = 0;
foreach ($rawSessions as $session) {
    $activePlaybackTime = $session->active_playback_time ?? null;
    $contentId = $session->content_id ?? null;
    
    // Check what duration is being used
    if ($activePlaybackTime !== null && $activePlaybackTime > 0) {
        echo "Session {$session->id}: Using active_playback_time = {$activePlaybackTime} minutes\n";
        $totalRealMinutes += $activePlaybackTime;
    } else {
        // Calculate from timestamps
        if ($session->session_start && $session->session_end) {
            $start = new DateTime($session->session_start);
            $end = new DateTime($session->session_end);
            $diff = $end->getTimestamp() - $start->getTimestamp();
            $calculatedMinutes = round($diff / 60, 2);
            echo "Session {$session->id}: Using calculated duration = {$calculatedMinutes} minutes\n";
            $totalRealMinutes += $calculatedMinutes;
        } else {
            echo "Session {$session->id}: No duration available (missing end time)\n";
        }
    }
}

echo "\nTotal Learning Time (as calculated by report): {$totalRealMinutes} minutes\n";
echo "Total Learning Hours (as shown in report): " . round($totalRealMinutes / 60, 1) . " hours\n";
