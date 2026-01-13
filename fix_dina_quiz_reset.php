<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\CourseModule;
use App\Models\QuizAttempt;
use App\Models\ModuleQuizResult;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING DINA'S QUIZ ISSUE ===\n\n";

// Find Dina
$dina = User::find(127);
if (!$dina) {
    echo "❌ User Dina not found!\n";
    exit;
}

echo "✅ Found user: {$dina->name}\n";

// Find Module 3 (Communication for Common Situations)
$module3 = CourseModule::find(40);
if (!$module3) {
    echo "❌ Module 3 not found!\n";
    exit;
}

echo "✅ Found module: {$module3->name}\n";

// Find the failed quiz attempt
$failedAttempt = QuizAttempt::where('user_id', $dina->id)
    ->where('quiz_id', 18) // Module 3 quiz
    ->first();

if (!$failedAttempt) {
    echo "❌ No failed attempt found!\n";
    exit;
}

echo "✅ Found failed attempt: ID {$failedAttempt->id}, Score: {$failedAttempt->score}\n";

// Find the module quiz result
$quizResult = ModuleQuizResult::where('user_id', $dina->id)
    ->where('module_id', $module3->id)
    ->first();

if ($quizResult) {
    echo "✅ Found quiz result: Score {$quizResult->best_score}, Passed: " . ($quizResult->passed ? 'Yes' : 'No') . "\n";
}

echo "\n🔧 RESETTING QUIZ ATTEMPT...\n";

try {
    // Delete the failed attempt
    $failedAttempt->delete();
    echo "✅ Deleted failed quiz attempt\n";
    
    // Delete the quiz result
    if ($quizResult) {
        $quizResult->delete();
        echo "✅ Deleted quiz result record\n";
    }
    
    echo "\n🎉 SUCCESS! Dina can now retake Module 3 quiz.\n";
    echo "\nNext steps:\n";
    echo "1. Notify Dina that she can retake the quiz\n";
    echo "2. She should go to Module 3 and click the quiz button\n";
    echo "3. The system will allow her to start a new attempt\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";