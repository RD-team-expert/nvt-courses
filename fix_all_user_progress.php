<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Batch Progress Recalculation ===\n\n";

// Get all in-progress assignments
$assignments = DB::table('course_online_assignments')
    ->where('status', 'in_progress')
    ->get();

echo "Found " . $assignments->count() . " in-progress assignments\n\n";

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($assignments as $assignment) {
    try {
        // Get all required content for this course
        $requiredContent = DB::table('module_content')
            ->join('course_modules', 'module_content.module_id', '=', 'course_modules.id')
            ->where('course_modules.course_online_id', $assignment->course_online_id)
            ->where('course_modules.is_required', true)
            ->where('module_content.is_required', true)
            ->select('module_content.id')
            ->get();

        if ($requiredContent->count() === 0) {
            echo "⚠️  Assignment {$assignment->id}: No required content, skipping\n";
            $skipped++;
            continue;
        }

        // Calculate average progress
        $totalCompletion = 0;
        foreach ($requiredContent as $content) {
            $progress = DB::table('user_content_progress')
                ->where('user_id', $assignment->user_id)
                ->where('content_id', $content->id)
                ->first();
            
            $completion = $progress ? ($progress->completion_percentage ?? 0) : 0;
            $totalCompletion += $completion;
        }

        $averageProgress = round($totalCompletion / $requiredContent->count(), 2);
        
        // Only update if progress changed
        if (abs($averageProgress - $assignment->progress_percentage) > 0.01) {
            DB::table('course_online_assignments')
                ->where('id', $assignment->id)
                ->update([
                    'progress_percentage' => $averageProgress,
                    'updated_at' => now()
                ]);
            
            echo "✅ Assignment {$assignment->id} (User {$assignment->user_id}): {$assignment->progress_percentage}% → {$averageProgress}%\n";
            $updated++;
        } else {
            $skipped++;
        }
    } catch (Exception $e) {
        echo "❌ Error processing assignment {$assignment->id}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Total Assignments: " . $assignments->count() . "\n";
echo "Updated: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "Errors: {$errors}\n";
echo "\n✅ Batch recalculation complete!\n";
