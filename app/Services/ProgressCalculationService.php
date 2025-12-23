<?php

namespace App\Services;

use App\Models\User;
use App\Models\CourseOnlineAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Smart Progress Calculation Service
 * 
 * This service intelligently calculates progress by:
 * 1. Checking database data first
 * 2. Validating if data is correct
 * 3. Using backup calculation if data is wrong
 * 4. Always returning accurate progress
 * 
 * NEVER modifies database - only calculates correct values for display
 */
class ProgressCalculationService
{
    /**
     * Get accurate progress for a user's assignment
     * 
     * @param int $userId
     * @param int $courseOnlineId
     * @return array ['progress' => float, 'source' => string, 'is_valid' => bool]
     */
    public function getAccurateProgress(int $userId, int $courseOnlineId): array
    {
        // Step 1: Get stored progress from database
        $assignment = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->first();
        
        if (!$assignment) {
            return [
                'progress' => 0,
                'source' => 'no_assignment',
                'is_valid' => true,
                'details' => 'No assignment found'
            ];
        }
        
        $storedProgress = $assignment->progress_percentage ?? 0;
        
        // Step 2: Calculate actual progress from content completion
        $actualProgress = $this->calculateActualProgress($userId, $courseOnlineId);
        
        // Step 3: Validate stored progress
        $validation = $this->validateProgress($assignment, $actualProgress);
        
        // Step 4: Decide which progress to use
        if ($validation['is_valid']) {
            // Database is correct, use stored value
            return [
                'progress' => $storedProgress,
                'source' => 'database',
                'is_valid' => true,
                'details' => 'Database value is correct',
                'stored' => $storedProgress,
                'actual' => $actualProgress['progress']
            ];
        } else {
            // Database is wrong, use calculated value
            Log::warning('Progress mismatch detected', [
                'user_id' => $userId,
                'course_id' => $courseOnlineId,
                'assignment_id' => $assignment->id,
                'stored' => $storedProgress,
                'actual' => $actualProgress['progress'],
                'reason' => $validation['reason']
            ]);
            
            return [
                'progress' => $actualProgress['progress'],
                'source' => 'calculated',
                'is_valid' => false,
                'details' => $validation['reason'],
                'stored' => $storedProgress,
                'actual' => $actualProgress['progress']
            ];
        }
    }
    
    /**
     * Calculate actual progress from content completion
     * This is the TRUTH - what user actually completed
     * 
     * ✅ SMART: Checks BOTH user_content_progress AND learning_sessions
     * to find if user EVER completed the content (handles overwrites)
     * 
     * @param int $userId
     * @param int $courseOnlineId
     * @return array ['progress' => float, 'completed' => int, 'total' => int]
     */
    public function calculateActualProgress(int $userId, int $courseOnlineId): array
    {
        // Get total required content
        $requiredContent = DB::table('module_content')
            ->join('course_modules', 'module_content.module_id', '=', 'course_modules.id')
            ->where('course_modules.course_online_id', $courseOnlineId)
            ->where('course_modules.is_required', true)
            ->where('module_content.is_required', true)
            ->select('module_content.id', 'module_content.title')
            ->get();
        
        $totalRequired = $requiredContent->count();
        
        if ($totalRequired === 0) {
            return [
                'progress' => 0,
                'completed' => 0,
                'total' => 0,
                'details' => 'No required content in course'
            ];
        }
        
        // ✅ SMART: Check each required content item
        $completedRequired = 0;
        
        foreach ($requiredContent as $content) {
            $isCompleted = $this->isContentCompleted($userId, $courseOnlineId, $content->id);
            if ($isCompleted) {
                $completedRequired++;
            }
        }
        
        $progress = round(($completedRequired / $totalRequired) * 100, 2);
        
        return [
            'progress' => $progress,
            'completed' => $completedRequired,
            'total' => $totalRequired,
            'details' => "Completed {$completedRequired} out of {$totalRequired} required items"
        ];
    }
    
    /**
     * ✅ SMART: Check if content was EVER completed by user
     * Checks multiple sources to handle bad data overwrites
     * 
     * @param int $userId
     * @param int $courseOnlineId
     * @param int $contentId
     * @return bool
     */
    private function isContentCompleted(int $userId, int $courseOnlineId, int $contentId): bool
    {
        // Source 1: Check user_content_progress table
        $contentProgress = DB::table('user_content_progress')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->where('content_id', $contentId)
            ->first();
        
        if ($contentProgress && $contentProgress->is_completed) {
            return true;
        }
        
        // Source 2: Check if ANY learning session has 100% video completion
        // This handles the case where Session 2 overwrote Session 1's completion
        $hasCompletedSession = DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->where('content_id', $contentId)
            ->where('video_completion_percentage', '>=', 95) // 95% or more = completed
            ->exists();
        
        if ($hasCompletedSession) {
            return true;
        }
        
        // Source 3: Check if content_progress ever had high completion
        // (completion_percentage might be overwritten but still > 95%)
        if ($contentProgress && $contentProgress->completion_percentage >= 95) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate if stored progress matches actual progress
     * 
     * @param CourseOnlineAssignment $assignment
     * @param array $actualProgress
     * @return array ['is_valid' => bool, 'reason' => string]
     */
    private function validateProgress(CourseOnlineAssignment $assignment, array $actualProgress): array
    {
        $stored = $assignment->progress_percentage ?? 0;
        $actual = $actualProgress['progress'];
        
        // Rule 1: If completed, must be 100%
        if ($assignment->status === 'completed') {
            if ($stored < 100) {
                return [
                    'is_valid' => false,
                    'reason' => "Assignment is completed but progress is {$stored}% (should be 100%)"
                ];
            }
            
            // Also check if user actually completed all content
            if ($actual < 100) {
                return [
                    'is_valid' => false,
                    'reason' => "Assignment marked completed but user only completed {$actual}% of content"
                ];
            }
        }
        
        // Rule 2: Progress should not exceed actual completion
        if ($stored > $actual + 5) { // Allow 5% tolerance for rounding
            return [
                'is_valid' => false,
                'reason' => "Stored progress ({$stored}%) exceeds actual completion ({$actual}%)"
            ];
        }
        
        // Rule 3: If actual is 100%, stored should be close to 100%
        if ($actual >= 100 && $stored < 95) {
            return [
                'is_valid' => false,
                'reason' => "User completed all content but stored progress is only {$stored}%"
            ];
        }
        
        // Rule 4: Progress should not be negative
        if ($stored < 0) {
            return [
                'is_valid' => false,
                'reason' => "Progress cannot be negative ({$stored}%)"
            ];
        }
        
        // All validations passed
        return [
            'is_valid' => true,
            'reason' => 'Progress is valid'
        ];
    }
    
    /**
     * Get accurate progress for multiple users
     * Optimized for bulk operations
     * 
     * @param array $userIds
     * @param int $courseOnlineId
     * @return array [user_id => progress_data]
     */
    public function getAccurateProgressBulk(array $userIds, int $courseOnlineId): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            $results[$userId] = $this->getAccurateProgress($userId, $courseOnlineId);
        }
        
        return $results;
    }
    
    /**
     * Get accurate average progress for a user across all assignments
     * 
     * @param int $userId
     * @param array $filters Optional filters (course_id, date_from, date_to)
     * @return array ['avg_progress' => float, 'source' => string, 'assignments' => array]
     */
    public function getAccurateAverageProgress(int $userId, array $filters = []): array
    {
        $query = CourseOnlineAssignment::where('user_id', $userId);
        
        // Apply filters
        if (!empty($filters['course_id'])) {
            $query->where('course_online_id', $filters['course_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('assigned_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('assigned_at', '<=', $filters['date_to']);
        }
        
        $assignments = $query->get();
        
        if ($assignments->isEmpty()) {
            return [
                'avg_progress' => 0,
                'source' => 'no_assignments',
                'assignments' => [],
                'details' => 'No assignments found'
            ];
        }
        
        $progressData = [];
        $totalProgress = 0;
        $validCount = 0;
        $invalidCount = 0;
        
        foreach ($assignments as $assignment) {
            $progress = $this->getAccurateProgress($userId, $assignment->course_online_id);
            
            // ✅ FIXED: Include completed assignments even if progress is 0
            // This handles the case where assignment is marked completed but has bad data
            $shouldInclude = false;
            $progressToUse = $progress['progress'];
            
            if ($progress['progress'] > 0) {
                // Normal case: assignment has progress
                $shouldInclude = true;
            } elseif ($assignment->status === 'completed') {
                // ✅ FIX: Assignment is completed but has 0% progress
                // This is bad data - include it but with 100% if truly completed
                // or 0% if not actually completed
                $actualProgress = $this->calculateActualProgress($userId, $assignment->course_online_id);
                
                if ($actualProgress['progress'] >= 100) {
                    // User actually completed all content, use 100%
                    $progressToUse = 100;
                    $shouldInclude = true;
                } else {
                    // User didn't complete content but assignment is marked completed
                    // This is invalid data - include with actual progress
                    $progressToUse = $actualProgress['progress'];
                    $shouldInclude = true; // Include to show the problem
                    $invalidCount++;
                }
            }
            
            if ($shouldInclude) {
                $totalProgress += $progressToUse;
                $validCount++;
                
                if (!$progress['is_valid']) {
                    $invalidCount++;
                }
            }
            
            $progressData[] = [
                'assignment_id' => $assignment->id,
                'course_id' => $assignment->course_online_id,
                'progress' => $progressToUse,
                'source' => $progress['source'],
                'is_valid' => $progress['is_valid'],
                'status' => $assignment->status
            ];
        }
        
        $avgProgress = $validCount > 0 ? round($totalProgress / $validCount, 1) : 0;
        
        return [
            'avg_progress' => $avgProgress,
            'source' => $invalidCount > 0 ? 'calculated_with_corrections' : 'database',
            'assignments' => $progressData,
            'total_assignments' => $assignments->count(),
            'active_assignments' => $validCount,
            'corrected_assignments' => $invalidCount,
            'details' => $invalidCount > 0 
                ? "Corrected {$invalidCount} assignments with invalid data"
                : "All assignments have valid data"
        ];
    }
    
    /**
     * Check if assignment should be marked as completed
     * 
     * @param int $userId
     * @param int $courseOnlineId
     * @return array ['should_complete' => bool, 'reason' => string, 'progress' => float]
     */
    public function shouldCompleteAssignment(int $userId, int $courseOnlineId): array
    {
        $actualProgress = $this->calculateActualProgress($userId, $courseOnlineId);
        
        if ($actualProgress['progress'] >= 100) {
            return [
                'should_complete' => true,
                'reason' => 'User completed all required content',
                'progress' => $actualProgress['progress'],
                'completed' => $actualProgress['completed'],
                'total' => $actualProgress['total']
            ];
        }
        
        return [
            'should_complete' => false,
            'reason' => "User completed {$actualProgress['completed']} out of {$actualProgress['total']} required items",
            'progress' => $actualProgress['progress'],
            'completed' => $actualProgress['completed'],
            'total' => $actualProgress['total']
        ];
    }
}
