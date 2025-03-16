<?php

namespace App\Services;

use App\Mail\CourseCreatedNotification;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send course creation notification to all users
     *
     * @param Course $course The newly created course
     * @return void
     */
    public function sendCourseCreationNotification(Course $course)
    {
        // Get all users
        $users = User::where('role', '!=', 'admin')->get();
        
        foreach ($users as $user) {
            Mail::to($user->email)
                ->queue(new CourseCreatedNotification($course, $user));
        }
    }
    
    /**
     * Send course creation notification to a specific user
     *
     * @param Course $course The newly created course
     * @param User $user The user to notify
     * @return void
     */
    public function sendCourseCreationNotificationToUser(Course $course, User $user)
    {
        Mail::to($user->email)
            ->queue(new CourseCreatedNotification($course, $user));
    }
}