<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'completed_at',
        'rating',
        'feedback'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that completed the course.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that was completed.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
