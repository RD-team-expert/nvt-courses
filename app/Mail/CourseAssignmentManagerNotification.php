<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CourseAssignmentManagerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Course $course;
    public $assignedUsers; // Collection of users
    public User $assignedBy;
    public User $manager;
    public $assignmentDetails;

    public function __construct(Course $course, $assignedUsers, User $assignedBy, User $manager, $assignmentDetails = null)
    {
        $this->course = $course;
        $this->assignedUsers = $assignedUsers;
        $this->assignedBy = $assignedBy;
        $this->manager = $manager;
        $this->assignmentDetails = $assignmentDetails ?: [];
    }

    public function build()
    {
        // ✅ Load course with availabilities and scheduling data
        $this->course->load('availabilities');

        // ✅ Process availabilities with scheduling data for email display
        $processedAvailabilities = $this->course->availabilities->map(function ($availability) {
            return [
                'id' => $availability->id,
                'start_date' => $availability->start_date,
                'end_date' => $availability->end_date,
                'formatted_date_range' => $availability->formatted_date_range ?? 'TBD',
                'capacity' => $availability->capacity,
                'sessions' => $availability->sessions,
                'available_spots' => $availability->available_spots ?? $availability->sessions,
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
        });

        $userCount = $this->assignedUsers->count();
        $userNames = $this->assignedUsers->take(3)->pluck('name')->join(', ');

        if ($userCount > 3) {
            $userNames .= " and " . ($userCount - 3) . " more";
        }

        return $this->subject("Course Assignment: {$this->course->name} - {$userNames}")
            ->view('emails.course-assignment-manager-notification')
            ->with([
                'course' => $this->course,
                'assignedUsers' => $this->assignedUsers,
                'assignedBy' => $this->assignedBy,
                'manager' => $this->manager,
                'userCount' => $userCount,
                'userNames' => $userNames,
                'assignmentDate' => Carbon::now()->format('F j, Y'),
                'assignmentDetails' => $this->assignmentDetails,

                // ✅ NEW: Pass processed availabilities with scheduling data
                'availabilities' => $processedAvailabilities,
            ]);
    }
}
