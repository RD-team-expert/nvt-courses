<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'registered_at',
        'completed_at',
        'rating',
        'feedback'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'completed_at' => 'datetime',
        'rating' => 'integer'
    ];

    /**
     * Get the user that owns the registration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that the registration belongs to.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}