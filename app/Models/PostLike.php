<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLike extends Model
{
    protected $fillable = [
        'podcast_post_id',
        'user_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(PodcastPost::class, 'podcast_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}