<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserContentProgress;
use App\Models\CourseOnlineAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProgressController extends Controller
{
    /**
     * Update video progress
     */
    public function updateVideoProgress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_id' => 'required|exists:module_content,id',
            'current_time' => 'required|numeric|min:0',
            'total_duration' => 'nullable|integer|min:1',
        ]);

        $progress = UserContentProgress::where('user_id', auth()->id())
            ->where('content_id', $validated['content_id'])
            ->firstOrFail();

        $progress->updateVideoProgress(
            $validated['current_time'],
            $validated['total_duration']
        );

        // Update course assignment progress
        $this->updateCourseProgress($progress->course_online_id);

        return response()->json([
            'success' => true,
            'progress' => [
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
                'playback_position' => $progress->playback_position,
                'can_access_next' => $progress->canAccessNext(),
            ]
        ]);
    }

    /**
     * Update PDF progress
     */
    public function updatePdfProgress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_id' => 'required|exists:module_content,id',
            'pages_viewed' => 'required|integer|min:0',
            'total_pages' => 'required|integer|min:1',
        ]);

        $progress = UserContentProgress::where('user_id', auth()->id())
            ->where('content_id', $validated['content_id'])
            ->firstOrFail();

        $progress->updatePdfProgress(
            $validated['pages_viewed'],
            $validated['total_pages']
        );

        // Update course assignment progress
        $this->updateCourseProgress($progress->course_online_id);

        return response()->json([
            'success' => true,
            'progress' => [
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
                'pages_viewed' => $progress->pdf_pages_viewed,
                'can_access_next' => $progress->canAccessNext(),
            ]
        ]);
    }

    /**
     * Update overall course progress
     * ✅ FIXED: Only count REQUIRED content for progress calculation
     */
    private function updateCourseProgress(int $courseOnlineId): void
    {
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseOnlineId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return;
        }

        // ✅ FIXED: Calculate progress based on REQUIRED content only
        $totalRequiredContent = \App\Models\ModuleContent::whereHas('module', function ($query) use ($courseOnlineId) {
            $query->where('course_online_id', $courseOnlineId)
                ->where('is_required', true);
        })
        ->where('is_required', true)
        ->count();

        if ($totalRequiredContent === 0) {
            // No required content, don't update progress
            return;
        }

        $completedRequiredContent = UserContentProgress::where('user_id', auth()->id())
            ->where('course_online_id', $courseOnlineId)
            ->where('is_completed', true)
            ->whereHas('content', function ($query) {
                $query->where('is_required', true)
                    ->whereHas('module', function ($subQuery) {
                        $subQuery->where('is_required', true);
                    });
            })
            ->count();

        // ✅ Calculate progress as percentage of completed required content
        $totalProgress = round(($completedRequiredContent / $totalRequiredContent) * 100, 2);

        // Find current module
        $currentModuleId = UserContentProgress::where('user_id', auth()->id())
            ->where('course_online_id', $courseOnlineId)
            ->where('is_completed', false)
            ->with('module')
            ->orderBy('module_id')
            ->orderBy('content_id')
            ->first()?->module_id;

        $assignment->updateProgress($totalProgress, $currentModuleId);
    }
}
