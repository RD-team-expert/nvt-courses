<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking active_playback_time Units Across All Sessions ===\n\n";

// Get sessions with active_playback_time > 0
$sessions = DB::table('learning_sessions')
    ->whereNotNull('active_playback_time')
    ->where('active_playback_time', '>', 0)
    ->whereNotNull('content_id')
    ->select('id', 'user_id', 'content_id', 'active_playback_time', 'session_start', 'session_end', 'video_completion_percentage')
    ->get();

echo "Total sessions with active_playback_time: " . $sessions->count() . "\n\n";

$likelyMinutes = [];
$likelySeconds = [];
$ambiguous = [];

foreach ($sessions as $session) {
    // Get video duration
    $content = DB::table('module_content')->where('id', $session->content_id)->first();
    
    if (!$content || !$content->duration) {
        continue;
    }
    
    $videoDurationSeconds = $content->duration;
    $videoDurationMinutes = round($videoDurationSeconds / 60, 2);
    $activePlaybackTime = $session->active_playback_time;
    
    // Check if active_playback_time makes sense as seconds or minutes
    $asSeconds = round($activePlaybackTime / 60, 2);
    $asMinutes = $activePlaybackTime;
    
    // Compare with video duration
    $secondsRatio = $asSeconds / $videoDurationMinutes;
    $minutesRatio = $asMinutes / $videoDurationMinutes;
    
    // If treating as seconds gives a ratio close to 1, it's likely in seconds
    // If treating as minutes gives a ratio close to 1, it's likely in minutes
    
    if ($secondsRatio >= 0.5 && $secondsRatio <= 1.5) {
        // Likely in seconds (correct)
        $likelySeconds[] = [
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'active_playback_time' => $activePlaybackTime,
            'video_duration_min' => $videoDurationMinutes,
            'as_seconds' => $asSeconds,
            'ratio' => $secondsRatio
        ];
    } elseif ($minutesRatio >= 0.5 && $minutesRatio <= 1.5) {
        // Likely in minutes (WRONG!)
        $likelyMinutes[] = [
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'active_playback_time' => $activePlaybackTime,
            'video_duration_min' => $videoDurationMinutes,
            'as_minutes' => $asMinutes,
            'ratio' => $minutesRatio
        ];
    } else {
        // Ambiguous or corrupted
        $ambiguous[] = [
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'active_playback_time' => $activePlaybackTime,
            'video_duration_min' => $videoDurationMinutes,
            'seconds_ratio' => $secondsRatio,
            'minutes_ratio' => $minutesRatio
        ];
    }
}

echo "=== Analysis Results ===\n";
echo "Likely in SECONDS (correct): " . count($likelySeconds) . "\n";
echo "Likely in MINUTES (wrong!): " . count($likelyMinutes) . "\n";
echo "Ambiguous/Corrupted: " . count($ambiguous) . "\n\n";

if (count($likelyMinutes) > 0) {
    echo "=== Sessions with active_playback_time in MINUTES (WRONG!) ===\n";
    foreach (array_slice($likelyMinutes, 0, 10) as $s) {
        $user = DB::table('users')->where('id', $s['user_id'])->first();
        echo "Session {$s['session_id']} (User: " . ($user ? $user->name : 'Unknown') . "):\n";
        echo "  active_playback_time: {$s['active_playback_time']} (stored value)\n";
        echo "  Video duration: {$s['video_duration_min']} minutes\n";
        echo "  Ratio: {$s['ratio']} (close to 1.0 = likely in minutes)\n";
        echo "  ❌ This should be " . round($s['active_playback_time'] * 60) . " seconds\n\n";
    }
    
    if (count($likelyMinutes) > 10) {
        echo "... and " . (count($likelyMinutes) - 10) . " more\n\n";
    }
}

if (count($ambiguous) > 0) {
    echo "=== Ambiguous/Corrupted Sessions (first 5) ===\n";
    foreach (array_slice($ambiguous, 0, 5) as $s) {
        $user = DB::table('users')->where('id', $s['user_id'])->first();
        echo "Session {$s['session_id']} (User: " . ($user ? $user->name : 'Unknown') . "):\n";
        echo "  active_playback_time: {$s['active_playback_time']}\n";
        echo "  Video duration: {$s['video_duration_min']} minutes\n";
        echo "  As seconds ratio: {$s['seconds_ratio']}\n";
        echo "  As minutes ratio: {$s['minutes_ratio']}\n\n";
    }
}
