<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find Ryan
$ryan = DB::table('users')->where('email', 'ryan@pnefoods.com')->first();

if (!$ryan) {
    echo "Ryan not found\n";
    exit;
}

echo "=== Ryan's Learning Time Analysis ===\n\n";
echo "User ID: {$ryan->id}\n";
echo "Name: {$ryan->name}\n\n";

// Get all learning sessions
$sessions = DB::table('learning_sessions')
    ->where('user_id', $ryan->id)
    ->orderBy('session_start', 'desc')
    ->get();

echo "Total Sessions: " . $sessions->count() . "\n\n";

$totalActivePlaybackTime = 0;
$totalCalculatedDuration = 0;
$sessionsWithIssues = [];

foreach ($sessions as $session) {
    echo "--- Session {$session->id} ---\n";
    
    // Get course name
    $course = DB::table('course_online')->where('id', $session->course_online_id)->first();
    echo "Course: " . ($course ? $course->name : 'Unknown') . " (ID: {$session->course_online_id})\n";
    
    // Get content info
    if ($session->content_id) {
        $content = DB::table('module_content')->where('id', $session->content_id)->first();
        echo "Content: " . ($content ? $content->title : 'Unknown') . " (ID: {$session->content_id})\n";
        
        if ($content && $content->duration) {
            $videoDurationMinutes = round($content->duration / 60, 2);
            echo "Video Duration: {$videoDurationMinutes} minutes ({$content->duration} seconds)\n";
        }
    }
    
    echo "Start: {$session->session_start}\n";
    echo "End: " . ($session->session_end ?? 'NULL') . "\n";
    echo "Active Playback Time: " . ($session->active_playback_time ?? 0) . " minutes\n";
    
    // Calculate duration from start/end times
    if ($session->session_start && $session->session_end) {
        $start = new DateTime($session->session_start);
        $end = new DateTime($session->session_end);
        $diff = $end->getTimestamp() - $start->getTimestamp();
        $calculatedMinutes = round($diff / 60, 2);
        echo "Calculated Duration (timestamps): {$calculatedMinutes} minutes\n";
        $totalCalculatedDuration += $calculatedMinutes;
        
        // Check if active_playback_time is reasonable
        $activeTime = $session->active_playback_time ?? 0;
        if ($activeTime > $calculatedMinutes * 1.5) {
            echo "⚠️  WARNING: Active playback time ({$activeTime}m) is much higher than session duration ({$calculatedMinutes}m)\n";
            $sessionsWithIssues[] = $session->id;
        }
        
        // Check if it's longer than video duration
        if ($session->content_id && isset($content) && $content->duration) {
            $videoDurationMinutes = round($content->duration / 60, 2);
            if ($activeTime > $videoDurationMinutes * 1.5) {
                echo "⚠️  WARNING: Active playback time ({$activeTime}m) is much higher than video duration ({$videoDurationMinutes}m)\n";
                $sessionsWithIssues[] = $session->id;
            }
        }
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
echo "Total Calculated Duration (timestamps): {$totalCalculatedDuration} minutes\n";
echo "Total in Hours: " . round($totalActivePlaybackTime / 60, 2) . " hours\n";
echo "Sessions with potential issues: " . count(array_unique($sessionsWithIssues)) . "\n";

// Get assignment info
$assignment = DB::table('course_online_assignments')
    ->where('user_id', $ryan->id)
    ->first();

if ($assignment) {
    echo "\n=== Assignment Info ===\n";
    $course = DB::table('course_online')->where('id', $assignment->course_online_id)->first();
    echo "Course: " . ($course ? $course->name : 'Unknown') . "\n";
    echo "Status: {$assignment->status}\n";
    echo "Progress: {$assignment->progress_percentage}%\n";
    echo "Assigned: {$assignment->assigned_at}\n";
}

// Check if there are multiple sessions for the same content
echo "\n=== Checking for Duplicate Sessions ===\n";
$contentSessions = DB::table('learning_sessions')
    ->where('user_id', $ryan->id)
    ->whereNotNull('content_id')
    ->select('content_id', DB::raw('COUNT(*) as session_count'), DB::raw('SUM(active_playback_time) as total_time'))
    ->groupBy('content_id')
    ->having('session_count', '>', 1)
    ->get();

if ($contentSessions->count() > 0) {
    echo "Found content with multiple sessions:\n";
    foreach ($contentSessions as $cs) {
        $content = DB::table('module_content')->where('id', $cs->content_id)->first();
        echo "  Content {$cs->content_id} (" . ($content ? $content->title : 'Unknown') . "): {$cs->session_count} sessions, Total time: {$cs->total_time} minutes\n";
        
        if ($content && $content->duration) {
            $videoDurationMinutes = round($content->duration / 60, 2);
            echo "    Video duration: {$videoDurationMinutes} minutes\n";
            if ($cs->total_time > $videoDurationMinutes * 1.5) {
                echo "    ⚠️  Total time is much higher than video duration!\n";
            }
        }
    }
} else {
    echo "No duplicate sessions found.\n";
}
