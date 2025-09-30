<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Audio extends Model
{
    use HasFactory;
    protected $table = 'audios';

    protected $fillable = [
        'name',
        'description',
        'google_cloud_url',
        'duration',
        'thumbnail_url',
        'thumbnail_path',
        'is_active',
        'created_by',
        'audio_category_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
    ];



    /**
     * Get the user who created this audio
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the category for this audio
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AudioCategory::class, 'audio_category_id');
    }

    /**
     * Get all progress records for this audio
     */
    public function progress(): HasMany
    {
        return $this->hasMany(AudioProgress::class);
    }

    /**
     * Get progress for a specific user
     */
    public function progressForUser(User $user)
    {
        return $this->progress()->where('user_id', $user->id)->first();
    }

    /**
     * Scope to get only active audios
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format duration to human readable format (MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) return '00:00';

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }



    public function hasThumbnail(): bool
    {
        return !empty($this->thumbnail_path) || !empty($this->attributes['thumbnail_url']);
    }
}
