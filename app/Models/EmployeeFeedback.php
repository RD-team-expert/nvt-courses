<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFeedback extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'status',
        'admin_response'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const TYPE_SUGGESTION = 'suggestion';
    const TYPE_IMPROVEMENT = 'improvement';
    const TYPE_FEATURE_REQUEST = 'feature_request';
    const TYPE_GENERAL = 'general';
}
