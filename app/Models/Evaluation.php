<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PerformanceLevel;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id','course_type',
        'course_online_id',
        'department_id',
        'total_score',
        'performance_level',
        'performance_points_min',
        'performance_points_max',
        'incentive_amount', // deprecated - retained for legacy data during transition
    ];

    protected $casts = [
        'course_type' => 'string',
        'total_score' => 'integer',
        'performance_level' => 'integer',
        'performance_points_min' => 'integer',
        'performance_points_max' => 'integer',
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

    /**
     * Get performance level label (e.g. "Outstanding").
     *
     * @return string|null
     */
    public function getPerformanceLevelLabel()
    {
        if (!$this->performance_level) return null;
        return PerformanceLevel::getLabelByLevel($this->performance_level);
    }

    /**
     * Get the points range string for the performance level (e.g. "13-15" or "Below 7").
     *
     * @return string|null
     */
    public function getPerformancePointsRange()
    {
        if (!$this->performance_level) return null;
        return PerformanceLevel::getRangeByLevel($this->performance_level);
    }

    /**
     * Full description for tooltips/helper text.
     */
    public function getPerformanceLevelDescription()
    {
        if (!$this->performance_level) return null;
        return PerformanceLevel::getDescriptionByLevel($this->performance_level);
    }

    /**
     * Color for UI badges.
     */
    public function getPerformanceLevelColor()
    {
        if (!$this->performance_level) return null;
        return PerformanceLevel::getColorByLevel($this->performance_level);
    }

    /**
     * Scope to filter by performance level
     */
    public function scopeWherePerformanceLevel($query, $level)
    {
        return $query->where('performance_level', $level);
    }

}

