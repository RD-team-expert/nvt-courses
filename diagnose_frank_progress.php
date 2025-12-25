<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FRANK'S PROGRESS DIAGNOSIS ===\n\n";

// Find Frank
$user = DB::table('users')->where('email', 'frank@nvt360.com')->first();

if (!$user) {
    echo "❌ Frank not found\n";
    exit;
}

echo "✅ User Found: {$user->name} (ID: {$user->id})\n\n";

// Check assignments
echo "--- ASSIGNMENTS ---\n";
$assignments = DB::table('course_online_assignments')
    ->join('course_online', 'course_online_assignments.course_online_id', '=', 'course_online.id')
    ->where('course_online_assignments.user_id', $user->id)
    ->select(
        'course_online_assignments.*',
        'course_online.name as course_name'
    )
    ->get();

echo "Total Assignments: " . $assignments->count() . "\n\n";

foreach ($assignments as $assignment) {
    echo "Course: {$assignment->course_name}\n";
    echo "Status: {$assignment->status}\n";
    echo "Progress: {$assignment->progress_percentage}%\n";
    echo "Assigned: {$assignment->assigned_at}\n";
    echo "Started: " . ($assignment->started_at ?? 'Not started') . "\n";
    echo "Completed: " . ($assignment->completed_at ?? 'Not completed') . "\n\n";
}

// Check learning sessions
echo "--- LEARNING SESSIONS ---\n";
$sessions = DB::table('learning_sessions')
    ->where('user_id', $user->id)
    ->get();

echo "Total Sessions: " . $sessions->count() . "\n";

$totalMinutes = 0;
foreach ($sessions as $session) {
    $duration = $session->total_duration_minutes ?? 0;
    $totalMinutes += $duration;
    
    echo "\nSession ID: {$session->id}\n";
    echo "  Course ID: " . ($session->course_online_id ?? 'N/A') . "\n";
    echo "  Content ID: " . ($session->content_id ?? 'N/A') . "\n";
    echo "  Start: {$session->session_start}\n";
    echo "  End: " . ($session->session_end ?? 'Active') . "\n";
    echo "  Duration: {$duration} minutes\n";
    echo "  Active Playback Time: " . ($session->active_playback_time ?? 0) . " seconds\n";
    echo "  Attention Score: " . ($session->attention_score ?? 0) . "\n";
}

echo "\nTotal Learning Time: {$totalMinutes} minutes (" . round($totalMinutes / 60, 2) . " hours)\n\n";

// Check content progress
echo "--- CONTENT PROGRESS ---\n";
$progress = DB::table('user_content_progress')
    ->where('user_id', $user->id)
    ->get();

echo "Total Progress Records: " . $progress->count() . "\n\n";

$completedCount = 0;
foreach ($progress as $p) {
    $content = DB::table('module_content')->where('id', $p->content_id)->first();
    
    echo "Content ID: {$p->content_id}\n";
    if ($content) {
        echo "  Title: {$content->title}\n";
        echo "  Type: {$content->content_type}\n";
    }
    echo "  Is Completed: " . ($p->is_completed ? 'Yes' : 'No') . "\n";
    echo "  Progress: " . ($p->progress_percentage ?? 0) . "%\n";
    echo "  Watch Time: " . ($p->watch_time ?? 0) . " seconds\n";
    echo "  Completed At: " . ($p->completed_at ?? 'Not completed') . "\n\n";
    
    if ($p->is_completed) {
        $completedCount++;
    }
}

// Calculate expected progress
echo "--- PROGRESS CALCULATION ---\n";
foreach ($assignments as $assignment) {
    echo "Course: {$assignment->course_name}\n";
    
    // Get total content in course
    $totalContent = DB::table('module_content')
        ->join('course_modules', 'module_content.module_id', '=', 'course_modules.id')
        ->where('course_modules.course_online_id', $assignment->course_online_id)
        ->count();
    
    // Get completed content for this user in this course
    $completedContent = DB::table('user_content_progress')
        ->join('module_content', 'user_content_progress.content_id', '=', 'module_content.id')
        ->join('course_modules', 'module_content.module_id', '=', 'course_modules.id')
        ->where('user_content_progress.user_id', $user->id)
        ->where('course_modules.course_online_id', $assignment->course_online_id)
        ->where('user_content_progress.is_completed', true)
        ->count();
    
    echo "  Total Content Items: {$totalContent}\n";
    echo "  Completed Items: {$completedContent}\n";
    
    if ($totalContent > 0) {
        $calculatedProgress = ($completedContent / $totalContent) * 100;
        echo "  Calculated Progress: " . round($calculatedProgress, 2) . "%\n";
    } else {
        echo "  Calculated Progress: 0% (no content found)\n";
    }
    
    echo "  Stored Progress: {$assignment->progress_percentage}%\n\n";
}

echo "\n=== DIAGNOSIS SUMMARY ===\n";
echo "Frank has {$totalMinutes} minutes of learning time but {$assignments->first()->progress_percentage}% progress.\n\n";

echo "POSSIBLE REASONS:\n";
echo "1. Learning time is tracked from session duration (time spent watching)\n";
echo "2. Progress is calculated from completed content items\n";
echo "3. If Frank watched content but didn't complete it (e.g., didn't finish videos), time is tracked but progress stays 0%\n";
echo "4. The session might not be properly linked to content (check content_id in sessions)\n";
echo "5. The content progress might not be marked as completed (check is_completed flag)\n\n";

if ($sessions->count() > 0 && $progress->count() == 0) {
    echo "⚠️  ISSUE FOUND: Frank has sessions but NO content progress records!\n";
    echo "This means the progress tracking is not working properly.\n";
} elseif ($sessions->count() > 0 && $completedCount == 0) {
    echo "⚠️  ISSUE FOUND: Frank has sessions and progress records, but NO completed content!\n";
    echo "This means Frank is watching content but not completing it.\n";
} elseif ($sessions->count() == 0) {
    echo "⚠️  ISSUE FOUND: Frank has NO learning sessions!\n";
    echo "The 12 minutes shown in the report might be calculated incorrectly.\n";
}
