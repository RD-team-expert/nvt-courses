<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING MOST RECENT SESSION ===\n\n";

$recent = DB::table('learning_sessions')
    ->orderBy('id', 'desc')
    ->first();

if ($recent) {
    echo "Most recent session: {$recent->id}\n";
    echo "User: {$recent->user_id}\n";
    echo "Created: {$recent->created_at}\n";
    echo "Session End: " . ($recent->session_end ?? 'NULL') . "\n";
    echo "Last Heartbeat: " . ($recent->last_heartbeat ?? 'NULL') . "\n";
    echo "Active Playback: " . ($recent->active_playback_time ?? 'NULL') . " seconds\n";
    echo "Completion: " . ($recent->video_completion_percentage ?? 'NULL') . "%\n";
    
    if ($recent->last_heartbeat) {
        echo "\n✅ HEARTBEAT SYSTEM IS NOW WORKING!\n";
    } else {
        echo "\n❌ Heartbeat still not working for this session\n";
    }
} else {
    echo "No sessions found\n";
}

echo "\n✅ Check completed!\n";