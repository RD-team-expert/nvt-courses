<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clocking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'clock_in',
        'clock_out',
        'rating',
        'comment',
        'duration_in_minutes'
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'duration_in_minutes' => 'integer'
    ];

    /**
     * Get the user that owns the clocking record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the duration in minutes between clock in and clock out
     */
    public function getDurationInMinutesAttribute()
    {
        if (!$this->clock_out) {
            return null;
        }

        return $this->clock_in->diffInMinutes($this->clock_out);
    }

    /**
     * Update the duration when clocking out
     */
    public function updateDuration()
    {
        if ($this->clock_out) {
            $this->duration_in_minutes = $this->getDurationInMinutesAttribute();
            $this->save();
        }
        
        return $this;
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
