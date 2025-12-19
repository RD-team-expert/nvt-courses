<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\LearningScoreCalculator;

echo "=== UPDATING USER ATTENTION SCORES ===\n\n";

// Get user kami55 (ID: 134)
$userId = 134;
echo "🔄 Updating attention scores for user ID: {$userId}\n\n";

// Get all sessions for this user that have tracking data but no attention score
$sessionsToUpdate = DB::table('learning_sessions')
    ->where('user_id', $userId)
    ->where(function($query) {
        $query->whereNull('attention_score')
              ->orWhere('attention_score', 0);
    })
    ->where(function($query) {
        $query->where('active_playback_time', '>', 0)
              ->orWhere('video_completion_percentage', '>', 0)
              ->orWhereNotNull('last_heartbeat');
    })
    ->select('id', 'course_online_id', 'active_playback_time', 'video_completion_percentage', 'last_heartbeat')
    ->get();

echo "📊 Found " . count($sessionsToUpdate) . " sessions to update:\n";
echo str_repeat("-", 40) . "\n";

$calculator = new LearningScoreCalculator();
$updatedCount = 0;

foreach ($sessionsToUpdate as $session) {
    try {
        // Calculate attention score using the service
        $attentionScore = $calculator->getAttentionScore($userId, $session->course_online_id, 'online');
        
        // Update the session with the calculated score
        DB::table('learning_sessions')
            ->where('id', $session->id)
            ->update([
                'attention_score' => round($attentionScore),
                'is_suspicious_activity' => $attentionScore < 30 // Mark as suspicious if very low
            ]);
        
        echo "✅ Session {$session->id}: Updated to {$attentionScore}%\n";
        $updatedCount++;
        
    } catch (Exception $e) {
        echo "❌ Session {$session->id}: Error - " . $e->getMessage() . "\n";
    }
}

echo "\n📈 UPDATE SUMMARY:\n";
echo str_repeat("-", 20) . "\n";
echo "Sessions updated: {$updatedCount}\n";

// Check the results
$userSessions = DB::table('learning_sessions')
    ->where('user_id', $userId)
    ->whereNotNull('attention_score')
    ->where('attention_score', '>', 0)
    ->select('id', 'attention_score', 'active_playback_time', 'video_completion_percentage')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($userSessions->isNotEmpty()) {
    echo "\n🎯 UPDATED SESSIONS:\n";
    foreach ($userSessions as $session) {
        echo "Session {$session->id}: {$session->attention_score}% ";
        echo "(Playback: {$session->active_playback_time}s, Completion: {$session->video_completion_percentage}%)\n";
    }
    
    $avgScore = $userSessions->avg('attention_score');
    echo "\n📊 New Average Attention Score: " . round($avgScore, 1) . "%\n";
} else {
    echo "\n❌ No sessions with attention scores found\n";
}

echo "\n✅ User attention scores updated!\n";
echo "💡 Refresh the UserPerformanceReport to see the new scores.\n";