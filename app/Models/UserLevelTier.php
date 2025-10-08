<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevelTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_level_id',
        'tier_name',
        'tier_order',
        'description',
    ];

    protected $casts = [
        'tier_order' => 'integer'
    ];




    /**
     * Get the user level that owns the tier
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get incentives for this tier
     */
    public function incentives()
    {
        return $this->hasMany(Incentive::class);
    }

    public function userLevel()
    {
        return $this->belongsTo(UserLevel::class);
    }

    /**
     * Scope to get tiers ordered by tier_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('tier_order');
    }

    /**
     * Get tier display name (e.g., "Level 1 - Tier 2")
     */
    public function getDisplayNameAttribute()
    {
        return $this->userLevel->name . ' - ' . $this->tier_name;
    }
}
