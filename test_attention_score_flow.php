<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\LearningScoreCalculator;

echo "=== COMPREHENSIVE ATTENTION SCORE FLOW TEST ===\n\n";

// Select a test user with some learning activity
$testUser = DB::table('users')
    ->join('learning_sessions', 'users.id', '=', 'learning_sessions.user_id')
    ->select('users.id', 'users.name', 'users.email')
    ->groupBy('users.id', 'users.name', 'users.email')
    ->havingRaw('COUNT(learning_sessions.id) > 5')
    ->first();

if (!$testUser) {
    echo "❌ No test user found with learning sessions!\n";
    exit;
}

echo "🧪 TESTING USER: {$testUser->name} (ID: {$testUser->id})\n";
echo "📧 Email: {$testUser->email}\n";
echo str_repeat("=", 60) . "\n\n";

// Get user's courses
$userCourses = DB::table('learning_sessions')
    ->where('user_id', $testUser->id)
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
    echo str_repeat("-", 50) . "\n";
    
    // 1. CHECK RAW LEARNING SESSIONS DATA
    echo "1️⃣ RAW LEARNING SESSIONS DATA:\n";
    $sessions = DB::table('learning_sessions')
        ->where('user_id', $testUser->id)
        ->where('course_online_id', $courseId)
        ->select('id', 'session_start', 'session_end', 'content_id', 
                'active_playback_time', 'total_duration_minutes', 
                'video_completion_percentage', 'attention_score', 
                'is_suspicious_activity', 'pause_count', 'video_skip_count', 
                'video_replay_count', 'seek_count')
        ->orderBy('session_start')
        ->get();
    
    echo "   Sessions found: " . count($sessions) . "\n";
    
    foreach ($sessions->take(3) as $session) {
        echo "   Session {$session->id}:\n";
        echo "   ├─ Content ID: " . ($session->content_id ?? 'NULL') . "\n";
        echo "   ├─ Active Playback: " . ($session->active_playback_time ?? 'NULL') . " seconds\n";
        echo "   ├─ Duration: " . ($session->total_duration_minutes ?? 'NULL') . " minutes\n";
        echo "   ├─ Completion: " . ($session->video_completion_percentage ?? 'NULL') . "%\n";
        echo "   ├─ Stored Score: " . ($session->attention_score ?? 'NULL') . "\n";
        echo "   └─ Suspicious: " . ($session->is_suspicious_activity ? 'YES' : 'NO') . "\n\n";
    }
    
    // 2. CHECK MODULE CONTENT DATA
    echo "2️⃣ MODULE CONTENT DATA:\n";
    $contentIds = $sessions->pluck('content_id')->filter()->unique();
    
    foreach ($contentIds->take(3) as $contentId) {
        $content = DB::table('module_content')
            ->where('id', $contentId)
            ->select('id', 'title', 'content_type', 'duration', 'video_id')
            ->first();
        
        if ($content) {
            echo "   Content {$content->id}: {$content->title}\n";
            echo "   ├─ Type: {$content->content_type}\n";
            echo "   ├─ Duration: " . ($content->duration ?? 'NULL') . " seconds\n";
            echo "   └─ Video ID: " . ($content->video_id ?? 'NULL') . "\n\n";
            
            // Check linked video if exists
            if ($content->video_id) {
                $video = DB::table('videos')
                    ->where('id', $content->video_id)
                    ->select('id', 'name', 'duration')
                    ->first();
                
                if ($video) {
                    echo "   Linked Video {$video->id}: {$video->name}\n";
                    echo "   └─ Video Duration: " . ($video->duration ?? 'NULL') . " seconds\n\n";
                }
            }
        }
    }
    
    // 3. TEST LEARNINGSCORECCALCULATOR SERVICE
    echo "3️⃣ LEARNINGSCORECCALCULATOR SERVICE:\n";
    $calculator = new LearningScoreCalculator();
    
    try {
        $serviceScore = $calculator->getAttentionScore($testUser->id, $courseId, 'online');
        echo "   Service Calculated Score: {$serviceScore}%\n";
        
        // Check if it's using backup calculation
        $hasValidData = false;
        foreach ($sessions as $session) {
            if (($session->active_playback_time ?? 0) > 0 || 
                ($session->total_duration_minutes ?? 0) > 0) {
                $hasValidData = true;
                break;
            }
        }
        echo "   Using Backup Calculation: " . ($hasValidData ? 'NO' : 'YES') . "\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ Service Error: " . $e->getMessage() . "\n\n";
    }
    
    // 4. TEST COURSEONLINEREPORTCONTROLLER
    echo "4️⃣ COURSEONLINEREPORTCONTROLLER:\n";
    try {
        // Simulate the controller's calculation
        $rawSessions = DB::table('learning_sessions')
            ->where('user_id', $testUser->id)
            ->where('course_online_id', $courseId)
            ->select('id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
                    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
                    'video_completion_percentage', 'content_id', 'active_playback_time')
            ->get();
        
        $simulatedAttentionScores = [];
        $suspiciousSessions = 0;
        
        foreach ($rawSessions as $session) {
            // Get stored attention score
            if ($session->attention_score) {
                $simulatedAttentionScores[] = $session->attention_score;
            }
            
            if ($session->is_suspicious_activity) {
                $suspiciousSessions++;
            }
        }
        
        $avgControllerScore = count($simulatedAttentionScores) > 0
            ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
            : 0;
        
        echo "   Controller Calculated Score: " . round($avgControllerScore, 1) . "%\n";
        echo "   Suspicious Sessions: {$suspiciousSessions}/" . count($rawSessions) . "\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ Controller Error: " . $e->getMessage() . "\n\n";
    }
    
    // 5. CHECK USERPERFORMANCEREPORT DATA
    echo "5️⃣ USERPERFORMANCEREPORT DATA:\n";
    try {
        // Get assignment data
        $assignmentStats = DB::table('course_online_assignments')
            ->where('user_id', $testUser->id)
            ->where('course_online_id', $courseId)
            ->selectRaw('
                COUNT(*) as total_assignments,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_assignments,
                AVG(COALESCE(progress_percentage, 0)) as avg_progress
            ')
            ->first();
        
        echo "   Assignment Stats:\n";
        echo "   ├─ Total: " . ($assignmentStats->total_assignments ?? 0) . "\n";
        echo "   ├─ Completed: " . ($assignmentStats->completed_assignments ?? 0) . "\n";
        echo "   └─ Avg Progress: " . round($assignmentStats->avg_progress ?? 0, 1) . "%\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ Report Error: " . $e->getMessage() . "\n\n";
    }
    
    // 6. SUMMARY AND COMPARISON
    echo "6️⃣ SUMMARY AND COMPARISON:\n";
    echo "   Raw Sessions: " . count($sessions) . "\n";
    echo "   Sessions with Active Playback: " . $sessions->where('active_playback_time', '>', 0)->count() . "\n";
    echo "   Sessions with Completion Data: " . $sessions->where('video_completion_percentage', '>', 0)->count() . "\n";
    echo "   Sessions with Stored Scores: " . $sessions->whereNotNull('attention_score')->count() . "\n";
    
    $avgStoredScore = $sessions->whereNotNull('attention_score')->avg('attention_score');
    echo "   Average Stored Score: " . round($avgStoredScore ?? 0, 1) . "%\n";
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

// 7. OVERALL SYSTEM HEALTH CHECK
echo "7️⃣ OVERALL SYSTEM HEALTH CHECK:\n";
echo str_repeat("-", 35) . "\n";

$systemStats = DB::table('learning_sessions')
    ->selectRaw('
        COUNT(*) as total_sessions,
        COUNT(CASE WHEN active_playback_time > 0 THEN 1 END) as sessions_with_playback,
        COUNT(CASE WHEN video_completion_percentage > 0 THEN 1 END) as sessions_with_completion,
        COUNT(CASE WHEN attention_score IS NOT NULL THEN 1 END) as sessions_with_scores,
        AVG(attention_score) as avg_attention_score
    ')
    ->first();

echo "System-wide Statistics:\n";
echo "├─ Total Sessions: {$systemStats->total_sessions}\n";
echo "├─ With Active Playback: {$systemStats->sessions_with_playback} (" . round(($systemStats->sessions_with_playback / $systemStats->total_sessions) * 100, 1) . "%)\n";
echo "├─ With Completion Data: {$systemStats->sessions_with_completion} (" . round(($systemStats->sessions_with_completion / $systemStats->total_sessions) * 100, 1) . "%)\n";
echo "├─ With Attention Scores: {$systemStats->sessions_with_scores} (" . round(($systemStats->sessions_with_scores / $systemStats->total_sessions) * 100, 1) . "%)\n";
echo "└─ Average Attention Score: " . round($systemStats->avg_attention_score ?? 0, 1) . "%\n\n";

// 8. MODULE CONTENT HEALTH CHECK
echo "8️⃣ MODULE CONTENT HEALTH CHECK:\n";
echo str_repeat("-", 30) . "\n";

$contentStats = DB::table('module_content')
    ->where('content_type', 'video')
    ->selectRaw('
        COUNT(*) as total_video_content,
        COUNT(CASE WHEN duration IS NOT NULL AND duration > 0 THEN 1 END) as content_with_duration,
        COUNT(CASE WHEN video_id IS NOT NULL THEN 1 END) as content_with_video_link
    ')
    ->first();

echo "Module Content Statistics:\n";
echo "├─ Total Video Content: {$contentStats->total_video_content}\n";
echo "├─ With Duration: {$contentStats->content_with_duration} (" . round(($contentStats->content_with_duration / $contentStats->total_video_content) * 100, 1) . "%)\n";
echo "└─ With Video Link: {$contentStats->content_with_video_link} (" . round(($contentStats->content_with_video_link / $contentStats->total_video_content) * 100, 1) . "%)\n\n";

echo "✅ Comprehensive attention score flow test completed!\n";
echo "💡 This shows exactly where attention scores come from and what data is available.\n";