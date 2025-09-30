<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        // Your existing fields
        'name',
        'content',
        'department_id',

        // New evaluation notification fields (if you added the migration)
        'notification_type',
        'target_manager_level',
        'employee_count',
        'department_name',
        'sent_by',
        'sent_at',
        'email_subject',
        'managers_notified',
        'manager_emails',
        'status',
        'failure_reason',
        'evaluation_ids',
        'employee_names'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'employee_count' => 'integer',
        'managers_notified' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Admin who sent this notification
     */
    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Department this notification belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope for evaluation report notifications
     */
    public function scopeEvaluationReports($query)
    {
        return $query->where('notification_type', 'evaluation_report');
    }

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get evaluation IDs as array
     */
    public function getEvaluationIdsArray(): array
    {
        if (empty($this->evaluation_ids)) {
            return [];
        }

        return array_map('intval', explode(',', $this->evaluation_ids));
    }

    /**
     * Set evaluation IDs from array
     */
    public function setEvaluationIdsArray(array $ids): void
    {
        $this->evaluation_ids = implode(',', array_filter($ids));
    }

    /**
     * Get employee names as array
     */
    public function getEmployeeNamesArray(): array
    {
        if (empty($this->employee_names)) {
            return [];
        }

        return explode(',', $this->employee_names);
    }

    /**
     * Set employee names from array
     */
    public function setEmployeeNamesArray(array $names): void
    {
        $this->employee_names = implode(',', array_filter($names));
    }

    /**
     * Get manager emails as array
     */
    public function getManagerEmailsArray(): array
    {
        if (empty($this->manager_emails)) {
            return [];
        }

        return explode(',', $this->manager_emails);
    }

    /**
     * Set manager emails from array
     */
    public function setManagerEmailsArray(array $emails): void
    {
        $this->manager_emails = implode(',', array_filter($emails));
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(int $managersCount, array $managerEmails): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'managers_notified' => $managersCount,
            'manager_emails' => implode(',', $managerEmails)
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => 'failed',
            'failure_reason' => $reason
        ]);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'sent' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'partial' => 'bg-yellow-100 text-yellow-800',
            'draft' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get readable status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'sent' => 'Sent Successfully',
            'failed' => 'Failed to Send',
            'partial' => 'Partially Sent',
            'draft' => 'Draft',
            default => 'Unknown'
        };
    }

    /**
     * Get formatted sent date
     */
    public function getFormattedSentDate(): ?string
    {
        return $this->sent_at ? $this->sent_at->format('M d, Y H:i') : null;
    }

    // ==========================================
    // MISSING METHOD - ADD THIS
    // ==========================================

    /**
     * Create evaluation notification record
     */
    public static function createEvaluationNotification(array $data): self
    {
        return self::create([
            'name' => $data['name'] ?? 'Evaluation Report - ' . now()->format('M d, Y'),
            'content' => $data['content'] ?? '',
            'notification_type' => 'evaluation_report',
            'target_manager_level' => $data['target_manager_level'] ?? 'L2',
            'employee_count' => $data['employee_count'] ?? 0,
            'department_name' => $data['department_name'] ?? null,
            'email_subject' => $data['email_subject'] ?? 'Employee Evaluation Report',
            'sent_by' => $data['sent_by'] ?? auth()->id(),
            'status' => 'draft'
        ]);
    }
}
