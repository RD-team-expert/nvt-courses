<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Quiz;
use App\Models\User;

class QuizCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $quiz;
    public $course;
    public $user;
    public $quizLink;

    /**
     * Create a new message instance.
     */
    public function __construct(Quiz $quiz, $course, User $user, $quizLink)
    {
        $this->quiz = $quiz;
        $this->course = $course;
        $this->user = $user;
        $this->quizLink = $quizLink;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = 'New Quiz Available: ' . $this->quiz->title;

        // Add deadline urgency to subject if applicable
        if ($this->quiz->has_deadline && $this->quiz->deadline) {
            $deadlineStatus = $this->quiz->getDeadlineStatus();
            if ($deadlineStatus['status'] === 'urgent') {
                $subject = '🚨 URGENT - ' . $subject;
            } elseif ($deadlineStatus['status'] === 'soon') {
                $subject = '⏰ ' . $subject . ' (Due Soon)';
            }
        }

        return $this->subject($subject)
            ->view('emails.quiz-created')
            ->with([
                'quiz' => $this->quiz,
                'course' => $this->course,
                'user' => $this->user,
                'quizLink' => $this->quizLink,
                'courseType' => $this->quiz->getCourseType(),
                'hasDeadline' => $this->quiz->has_deadline,
                'deadline' => $this->quiz->deadline,
                'deadlineFormatted' => $this->quiz->getFormattedDeadline(),
                'timeUntilDeadline' => $this->quiz->getTimeUntilDeadline(),
                'deadlineStatus' => $this->quiz->getDeadlineStatus(),
                'enforceDeadline' => $this->quiz->enforce_deadline,
                'timeLimitMinutes' => $this->quiz->time_limit_minutes,
            ]);
    }
}
