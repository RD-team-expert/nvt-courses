<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test user 131, course 29
$userId = 131;
$courseId = 29;

echo "=== Testing User $userId, Course $courseId ===\n\n";

// Check sessions
$sessions = DB::table('learning_sessions')
    ->where('user_id', $userId)
    ->where('course_online_id', $courseId)
    ->get(['id', 'session_start', 'session_end', 'total_duration_minutes', 'active_playback_time', 'content_id']);

echo "Total sessions found: " . $sessions->count() . "\n\n";

foreach ($sessions as $session) {
    echo "Session ID: {$session->id}\n";
    echo "Start: {$session->session_start}\n";
    echo "End: {$session->session_end}\n";
    echo "Stored Duration: {$session->total_duration_minutes} minutes\n";
    echo "Active Playback Time: {$session->active_playback_time} seconds\n";
    echo "Content ID: {$session->content_id}\n";
    
    // Calculate actual duration
    if ($session->session_start && $session->session_end) {
        $start = \Carbon\Carbon::parse($session->session_start);
        $end = \Carbon\Carbon::parse($session->session_end);
        $calculatedMinutes = $start->diffInMinutes($end);
        echo "Calculated Duration: {$calculatedMinutes} minutes\n";
    }
    
    // Get content info
    if ($session->content_id) {
        $content = DB::table('module_content')
            ->where('id', $session->content_id)
            ->first(['title', 'content_type', 'duration']);
        if ($content) {
            echo "Content: {$content->title} ({$content->content_type})\n";
            echo "Video Duration: {$content->duration} seconds (" . round($content->duration / 60, 1) . " minutes)\n";
        }
    }
    
    echo "\n---\n\n";
}
