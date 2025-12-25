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

// Get Frank's assignments
$assignments = DB::table('course_online_assignments')
    ->where('user_id', $frank->id)
    ->get();

echo "Total Assignments: " . $assignments->count() . "\n\n";

foreach ($assignments as $assignment) {
    echo "--- Assignment ID: {$assignment->id} ---\n";
    echo "Course ID: {$assignment->course_online_id}\n";
    echo "Status: {$assignment->status}\n";
    echo "Progress: {$assignment->progress_percentage}%\n";
    echo "Assigned At: {$assignment->assigned_at}\n";
    
    // Get course name
    $course = DB::table('course_online')->where('id', $assignment->course_online_id)->first();
    echo "Course Name: " . ($course ? $course->name : 'Unknown') . "\n";
    
    // Get learning sessions for this assignment
    $sessions = DB::table('learning_sessions')
        ->where('user_id', $frank->id)
        ->where('course_online_id', $assignment->course_online_id)
        ->get();
    
    echo "Sessions: " . $sessions->count() . "\n";
    
    if ($sessions->count() > 0) {
        $totalMinutes = 0;
        foreach ($sessions as $session) {
            $duration = $session->active_playback_time ?? 0;
            $totalMinutes += $duration;
            echo "  Session {$session->id}: {$duration} minutes (Start: {$session->session_start}, End: {$session->session_end})\n";
        }
        echo "Total Learning Time: {$totalMinutes} minutes\n";
    }
    
    // Get content progress
    $contentProgress = DB::table('user_content_progress')
        ->where('user_id', $frank->id)
        ->whereIn('content_id', function($query) use ($assignment) {
            $query->select('id')
                ->from('module_content')
                ->whereIn('module_id', function($q) use ($assignment) {
                    $q->select('id')
                        ->from('course_modules')
                        ->where('course_online_id', $assignment->course_online_id);
                });
        })
        ->get();
    
    echo "Content Progress Records: " . $contentProgress->count() . "\n";
    foreach ($contentProgress as $cp) {
        echo "  Content {$cp->content_id}: ";
        echo "Completion: " . ($cp->completion_percentage ?? 0) . "%, ";
        echo "Watch Time: " . ($cp->watch_time ?? 0) . "s, ";
        echo "Total Duration: " . ($cp->total_duration ?? 0) . "s, ";
        echo "Is Completed: " . ($cp->is_completed ?? 0) . "\n";
    }
    
    // Get all module contents for this course
    $allContents = DB::table('module_content')
        ->whereIn('module_id', function($q) use ($assignment) {
            $q->select('id')
                ->from('course_modules')
                ->where('course_online_id', $assignment->course_online_id);
        })
        ->get();
    
    echo "Total Contents in Course: " . $allContents->count() . "\n";
    
    echo "\n";
}
