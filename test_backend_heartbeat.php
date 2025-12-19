<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\ContentView\LearningSessionService;

echo "=== TESTING BACKEND HEARTBEAT PROCESSING ===\n\n";

// Get session 4466
$session = DB::table('learning_sessions')->where('id', 4466)->first();

if (!$session) {
    echo "❌ Session 4466 not found!\n";
    exit;
}

echo "📊 CURRENT SESSION STATE:\n";
echo "Session ID: {$session->id}\n";
echo "User ID: {$session->user_id}\n";
echo "Content ID: {$session->content_id}\n";
echo "Session End: " . ($session->session_end ?? 'NULL') . "\n";
echo "Last Heartbeat: " . ($session->last_heartbeat ?? 'NULL') . "\n";
echo "Active Playback Time: " . ($session->active_playback_time ?? 'NULL') . " seconds\n";
echo "Video Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n\n";

// Test the heartbeat service directly
echo "🧪 TESTING HEARTBEAT SERVICE:\n";
echo str_repeat("-", 30) . "\n";

try {
    $sessionService = new LearningSessionService(
        app(\App\Services\ContentView\ContentProgressService::class)
    );
    
    // Test if we can update the heartbeat
    echo "Attempting to update heartbeat for session {$session->id}...\n";
    
    $updated = $sessionService->updateHeartbeat(
        $session->id,
        100.0, // current_position
        5, // watch_time_increment
        0, // skip_count_increment
        1, // seek_count_increment
        2, // pause_count_increment
        85.5 // completion_percentage
    );
    
    echo "✅ Heartbeat update successful!\n";
    echo "Updated session data:\n";
    echo "├─ Last Heartbeat: {$updated->last_heartbeat}\n";
    echo "├─ Total Duration: {$updated->total_duration_minutes} minutes\n";
    echo "├─ Video Watch Time: {$updated->video_watch_time}\n";
    echo "├─ Pause Count: {$updated->pause_count}\n";
    echo "├─ Seek Count: {$updated->seek_count}\n";
    echo "└─ Video Completion: {$updated->video_completion_percentage}%\n\n";
    
} catch (Exception $e) {
    echo "❌ Heartbeat update failed: " . $e->getMessage() . "\n\n";
}

// Check if the database was actually updated
echo "🔍 VERIFYING DATABASE UPDATE:\n";
echo str_repeat("-", 30) . "\n";

$updatedSession = DB::table('learning_sessions')->where('id', 4466)->first();

echo "After heartbeat test:\n";
echo "├─ Last Heartbeat: " . ($updatedSession->last_heartbeat ?? 'NULL') . "\n";
echo "├─ Total Duration: " . ($updatedSession->total_duration_minutes ?? 'NULL') . " minutes\n";
echo "├─ Video Watch Time: " . ($updatedSession->video_watch_time ?? 'NULL') . "\n";
echo "├─ Pause Count: " . ($updatedSession->pause_count ?? 'NULL') . "\n";
echo "├─ Seek Count: " . ($updatedSession->seek_count ?? 'NULL') . "\n";
echo "└─ Video Completion: " . ($updatedSession->video_completion_percentage ?? 'NULL') . "%\n\n";

// Check if there are any database constraints or issues
echo "🔍 DATABASE SCHEMA CHECK:\n";
echo str_repeat("-", 25) . "\n";

try {
    $columns = DB::select("SHOW COLUMNS FROM learning_sessions LIKE 'last_heartbeat'");
    if (!empty($columns)) {
        $column = $columns[0];
        echo "last_heartbeat column exists:\n";
        echo "├─ Type: {$column->Type}\n";
        echo "├─ Null: {$column->Null}\n";
        echo "└─ Default: " . ($column->Default ?? 'NULL') . "\n\n";
    } else {
        echo "❌ last_heartbeat column does not exist!\n\n";
    }
} catch (Exception $e) {
    echo "❌ Schema check failed: " . $e->getMessage() . "\n\n";
}

echo "✅ Backend heartbeat test completed!\n";