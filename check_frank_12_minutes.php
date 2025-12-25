<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== WHERE IS FRANK'S 12 MINUTES COMING FROM? ===\n\n";

$user = DB::table('users')->where('email', 'frank@nvt360.com')->first();

if (!$user) {
    echo "❌ Frank not found\n";
    exit;
}

echo "✅ User: {$user->name} (ID: {$user->id})\n\n";

// Check the session data
$session = DB::table('learning_sessions')
    ->where('user_id', $user->id)
    ->first();

if ($session) {
    echo "--- SESSION DATA ---\n";
    echo "Session ID: {$session->id}\n";
    echo "Start: {$session->session_start}\n";
    echo "End: " . ($session->session_end ?? 'Active (NULL)') . "\n";
    echo "Stored Duration (total_duration_minutes): " . ($session->total_duration_minutes ?? 0) . " minutes\n";
    echo "Active Playback Time: " . ($session->active_playback_time ?? 0) . " seconds = " . round(($session->active_playback_time ?? 0) / 60, 2) . " minutes\n";
    echo "Content ID: " . ($session->content_id ?? 'NULL') . "\n\n";
    
    // Check if content exists
    if ($session->content_id) {
        $content = DB::table('module_content')->where('id', $session->content_id)->first();
        if ($content) {
            echo "--- CONTENT DATA ---\n";
            echo "Content Title: {$content->title}\n";
            echo "Content Type: {$content->content_type}\n";
            echo "Duration: " . ($content->duration ?? 0) . " seconds = " . round(($content->duration ?? 0) / 60, 2) . " minutes\n\n";
        }
    }
    
    // Calculate what the report controller would show
    echo "--- REPORT CALCULATION ---\n";
    
    // The report uses active_playback_time if available
    $activePlaybackMinutes = ($session->active_playback_time ?? 0) / 60;
    echo "Active Playback Time: " . round($activePlaybackMinutes, 2) . " minutes\n";
    
    // If no active playback time, it would use calculated duration
    if ($activePlaybackMinutes <= 0) {
        echo "Since active_playback_time is 0, report would try to calculate from timestamps...\n";
        
        if ($session->session_end) {
            $start = new DateTime($session->session_start);
            $end = new DateTime($session->session_end);
            $calculatedMinutes = $start->diff($end)->days * 24 * 60 + 
                               $start->diff($end)->h * 60 + 
                               $start->diff($end)->i;
            echo "Calculated from timestamps: {$calculatedMinutes} minutes\n";
        } else {
            echo "Session has no end time (still active), so duration would be 0\n";
        }
    }
    
    echo "\n--- CONCLUSION ---\n";
    if ($activePlaybackMinutes > 0) {
        echo "✅ The report shows " . round($activePlaybackMinutes, 0) . " minutes from active_playback_time\n";
        echo "This is the actual time Frank spent watching the video.\n\n";
    } else {
        echo "⚠️  Active playback time is 0, but report shows 12 minutes.\n";
        echo "This might be coming from a different calculation or cached data.\n\n";
    }
    
    echo "WHY IS PROGRESS 0%?\n";
    echo "- Frank watched " . round($activePlaybackMinutes, 2) . " minutes of video\n";
    echo "- But the content is NOT marked as completed (is_completed = 0)\n";
    echo "- Progress is calculated from COMPLETED content, not watch time\n";
    echo "- So even though Frank has learning time, progress stays at 0%\n\n";
    
    // Check what percentage of video was watched
    if ($session->content_id) {
        $content = DB::table('module_content')->where('id', $session->content_id)->first();
        if ($content && $content->duration > 0) {
            $watchedPercentage = (($session->active_playback_time ?? 0) / $content->duration) * 100;
            echo "VIDEO COMPLETION:\n";
            echo "- Video Duration: " . round($content->duration / 60, 2) . " minutes\n";
            echo "- Time Watched: " . round($activePlaybackMinutes, 2) . " minutes\n";
            echo "- Percentage Watched: " . round($watchedPercentage, 2) . "%\n\n";
            
            if ($watchedPercentage < 95) {
                echo "⚠️  Frank only watched " . round($watchedPercentage, 2) . "% of the video!\n";
                echo "The system requires 95% completion to mark content as completed.\n";
            }
        }
    }
}
