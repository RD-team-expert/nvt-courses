<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityService
{
    /**
     * Log an activity
     *
     * @param string $description
     * @param string|null $action
     * @param mixed|null $model
     * @param array|null $properties
     * @return ActivityLog
     */
    public static function log(string $description, ?string $action = null, $model = null, ?array $properties = null): ActivityLog
    {
        $userId = Auth::id();
        
        $data = [
            'user_id' => $userId,
            'description' => $description,
            'action' => $action,
            'properties' => $properties,
        ];
        
        if ($model) {
            $data['model_type'] = get_class($model);
            $data['model_id'] = $model->getKey();
        }
        
        return ActivityLog::create($data);
    }
    
    /**
     * Get recent activities
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecent(int $limit = 10)
    {
        return ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get activities for a specific user
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserActivities(int $userId, int $limit = 10)
    {
        return ActivityLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Log a course enrollment
     *
     * @param \App\Models\User $user
     * @param \App\Models\Course $course
     * @return ActivityLog
     */
    public static function logEnrollment($user, $course)
    {
        return self::log(
            "User {$user->name} enrolled in course {$course->name}",
            'enroll',
            $course,
            ['course_name' => $course->name]
        );
    }
    
    /**
     * Log a course completion
     *
     * @param \App\Models\User $user
     * @param \App\Models\Course $course
     * @return ActivityLog
     */
    public static function logCourseCompletion($user, $course)
    {
        return self::log(
            "User {$user->name} completed course {$course->name}",
            'complete',
            $course,
            ['course_name' => $course->name]
        );
    }
    
    /**
     * Log attendance
     *
     * @param \App\Models\User $user
     * @param \App\Models\Course $course
     * @param string $status
     * @return ActivityLog
     */
    public static function logAttendance($user, $course, $status)
    {
        return self::log(
            "User {$user->name} marked as {$status} for course {$course->name}",
            'attendance',
            $course,
            [
                'course_name' => $course->name,
                'status' => $status
            ]
        );
    }
}