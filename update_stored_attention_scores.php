<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\CourseOnlineReportController;
use Illuminate\Support\Facades\DB;

echo "=== UPDATING STORED ATTENTION SCORES ===\n\n";

// Create an instance of the controller to access the private method
$controller = new CourseOnlineReportController(
    app(\App\Services\CsvExportService::class),
    app(\App\Services\DepartmentPerformanceService::class)
);

// Use reflection to access the private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('calculateSimulatedAttentionScore');
$method->setAccessible(true);

// Get sessions that have low attention scores but might be improvable
$sessionsToUpdate = DB::table('learning_sessions')
    ->where(function($query) {
        $query->where('attention_score', '<', 50)
              ->orWhereNull('attention_score');
    })
    ->where(function($query) {
        $query->whereNotNull('session_start')
              ->whereNotNull('session_end')
              ->orWhere('video_completion_percentage', '>', 0);
    })
    ->select('id', 'user_id', 'content_id', 'session_start', 'session_end', 
             'total_duration_minutes', 'video_completion_percentage', 'attention_score',
             'active_playback_time', 'video_skip_count', 'pause_count', 'video_replay_count')
    ->limit(100) // Process in batches
    ->get();

echo "🔄 PROCESSING " . count($sessionsToUpdate) . " SESSIONS:\n";
echo str_repeat("-", 40) . "\n";

$updatedCount = 0;
$improvedCount = 0;
$totalImprovement = 0;

foreach ($sessionsToUpdate as $session) {
    try {
        // Calculate actual duration from timestamps
        $actualDuration = 0;
        if ($session->session_start && $session->session_end) {
            $start = new DateTime($session->session_start);
            $end = new DateTime($session->session_end);
            $totalMinutes = $start->diff($end)->days * 24 * 60 + 
                           $start->diff($end)->h * 60 + 
                           $start->diff($end)->i;
            
            // Only use if reasonable
            if ($totalMinutes >= 1 && $totalMinutes <= 180) {
                $actualDuration = $totalMinutes;
            }
        }
        
        // Calculate new attention score
        $result = $method->invoke(
            $controller,
            $session->session_start,
            $session->session_end,
            $actualDuration,
            $session->content_id,
            $session
        );
        
        $newScore = $result['score'];
        $oldScore = $session->attention_score ?? 0;
        
        // Only update if the new score is significantly better
        if ($newScore > $oldScore + 5) {
            DB::table('learning_sessions')
                ->where('id', $session->id)
                ->update([
                    'attention_score' => $newScore,
                    'is_suspicious_activity' => $result['is_suspicious']
                ]);
            
            $improvement = $newScore - $oldScore;
            $totalImprovement += $improvement;
            $improvedCount++;
            
            echo "✅ Session {$session->id}: {$oldScore}% → {$newScore}% (+{$improvement})\n";
        } else {
            echo "⏭️  Session {$session->id}: {$oldScore}% → {$newScore}% (no update)\n";
        }
        
        $updatedCount++;
        
    } catch (Exception $e) {
        echo "❌ Session {$session->id}: Error - " . $e->getMessage() . "\n";
    }
}

echo "\n📊 UPDATE SUMMARY:\n";
echo str_repeat("-", 20) . "\n";
echo "Sessions Processed: {$updatedCount}\n";
echo "Sessions Improved: {$improvedCount}\n";
echo "Total Improvement: {$totalImprovement} points\n";
echo "Average Improvement: " . ($improvedCount > 0 ? round($totalImprovement / $improvedCount, 1) : 0) . " points per session\n";

// Check overall improvement in database
echo "\n🔍 DATABASE IMPACT CHECK:\n";
echo str_repeat("-", 25) . "\n";

$beforeAfter = DB::table('learning_sessions')
    ->selectRaw('
        COUNT(*) as total_sessions,
        AVG(attention_score) as avg_score,
        COUNT(CASE WHEN attention_score >= 70 THEN 1 END) as high_scores,
        COUNT(CASE WHEN attention_score >= 50 AND attention_score < 70 THEN 1 END) as medium_scores,
        COUNT(CASE WHEN attention_score < 50 THEN 1 END) as low_scores
    ')
    ->first();

echo "Current Database Stats:\n";
echo "├─ Average Score: " . round($beforeAfter->avg_score, 1) . "%\n";
echo "├─ High Scores (70%+): {$beforeAfter->high_scores}\n";
echo "├─ Medium Scores (50-69%): {$beforeAfter->medium_scores}\n";
echo "└─ Low Scores (<50%): {$beforeAfter->low_scores}\n";

echo "\n✅ Update completed!\n";
echo "💡 Tip: Refresh the UserPerformanceReport to see the improved scores.\n";