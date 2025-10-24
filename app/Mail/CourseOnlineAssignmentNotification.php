<?php

namespace App\Mail;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseOnlineAssignmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public CourseOnline $course;
    public User $user;
    public User $assignedBy;
    public ?string $loginLink;
    public  $metadata;
    // ✅ NEW: Deadline-related properties
    public bool $hasDeadline;
    public ?string $deadlineDate;
    public ?string $deadlineStatus;
    public ?int $daysUntilDeadline;
    public ?string $urgencyLevel;

    public function __construct(
        CourseOnline $course,
        User $user,
        User $assignedBy,
        ?string $loginLink = null,
        array $metadata = []
    ) {
        $this->course = $course;
        $this->user = $user;
        $this->assignedBy = $assignedBy;
        $this->loginLink = $loginLink;
        $this->metadata = $metadata;

        // ✅ NEW: Initialize deadline properties
        $this->hasDeadline = $course->has_deadline ?? false;
        $this->deadlineDate = $course->deadline?->format('M d, Y H:i');
        $this->daysUntilDeadline = $course->daysUntilDeadline();
        $this->deadlineStatus = $this->determineDeadlineStatus();
        $this->urgencyLevel = $this->determineUrgencyLevel();
    }

    public function envelope(): Envelope
    {
        // ✅ ENHANCED: Include deadline urgency in subject
        $baseSubject = "New Online Course Assignment: {$this->course->name}";

        // ✅ NEW: Add urgency indicators to subject line
        if ($this->hasDeadline) {
            $subject = match($this->urgencyLevel) {
                'critical' => "🚨 URGENT - " . $baseSubject . " (Due Soon!)",
                'high' => "⚠️ " . $baseSubject . " (Due in {$this->daysUntilDeadline} days)",
                'medium' => "📅 " . $baseSubject . " (Due: " . $this->course->deadline->format('M d') . ")",
                default => $baseSubject
            };
        } else {
            $subject = $baseSubject;
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-online-assignment-notification',
            with: [
                // ✅ EXISTING: Pass original data
                'course' => $this->course,
                'user' => $this->user,
                'assignedBy' => $this->assignedBy,
                'loginLink' => $this->loginLink,
                'metadata' => $this->metadata,

                // ✅ NEW: Pass deadline data to template
                'hasDeadline' => $this->hasDeadline,
                'deadlineDate' => $this->deadlineDate,
                'deadlineStatus' => $this->deadlineStatus,
                'daysUntilDeadline' => $this->daysUntilDeadline,
                'urgencyLevel' => $this->urgencyLevel,
                'deadlineType' => $this->course->deadline_type,

                // ✅ NEW: Rich deadline data for template
                'deadlineFormatted' => $this->getFormattedDeadline(),
                'urgencyMessage' => $this->getUrgencyMessage(),
                'actionMessage' => $this->getActionMessage(),
                'timeRemaining' => $this->getTimeRemaining(),
                'completionGuidance' => $this->getCompletionGuidance(),
            ]
        );
    }

    /**
     * ✅ NEW: Determine deadline status
     */
    private function determineDeadlineStatus(): ?string
    {
        if (!$this->hasDeadline || !$this->daysUntilDeadline) {
            return null;
        }

        $days = $this->daysUntilDeadline;

        if ($days < 0) {
            return 'overdue';
        } elseif ($days <= 1) {
            return 'due_today';
        } elseif ($days <= 3) {
            return 'due_soon';
        } elseif ($days <= 7) {
            return 'due_this_week';
        }

        return 'upcoming';
    }

    /**
     * ✅ NEW: Determine urgency level for email priority
     */
    private function determineUrgencyLevel(): ?string
    {
        if (!$this->hasDeadline) {
            return null;
        }

        return match($this->deadlineStatus) {
            'overdue', 'due_today' => 'critical',
            'due_soon' => 'high',
            'due_this_week' => 'medium',
            default => 'low'
        };
    }

    /**
     * ✅ NEW: Get formatted deadline information
     */
    private function getFormattedDeadline(): array
    {
        if (!$this->hasDeadline || !$this->course->deadline) {
            return [
                'full_date' => null,
                'short_date' => null,
                'relative' => null,
                'day_of_week' => null,
                'time_only' => null
            ];
        }

        $deadline = $this->course->deadline;

        return [
            'full_date' => $deadline->format('l, F j, Y \a\t g:i A'),
            'short_date' => $deadline->format('M d, Y'),
            'relative' => $deadline->diffForHumans(),
            'day_of_week' => $deadline->format('l'),
            'time_only' => $deadline->format('g:i A'),
            'is_weekend' => $deadline->isWeekend(),
            'is_today' => $deadline->isToday(),
            'is_tomorrow' => $deadline->isTomorrow(),
        ];
    }

    /**
     * ✅ NEW: Get urgency message for the user
     */
    private function getUrgencyMessage(): ?string
    {
        if (!$this->hasDeadline) {
            return null;
        }

        return match($this->deadlineStatus) {
            'overdue' => '🚨 This course deadline has passed! Please complete it as soon as possible.',
            'due_today' => '⏰ This course is due TODAY! Please prioritize completion immediately.',
            'due_soon' => '⚠️ This course is due within 3 days. Please start as soon as possible.',
            'due_this_week' => '📅 This course is due this week. Please plan your time accordingly.',
            'upcoming' => '📋 Please note the upcoming deadline for this course.',
            default => null
        };
    }

    /**
     * ✅ NEW: Get specific action message
     */
    private function getActionMessage(): ?string
    {
        if (!$this->hasDeadline) {
            return 'You can start this course at your convenience.';
        }

        return match($this->urgencyLevel) {
            'critical' => 'Immediate action required - please start this course now!',
            'high' => 'Please begin this course within the next 24 hours.',
            'medium' => 'Please start this course within the next few days.',
            default => 'Please plan to complete this course by the deadline.'
        };
    }

    /**
     * ✅ NEW: Get time remaining information
     */
    private function getTimeRemaining(): ?array
    {
        if (!$this->hasDeadline || !$this->course->deadline) {
            return null;
        }

        $deadline = $this->course->deadline;
        $now = now();

        if ($deadline->isPast()) {
            $diff = $deadline->diff($now);
            return [
                'status' => 'overdue',
                'message' => 'Overdue by ' . $this->formatTimeDifference($diff),
                'class' => 'overdue'
            ];
        }

        $diff = $now->diff($deadline);
        $timeString = $this->formatTimeDifference($diff);

        $status = match(true) {
            $diff->days === 0 && $diff->h <= 4 => 'critical',
            $diff->days === 0 => 'urgent',
            $diff->days <= 2 => 'warning',
            default => 'normal'
        };

        return [
            'status' => $status,
            'message' => $timeString . ' remaining',
            'class' => $status,
            'exact_time' => $timeString
        ];
    }

    /**
     * ✅ NEW: Format time difference for human reading
     */
    private function formatTimeDifference(\DateInterval $diff): string
    {
        if ($diff->days > 0) {
            $result = $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
            if ($diff->h > 0) {
                $result .= ' and ' . $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
            }
            return $result;
        }

        if ($diff->h > 0) {
            $result = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
            if ($diff->i > 0) {
                $result .= ' and ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
            }
            return $result;
        }

        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }

    /**
     * ✅ NEW: Get completion guidance based on course and deadline
     */
    private function getCompletionGuidance(): array
    {
        $guidance = [
            'estimated_time' => $this->course->estimated_duration . ' minutes',
            'difficulty' => ucfirst($this->course->difficulty_level),
            'modules_count' => $this->course->modules()->count(),
        ];

        if ($this->hasDeadline && $this->daysUntilDeadline) {
            $dailyTime = $this->course->estimated_duration / max(1, $this->daysUntilDeadline);

            $guidance['daily_commitment'] = [
                'minutes_per_day' => round($dailyTime),
                'sessions_suggested' => $this->getSuggestedSessions($dailyTime),
                'recommendation' => $this->getStudyRecommendation($dailyTime)
            ];
        }

        return $guidance;
    }

    /**
     * ✅ NEW: Get suggested study sessions
     */
    private function getSuggestedSessions(float $dailyMinutes): string
    {
        if ($dailyMinutes <= 15) {
            return 'One short session per day';
        } elseif ($dailyMinutes <= 30) {
            return 'One 30-minute session per day';
        } elseif ($dailyMinutes <= 60) {
            return 'One hour-long session per day';
        } else {
            return 'Multiple sessions per day recommended';
        }
    }

    /**
     * ✅ NEW: Get study recommendation
     */
    private function getStudyRecommendation(float $dailyMinutes): string
    {
        return match(true) {
            $dailyMinutes <= 20 => 'Very manageable - just a few minutes each day!',
            $dailyMinutes <= 45 => 'Easily achievable with consistent daily effort.',
            $dailyMinutes <= 90 => 'Requires dedicated study time but very doable.',
            default => 'Consider starting immediately to meet the deadline comfortably.'
        };
    }

    /**
     * ✅ NEW: Check if email should be high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->urgencyLevel, ['critical', 'high']);
    }

    /**
     * ✅ NEW: Get email priority for email clients
     */
    public function getEmailPriority(): string
    {
        return match($this->urgencyLevel) {
            'critical' => 'high',
            'high' => 'high',
            'medium' => 'normal',
            default => 'low'
        };
    }

    /**
     * ✅ NEW: Get deadline badge color for UI
     */
    public function getDeadlineBadgeColor(): string
    {
        return match($this->deadlineStatus) {
            'overdue' => '#dc2626', // red
            'due_today' => '#ea580c', // orange-red
            'due_soon' => '#f59e0b', // amber
            'due_this_week' => '#3b82f6', // blue
            default => '#6b7280' // gray
        };
    }
}
