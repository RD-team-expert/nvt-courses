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
        'level',
        'duration'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

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
    // Check if this relationship exists in your Course model
    public function users()
    {
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot('user_status')
            ->withTimestamps();
    }
}
