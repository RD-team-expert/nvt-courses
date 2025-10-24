<?php

namespace App\Mail;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CourseOnlineAssignmentManagerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public CourseOnline $course;
    public Collection $teamMembers;
    public User $assignedBy;
    public User $manager;
    public  $metadata;
    // ✅ NEW: Deadline-related properties
    public bool $hasDeadline;
    public ?string $deadlineDate;
    public ?string $deadlineStatus;
    public ?int $daysUntilDeadline;

    public function __construct(
        CourseOnline $course,
        Collection $teamMembers,
        User $assignedBy,
        User $manager,
        array $metadata = []
    ) {
        $this->course = $course;
        $this->teamMembers = $teamMembers;
        $this->assignedBy = $assignedBy;
        $this->manager = $manager;
        $this->metadata = $metadata;

        // ✅ NEW: Initialize deadline properties
        $this->hasDeadline = $course->has_deadline ?? false;
        $this->deadlineDate = $course->deadline?->format('M d, Y H:i');
        $this->daysUntilDeadline = $course->daysUntilDeadline();
        $this->deadlineStatus = $this->determineUrgency();
    }

    public function envelope(): Envelope
    {
        $memberCount = $this->teamMembers->count();

        // ✅ ENHANCED: Include deadline urgency in subject
        $baseSubject = $memberCount === 1
            ? "Team Member Assigned to Online Course: {$this->course->name}"
            : "{$memberCount} Team Members Assigned to Online Course: {$this->course->name}";

        // ✅ NEW: Add urgency indicator to subject if deadline is urgent
        if ($this->hasDeadline && $this->deadlineStatus === 'urgent') {
            $baseSubject = "⚠️ URGENT - " . $baseSubject;
        } elseif ($this->hasDeadline && $this->deadlineStatus === 'warning') {
            $baseSubject = "📅 " . $baseSubject;
        }

        return new Envelope(subject: $baseSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-online-assignment-manager-notification',
            with: [
                // ✅ NEW: Pass deadline data to the email template
                'course' => $this->course,
                'teamMembers' => $this->teamMembers,
                'assignedBy' => $this->assignedBy,
                'manager' => $this->manager,
                'metadata' => $this->metadata,
                // ✅ NEW: Deadline-specific data for template
                'hasDeadline' => $this->hasDeadline,
                'deadlineDate' => $this->deadlineDate,
                'deadlineStatus' => $this->deadlineStatus,
                'daysUntilDeadline' => $this->daysUntilDeadline,
                'deadlineType' => $this->course->deadline_type,
                'urgencyMessage' => $this->getUrgencyMessage(),
                'deadlineFormatted' => $this->getFormattedDeadline(),
                'memberAssignments' => $this->getMemberAssignmentData(),
            ]
        );
    }

    /**
     * ✅ NEW: Determine deadline urgency level
     */
    private function determineUrgency(): ?string
    {
        if (!$this->hasDeadline || !$this->daysUntilDeadline) {
            return null;
        }

        $days = $this->daysUntilDeadline;

        if ($days < 0) {
            return 'overdue';
        } elseif ($days <= 1) {
            return 'urgent';
        } elseif ($days <= 3) {
            return 'warning';
        } elseif ($days <= 7) {
            return 'notice';
        }

        return 'normal';
    }

    /**
     * ✅ NEW: Get urgency message for email
     */
    private function getUrgencyMessage(): ?string
    {
        if (!$this->hasDeadline) {
            return null;
        }

        return match($this->deadlineStatus) {
            'overdue' => '🚨 This course deadline has already passed! Immediate action required.',
            'urgent' => '⚠️ This course is due within 24 hours! Please prioritize completion.',
            'warning' => '📅 This course is due within 3 days. Please ensure timely completion.',
            'notice' => '📋 This course is due within a week. Please plan accordingly.',
            default => null
        };
    }

    /**
     * ✅ NEW: Get formatted deadline with relative time
     */
    private function getFormattedDeadline(): array
    {
        if (!$this->hasDeadline || !$this->course->deadline) {
            return [
                'date' => null,
                'relative' => null,
                'time_left' => null
            ];
        }

        $deadline = $this->course->deadline;
        $now = now();

        // Calculate time remaining
        $timeLeft = '';
        if ($deadline->isFuture()) {
            $diff = $now->diff($deadline);

            if ($diff->days > 0) {
                $timeLeft = $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
                if ($diff->h > 0) {
                    $timeLeft .= ' and ' . $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
                }
            } elseif ($diff->h > 0) {
                $timeLeft = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
                if ($diff->i > 0) {
                    $timeLeft .= ' and ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
                }
            } else {
                $timeLeft = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
            }
            $timeLeft .= ' remaining';
        } else {
            $diff = $deadline->diff($now);
            $timeLeft = 'Overdue by ';

            if ($diff->days > 0) {
                $timeLeft .= $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
            } elseif ($diff->h > 0) {
                $timeLeft .= $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
            } else {
                $timeLeft .= $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
            }
        }

        return [
            'date' => $deadline->format('l, F j, Y \a\t g:i A'),
            'relative' => $deadline->diffForHumans(),
            'time_left' => $timeLeft
        ];
    }

    /**
     * ✅ NEW: Get member assignment data with deadline context
     */
    private function getMemberAssignmentData(): Collection
    {
        return $this->teamMembers->map(function ($member) {
            // Get the member's assignment for this course
            $assignment = $member->courseAssignments()
                ->where('course_online_id', $this->course->id)
                ->first();

            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'department' => $member->department?->name ?? 'No Department',
                'assignment' => [
                    'id' => $assignment?->id,
                    'status' => $assignment?->status ?? 'not_assigned',
                    'progress_percentage' => $assignment?->progress_percentage ?? 0,
                    'assigned_at' => $assignment?->assigned_at?->format('M d, Y H:i'),
                    'deadline' => $assignment?->deadline?->format('M d, Y H:i'),
                    'is_overdue' => $assignment?->is_overdue ?? false,
                    'days_until_deadline' => $assignment?->daysUntilDeadline(),
                ]
            ];
        });
    }

    /**
     * ✅ NEW: Get deadline statistics for the team
     */
    public function getTeamDeadlineStats(): array
    {
        if (!$this->hasDeadline) {
            return [];
        }

        $assignments = $this->getMemberAssignmentData();

        return [
            'total_members' => $assignments->count(),
            'overdue_count' => $assignments->where('assignment.is_overdue', true)->count(),
            'urgent_count' => $assignments->filter(function($member) {
                $days = $member['assignment']['days_until_deadline'];
                return $days !== null && $days <= 1 && $days >= 0;
            })->count(),
            'warning_count' => $assignments->filter(function($member) {
                $days = $member['assignment']['days_until_deadline'];
                return $days !== null && $days > 1 && $days <= 3;
            })->count(),
        ];
    }

    /**
     * ✅ NEW: Check if email should be marked as high priority
     */
    public function shouldMarkAsHighPriority(): bool
    {
        if (!$this->hasDeadline) {
            return false;
        }

        // Mark as high priority if deadline is urgent or overdue
        return in_array($this->deadlineStatus, ['urgent', 'overdue']);
    }

    /**
     * ✅ NEW: Get email priority level
     */
    public function getPriority(): string
    {
        if (!$this->hasDeadline) {
            return 'normal';
        }

        return match($this->deadlineStatus) {
            'overdue', 'urgent' => 'high',
            'warning' => 'medium',
            default => 'normal'
        };
    }
}
