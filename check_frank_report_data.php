<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simulate the exact report controller logic
$frank = DB::table('users')->where('email', 'frank@nvt360.com')->first();

if (!$frank) {
    echo "Frank not found\n";
    exit;
}

echo "=== Simulating Report Controller for Frank ===\n\n";

// Get the ProgressCalculationService
$progressService = app(\App\Services\ProgressCalculationService::class);

$filters = [];

// Get assignment stats
$assignmentQuery = DB::table('course_online_assignments')
    ->where('user_id', $frank->id);

$assignmentStats = $assignmentQuery
    ->selectRaw('
        COUNT(*) as total_assignments,
        COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_assignments,
        AVG(COALESCE(progress_percentage, 0)) as avg_progress,
        AVG(CASE WHEN progress_percentage > 0 THEN progress_percentage END) as avg_active_progress
    ')
    ->first();

echo "Assignment Stats:\n";
echo "  Total: {$assignmentStats->total_assignments}\n";
echo "  Completed: {$assignmentStats->completed_assignments}\n";
echo "  Avg Progress: " . round($assignmentStats->avg_progress, 2) . "%\n\n";

// Get session stats
$sessionQuery = DB::table('learning_sessions')->where('user_id', $frank->id);

$rawSessions = $sessionQuery->select(
    'id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
    'video_completion_percentage', 'content_id', 'active_playback_time'
)->get();

$totalSessions = $rawSessions->count();
$totalRealMinutes = 0;

echo "Processing Sessions:\n";
foreach ($rawSessions as $session) {
    $activePlaybackTime = $session->active_playback_time ?? null;
    
    if ($activePlaybackTime !== null && $activePlaybackTime > 0) {
        $duration = $activePlaybackTime;
        echo "  Session {$session->id}: {$duration} minutes (from active_playback_time)\n";
    } else {
        // Calculate from timestamps
        if ($session->session_start && $session->session_end) {
            $start = strtotime($session->session_start);
            $end = strtotime($session->session_end);
            $duration = round(($end - $start) / 60, 2);
            echo "  Session {$session->id}: {$duration} minutes (calculated from timestamps)\n";
        } else {
            $duration = 0;
            echo "  Session {$session->id}: 0 minutes (no end time)\n";
        }
    }
    
    $totalRealMinutes += $duration;
}

echo "\nTotal Minutes: {$totalRealMinutes}\n";
echo "Total Hours: " . round($totalRealMinutes / 60, 1) . "\n\n";

// Check what gets displayed in the report
$completionRate = $assignmentStats->total_assignments > 0
    ? round(($assignmentStats->completed_assignments / $assignmentStats->total_assignments) * 100, 1)
    : 0;

echo "=== Final Report Data ===\n";
echo "Total Learning Hours: " . round($totalRealMinutes / 60, 1) . "\n";
echo "Total Sessions: {$totalSessions}\n";
echo "Completion Rate: {$completionRate}%\n";

// Check if there's a conversion issue
echo "\n=== Checking for Conversion Issues ===\n";
echo "Minutes stored: {$totalRealMinutes}\n";
echo "Hours (÷60): " . ($totalRealMinutes / 60) . "\n";
echo "Rounded hours: " . round($totalRealMinutes / 60, 1) . "\n";

// Check if the issue is in the display
echo "\n=== Checking Display Logic ===\n";
echo "If showing minutes in UI: {$totalRealMinutes} minutes\n";
echo "If showing hours * 60 in UI: " . round(($totalRealMinutes / 60) * 60) . " minutes\n";

// Check the actual value that would be sent to frontend
$reportData = [
    'total_learning_hours' => round($totalRealMinutes / 60, 1),
    'total_sessions' => $totalSessions,
];

echo "\nData sent to frontend:\n";
print_r($reportData);

echo "\nIf frontend multiplies by 60:\n";
echo "  " . round($reportData['total_learning_hours'] * 60) . " minutes\n";
