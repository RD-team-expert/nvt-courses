<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Find Frank
$frank = User::where('email', 'frank@nvt360.com')->first();

if (!$frank) {
    echo "Frank not found\n";
    exit;
}

echo "=== Checking Frank's Actual Report Data ===\n\n";

// Get session data exactly as the report controller does
$sessionQuery = DB::table('learning_sessions')->where('user_id', $frank->id);

$rawSessions = $sessionQuery->select(
    'id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
    'video_completion_percentage', 'content_id', 'active_playback_time'
)->get();

echo "Total Sessions: " . $rawSessions->count() . "\n\n";

$totalRealMinutes = 0;

foreach ($rawSessions as $session) {
    echo "Session {$session->id}:\n";
    echo "  active_playback_time: " . ($session->active_playback_time ?? 'NULL') . "\n";
    echo "  session_start: {$session->session_start}\n";
    echo "  session_end: " . ($session->session_end ?? 'NULL') . "\n";
    
    $activePlaybackTime = $session->active_playback_time ?? null;
    $contentId = $session->content_id ?? null;
    
    // Simulate getActualSessionDuration logic
    $duration = 0;
    
    // Priority 1: Use active_playback_time if available
    if ($activePlaybackTime && $activePlaybackTime > 0) {
        // ❌ BUG: Code assumes seconds, but it's actually minutes!
        $duration = round($activePlaybackTime / 60, 2);
        echo "  Duration calculation: {$activePlaybackTime} / 60 = {$duration} minutes\n";
        echo "  ❌ BUG: Code assumes active_playback_time is in SECONDS\n";
        echo "  ✅ FIX: active_playback_time is already in MINUTES\n";
    } else if ($session->session_start && $session->session_end) {
        $start = Carbon::parse($session->session_start);
        $end = Carbon::parse($session->session_end);
        $duration = $start->diffInMinutes($end);
        echo "  Duration calculation: timestamp diff = {$duration} minutes\n";
    } else {
        echo "  Duration: 0 (no data)\n";
    }
    
    $totalRealMinutes += $duration;
    echo "\n";
}

echo "=== Summary ===\n";
echo "Total Minutes (WRONG): {$totalRealMinutes}\n";
echo "Total Hours (WRONG): " . round($totalRealMinutes / 60, 1) . "\n";
echo "Displayed in UI (hours * 60): " . round(($totalRealMinutes / 60) * 60) . "m\n\n";

echo "=== Correct Calculation ===\n";
$correctMinutes = 0;
foreach ($rawSessions as $session) {
    $activePlaybackTime = $session->active_playback_time ?? 0;
    if ($activePlaybackTime > 0) {
        // active_playback_time is ALREADY in minutes, don't divide!
        $correctMinutes += $activePlaybackTime;
    }
}
echo "Total Minutes (CORRECT): {$correctMinutes}\n";
echo "Total Hours (CORRECT): " . round($correctMinutes / 60, 1) . "\n";
echo "Displayed in UI (hours * 60): " . round(($correctMinutes / 60) * 60) . "m\n";
