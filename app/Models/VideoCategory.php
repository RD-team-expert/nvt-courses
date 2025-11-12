<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VideoCategory extends Model
{
    use HasFactory;

    protected $table = 'content_categories'; // ✅ Already fixed

    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all videos in this category
     * ✅ FIXED: Use correct foreign key
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'content_category_id');
    }

    /**
     * Get active videos in this category
     * ✅ FIXED: Use correct foreign key
     */
    public function activeVideos(): HasMany
    {
        return $this->hasMany(Video::class, 'content_category_id')
            ->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
