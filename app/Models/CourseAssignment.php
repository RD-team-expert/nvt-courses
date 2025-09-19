<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CourseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'assigned_by',
        'course_availability_id',
        'status',

        'assigned_at',
        'responded_at',
        'completed_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'responded_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the course for this assignment
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user assigned to this course
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who assigned this course
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the course availability if assigned
     */
    public function courseAvailability()
    {
        return $this->belongsTo(CourseAvailability::class);
    }

    /**
     * Check if assignment is overdue
     */


    /**
     * Check if assignment is pending response
     */
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Scope for pending assignments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNotIn('status', ['completed', 'declined']);
    }
}
