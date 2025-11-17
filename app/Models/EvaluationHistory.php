<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'category_name',
        'course_type',        // NEW
        'course_online_id',   // NEW
        'type_name',
        'score',
    ];

    protected $casts = [
        'course_type' => 'string',  // NEW
        'score' => 'integer',
        'created_at' => 'datetime',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }
    /**
     * NEW: Relationship to online course
     */
    public function courseOnline()
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    /**
     * NEW: Scope for filtering by course type
     */
    public function scopeForCourseType($query, string $type)
    {
        return $query->where('course_type', $type);
    }

}
