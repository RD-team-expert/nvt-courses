<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\CourseOnlineReportController;
use Illuminate\Support\Facades\DB;

echo "=== TESTING IMPROVED ATTENTION CALCULATION ===\n\n";

// Create an instance of the controller to access the private method
$controller = new CourseOnlineReportController(
    app(\App\Services\CsvExportService::class),
    app(\App\Services\DepartmentPerformanceService::class)
);

// Use reflection to access the private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('calculateSimulatedAttentionScore');
$method->setAccessible(true);

// Test with some sample sessions that have different data scenarios
$testSessions = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('active_playback_time')
              ->orWhere('active_playback_time', 0);
    })
    ->whereNotNull('session_start')
    ->whereNotNull('session_end')
    ->select('id', 'user_id', 'content_id', 'session_start', 'session_end', 
             'total_duration_minutes', 'video_completion_percentage', 'attention_score',
             'active_playback_time', 'video_skip_count', 'pause_count', 'video_replay_count')
    ->limit(10)
    ->get();

echo "🧪 TESTING " . count($testSessions) . " SAMPLE SESSIONS:\n";
echo str_repeat("-", 60) . "\n";

foreach ($testSessions as $session) {
    echo "Session ID: {$session->id} (User: {$session->user_id}, Content: {$session->content_id})\n";
    
    // Calculate actual duration from timestamps
    $start = new DateTime($session->session_start);
    $end = new DateTime($session->session_end);
    $actualDuration = $start->diff($end)->i + ($start->diff($end)->h * 60); // minutes
    
    echo "├─ Timestamps: " . $start->format('H:i') . " to " . $end->format('H:i') . " ({$actualDuration} min)\n";
    echo "├─ Active Playback: " . ($session->active_playback_time ?? 'NULL') . " seconds\n";
    echo "├─ Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
    echo "├─ Current Score: " . ($session->attention_score ?? 'NULL') . "\n";
    
    // Test the improved calculation
    try {
        $result = $method->invoke(
            $controller,
            $session->session_start,
            $session->session_end,
            $actualDuration,
            $session->content_id,
            $session
        );
        
        echo "├─ NEW Score: {$result['score']}%\n";
        echo "├─ Suspicious: " . ($result['is_suspicious'] ? 'YES' : 'NO') . "\n";
        echo "├─ Details: " . implode(', ', $result['details']) . "\n";
        
        $improvement = $result['score'] - ($session->attention_score ?? 0);
        $improvementText = $improvement > 0 ? "+{$improvement}" : ($improvement < 0 ? "{$improvement}" : "0");
        echo "└─ Improvement: {$improvementText} points\n\n";
        
    } catch (Exception $e) {
        echo "└─ ERROR: " . $e->getMessage() . "\n\n";
    }
}

// Calculate overall improvement potential
echo "📊 OVERALL IMPROVEMENT ANALYSIS:\n";
echo str_repeat("-", 35) . "\n";

$improvementStats = [
    'sessions_tested' => count($testSessions),
    'sessions_with_completion' => 0,
    'sessions_with_timestamps' => 0,
    'average_old_score' => 0,
    'average_new_score' => 0,
];

$totalOldScore = 0;
$totalNewScore = 0;

foreach ($testSessions as $session) {
    if ($session->video_completion_percentage > 0) {
        $improvementStats['sessions_with_completion']++;
    }
    if ($session->session_start && $session->session_end) {
        $improvementStats['sessions_with_timestamps']++;
    }
    
    $totalOldScore += ($session->attention_score ?? 0);
    
    // Calculate new score
    try {
        $start = new DateTime($session->session_start);
        $end = new DateTime($session->session_end);
        $actualDuration = $start->diff($end)->i + ($start->diff($end)->h * 60);
        
        $result = $method->invoke(
            $controller,
            $session->session_start,
            $session->session_end,
            $actualDuration,
            $session->content_id,
            $session
        );
        
        $totalNewScore += $result['score'];
    } catch (Exception $e) {
        // Skip this session
    }
}

$improvementStats['average_old_score'] = round($totalOldScore / count($testSessions), 1);
$improvementStats['average_new_score'] = round($totalNewScore / count($testSessions), 1);

echo "Sessions Tested: {$improvementStats['sessions_tested']}\n";
echo "With Completion Data: {$improvementStats['sessions_with_completion']}\n";
echo "With Timestamps: {$improvementStats['sessions_with_timestamps']}\n";
echo "Average Old Score: {$improvementStats['average_old_score']}%\n";
echo "Average New Score: {$improvementStats['average_new_score']}%\n";
echo "Average Improvement: " . round($improvementStats['average_new_score'] - $improvementStats['average_old_score'], 1) . " points\n";

echo "\n✅ Test completed!\n";