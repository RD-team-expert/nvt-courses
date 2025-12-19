<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== USER TRACKING DEBUG ANALYSIS ===\n\n";

// Get a recent session to analyze
$recentSession = DB::table('learning_sessions')
    ->where('created_at', '>=', now()->subDays(1))
    ->whereNotNull('session_start')
    ->whereNotNull('session_end')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$recentSession) {
    echo "❌ No recent sessions found!\n";
    exit;
}

echo "🔍 ANALYZING RECENT SESSION:\n";
echo "Session ID: {$recentSession->id}\n";
echo "User ID: {$recentSession->user_id}\n";
echo "Content ID: {$recentSession->content_id}\n";
echo "Course ID: {$recentSession->course_online_id}\n";
echo "Created: {$recentSession->created_at}\n";
echo str_repeat("-", 50) . "\n\n";

// Check what data this session has
echo "📊 SESSION DATA ANALYSIS:\n";
echo "├─ Session Start: " . ($recentSession->session_start ?? 'NULL') . "\n";
echo "├─ Session End: " . ($recentSession->session_end ?? 'NULL') . "\n";
echo "├─ Active Playback Time: " . ($recentSession->active_playback_time ?? 'NULL') . " seconds\n";
echo "├─ Total Duration: " . ($recentSession->total_duration_minutes ?? 'NULL') . " minutes\n";
echo "├─ Video Completion: " . ($recentSession->video_completion_percentage ?? 'NULL') . "%\n";
echo "├─ Pause Count: " . ($recentSession->pause_count ?? 'NULL') . "\n";
echo "├─ Skip Count: " . ($recentSession->video_skip_count ?? 'NULL') . "\n";
echo "├─ Seek Count: " . ($recentSession->seek_count ?? 'NULL') . "\n";
echo "├─ Replay Count: " . ($recentSession->video_replay_count ?? 'NULL') . "\n";
echo "├─ Attention Score: " . ($recentSession->attention_score ?? 'NULL') . "\n";
echo "└─ Is Suspicious: " . ($recentSession->is_suspicious_activity ? 'YES' : 'NO') . "\n\n";

// Check the content this session was for
if ($recentSession->content_id) {
    echo "📄 CONTENT ANALYSIS:\n";
    $content = DB::table('module_content')
        ->where('id', $recentSession->content_id)
        ->first();
    
    if ($content) {
        echo "├─ Title: {$content->title}\n";
        echo "├─ Type: {$content->content_type}\n";
        echo "├─ Duration: " . ($content->duration ?? 'NULL') . " seconds\n";
        echo "└─ Video ID: " . ($content->video_id ?? 'NULL') . "\n\n";
        
        if ($content->video_id) {
            $video = DB::table('videos')
                ->where('id', $content->video_id)
                ->first();
            
            if ($video) {
                echo "🎥 VIDEO ANALYSIS:\n";
                echo "├─ Name: {$video->name}\n";
                echo "├─ Duration: " . ($video->duration ?? 'NULL') . " seconds\n";
                echo "└─ Google Drive URL: " . ($video->google_drive_url ? 'EXISTS' : 'NULL') . "\n\n";
            }
        }
    }
}

// Check if there are any user progress records
echo "📈 USER PROGRESS ANALYSIS:\n";
$progress = DB::table('user_content_progress')
    ->where('user_id', $recentSession->user_id)
    ->where('content_id', $recentSession->content_id)
    ->first();

if ($progress) {
    echo "├─ Completion Percentage: " . ($progress->completion_percentage ?? 'NULL') . "%\n";
    echo "├─ Is Completed: " . ($progress->is_completed ? 'YES' : 'NO') . "\n";
    echo "├─ Watch Time: " . ($progress->watch_time ?? 'NULL') . " minutes\n";
    echo "└─ Last Accessed: " . ($progress->last_accessed_at ?? 'NULL') . "\n\n";
} else {
    echo "❌ No user progress record found\n\n";
}

// Check recent heartbeat activity
echo "💓 HEARTBEAT ACTIVITY ANALYSIS:\n";
$recentHeartbeats = DB::table('learning_sessions')
    ->where('last_heartbeat', '>=', now()->subHours(2))
    ->orderBy('last_heartbeat', 'desc')
    ->limit(5)
    ->get();

if ($recentHeartbeats->isEmpty()) {
    echo "❌ No recent heartbeat activity (last 2 hours)\n";
} else {
    echo "Recent heartbeat activity:\n";
    foreach ($recentHeartbeats as $session) {
        echo "├─ Session {$session->id}: {$session->last_heartbeat}\n";
    }
}
echo "\n";

// Check for common issues
echo "🚨 COMMON ISSUES CHECK:\n";
echo str_repeat("-", 25) . "\n";

// Issue 1: Sessions with no end time
$noEndTime = DB::table('learning_sessions')
    ->whereNull('session_end')
    ->count();
echo "Sessions with no end time: {$noEndTime}\n";

// Issue 2: Sessions with no heartbeat
$noHeartbeat = DB::table('learning_sessions')
    ->whereNull('last_heartbeat')
    ->count();
echo "Sessions with no heartbeat: {$noHeartbeat}\n";

// Issue 3: Sessions with zero duration but have timestamps
$zeroDurationWithTimestamps = DB::table('learning_sessions')
    ->whereNotNull('session_start')
    ->whereNotNull('session_end')
    ->where(function($query) {
        $query->whereNull('total_duration_minutes')
              ->orWhere('total_duration_minutes', 0);
    })
    ->count();
echo "Sessions with timestamps but zero duration: {$zeroDurationWithTimestamps}\n";

// Issue 4: Sessions with zero active playback time
$zeroActivePlayback = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('active_playback_time')
              ->orWhere('active_playback_time', 0);
    })
    ->count();
echo "Sessions with zero active playback time: {$zeroActivePlayback}\n";

echo "\n🔧 POTENTIAL FIXES:\n";
echo str_repeat("-", 20) . "\n";

if ($noEndTime > 0) {
    echo "1. Fix sessions with no end time (frontend not calling endSession)\n";
}

if ($noHeartbeat > 0) {
    echo "2. Fix heartbeat mechanism (frontend not sending heartbeats)\n";
}

if ($zeroDurationWithTimestamps > 0) {
    echo "3. Fix duration calculation (backend not calculating from timestamps)\n";
}

if ($zeroActivePlayback > 0) {
    echo "4. Fix active playback tracking (frontend not tracking video play time)\n";
}

echo "\n✅ Debug analysis completed!\n";
echo "💡 The main issue appears to be that the frontend is not properly tracking and sending:\n";
echo "   - Active playback time\n";
echo "   - Video completion percentage\n";
echo "   - Heartbeat data\n";