<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "=== SIMPLE CALCULATION TEST ===\n\n";

// Reset session 4459 to have no tracking data
DB::table('learning_sessions')
    ->where('id', 4459)
    ->update([
        'session_end' => null,
        'active_playback_time' => 0
    ]);

$session = DB::table('learning_sessions')
    ->where('id', 4459)
    ->first();

echo "Session ID: {$session->id}\n";
echo "Session Start: {$session->session_start}\n";
echo "Session End: " . ($session->session_end ?? 'NULL') . "\n";
echo "Active Playback: {$session->active_playback_time} seconds\n";
echo "\n";

// Get completion time
$progress = DB::table('user_content_progress')
    ->where('user_id', $session->user_id)
    ->where('content_id', $session->content_id)
    ->where('is_completed', true)
    ->first();

if ($progress && $progress->completed_at) {
    echo "Completed At: {$progress->completed_at}\n\n";
    
    $start = Carbon::parse($session->session_start);
    $completed = Carbon::parse($progress->completed_at);
    $minutes = $start->diffInMinutes($completed);
    
    echo "=== CALCULATION ===\n";
    echo "Completed At - Session Start = {$minutes} minutes\n";
    echo "\n";
    echo "✅ Should display: {$minutes}m (NOT " . round($minutes * 0.6) . "m)\n";
} else {
    echo "❌ No completion data\n";
}
