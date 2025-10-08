<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOnline extends Model
{
    use HasFactory;
    protected $table = 'course_online';

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'estimated_duration',
        'difficulty_level',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_duration' => 'integer',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_number');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseOnlineAssignment::class);
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getTotalDurationAttribute()
    {
        return $this->modules->sum('estimated_duration');
    }

    public function getEnrollmentCountAttribute()
    {
        return $this->assignments()->count();
    }

    public function getCompletionRateAttribute()
    {
        $total = $this->assignments()->count();
        if ($total === 0) return 0;

        $completed = $this->assignments()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }
    public function analytics()
    {
        return $this->hasOne(CourseAnalytics::class);
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }

// Method to get or create analytics
    public function getAnalytics(): CourseAnalytics
    {
        // Try to get existing analytics first
        $analytics = $this->analytics()->first();

        if (!$analytics) {
            // Create new analytics record with proper course_online_id
            $analytics = CourseAnalytics::create([
                'course_online_id' => $this->id, // ✅ Make sure $this->id is not null
            ]);

            // Update analytics with calculated data
            $analytics->updateAnalytics();
        }

        return $analytics;
    }
}
