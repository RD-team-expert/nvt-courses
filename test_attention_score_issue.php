<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ATTENTION SCORE ISSUE ANALYSIS ===\n\n";

// Check sessions with different active_playback_time values
echo "📊 SESSION ACTIVE PLAYBACK TIME DISTRIBUTION:\n";
echo str_repeat("-", 50) . "\n";

$distribution = DB::table('learning_sessions')
    ->selectRaw('
        COUNT(*) as total_sessions,
        SUM(CASE WHEN active_playback_time IS NULL THEN 1 ELSE 0 END) as null_playback,
        SUM(CASE WHEN active_playback_time = 0 THEN 1 ELSE 0 END) as zero_playback,
        SUM(CASE WHEN active_playback_time > 0 AND active_playback_time <= 60 THEN 1 ELSE 0 END) as low_playback,
        SUM(CASE WHEN active_playback_time > 60 THEN 1 ELSE 0 END) as good_playback
    ')
    ->first();

echo "Total Sessions: {$distribution->total_sessions}\n";
echo "NULL Playback Time: {$distribution->null_playback}\n";
echo "Zero Playback Time: {$distribution->zero_playback}\n";
echo "Low Playback (1-60s): {$distribution->low_playback}\n";
echo "Good Playback (>60s): {$distribution->good_playback}\n\n";

$problemSessions = $distribution->null_playback + $distribution->zero_playback;
$problemPercentage = round(($problemSessions / $distribution->total_sessions) * 100, 1);

echo "🚨 PROBLEM SESSIONS: {$problemSessions} ({$problemPercentage}%)\n\n";

// Check if these sessions have other data we can use
echo "🔍 CHECKING ALTERNATIVE DATA FOR PROBLEM SESSIONS:\n";
echo str_repeat("-", 50) . "\n";

$alternativeData = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('active_playback_time')
              ->orWhere('active_playback_time', 0);
    })
    ->selectRaw('
        COUNT(*) as total_problem_sessions,
        SUM(CASE WHEN session_start IS NOT NULL AND session_end IS NOT NULL THEN 1 ELSE 0 END) as has_timestamps,
        SUM(CASE WHEN total_duration_minutes > 0 THEN 1 ELSE 0 END) as has_duration,
        SUM(CASE WHEN video_completion_percentage > 0 THEN 1 ELSE 0 END) as has_completion,
        AVG(CASE WHEN video_completion_percentage > 0 THEN video_completion_percentage ELSE NULL END) as avg_completion
    ')
    ->first();

echo "Problem Sessions: {$alternativeData->total_problem_sessions}\n";
echo "Have Timestamps: {$alternativeData->has_timestamps}\n";
echo "Have Duration: {$alternativeData->has_duration}\n";
echo "Have Completion: {$alternativeData->has_completion}\n";
echo "Avg Completion: " . round($alternativeData->avg_completion ?? 0, 1) . "%\n\n";

// Sample some problem sessions to see what data they have
echo "📋 SAMPLE PROBLEM SESSIONS:\n";
echo str_repeat("-", 30) . "\n";

$sampleSessions = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('active_playback_time')
              ->orWhere('active_playback_time', 0);
    })
    ->whereNotNull('session_start')
    ->whereNotNull('session_end')
    ->select('id', 'user_id', 'content_id', 'session_start', 'session_end', 
             'total_duration_minutes', 'video_completion_percentage', 'attention_score')
    ->limit(5)
    ->get();

foreach ($sampleSessions as $session) {
    $startTime = new DateTime($session->session_start);
    $endTime = new DateTime($session->session_end);
    $actualDuration = $startTime->diff($endTime)->i; // minutes
    
    echo "Session ID: {$session->id}\n";
    echo "├─ User: {$session->user_id}, Content: {$session->content_id}\n";
    echo "├─ Duration: {$session->session_start} to {$session->session_end}\n";
    echo "├─ Actual Duration: {$actualDuration} minutes\n";
    echo "├─ Stored Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
    echo "├─ Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
    echo "└─ Current Score: " . ($session->attention_score ?? 'NULL') . "\n\n";
}

// Check what percentage of sessions could be improved
$improvableSessions = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('active_playback_time')
              ->orWhere('active_playback_time', 0);
    })
    ->where(function($query) {
        $query->whereNotNull('session_start')
              ->whereNotNull('session_end')
              ->orWhere('video_completion_percentage', '>', 0)
              ->orWhere('total_duration_minutes', '>', 0);
    })
    ->count();

$improvablePercentage = round(($improvableSessions / $distribution->total_sessions) * 100, 1);

echo "💡 POTENTIAL SOLUTION:\n";
echo str_repeat("-", 20) . "\n";
echo "Sessions that could be improved: {$improvableSessions} ({$improvablePercentage}%)\n";
echo "These sessions have timestamps or completion data we could use\n";
echo "to calculate better attention scores instead of defaulting to 0.\n\n";

echo "✅ Analysis completed!\n";