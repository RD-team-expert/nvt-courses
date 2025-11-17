<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id','course_type',
        'course_online_id',
        'department_id',
        'total_score',
        'incentive_amount',
    ];

    protected $casts = [
        'course_type' => 'string',
        'total_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function history()
    {
        return $this->hasMany(EvaluationHistory::class);
    }
    public function evaluatedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    /**
     * Alternative name for the same relationship
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * NEW: Relationship to online course
     */
    public function courseOnline()
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    /**
     * NEW: Get the course (either regular or online) dynamically
     */
    public function getCourseAttribute()
    {
        if ($this->course_type === 'online' && $this->course_online_id) {
            return $this->courseOnline;
        }
        return $this->course()->first();
    }

    /**
     * NEW: Scope for filtering by course type
     */
    public function scopeForCourseType($query, string $type)
    {
        return $query->where('course_type', $type);
    }

    /**
     * NEW: Scope for regular courses
     */
    public function scopeRegularCourses($query)
    {
        return $query->where('course_type', 'regular');
    }

    /**
     * NEW: Scope for online courses
     */
    public function scopeOnlineCourses($query)
    {
        return $query->where('course_type', 'online');
    }

    /**
     * NEW: Boot method to ensure data integrity
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            // Ensure only one course type is set
            if ($evaluation->course_type === 'online') {
                $evaluation->course_id = null;
            } else {
                $evaluation->course_online_id = null;
            }
        });
    }

}

