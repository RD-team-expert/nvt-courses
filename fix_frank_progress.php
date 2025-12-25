<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find Frank's user
$frank = DB::table('users')->where('email', 'frank@nvt360.com')->first();

if (!$frank) {
    echo "Frank not found\n";
    exit;
}

echo "Frank User ID: {$frank->id}\n";
echo "Frank Name: {$frank->name}\n\n";

// Get Frank's assignment
$assignment = DB::table('course_online_assignments')
    ->where('user_id', $frank->id)
    ->first();

if (!$assignment) {
    echo "No assignment found\n";
    exit;
}

echo "Assignment ID: {$assignment->id}\n";
echo "Course ID: {$assignment->course_online_id}\n";
echo "Current Progress: {$assignment->progress_percentage}%\n\n";

// Get all required content for this course
$requiredContent = DB::table('module_content')
    ->join('course_modules', 'module_content.module_id', '=', 'course_modules.id')
    ->where('course_modules.course_online_id', $assignment->course_online_id)
    ->where('course_modules.is_required', true)
    ->where('module_content.is_required', true)
    ->select('module_content.id', 'module_content.title')
    ->get();

echo "Total Required Content: " . $requiredContent->count() . "\n";

if ($requiredContent->count() === 0) {
    echo "No required content found. Cannot calculate progress.\n";
    exit;
}

// Get Frank's progress on all required content
$totalCompletion = 0;
$completedCount = 0;

foreach ($requiredContent as $content) {
    $progress = DB::table('user_content_progress')
        ->where('user_id', $frank->id)
        ->where('content_id', $content->id)
        ->first();
    
    if ($progress) {
        $completion = $progress->completion_percentage ?? 0;
        $totalCompletion += $completion;
        
        if ($progress->is_completed) {
            $completedCount++;
        }
        
        echo "  Content {$content->id} ({$content->title}): {$completion}% " . 
             ($progress->is_completed ? "[COMPLETED]" : "[IN PROGRESS]") . "\n";
    } else {
        echo "  Content {$content->id} ({$content->title}): 0% [NOT STARTED]\n";
    }
}

// Calculate average progress
$averageProgress = round($totalCompletion / $requiredContent->count(), 2);

echo "\n--- Calculation ---\n";
echo "Total Completion Sum: {$totalCompletion}%\n";
echo "Average Progress: {$averageProgress}%\n";
echo "Completed Content: {$completedCount}/{$requiredContent->count()}\n";
echo "Completion-based Progress: " . round(($completedCount / $requiredContent->count()) * 100, 2) . "%\n";

// Update the assignment with the correct progress
echo "\n--- Updating Assignment ---\n";
echo "Old Progress: {$assignment->progress_percentage}%\n";
echo "New Progress: {$averageProgress}%\n";

DB::table('course_online_assignments')
    ->where('id', $assignment->id)
    ->update([
        'progress_percentage' => $averageProgress,
        'updated_at' => now()
    ]);

echo "\n✅ Assignment progress updated successfully!\n";
