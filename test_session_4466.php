<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING SESSION 4466 ===\n\n";

// Get the specific session
$session = DB::table('learning_sessions')
    ->where('id', 4466)
    ->first();

if (!$session) {
    echo "❌ Session 4466 not found!\n";
    exit;
}

echo "🔍 SESSION 4466 ANALYSIS:\n";
echo str_repeat("-", 30) . "\n";
echo "Session ID: {$session->id}\n";
echo "User ID: {$session->user_id}\n";
echo "Content ID: {$session->content_id}\n";
echo "Course ID: {$session->course_online_id}\n";
echo "Created: {$session->created_at}\n";
echo "Session Start: " . ($session->session_start ?? 'NULL') . "\n";
echo "Session End: " . ($session->session_end ?? 'NULL') . "\n";
echo "Last Heartbeat: " . ($session->last_heartbeat ?? 'NULL') . "\n\n";

echo "📊 TRACKING DATA:\n";
echo str_repeat("-", 20) . "\n";
echo "Active Playback Time: " . ($session->active_playback_time ?? 'NULL') . " seconds\n";
echo "Total Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
echo "Video Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
echo "Pause Count: " . ($session->pause_count ?? 'NULL') . "\n";
echo "Skip Count: " . ($session->video_skip_count ?? 'NULL') . "\n";
echo "Seek Count: " . ($session->seek_count ?? 'NULL') . "\n";
echo "Replay Count: " . ($session->video_replay_count ?? 'NULL') . "\n";
echo "Attention Score: " . ($session->attention_score ?? 'NULL') . "\n";
echo "Is Suspicious: " . ($session->is_suspicious_activity ? 'YES' : 'NO') . "\n\n";

// Check the content this session was for
if ($session->content_id) {
    echo "📄 CONTENT DETAILS:\n";
    echo str_repeat("-", 20) . "\n";
    $content = DB::table('module_content')
        ->where('id', $session->content_id)
        ->first();
    
    if ($content) {
        echo "Title: {$content->title}\n";
        echo "Type: {$content->content_type}\n";
        echo "Duration: " . ($content->duration ?? 'NULL') . " seconds\n";
        echo "Video ID: " . ($content->video_id ?? 'NULL') . "\n\n";
        
        if ($content->video_id) {
            $video = DB::table('videos')
                ->where('id', $content->video_id)
                ->first();
            
            if ($video) {
                echo "🎥 VIDEO DETAILS:\n";
                echo str_repeat("-", 15) . "\n";
                echo "Name: {$video->name}\n";
                echo "Duration: " . ($video->duration ?? 'NULL') . " seconds\n";
                echo "Google Drive URL: " . ($video->google_drive_url ? 'EXISTS' : 'NULL') . "\n\n";
            }
        }
    }
}

// Calculate session duration from timestamps
if ($session->session_start && $session->session_end) {
    $start = new DateTime($session->session_start);
    $end = new DateTime($session->session_end);
    $actualDuration = $start->diff($end);
    $totalMinutes = $actualDuration->days * 24 * 60 + $actualDuration->h * 60 + $actualDuration->i;
    $totalSeconds = $totalMinutes * 60 + $actualDuration->s;
    
    echo "⏱️ CALCULATED DURATION:\n";
    echo str_repeat("-", 25) . "\n";
    echo "From Timestamps: {$totalMinutes} minutes ({$totalSeconds} seconds)\n";
    echo "Stored Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
    echo "Match: " . ($totalMinutes == $session->total_duration_minutes ? '✅ YES' : '❌ NO') . "\n\n";
}

// Check heartbeat activity
if ($session->last_heartbeat) {
    $heartbeatTime = new DateTime($session->last_heartbeat);
    $sessionStartTime = new DateTime($session->session_start);
    $heartbeatDelay = $sessionStartTime->diff($heartbeatTime);
    $delayMinutes = $heartbeatDelay->i + ($heartbeatDelay->h * 60);
    
    echo "💓 HEARTBEAT ANALYSIS:\n";
    echo str_repeat("-", 20) . "\n";
    echo "First Heartbeat Delay: {$delayMinutes} minutes after session start\n";
    echo "Heartbeat Status: ✅ WORKING\n\n";
} else {
    echo "💓 HEARTBEAT ANALYSIS:\n";
    echo str_repeat("-", 20) . "\n";
    echo "Heartbeat Status: ❌ NO HEARTBEAT RECEIVED\n\n";
}

// Compare with previous sessions from same user
echo "📈 COMPARISON WITH PREVIOUS SESSIONS:\n";
echo str_repeat("-", 40) . "\n";

$previousSessions = DB::table('learning_sessions')
    ->where('user_id', $session->user_id)
    ->where('id', '<', 4466)
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get();

if ($previousSessions->isEmpty()) {
    echo "No previous sessions found for this user\n";
} else {
    echo "Recent sessions from same user:\n";
    foreach ($previousSessions as $prev) {
        echo "Session {$prev->id}: ";
        echo "Active Playback: " . ($prev->active_playback_time ?? 'NULL') . "s, ";
        echo "Completion: " . ($prev->video_completion_percentage ?? 'NULL') . "%, ";
        echo "Heartbeat: " . ($prev->last_heartbeat ? 'YES' : 'NO') . "\n";
    }
}

echo "\n🎯 ASSESSMENT:\n";
echo str_repeat("-", 15) . "\n";

$improvements = [];
$issues = [];

// Check improvements
if (($session->active_playback_time ?? 0) > 0) {
    $improvements[] = "✅ Active playback time is being tracked";
} else {
    $issues[] = "❌ Active playback time is still 0";
}

if (($session->video_completion_percentage ?? 0) > 0) {
    $improvements[] = "✅ Video completion percentage is being tracked";
} else {
    $issues[] = "❌ Video completion percentage is still 0";
}

if ($session->last_heartbeat) {
    $improvements[] = "✅ Heartbeat system is working";
} else {
    $issues[] = "❌ No heartbeat received";
}

if (($session->total_duration_minutes ?? 0) > 0) {
    $improvements[] = "✅ Session duration is being calculated";
} else {
    $issues[] = "❌ Session duration is still 0";
}

if (!empty($improvements)) {
    echo "IMPROVEMENTS:\n";
    foreach ($improvements as $improvement) {
        echo "  {$improvement}\n";
    }
    echo "\n";
}

if (!empty($issues)) {
    echo "REMAINING ISSUES:\n";
    foreach ($issues as $issue) {
        echo "  {$issue}\n";
    }
    echo "\n";
}

// Overall status
$totalChecks = count($improvements) + count($issues);
$successRate = count($improvements) / $totalChecks * 100;

echo "OVERALL STATUS: " . round($successRate, 1) . "% working\n";

if ($successRate >= 75) {
    echo "🎉 GREAT! The fixes are working well!\n";
} elseif ($successRate >= 50) {
    echo "🔧 PARTIAL SUCCESS - Some fixes are working\n";
} else {
    echo "⚠️ NEEDS MORE WORK - Most issues remain\n";
}

echo "\n✅ Session 4466 analysis completed!\n";