<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'start_date',
        'end_date',
        'status',
        'privacy',
        'level',
        'duration'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    /**
     * Get all availabilities for this course
     */
    public function availabilities()
    {
        return $this->hasMany(CourseAvailability::class);
    }

    /**
     * Get only active availabilities
     */
    public function activeAvailabilities()
    {
        return $this->availabilities()->active();
    }

    /**
     * Get only available availabilities (not full, not expired)
     */
    public function availableAvailabilities()
    {
        return $this->availabilities()->available();
    }

    /**
     * Get the registrations for the course.
     */
    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    /**
     * Get the users registered for this course.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'course_registrations')
            ->withPivot(['status', 'course_availability_id', 'registered_at', 'completed_at', 'rating', 'feedback'])
            ->withTimestamps();
    }

    /**
     * Check if course is public
     */
    public function isPublic()
    {
        return $this->privacy === 'public';
    }

    /**
     * Check if course is private
     */
    public function isPrivate()
    {
        return $this->privacy === 'private';
    }

    /**
     * Scope to get only public courses
     */
    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    /**
     * Check if course has available spots
     */
    public function getHasAvailableSpotsAttribute()
    {
        return $this->availableAvailabilities()->exists();
    }

    /**
     * Get total enrollment across all availabilities
     */
    public function getTotalEnrollmentAttribute()
    {
        return $this->registrations()->count();
    }

    public function assignments()
    {
        return $this->hasMany(CourseAssignment::class);
    }
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
