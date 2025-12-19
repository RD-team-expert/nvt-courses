<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\LearningScoreCalculator;
use Illuminate\Support\Facades\DB;

echo "=== ATTENTION SCORE CALCULATION TEST ===\n\n";

// Get some test users with learning sessions
$testUsers = DB::table('users')
    ->join('learning_sessions', 'users.id', '=', 'learning_sessions.user_id')
    ->select('users.id', 'users.name', 'users.email')
    ->distinct()
    ->limit(5)
    ->get();

if ($testUsers->isEmpty()) {
    echo "❌ No users with learning sessions found!\n";
    exit;
}

$calculator = new LearningScoreCalculator();

foreach ($testUsers as $user) {
    echo "🔍 TESTING USER: {$user->name} (ID: {$user->id})\n";
    echo "📧 Email: {$user->email}\n";
    echo str_repeat("-", 60) . "\n";
    
    // Get user's courses
    $userCourses = DB::table('learning_sessions')
        ->where('user_id', $user->id)
        ->whereNotNull('course_online_id')
        ->select('course_online_id')
        ->distinct()
        ->get();
    
    foreach ($userCourses as $courseData) {
        $courseId = $courseData->course_online_id;
        
        // Get course name
        $courseName = DB::table('course_online')
            ->where('id', $courseId)
            ->value('name') ?? 'Unknown Course';
        
        echo "📚 COURSE: {$courseName} (ID: {$courseId})\n";
        
        // Get all learning sessions for this user/course
        $sessions = DB::table('learning_sessions')
            ->where('user_id', $user->id)
            ->where('course_online_id', $courseId)
            ->select('id', 'session_start', 'session_end', 'content_id', 
                    'video_skip_count', 'seek_count', 'pause_count', 
                    'video_replay_count', 'video_completion_percentage', 
                    'active_playback_time', 'total_duration_minutes',
                    'attention_score', 'is_suspicious_activity')
            ->get();
        
        echo "📊 SESSIONS FOUND: " . count($sessions) . "\n";
        
        if ($sessions->isEmpty()) {
            echo "⚠️  No sessions found for this course\n\n";
            continue;
        }
        
        // Analyze each session
        $sessionScores = [];
        $suspiciousCount = 0;
        
        foreach ($sessions as $index => $session) {
            echo "\n  SESSION " . ($index + 1) . " (ID: {$session->id}):\n";
            echo "  ├─ Start: " . ($session->session_start ?? 'NULL') . "\n";
            echo "  ├─ End: " . ($session->session_end ?? 'NULL') . "\n";
            echo "  ├─ Content ID: " . ($session->content_id ?? 'NULL') . "\n";
            echo "  ├─ Active Playback Time: " . ($session->active_playback_time ?? 0) . " seconds\n";
            echo "  ├─ Total Duration: " . ($session->total_duration_minutes ?? 0) . " minutes\n";
            echo "  ├─ Video Completion: " . ($session->video_completion_percentage ?? 0) . "%\n";
            echo "  ├─ Pauses: " . ($session->pause_count ?? 0) . "\n";
            echo "  ├─ Replays: " . ($session->video_replay_count ?? 0) . "\n";
            echo "  ├─ Skips: " . ($session->video_skip_count ?? 0) . "\n";
            echo "  ├─ Stored Attention Score: " . ($session->attention_score ?? 'NULL') . "\n";
            echo "  └─ Is Suspicious: " . ($session->is_suspicious_activity ? 'YES' : 'NO') . "\n";
            
            // Get video duration if content_id exists
            if ($session->content_id) {
                $videoDuration = DB::table('module_content')
                    ->where('id', $session->content_id)
                    ->value('duration');
                
                if ($videoDuration) {
                    echo "  └─ Video Duration: " . $videoDuration . " seconds (" . round($videoDuration/60, 1) . " minutes)\n";
                }
            }
            
            if ($session->attention_score) {
                $sessionScores[] = $session->attention_score;
            }
            
            if ($session->is_suspicious_activity) {
                $suspiciousCount++;
            }
        }
        
        // Calculate attention score using the service
        $calculatedScore = $calculator->getAttentionScore($user->id, $courseId, 'online');
        
        echo "\n📈 ATTENTION SCORE ANALYSIS:\n";
        echo "  ├─ Calculated Score: {$calculatedScore}%\n";
        
        if (!empty($sessionScores)) {
            $avgStoredScore = round(array_sum($sessionScores) / count($sessionScores), 1);
            echo "  ├─ Average Stored Score: {$avgStoredScore}%\n";
        } else {
            echo "  ├─ Average Stored Score: No scores found\n";
        }
        
        echo "  ├─ Suspicious Sessions: {$suspiciousCount}/" . count($sessions) . "\n";
        
        // Check if backup calculation was used
        $hasValidData = false;
        foreach ($sessions as $session) {
            if (($session->active_playback_time ?? 0) > 0 || 
                ($session->total_duration_minutes ?? 0) > 0) {
                $hasValidData = true;
                break;
            }
        }
        
        echo "  └─ Using Backup Calculation: " . ($hasValidData ? 'NO' : 'YES') . "\n";
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
}

// Additional analysis: Check overall attention score distribution
echo "📊 OVERALL ATTENTION SCORE DISTRIBUTION:\n";
echo str_repeat("-", 40) . "\n";

$scoreDistribution = DB::table('learning_sessions')
    ->whereNotNull('attention_score')
    ->selectRaw('
        COUNT(*) as total_sessions,
        AVG(attention_score) as avg_score,
        MIN(attention_score) as min_score,
        MAX(attention_score) as max_score,
        SUM(CASE WHEN attention_score >= 80 THEN 1 ELSE 0 END) as high_scores,
        SUM(CASE WHEN attention_score >= 60 AND attention_score < 80 THEN 1 ELSE 0 END) as medium_scores,
        SUM(CASE WHEN attention_score >= 40 AND attention_score < 60 THEN 1 ELSE 0 END) as low_scores,
        SUM(CASE WHEN attention_score < 40 THEN 1 ELSE 0 END) as very_low_scores,
        SUM(CASE WHEN is_suspicious_activity = 1 THEN 1 ELSE 0 END) as suspicious_sessions
    ')
    ->first();

if ($scoreDistribution && $scoreDistribution->total_sessions > 0) {
    echo "Total Sessions: {$scoreDistribution->total_sessions}\n";
    echo "Average Score: " . round($scoreDistribution->avg_score, 1) . "%\n";
    echo "Score Range: {$scoreDistribution->min_score}% - {$scoreDistribution->max_score}%\n";
    echo "High Scores (80-100%): {$scoreDistribution->high_scores}\n";
    echo "Medium Scores (60-79%): {$scoreDistribution->medium_scores}\n";
    echo "Low Scores (40-59%): {$scoreDistribution->low_scores}\n";
    echo "Very Low Scores (0-39%): {$scoreDistribution->very_low_scores}\n";
    echo "Suspicious Sessions: {$scoreDistribution->suspicious_sessions}\n";
} else {
    echo "❌ No attention scores found in database!\n";
}

// Check for common issues
echo "\n🔍 COMMON ISSUES CHECK:\n";
echo str_repeat("-", 30) . "\n";

// Check for sessions with no active playback time
$noActiveTime = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('active_playback_time')
              ->orWhere('active_playback_time', 0);
    })
    ->count();

echo "Sessions with no active playback time: {$noActiveTime}\n";

// Check for sessions with no video completion data
$noCompletion = DB::table('learning_sessions')
    ->where(function($query) {
        $query->whereNull('video_completion_percentage')
              ->orWhere('video_completion_percentage', 0);
    })
    ->count();

echo "Sessions with no completion data: {$noCompletion}\n";

// Check for sessions with no content_id
$noContentId = DB::table('learning_sessions')
    ->whereNull('content_id')
    ->count();

echo "Sessions with no content_id: {$noContentId}\n";

echo "\n✅ Test completed!\n";