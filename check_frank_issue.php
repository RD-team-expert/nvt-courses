<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'frank@nvt360.com')->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "=== FRANK'S DATA ANALYSIS ===\n\n";
echo "User ID: {$user->id}\n";
echo "Name: {$user->name}\n\n";

// Check assignments
$assignments = $user->assignments()->with('course')->get();
echo "--- ASSIGNMENTS ---\n";
echo "Total: {$assignments->count()}\n";
foreach ($assignments as $assignment) {
    echo "  Course: {$assignment->course->title}\n";
    echo "  Status: {$assignment->status}\n";
    echo "  Assigned: {$assignment->assigned_at}\n\n";
}

// Check sessions
$sessions = App\Models\LearningSession::where('user_id', $user->id)->get();
echo "--- LEARNING SESSIONS ---\n";
echo "Total Sessions: {$sessions->count()}\n";
echo "Total Learning Time: " . $sessions->sum('learning_time') . " seconds (" . round($sessions->sum('learning_time')/60, 2) . " minutes)\n\n";

foreach ($sessions as $session) {
    echo "  Session ID: {$session->id}\n";
    echo "  Content ID: {$session->module_content_id}\n";
    echo "  Learning Time: {$session->learning_time}s\n";
    echo "  Status: {$session->status}\n";
    echo "  Created: {$session->created_at}\n\n";
}

// Check progress records
$progress = App\Models\UserContentProgress::where('user_id', $user->id)->get();
echo "--- USER CONTENT PROGRESS RECORDS ---\n";
echo "Total Records: {$progress->count()}\n\n";

foreach ($progress as $p) {
    $content = App\Models\ModuleContent::find($p->module_content_id);
    echo "  Content ID: {$p->module_content_id}\n";
    if ($content) {
        echo "  Content Title: {$content->title}\n";
        echo "  Content Type: {$content->content_type}\n";
    }
    echo "  Status: {$p->status}\n";
    echo "  Progress: {$p->progress_percentage}%\n";
    echo "  Completed At: {$p->completed_at}\n\n";
}

// Check course progress calculation
echo "--- COURSE PROGRESS CALCULATION ---\n";
foreach ($assignments as $assignment) {
    $course = $assignment->course;
    echo "Course: {$course->title}\n";
    
    $totalContent = App\Models\ModuleContent::whereHas('module', function($q) use ($course) {
        $q->where('course_id', $course->id);
    })->count();
    
    $completedContent = App\Models\UserContentProgress::where('user_id', $user->id)
        ->where('status', 'completed')
        ->whereHas('moduleContent.module', function($q) use ($course) {
            $q->where('course_id', $course->id);
        })->count();
    
    echo "  Total Content Items: {$totalContent}\n";
    echo "  Completed Items: {$completedContent}\n";
    
    if ($totalContent > 0) {
        $progressPercent = ($completedContent / $totalContent) * 100;
        echo "  Calculated Progress: " . round($progressPercent, 2) . "%\n";
    } else {
        echo "  Calculated Progress: 0% (no content)\n";
    }
    echo "\n";
}

echo "=== DIAGNOSIS ===\n";
echo "Frank has learning time but 0% progress because:\n";
echo "1. Learning time is tracked from sessions (time spent watching/listening)\n";
echo "2. Progress is calculated from completed content items\n";
echo "3. If Frank watched content but didn't complete it, time is tracked but progress stays 0%\n";
