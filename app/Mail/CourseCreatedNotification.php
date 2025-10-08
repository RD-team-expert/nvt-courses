<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $course;
    public $user;
    public $loginLink;

    /**
     * Create a new message instance.
     */
    public function __construct(Course $course, User $user, string $loginLink)
    {
        $this->course = $course;
        $this->user = $user;
        $this->loginLink = $loginLink;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Course Available: ' . $this->course->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // ✅ Load availabilities with scheduling data
        $this->course->load('availabilities');

        // ✅ Process scheduling data for email display
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

        return new Content(
            view: 'emails.course-created1',
            with: [
                'course' => $this->course,
                'user' => $this->user,
                'courseName' => $this->course->name,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'loginLink' => $this->loginLink,
                'description' => $this->course->description,

                // ✅ NEW: Pass processed availabilities with scheduling data
                'availabilities' => $processedAvailabilities,

                // ✅ Keep backward compatibility for single dates
                'startDate' => $this->course->availabilities->first()
                    ? $this->course->availabilities->first()->start_date->format('F j, Y')
                    : 'TBD',
                'endDate' => $this->course->availabilities->first()
                    ? $this->course->availabilities->first()->end_date->format('F j, Y')
                    : 'TBD',
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
