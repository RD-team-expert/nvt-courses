<?php

namespace App\Services\ContentView;

use App\Models\User;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\CourseOnlineAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ContentProgressService
{
    /**
     * Get or create progress record for user and content
     * This is the main entry point for progress tracking
     */
    public function getOrCreateProgress(User $user, ModuleContent $content): UserContentProgress
    {
        return DB::transaction(function () use ($user, $content) {
            // Try to find it first to avoid locking if possible
            $progress = UserContentProgress::where('user_id', $user->id)
                ->where('content_id', $content->id)
                ->first();

            if ($progress) {
                return $progress;
            }

            // If not found, acquire lock to ensure uniqueness
            // We lock the user row or use a mutex if available, but for now, we'll use atomic creation
            // The unique constraint on the DB table (user_id, content_id) should also be there.
            
            return UserContentProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'content_id' => $content->id,
                ],
                [
                    'course_online_id' => $content->module->course_online_id,
                    'module_id' => $content->module_id,
                    'video_id' => $content->video_id,
                    'content_type' => $content->content_type,
                    'watch_time' => 0,
                    'completion_percentage' => 0,
                    'is_completed' => false,
                    'task_completed' => false,
                ]
            );
        });
    }

    /**
     * Update progress with new data (position, watch time, completion %)
     * Includes smart skip detection
     */
    public function updateProgress(
        int $progressId,
        float $currentPosition,
        float $completionPercentage,
        ?int $watchTime = null
    ): UserContentProgress {
        $progress = UserContentProgress::findOrFail($progressId);

        // Detect if user skipped content
        $previousPosition = $progress->playback_position ?? 0;
        $skipDetected = $this->detectSkip(
            $previousPosition,
            $currentPosition,
            $progress->content_type
        );

        // Adjust watch time if skip detected
        $adjustedWatchTime = $watchTime;
        if ($skipDetected && $watchTime) {
            $positionJump = $currentPosition - $previousPosition;
            $skippedTime = max(0, $positionJump - 5); // Allow 5 second buffer
            $adjustedWatchTime = max(0, $watchTime - $skippedTime);


        }

        // Update progress
        $progress->update([
            'playback_position' => $currentPosition,
            'completion_percentage' => min(100, max(0, $completionPercentage)),
            'watch_time' => $adjustedWatchTime ?? $progress->watch_time,
            'is_completed' => $completionPercentage >= 95,
            'last_accessed_at' => now(),
            'completed_at' => $completionPercentage >= 95 ? now() : $progress->completed_at,
        ]);

      

        return $progress->fresh();
    }

    /**
     * Mark content as 100% completed
     */
    public function markAsCompleted(UserContentProgress $progress): UserContentProgress
    {
        // Calculate final position based on content type
        $finalPosition = 0;
        $content = $progress->content;

        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
            $finalPosition = $content->pdf_page_count; // Last page
        } elseif ($content->content_type === 'video' && $content->video) {
            $finalPosition = $content->video->duration; // End of video
        }

        $progress->update([
            'playback_position' => $finalPosition,
            'completion_percentage' => 100,
            'is_completed' => true,
            'completed_at' => now(),
            'last_accessed_at' => now(),
        ]);

        // Update course assignment progress
        $this->updateCourseProgress($progress->course_online_id, $progress->user_id);

       

        return $progress->fresh();
    }

    /**
     * Calculate overall course progress for a user
     */
    public function calculateCourseProgress(int $courseId, int $userId): float
    {
        // Get total content count in course
        $totalContent = ModuleContent::whereHas('module', function($query) use ($courseId) {
            $query->where('course_online_id', $courseId);
        })->count();

        if ($totalContent === 0) {
            return 0;
        }

        // Get completed content count
        $completedContent = UserContentProgress::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->where('is_completed', true)
            ->count();

        $progressPercentage = round(($completedContent / $totalContent) * 100, 2);

       

        return $progressPercentage;
    }

    /**
     * Detect if user skipped content based on position jump
     */
    public function detectSkip(
        float $previousPosition,
        float $currentPosition,
        string $contentType
    ): bool {
        $positionJump = $currentPosition - $previousPosition;

        // For videos: skip if jump > 30 seconds
        if ($contentType === 'video' && $positionJump > 30) {
            return true;
        }

        // For PDFs: skip if jump > 2 pages
        if ($contentType === 'pdf' && $positionJump > 2) {
            return true;
        }

        return false;
    }

    /**
     * Check if progress should be updated based on completion threshold
     */
    public function shouldUpdateCompletion(
        UserContentProgress $progress,
        float $newPercentage
    ): bool {
        // Always update if increasing
        if ($newPercentage > $progress->completion_percentage) {
            return true;
        }

        // Don't update if decreasing and already completed
        if ($progress->is_completed) {
            return false;
        }

        return true;
    }

    /**
     * Get progress statistics for a user in a course
     */
    public function getProgressStats(int $userId, int $courseId): array
    {
        $stats = DB::table('user_content_progress')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_items,
                AVG(completion_percentage) as avg_completion,
                SUM(watch_time) as total_watch_time
            ')
            ->first();

        return [
            'total_items' => (int) $stats->total_items,
            'completed_items' => (int) $stats->completed_items,
            'avg_completion' => round($stats->avg_completion ?? 0, 2),
            'total_watch_time_minutes' => (int) ($stats->total_watch_time ?? 0),
            'completion_rate' => $stats->total_items > 0
                ? round(($stats->completed_items / $stats->total_items) * 100, 2)
                : 0,
        ];
    }

    /**
     * Update course assignment progress
     */
    protected function updateCourseProgress(int $courseId, int $userId): void
    {
        try {
            $progressPercentage = $this->calculateCourseProgress($courseId, $userId);

            $assignment = CourseOnlineAssignment::where('course_online_id', $courseId)
                ->where('user_id', $userId)
                ->first();

            if ($assignment) {
                $assignment->update([
                    'progress_percentage' => $progressPercentage,
                    'status' => $progressPercentage >= 100 ? 'completed' :
                               ($progressPercentage > 0 ? 'in_progress' : 'assigned'),
                    'completed_at' => $progressPercentage >= 100 ? now() : null,
                ]);

              
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to update course progress', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get progress for multiple contents at once
     */
    public function getBulkProgress(User $user, array $contentIds): array
    {
        return UserContentProgress::where('user_id', $user->id)
            ->whereIn('content_id', $contentIds)
            ->get()
            ->keyBy('content_id')
            ->toArray();
    }
}
