<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$userId = 131;
$courseId = 29;

echo "=== All Sessions for User $userId ===\n\n";

$allSessions = DB::table('learning_sessions')
    ->where('user_id', $userId)
    ->orderBy('session_start', 'desc')
    ->get(['id', 'course_online_id', 'session_start', 'session_end', 'total_duration_minutes', 'active_playback_time']);

echo "Total sessions: " . $allSessions->count() . "\n\n";

foreach ($allSessions as $session) {
    echo "Session ID: {$session->id} | Course: {$session->course_online_id}\n";
    echo "Start: {$session->session_start} | End: " . ($session->session_end ?: 'NULL') . "\n";
    echo "Duration: {$session->total_duration_minutes}m | Active: {$session->active_playback_time}s\n\n";
}

echo "\n=== Assignment Status ===\n\n";
$assignment = DB::table('course_online_assignments')
    ->where('user_id', $userId)
    ->where('course_online_id', $courseId)
    ->first();

if ($assignment) {
    echo "Status: {$assignment->status}\n";
    echo "Progress: {$assignment->progress_percentage}%\n";
    echo "Assigned: {$assignment->assigned_at}\n";
    echo "Started: " . ($assignment->started_at ?: 'NULL') . "\n";
    echo "Completed: " . ($assignment->completed_at ?: 'NULL') . "\n";
}
