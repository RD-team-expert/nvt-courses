<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "=== TESTING FIXED DURATION CALCULATION ===\n\n";

$sessionId = 4459;

$session = DB::table('learning_sessions')
    ->where('id', $sessionId)
    ->first();

if (!$session) {
    echo "Session not found!\n";
    exit;
}

echo "Session ID: {$session->id}\n";
echo "User ID: {$session->user_id}\n";
echo "Course ID: {$session->course_online_id}\n";
echo "Content ID: {$session->content_id}\n";
echo "Session Start: {$session->session_start}\n";
echo "Session End: " . ($session->session_end ?? 'NULL') . "\n";
echo "Active Playback Time: {$session->active_playback_time} seconds\n";
echo "\n";

// Check content progress
$progress = DB::table('user_content_progress')
    ->where('user_id', $session->user_id)
    ->where('content_id', $session->content_id)
    ->where('is_completed', true)
    ->first();

if ($progress && $progress->completed_at) {
    echo "=== BACKUP CALCULATION (From Completion) ===\n";
    echo "Completed At: {$progress->completed_at}\n";
    
    $start = Carbon::parse($session->session_start);
    $completed = Carbon::parse($progress->completed_at);
    
    $rawMinutes = $start->diffInMinutes($completed);
    $activeMinutes = $rawMinutes * 0.6;
    
    echo "Raw Duration: {$rawMinutes} minutes\n";
    echo "Active Time (60% factor): " . round($activeMinutes, 2) . " minutes\n";
    echo "\n";
    echo "✅ This should be displayed in the report instead of 60m\n";
} else {
    echo "❌ No completion data found\n";
}

echo "\n=== OLD BEHAVIOR (Before Fix) ===\n";
$start = Carbon::parse($session->session_start);
$now = Carbon::now();
$minutesToNow = $start->diffInMinutes($now);
$cappedMinutes = min($minutesToNow, 60);
echo "Time from start to now: {$minutesToNow} minutes\n";
echo "Capped at 60 minutes: {$cappedMinutes} minutes\n";
echo "❌ This was showing incorrectly as 60m\n";
