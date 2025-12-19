<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "=== TESTING CORRECT DURATION CALCULATION ===\n\n";

$sessionId = 4459;

$session = DB::table('learning_sessions')
    ->where('id', $sessionId)
    ->select('id', 'session_start', 'session_end', 'total_duration_minutes', 'attention_score', 'is_suspicious_activity', 'active_playback_time', 'content_id', 'user_id', 'course_online_id')
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

// Test Priority 1: Active Playback Time
if ($session->active_playback_time && $session->active_playback_time > 0) {
    $minutes = round($session->active_playback_time / 60, 2);
    echo "✅ Priority 1: Active Playback Time\n";
    echo "   {$session->active_playback_time} seconds = {$minutes} minutes\n";
    echo "   This should be displayed: {$minutes}m\n";
    echo "\n";
}

// Test Priority 2: Session End Time
if ($session->session_end) {
    $start = Carbon::parse($session->session_start);
    $end = Carbon::parse($session->session_end);
    $minutes = $start->diffInMinutes($end);
    echo "✅ Priority 2: Session End Time\n";
    echo "   End - Start = {$minutes} minutes\n";
    echo "\n";
}

// Test Priority 3: Video Duration
if ($session->content_id) {
    $videoDuration = DB::table('module_content')
        ->where('id', $session->content_id)
        ->value('duration');
    
    if ($videoDuration && $videoDuration > 0) {
        $minutes = round($videoDuration / 60, 2);
        echo "✅ Priority 3: Video Duration\n";
        echo "   {$videoDuration} seconds = {$minutes} minutes\n";
        echo "\n";
    } else {
        echo "❌ Priority 3: Video Duration = 0 or NULL\n\n";
    }
}

// Test Priority 4: Backup from Completion
$progress = DB::table('user_content_progress')
    ->where('user_id', $session->user_id)
    ->where('content_id', $session->content_id)
    ->where('is_completed', true)
    ->first();

if ($progress && $progress->completed_at) {
    $start = Carbon::parse($session->session_start);
    $completed = Carbon::parse($progress->completed_at);
    $rawMinutes = $start->diffInMinutes($completed);
    $activeMinutes = round($rawMinutes * 0.6, 2);
    
    echo "✅ Priority 4: Backup from Completion\n";
    echo "   Raw: {$rawMinutes} minutes\n";
    echo "   Active (60%): {$activeMinutes} minutes\n";
    echo "\n";
}

echo "=== EXPECTED RESULT ===\n";
echo "Since active_playback_time = {$session->active_playback_time} seconds (10.77 min)\n";
echo "The report should show: 10.77m or 11m\n";
