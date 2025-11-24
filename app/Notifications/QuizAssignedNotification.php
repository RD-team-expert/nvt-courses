<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public QuizAssignment $quizAssignment
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Quiz Assignment')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have been assigned a new quiz.')
            ->line('Quiz: ' . $this->quizAssignment->quiz->title)
            ->line('Assigned by: ' . $this->quizAssignment->assignedBy->name)
            ->action('Take Quiz', url('/quizzes/' . $this->quizAssignment->quiz_id))
            ->line('Good luck!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'quiz_id' => $this->quizAssignment->quiz_id,
            'assigned_by' => $this->quizAssignment->assigned_by,
        ];
    }
}
