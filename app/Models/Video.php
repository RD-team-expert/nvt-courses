<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'google_drive_url',
        'duration',
        'thumbnail_path',
        'is_active',
        'created_by',
        'video_category_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
    ];

    /**
     * Get the user who created this video
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the category for this video
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VideoCategory::class, 'video_category_id');
    }

    /**
     * Get all progress records for this video
     */
    public function progress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    /**
     * Get progress for a specific user
     */
    public function progressForUser(User $user)
    {
        return $this->progress()->where('user_id', $user->id)->first();
    }

    /**
     * Get all bookmarks for this video
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(VideoBookmark::class);
    }

    /**
     * Scope to get only active videos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format duration to human readable format (HH:MM:SS or MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) return '00:00';

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if video has a thumbnail
     */
    public function hasThumbnail(): bool
    {
        return !empty($this->thumbnail_path) && Storage::disk('public')->exists($this->thumbnail_path);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->hasThumbnail()) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }
        return null;
    }

    public function moduleContent()
    {
        return $this->hasOne(ModuleContent::class, 'video_id');
    }

    public function courseModule()
    {
        return $this->hasOneThrough(
            CourseModule::class,
            ModuleContent::class,
            'video_id', // Foreign key on ModuleContent
            'id', // Foreign key on CourseModule
            'id', // Local key on Video
            'module_id' // Local key on ModuleContent
        );
    }

    public function courseOnline()
    {
        return $this->hasOneThrough(
            CourseOnline::class,
            CourseModule::class,
            'id', // Foreign key on CourseModule (through moduleContent->module)
            'id', // Foreign key on CourseOnline
            'id', // Local key on Video
            'course_online_id' // Local key on CourseModule
        );
    }

// Enhanced progress relationship for course context
    public function courseProgress(): HasMany
    {
        return $this->hasMany(UserContentProgress::class, 'video_id');
    }
}
