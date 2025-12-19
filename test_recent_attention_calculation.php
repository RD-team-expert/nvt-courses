<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\LearningScoreCalculator;
use Illuminate\Support\Facades\DB;

echo "=== RECENT ATTENTION CALCULATION TEST ===\n\n";

// Get recent sessions with active playback time
$recentSessions = DB::table('learning_sessions')
    ->where('created_at', '>=', now()->subDays(2))
    ->where('active_playback_time', '>', 0)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentSessions->isEmpty()) {
    echo "❌ No recent sessions with active playback time found!\n";
    exit;
}

$calculator = new LearningScoreCalculator();

foreach ($recentSessions as $session) {
    echo "🔍 TESTING SESSION ID: {$session->id}\n";
    echo "👤 User ID: {$session->user_id}\n";
    echo "📚 Course ID: {$session->course_online_id}\n";
    echo "📄 Content ID: {$session->content_id}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Get content details
    $content = DB::table('module_content')
        ->where('id', $session->content_id)
        ->first();
    
    if ($content) {
        echo "📹 Content: {$content->title}\n";
        echo "⏱️  Video Duration: " . ($content->duration ?? 'NULL') . " seconds\n";
        if ($content->duration) {
            echo "⏱️  Video Duration: " . round($content->duration / 60, 1) . " minutes\n";
        }
    }
    
    echo "\n📊 SESSION DATA:\n";
    echo "  ├─ Active Playback Time: {$session->active_playback_time} seconds (" . round($session->active_playback_time / 60, 1) . " minutes)\n";
    echo "  ├─ Total Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
    echo "  ├─ Video Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
    echo "  ├─ Pause Count: " . ($session->pause_count ?? 'NULL') . "\n";
    echo "  ├─ Skip Count: " . ($session->video_skip_count ?? 'NULL') . "\n";
    echo "  ├─ Replay Count: " . ($session->video_replay_count ?? 'NULL') . "\n";
    echo "  ├─ Stored Attention Score: " . ($session->attention_score ?? 'NULL') . "\n";
    echo "  └─ Is Suspicious: " . ($session->is_suspicious_activity ? 'YES' : 'NO') . "\n";
    
    // Calculate attention score using the service
    $calculatedScore = $calculator->getAttentionScore($session->user_id, $session->course_online_id, 'online');
    
    echo "\n🧮 ATTENTION CALCULATION:\n";
    echo "  ├─ Service Calculated Score: {$calculatedScore}%\n";
    
    // Manual calculation to debug
    if ($content && $content->duration && $session->active_playback_time > 0) {
        $videoDurationMinutes = $content->duration / 60;
        $activePlaybackMinutes = $session->active_playback_time / 60;
        $allowedTimeMinutes = $videoDurationMinutes * 2;
        $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;
        
        echo "  ├─ Video Duration: " . round($videoDurationMinutes, 1) . " minutes\n";
        echo "  ├─ Active Playback: " . round($activePlaybackMinutes, 1) . " minutes\n";
        echo "  ├─ Allowed Time Window: " . round($allowedTimeMinutes, 1) . " minutes\n";
        echo "  ├─ Within Allowed Time: " . ($isWithinAllowedTime ? 'YES' : 'NO') . "\n";
        
        // Manual score calculation
        $manualScore = 0;
        
        // Active playback time matching (up to 30 points)
        if ($isWithinAllowedTime) {
            if ($activePlaybackMinutes >= $videoDurationMinutes * 0.8) {
                $manualScore += 30;
                echo "  ├─ Active Playback Bonus: +30 (Good time)\n";
            } elseif ($activePlaybackMinutes >= $videoDurationMinutes * 0.5) {
                $manualScore += 20;
                echo "  ├─ Active Playback Bonus: +20 (Acceptable time)\n";
            } else {
                $manualScore += 10;
                echo "  ├─ Active Playback Bonus: +10 (Short time)\n";
            }
        } else {
            $manualScore += 5;
            echo "  ├─ Active Playback Bonus: +5 (Exceeded window)\n";
        }
        
        // Session completion bonus (5 points)
        if ($session->session_end) {
            $manualScore += 5;
            echo "  ├─ Session Completion: +5\n";
        }
        
        // Video completion bonus (up to 35 points)
        if ($session->video_completion_percentage) {
            $completionPct = $session->video_completion_percentage;
            if ($completionPct >= 95) {
                $manualScore += 35;
                echo "  ├─ Video Completion: +35 (Full completion)\n";
            } elseif ($completionPct >= 80) {
                $manualScore += 25;
                echo "  ├─ Video Completion: +25 (High completion)\n";
            } elseif ($completionPct >= 60) {
                $manualScore += 15;
                echo "  ├─ Video Completion: +15 (Moderate completion)\n";
            } elseif ($completionPct >= 40) {
                $manualScore += 5;
                echo "  ├─ Video Completion: +5 (Low completion)\n";
            }
        }
        
        // Skip penalty
        if (($session->video_skip_count ?? 0) >= 1) {
            $manualScore -= 30;
            echo "  ├─ Skip Penalty: -30\n";
        }
        
        $manualScore = max(0, min(100, $manualScore));
        echo "  └─ Manual Calculated Score: {$manualScore}%\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "✅ Test completed!\n";