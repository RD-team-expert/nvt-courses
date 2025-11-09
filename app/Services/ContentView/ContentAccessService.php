<?php

namespace App\Services\ContentView;

use App\Models\User;
use App\Models\ModuleContent;
use App\Models\CourseOnlineAssignment;
use Illuminate\Support\Facades\Log;

class ContentAccessService
{
    /**
     * Check if user has access to specific content
     * Returns the assignment if user has access, null otherwise
     * 
     * This is the EXACT SAME logic from your controller - just moved here
     */
    public function checkAccess(User $user, ModuleContent $content): ?CourseOnlineAssignment
    {
        // Check if user has assignment for this course
        $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            Log::error('🚫 User access denied to content', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'course_id' => $content->module->course_online_id,
                'reason' => 'No assignment found',
            ]);
        } else {
            Log::info('✅ User access verified', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'assignment_id' => $assignment->id,
                'assignment_status' => $assignment->status,
            ]);
        }

        return $assignment;
    }

    /**
     * Check access and throw 403 if denied
     * Use this when you want to automatically abort if no access
     */
    public function verifyAccessOrFail(User $user, ModuleContent $content): CourseOnlineAssignment
    {
        $assignment = $this->checkAccess($user, $content);

        if (!$assignment) {
            abort(403, 'Access denied to this content');
        }

        return $assignment;
    }

    /**
     * Get user's assignment for a specific course
     * Useful for getting assignment details without checking specific content
     */
    public function getUserAssignment(User $user, int $courseId): ?CourseOnlineAssignment
    {
        return CourseOnlineAssignment::where('course_online_id', $courseId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Check if user has ANY active assignments
     * Useful for dashboard/overview pages
     */
    public function hasAnyAssignments(User $user): bool
    {
        return CourseOnlineAssignment::where('user_id', $user->id)
            ->exists();
    }
}
