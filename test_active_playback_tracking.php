<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ACTIVE PLAYBACK TIME TRACKING TEST ===\n\n";

// Check recent sessions with active_playback_time data
echo "🔍 CHECKING RECENT SESSIONS WITH ACTIVE PLAYBACK TIME:\n";
echo str_repeat("-", 50) . "\n";

$recentSessions = DB::table('learning_sessions')
    ->where('created_at', '>=', now()->subDays(7))
    ->whereNotNull('active_playback_time')
    ->where('active_playback_time', '>', 0)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentSessions->isEmpty()) {
    echo "❌ NO recent sessions with active_playback_time > 0 found!\n";
    
    // Check if there are any recent sessions at all
    $anyRecentSessions = DB::table('learning_sessions')
        ->where('created_at', '>=', now()->subDays(7))
        ->count();
    
    echo "📊 Total recent sessions (last 7 days): {$anyRecentSessions}\n";
    
    if ($anyRecentSessions > 0) {
        echo "\n🔍 SAMPLE OF RECENT SESSIONS (showing tracking data):\n";
        $sampleSessions = DB::table('learning_sessions')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($sampleSessions as $session) {
            echo "  Session ID: {$session->id}\n";
            echo "  ├─ Created: {$session->created_at}\n";
            echo "  ├─ User ID: {$session->user_id}\n";
            echo "  ├─ Content ID: {$session->content_id}\n";
            echo "  ├─ Active Playback Time: " . ($session->active_playback_time ?? 'NULL') . " seconds\n";
            echo "  ├─ Total Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
            echo "  ├─ Video Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
            echo "  ├─ Pause Count: " . ($session->pause_count ?? 'NULL') . "\n";
            echo "  ├─ Skip Count: " . ($session->video_skip_count ?? 'NULL') . "\n";
            echo "  └─ Session End: " . ($session->session_end ?? 'NULL') . "\n\n";
        }
    }
} else {
    echo "✅ Found " . count($recentSessions) . " recent sessions with active playback time!\n\n";
    
    foreach ($recentSessions as $session) {
        echo "  Session ID: {$session->id}\n";
        echo "  ├─ User ID: {$session->user_id}\n";
        echo "  ├─ Content ID: {$session->content_id}\n";
        echo "  ├─ Active Playback Time: {$session->active_playback_time} seconds\n";
        echo "  ├─ Total Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
        echo "  ├─ Video Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
        echo "  └─ Created: {$session->created_at}\n\n";
    }
}

// Check if the active_playback_time column exists and has the right data type
echo "\n🔍 DATABASE SCHEMA CHECK:\n";
echo str_repeat("-", 30) . "\n";

try {
    $columnInfo = DB::select("SHOW COLUMNS FROM learning_sessions LIKE 'active_playback_time'");
    
    if (empty($columnInfo)) {
        echo "❌ Column 'active_playback_time' does NOT exist in learning_sessions table!\n";
    } else {
        $column = $columnInfo[0];
        echo "✅ Column 'active_playback_time' exists\n";
        echo "  ├─ Type: {$column->Type}\n";
        echo "  ├─ Null: {$column->Null}\n";
        echo "  └─ Default: " . ($column->Default ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking column: " . $e->getMessage() . "\n";
}

// Check for any sessions that have been updated recently (heartbeat activity)
echo "\n🔍 RECENT HEARTBEAT ACTIVITY:\n";
echo str_repeat("-", 30) . "\n";

$recentHeartbeats = DB::table('learning_sessions')
    ->where('last_heartbeat', '>=', now()->subHours(1))
    ->orderBy('last_heartbeat', 'desc')
    ->limit(5)
    ->get();

if ($recentHeartbeats->isEmpty()) {
    echo "❌ No recent heartbeat activity (last 1 hour)\n";
} else {
    echo "✅ Found " . count($recentHeartbeats) . " sessions with recent heartbeat activity:\n\n";
    
    foreach ($recentHeartbeats as $session) {
        echo "  Session ID: {$session->id}\n";
        echo "  ├─ User ID: {$session->user_id}\n";
        echo "  ├─ Last Heartbeat: {$session->last_heartbeat}\n";
        echo "  ├─ Active Playback Time: " . ($session->active_playback_time ?? 'NULL') . " seconds\n";
        echo "  └─ Session End: " . ($session->session_end ?? 'ACTIVE') . "\n\n";
    }
}

// Check if there are any active sessions right now
echo "\n🔍 CURRENTLY ACTIVE SESSIONS:\n";
echo str_repeat("-", 30) . "\n";

$activeSessions = DB::table('learning_sessions')
    ->whereNull('session_end')
    ->orderBy('session_start', 'desc')
    ->limit(5)
    ->get();

if ($activeSessions->isEmpty()) {
    echo "ℹ️  No currently active sessions\n";
} else {
    echo "✅ Found " . count($activeSessions) . " active sessions:\n\n";
    
    foreach ($activeSessions as $session) {
        echo "  Session ID: {$session->id}\n";
        echo "  ├─ User ID: {$session->user_id}\n";
        echo "  ├─ Started: {$session->session_start}\n";
        echo "  ├─ Last Heartbeat: " . ($session->last_heartbeat ?? 'NULL') . "\n";
        echo "  └─ Active Playback Time: " . ($session->active_playback_time ?? 'NULL') . " seconds\n\n";
    }
}

echo "✅ Test completed!\n";