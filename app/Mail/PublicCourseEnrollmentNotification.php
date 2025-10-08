<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use App\Models\CourseRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class PublicCourseEnrollmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Course $course;
    public User $enrolledUser;
    public User $manager;
    public $enrollmentDetails;

    public function __construct(Course $course, User $enrolledUser, User $manager, $enrollmentDetails = null)
    {
        $this->course = $course;
        $this->enrolledUser = $enrolledUser;
        $this->manager = $manager;
        $this->enrollmentDetails = $enrollmentDetails ?: [];
    }

    public function build()
    {
        // ✅ Load course with availabilities and get user's selected schedule
        $this->course->load('availabilities');

        // ✅ Get the user's enrollment to find their selected availability
        $enrollment = CourseRegistration::where('course_id', $this->course->id)
            ->where('user_id', $this->enrolledUser->id)
            ->with('courseAvailability')
            ->first();

        // ✅ Process the selected availability with scheduling data
        $selectedSchedule = null;
        if ($enrollment && $enrollment->courseAvailability) {
            $availability = $enrollment->courseAvailability;
            $selectedSchedule = [
                'id' => $availability->id,
                'start_date' => $availability->start_date,
                'end_date' => $availability->end_date,
                'formatted_date_range' => $availability->formatted_date_range ?? 'TBD',
                'capacity' => $availability->capacity,
                'sessions' => $availability->sessions,
                'notes' => $availability->notes,

                // ✅ NEW: Scheduling fields
                'days_of_week' => $availability->days_of_week,
                'selected_days' => $availability->selected_days ?? [], // Array format
                'formatted_days' => $availability->formatted_days ?? 'TBD', // "Mon, Wed, Fri"
                'duration_weeks' => $availability->duration_weeks,
                'session_time' => $availability->session_time,
                'formatted_session_time' => $availability->formatted_session_time, // "09:00"
                'session_duration_minutes' => $availability->session_duration_minutes,
                'formatted_session_duration' => $availability->formatted_session_duration ?? 'TBD', // "2h 30m"
            ];
        }

        return $this->subject("Team Member Enrolled: {$this->enrolledUser->name} - {$this->course->name}")
            ->view('emails.public-course-enrollment-notification')
            ->with([
                'course' => $this->course,
                'enrolledUser' => $this->enrolledUser,
                'manager' => $this->manager,
                'enrollmentDate' => Carbon::now()->format('F j, Y'),
                'enrollmentDetails' => $this->enrollmentDetails,

                // ✅ NEW: Pass selected schedule with full scheduling data
                'selectedSchedule' => $selectedSchedule,
            ]);
    }
}
