<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'department_id',
        'total_score',
        'incentive_amount',
    ];

    protected $casts = [
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


}

