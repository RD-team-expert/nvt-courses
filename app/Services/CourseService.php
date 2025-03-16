<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\CourseCompletion; // Changed from App\Services\CourseCompletion
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class CourseService
{
    /**
     * Enroll a user in a course
     */
    public function enrollUser(Course $course, User $user): bool
    {
        try {
            // Log the enrollment attempt with detailed information
            Log::info('Attempting to enroll user in course', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);
            
            // Check if already enrolled
            $isEnrolled = $this->isUserEnrolled($course, $user);
            Log::info('User enrollment check', ['is_already_enrolled' => $isEnrolled]);
            
            if ($isEnrolled) {
                Log::info('User is already enrolled, skipping enrollment');
                return false;
            }
            
            // Enroll the user
            Log::info('Attaching user to course');
            $course->users()->attach($user->id, [
                'user_status' => 'enrolled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Verify the enrollment was successful
            $verifyEnrollment = $this->isUserEnrolled($course, $user);
            Log::info('Enrollment verification', ['enrollment_successful' => $verifyEnrollment]);
            
            return $verifyEnrollment;
        } catch (\Exception $e) {
            Log::error('Error enrolling user in course', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            report($e);
            return false;
        }
    }
    
    /**
     * Mark a course as completed for a user
     */
    public function markCourseCompleted(Course $course, User $user): bool
    {
        try {
            // Log the completion attempt
            Log::info('Attempting to mark course as completed', [
                'user_id' => $user->id,
                'course_id' => $course->id
            ]);
            
            // Check if enrolled
            $isEnrolled = $this->isUserEnrolled($course, $user);
            Log::info('User enrollment check for completion', ['is_enrolled' => $isEnrolled]);
            
            if (!$isEnrolled) {
                Log::info('User is not enrolled, cannot mark as completed');
                return false;
            }
            
            // Update status
            Log::info('Updating user course status to completed');
            $course->users()->updateExistingPivot($user->id, [
                'user_status' => 'completed',
                'updated_at' => now(),
            ]);
            
            // Verify the update was successful
            $status = $this->getUserCourseStatus($course, $user);
            Log::info('Completion verification', ['status_after_update' => $status]);
            
            return $status === 'completed';
        } catch (\Exception $e) {
            Log::error('Error marking course as completed', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            report($e);
            return false;
        }
    }
    
    /**
     * Check if a user is enrolled in a course
     */
    public function isUserEnrolled(Course $course, User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        $exists = $course->users()->where('user_id', $user->id)->exists();
        Log::debug('Checking if user is enrolled', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'is_enrolled' => $exists
        ]);
        
        return $exists;
    }
    
    /**
     * Get user's status in a course
     */
    public function getUserCourseStatus(Course $course, User $user): ?string
    {
        if (!$user) {
            return null;
        }
        
        $enrollment = $course->users()->where('user_id', $user->id)->first();
        $status = $enrollment ? $enrollment->pivot->user_status : null;
        
        Log::debug('Getting user course status', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => $status
        ]);
        
        return $status;
    }
    
    /**
     * Create or update a course completion record
     * 
     * @param Course $course
     * @param User $user
     * @return CourseCompletion|null
     */
    public function createCourseCompletionRecord(Course $course, User $user)
    {
        try {
            // Check if user has completed the course in the pivot table
            $status = $this->getUserCourseStatus($course, $user);
            
            if ($status !== 'completed') {
                Log::info('User has not completed the course yet', [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'status' => $status
                ]);
                return null;
            }
            
            // Create or update the completion record
            $completion = CourseCompletion::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id
                ],
                [
                    'completed_at' => now(),
                    // Don't set rating and feedback here, let the user do that
                ]
            );
            
            Log::info('Course completion record created/updated', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'completion_id' => $completion->id
            ]);
            
            return $completion;
        } catch (\Exception $e) {
            Log::error('Error creating course completion record', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'course_id' => $course->id
            ]);
            
            return null;
        }
    }
}