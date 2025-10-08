<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SuspiciousActivityWarning extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $warningData;
    public $adminUser;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $warningData, User $adminUser)
    {
        $this->user = $user;
        $this->warningData = $warningData;
        $this->adminUser = $adminUser;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Learning Activity Review Required - ' . config('app.name'))
            ->view('emails.suspicious-activity-warning')
            ->with([
                'userName' => $this->user->name,
                'courseName' => $this->warningData['course_name'],
                'riskScore' => $this->warningData['risk_score'],
                'riskLevel' => $this->warningData['risk_level'],
                'sessionDate' => $this->warningData['session_date'],
                'duration' => $this->warningData['duration'],
                'reasons' => $this->warningData['reasons'],
                'recommendations' => $this->warningData['recommendations'],
                'adminName' => $this->adminUser->name,
                'companyName' => config('app.name'),
                'supportEmail' => config('mail.support', 'support@yourcompany.com'),
            ]);
    }
}
