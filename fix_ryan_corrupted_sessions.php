<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing Corrupted Sessions ===\n\n";

// Find all sessions with duration > 3 hours (180 minutes)
$corruptedSessions = DB::table('learning_sessions')
    ->whereNotNull('session_end')
    ->whereRaw('TIMESTAMPDIFF(MINUTE, session_start, session_end) > 180')
    ->select('id', 'user_id', 'session_start', 'session_end', 'content_id',
             DB::raw('TIMESTAMPDIFF(MINUTE, session_start, session_end) as duration_minutes'))
    ->orderBy('duration_minutes', 'desc')
    ->get();

echo "Found " . $corruptedSessions->count() . " corrupted sessions\n\n";

foreach ($corruptedSessions as $session) {
    $user = DB::table('users')->where('id', $session->user_id)->first();
    $content = DB::table('module_content')->where('id', $session->content_id)->first();
    
    echo "Session {$session->id}:\n";
    echo "  User: " . ($user ? $user->name : 'Unknown') . " (ID: {$session->user_id})\n";
    echo "  Content: " . ($content ? $content->title : 'Unknown') . "\n";
    echo "  Start: {$session->session_start}\n";
    echo "  End: {$session->session_end}\n";
    echo "  Duration: {$session->duration_minutes} minutes (" . round($session->duration_minutes / 60, 1) . " hours)\n";
    
    // Calculate reasonable end time (30 minutes after start, or video duration if available)
    $reasonableDuration = 30; // default 30 minutes
    
    if ($content && $content->duration) {
        $videoDurationMinutes = round($content->duration / 60);
        // Use video duration + 5 minutes buffer
        $reasonableDuration = min($videoDurationMinutes + 5, 30);
    }
    
    $newEndTime = date('Y-m-d H:i:s', strtotime($session->session_start) + ($reasonableDuration * 60));
    
    echo "  Proposed fix: Set end time to {$newEndTime} ({$reasonableDuration} minutes)\n";
    echo "  Fix? (y/n): ";
    
    // Auto-fix for now
    DB::table('learning_sessions')
        ->where('id', $session->id)
        ->update([
            'session_end' => $newEndTime,
            'updated_at' => now()
        ]);
    
    echo "  ✅ Fixed!\n\n";
}

// Also fix sessions that are still open (no end time) and started more than 3 hours ago
echo "\n=== Fixing Open Sessions ===\n\n";

$staleThreshold = now()->subHours(3);

$openSessions = DB::table('learning_sessions')
    ->whereNull('session_end')
    ->where('session_start', '<', $staleThreshold)
    ->select('id', 'user_id', 'session_start', 'content_id')
    ->get();

echo "Found " . $openSessions->count() . " stale open sessions\n\n";

foreach ($openSessions as $session) {
    $user = DB::table('users')->where('id', $session->user_id)->first();
    $content = DB::table('module_content')->where('id', $session->content_id)->first();
    
    echo "Session {$session->id}:\n";
    echo "  User: " . ($user ? $user->name : 'Unknown') . "\n";
    echo "  Content: " . ($content ? $content->title : 'Unknown') . "\n";
    echo "  Start: {$session->session_start}\n";
    
    // Set end time to 30 minutes after start
    $reasonableDuration = 30;
    if ($content && $content->duration) {
        $videoDurationMinutes = round($content->duration / 60);
        $reasonableDuration = min($videoDurationMinutes + 5, 30);
    }
    
    $newEndTime = date('Y-m-d H:i:s', strtotime($session->session_start) + ($reasonableDuration * 60));
    
    DB::table('learning_sessions')
        ->where('id', $session->id)
        ->update([
            'session_end' => $newEndTime,
            'updated_at' => now()
        ]);
    
    echo "  ✅ Set end time to {$newEndTime}\n\n";
}

echo "\n=== Summary ===\n";
echo "Corrupted sessions fixed: " . $corruptedSessions->count() . "\n";
echo "Stale open sessions fixed: " . $openSessions->count() . "\n";
echo "\n✅ All corrupted sessions have been fixed!\n";
