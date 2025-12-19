<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BackupLearningTimeCalculator;
use Carbon\Carbon;

echo "=== BACKUP LEARNING TIME CALCULATION TEST ===\n\n";

// Test with Session 4459 data
$sessionId = 4459;
$userId = 131; // Adjust if needed
$courseOnlineId = null; // Will be fetched from session

// Get session details
$session = DB::table('learning_sessions')
    ->where('id', $sessionId)
    ->first();

if (!$session) {
    echo "Session $sessionId not found!\n";
    exit;
}

echo "Session ID: {$session->id}\n";
echo "User ID: {$session->user_id}\n";
echo "Course Online ID: {$session->course_online_id}\n";
echo "Content ID: {$session->content_id}\n";
echo "Session Start: {$session->session_start}\n";
echo "Session End: " . ($session->session_end ?? 'NULL (active)') . "\n";
echo "Stored Duration: {$session->total_duration_minutes} minutes\n";
echo "Active Playback Time: {$session->active_playback_time} seconds\n";
echo "\n";

$userId = $session->user_id;
$courseOnlineId = $session->course_online_id;

// Get content details
if ($session->content_id) {
    $content = DB::table('module_content')
        ->where('id', $session->content_id)
        ->first();
    
    if ($content) {
        echo "=== Content Details ===\n";
        echo "Content Type: {$content->content_type}\n";
        echo "Content Duration: " . ($content->duration ?? 0) . " seconds\n";
        echo "Content Title: {$content->title}\n";
        echo "\n";
    }
}

// Get assignment details
$assignment = DB::table('course_online_assignments')
    ->where('user_id', $userId)
    ->where('course_online_id', $courseOnlineId)
    ->first();

if ($assignment) {
    echo "=== Assignment Details ===\n";
    echo "Assigned At: {$assignment->assigned_at}\n";
    echo "Started At: " . ($assignment->started_at ?? 'NULL') . "\n";
    echo "Completed At: " . ($assignment->completed_at ?? 'NULL') . "\n";
    echo "Status: {$assignment->status}\n";
    echo "Progress: {$assignment->progress_percentage}%\n";
    echo "\n";
}

// Get content progress
$contentProgress = DB::table('user_content_progress')
    ->where('user_id', $userId)
    ->where('course_online_id', $courseOnlineId)
    ->get();

echo "=== Content Progress ===\n";
echo "Total Content Items: " . $contentProgress->count() . "\n";
echo "Completed Items: " . $contentProgress->where('is_completed', true)->count() . "\n";

foreach ($contentProgress as $progress) {
    $content = DB::table('module_content')->where('id', $progress->content_id)->first();
    echo "\nContent: " . ($content->title ?? 'Unknown') . "\n";
    echo "  Type: {$progress->content_type}\n";
    echo "  Completed: " . ($progress->is_completed ? 'Yes' : 'No') . "\n";
    echo "  Completed At: " . ($progress->completed_at ?? 'NULL') . "\n";
    echo "  Watch Time: {$progress->watch_time} seconds\n";
    echo "  Total Duration: " . ($progress->total_duration ?? 0) . " seconds\n";
}

echo "\n=== BACKUP CALCULATION ===\n\n";

// Run backup calculator
$calculator = new BackupLearningTimeCalculator();
$detailedReport = $calculator->getDetailedReport($userId, $courseOnlineId);

echo "Primary Tracking Status:\n";
echo "  Has Sessions: " . ($detailedReport['primary_tracking']['has_sessions'] ? 'Yes' : 'No') . "\n";
echo "  Sessions Count: {$detailedReport['primary_tracking']['sessions_count']}\n";
echo "  Has Valid Duration: " . ($detailedReport['primary_tracking']['has_valid_duration'] ? 'Yes' : 'No') . "\n";
echo "  Has Valid Playback Time: " . ($detailedReport['primary_tracking']['has_valid_playback_time'] ? 'Yes' : 'No') . "\n";
echo "  Has Session End Times: " . ($detailedReport['primary_tracking']['has_session_end_times'] ? 'Yes' : 'No') . "\n";
echo "  Needs Backup: " . ($detailedReport['primary_tracking']['needs_backup'] ? 'YES' : 'NO') . "\n";
echo "\n";

echo "Backup Calculation Result:\n";
echo "  Strategy Used: {$detailedReport['backup_calculation']['strategy_used']}\n";
echo "  Total Minutes: {$detailedReport['backup_calculation']['total_minutes']}\n";
echo "  Total Seconds: {$detailedReport['backup_calculation']['total_seconds']}\n";
if (isset($detailedReport['backup_calculation']['method'])) {
    echo "  Method: {$detailedReport['backup_calculation']['method']}\n";
}
if (isset($detailedReport['backup_calculation']['warning'])) {
    echo "  Warning: {$detailedReport['backup_calculation']['warning']}\n";
}
echo "\n";

echo "All Strategies Attempted:\n\n";

foreach ($detailedReport['all_strategies'] as $strategyName => $result) {
    echo "  {$strategyName}:\n";
    echo "    Minutes: {$result['total_minutes']}\n";
    echo "    Seconds: {$result['total_seconds']}\n";
    if (isset($result['method'])) {
        echo "    Method: {$result['method']}\n";
    }
    if (isset($result['sessions_count'])) {
        echo "    Sessions: {$result['sessions_count']}\n";
    }
    if (isset($result['completed_content_count'])) {
        echo "    Completed Content: {$result['completed_content_count']}\n";
    }
    if (isset($result['is_estimate'])) {
        echo "    Is Estimate: Yes\n";
    }
    echo "\n";
}

echo "=== RECOMMENDATION ===\n";
if ($detailedReport['primary_tracking']['needs_backup']) {
    echo "✓ Backup calculation is NEEDED and ACTIVE\n";
    echo "✓ Using: {$detailedReport['backup_calculation']['strategy_used']}\n";
    echo "✓ Calculated Time: {$detailedReport['backup_calculation']['total_minutes']} minutes\n";
} else {
    echo "✓ Primary tracking data is available\n";
    echo "✓ Backup calculation not needed\n";
}

echo "\nGenerated at: {$detailedReport['generated_at']}\n";
