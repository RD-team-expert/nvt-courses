<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CourseAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'start_date',
        'end_date',
        'capacity',
        'sessions',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    /**
     * Get the course this availability belongs to
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get all registrations for this availability
     */
    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    /**
     * Get enrolled users count for this availability
     */
    public function getEnrolledCountAttribute()
    {
        return $this->registrations()->count();
    }

    /**
     * Get available spots for this availability
     */
    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->capacity - $this->enrolled_count);
    }

    /**
     * Check if this availability is full
     */


    /**
     * Check if this availability is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->end_date < now();
    }

    /**
     * Check if this availability is available for enrollment
     */
    public function getIsAvailableAttribute()
    {
        return $this->status === 'active' && !$this->is_full && !$this->is_expired;
    }

    /**
     * Scope to get only active availabilities
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only available (not full, not expired, active)
     */
    public function scopeAvailable($query)
    {
        return $query->active()
            ->where('end_date', '>', now())
            ->whereRaw('capacity > (SELECT COUNT(*) FROM course_registrations WHERE course_availability_id = course_availabilities.id)');
    }

    /**
     * Format date range for display
     */
    public function getFormattedDateRangeAttribute()
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }
    public function getIsFullyBookedAttribute()
    {
        return $this->sessions <= 0;
    }
}
