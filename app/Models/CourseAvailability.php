<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'notes',
        // NEW FIELDS
        'days_of_week',
        'duration_weeks',
        'session_time',
        'session_duration_minutes'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        // NEW CASTS
        'session_time' => 'datetime:H:i',
        'duration_weeks' => 'integer',
        'session_duration_minutes' => 'integer',
    ];

    // EXISTING RELATIONSHIPS - KEEP THESE [attached_file:2]
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    // EXISTING METHODS - KEEP ALL OF THESE [attached_file:2]
    public function getEnrolledCountAttribute()
    {
        return $this->registrations->count();
    }

    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->capacity - $this->enrolled_count);
    }

    public function getIsFullAttribute()
    {
        return $this->enrolled_count >= $this->capacity;
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < now();
    }

    public function getIsAvailableAttribute()
    {
        return $this->status === 'active' && !$this->is_full && !$this->is_expired;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where('end_date', '>', now())
            ->whereRaw('capacity > (SELECT COUNT(*) FROM course_registrations WHERE course_availability_id = course_availabilities.id)');
    }

    public function getFormattedDateRangeAttribute()
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    public function getIsFullyBookedAttribute()
    {
        return $this->sessions > 0;
    }

    // NEW METHODS FOR DAYS/WEEKS FUNCTIONALITY

    /**
     * Get available days array for validation
     */
    public static function getAvailableDays(): array
    {
        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    }

    /**
     * Get selected days as array from SET field
     */
    public function getSelectedDaysAttribute(): array
    {
        if (!$this->days_of_week) {
            return [];
        }

        // SET field returns comma-separated string: "monday,wednesday,friday"
        return explode(',', $this->days_of_week);
    }

    /**
     * Set days from array for SET field
     */
    public function setSelectedDaysAttribute(array $days): void
    {
        // Convert array to comma-separated string for SET field
        $this->days_of_week = implode(',', $days);
    }

    /**
     * Calculate total sessions based on weeks × selected days
     */
    public function calculateTotalSessions(): int
    {
        $selectedDaysCount = count($this->getSelectedDaysAttribute());
        return $this->duration_weeks * $selectedDaysCount;
    }

    /**
     * Auto-calculate end date based on start date + weeks
     */
    public function calculateEndDate(): ?Carbon
    {
        if ($this->start_date && $this->duration_weeks) {
            return $this->start_date->copy()->addWeeks($this->duration_weeks);
        }
        return null;
    }

    /**
     * Get all actual session dates for selected days
     */
    public function getScheduledSessionsAttribute()
    {
        if (!$this->start_date || !$this->end_date || !$this->days_of_week) {
            return collect();
        }

        $sessions = collect();
        $selectedDays = $this->getSelectedDaysAttribute();
        $currentDate = $this->start_date->copy();

        while ($currentDate <= $this->end_date) {
            $dayName = strtolower($currentDate->format('l')); // 'monday', 'tuesday', etc.

            if (in_array($dayName, $selectedDays)) {
                $sessions->push($currentDate->copy());
            }

            $currentDate->addDay();
        }

        return $sessions;
    }

    /**
     * Get formatted session time
     */
    public function getFormattedSessionTimeAttribute(): ?string
    {
        return $this->session_time ? $this->session_time->format('H:i') : null;
    }

    /**
     * Get session duration in hours and minutes
     */
    public function getFormattedSessionDurationAttribute(): string
    {
        if (!$this->session_duration_minutes) {
            return '1 hour';
        }

        $hours = floor($this->session_duration_minutes / 60);
        $minutes = $this->session_duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours} hour" . ($hours > 1 ? 's' : '');
        } else {
            return "{$minutes} minute" . ($minutes > 1 ? 's' : '');
        }
    }

    /**
     * Get selected days formatted for display
     */
    public function getFormattedDaysAttribute(): string
    {
        $days = $this->getSelectedDaysAttribute();
        if (empty($days)) {
            return 'No days selected';
        }

        $dayNames = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun'
        ];

        $formatted = array_map(function($day) use ($dayNames) {
            return $dayNames[$day] ?? $day;
        }, $days);

        return implode(', ', $formatted);
    }
}
