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
        'content_category_id', // ✅ ADD THIS
        'thumbnail_path',
        'is_active',
        'created_by',
        'video_category_id',

        // NEW FIELDS FOR DUAL STORAGE
        'storage_type',
        'file_path',
        'file_size',
        'mime_type',
        'duration_seconds',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
        'file_size' => 'integer',       // NEW
        'duration_seconds' => 'integer', // NEW
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
        return $this->belongsTo(VideoCategory::class, 'content_category_id', 'id');
        //                       Model name           Foreign key         Owner key
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
    // ============================================
    // NEW METHODS FOR DUAL STORAGE SUPPORT
    // ============================================

    /**
     * Check if video is stored on Google Drive
     */
    public function isGoogleDrive(): bool
    {
        return $this->storage_type === 'google_drive';
    }

    /**
     * Check if video is stored locally
     */
    public function isLocal(): bool
    {
        return $this->storage_type === 'local';
    }

    /**
     * Get formatted file size (e.g., "125.5 MB")
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Delete physical file if local storage
     */
    public function deleteStoredFile(): bool
    {
        if ($this->isLocal() && $this->file_path) {
            if (Storage::disk('videos')->exists($this->file_path)) {
                return Storage::disk('videos')->delete($this->file_path);
            }
        }
        return true;
    }

    /**
     * Get storage type label for display
     */
    public function getStorageTypeLabel(): string
    {
        return match($this->storage_type) {
            'google_drive' => 'Google Drive',
            'local' => 'Local Storage',
            default => 'Unknown'
        };
    }
}
