<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use App\Models\CourseCompletion; // Changed from App\Services\CourseCompletion
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CourseService
{
    /**
     * Enroll a user in a course
     */
    public function enrollUser(Course $course, User $user): bool
    {
        try {
            // Log the enrollment attempt with detailed information

            // Check if already enrolled
            $isEnrolled = $this->isUserEnrolled($course, $user);

            if ($isEnrolled) {
                return false;
            }

            // Enroll the user
            $course->users()->attach($user->id, [
                'user_status' => 'enrolled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Verify the enrollment was successful
            $verifyEnrollment = $this->isUserEnrolled($course, $user);

            return $verifyEnrollment;
        } catch (\Exception $e) {

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


            // Check if enrolled
            $isEnrolled = $this->isUserEnrolled($course, $user);

            if (!$isEnrolled) {
                return false;
            }

            // Update status

            $course->users()->updateExistingPivot($user->id, [
                'user_status' => 'completed',
                'updated_at' => now(),
            ]);
            // Verify the update was successful
            $status = $this->getUserCourseStatus($course, $user);

            return $status === 'completed';
        } catch (\Exception $e) {

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


        return $exists;
    }

    /**
     * Get user's status in a course
     */
    public function getUserCourseStatus(Course $course, User $user)
    {
        $registration = CourseRegistration::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();



        return $registration ? $registration->status : null;
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



            return $completion;
        } catch (\Exception $e) {


            return null;
        }
    }

    /**
     * Format date for consistent display and storage
     *
     * @param string|null $date
     * @return string|null
     */
    public function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Parse the date using Carbon and ensure it's in the correct format
            // This prevents timezone issues by explicitly setting the timezone to UTC
            return Carbon::parse($date)->startOfDay()->toDateString();
        } catch (\Exception $e) {

            return $date; // Return original if parsing fails
        }
    }
}
