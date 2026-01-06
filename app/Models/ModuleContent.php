<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleContent extends Model
{
    use HasFactory;
    protected $table = 'module_content';

    protected $fillable = [
        'module_id',
        'content_type',
        'video_id', // Links to existing Video model
        'title',
        'description',
        'file_path', // For uploaded PDFs
        'google_drive_pdf_url', // For PDF Google Drive links
        'duration', // For videos (seconds)
        'file_size', // In bytes
        'pdf_page_count', // ✅ ADD: Page count for PDFs
        'thumbnail_path',
        'order_number',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'duration' => 'integer',
        'file_size' => 'integer',
        'order_number' => 'integer',
        'pdf_page_count' => 'integer', // ✅ ADD: Cast to integer

    ];

    // Relationships
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ModuleTask::class, 'content_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserContentProgress::class, 'content_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVideos($query)
    {
        return $query->where('content_type', 'video');
    }

    public function scopePdfs($query)
    {
        return $query->where('content_type', 'pdf');
    }

    // Helper methods
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) return '0:00';

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->content_type === 'video';
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->content_type === 'pdf';
    }

    public function getContentUrlAttribute(): ?string
    {
        if ($this->is_video && $this->video) {
            return $this->video->google_drive_url;
        }

        if ($this->is_pdf) {
            return $this->google_drive_pdf_url ??
                ($this->file_path ? asset('storage/' . $this->file_path) : null);
        }

        return null;
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class, 'content_id');
    }

    // ✅ ADD: Enhanced progress methods
    public function createOrUpdateProgress(int $userId, array $data): UserContentProgress
    {
        return UserContentProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'content_id' => $this->id,
            ],
            [
                'course_online_id' => $this->module->course_online_id,
                'module_id' => $this->module_id,
                'content_type' => $this->content_type,
                'video_id' => $this->video_id,
                'playback_position' => $data['playback_position'] ?? 0, // ✅ Ensure never null
                'completion_percentage' => $data['completion_percentage'] ?? 0,
                'is_completed' => $data['is_completed'] ?? false,
                'last_accessed_at' => now(),
            ]
        );
    }

    // ✅ ADD: Session management
    public function startLearningSession(int $userId): LearningSession
    {
        // End any existing active session
        LearningSession::where('user_id', $userId)
            ->where('content_id', $this->id)
            ->whereNull('session_end')
            ->update(['session_end' => now()]);

        // Create new session
        return LearningSession::create([
            'user_id' => $userId,
            'course_online_id' => $this->module->course_online_id,
            'content_id' => $this->id,
            'session_start' => now(),
        ]);
    }

    // ✅ ADD: Analytics methods
    public function getAverageEngagementScore(): float
    {
        return $this->learningSessions()
            ->whereNotNull('attention_score')
            ->avg('attention_score') ?? 0;
    }

    public function getSuspiciousActivityCount(): int
    {
        return $this->learningSessions()
            ->where('is_suspicious_activity', true)
            ->count();
    }
    public function getPdfPageCountAttribute()
    {
        if ($this->content_type !== 'pdf') {
            return null;
        }

        return $this->attributes['pdf_page_count'] ?? null;
    }

    public function hasPdfPageCount(): bool
    {
        return $this->content_type === 'pdf' && !is_null($this->pdf_page_count);
    }

    public function getEstimatedReadingTimeAttribute(): int
    {
        if ($this->content_type !== 'pdf' || !$this->pdf_page_count) {
            return 0;
        }

        // Estimate 2 minutes per page
        return $this->pdf_page_count * 2;
    }

}
