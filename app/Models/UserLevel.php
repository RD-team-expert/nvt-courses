<?php
// app/Models/UserLevel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'hierarchy_level',
        'description',
        'can_manage_levels'
    ];

    protected $casts = [
        'can_manage_levels' => 'array',
        'hierarchy_level' => 'integer'
    ];

    // === RELATIONSHIPS ===

    /**
     * Users with this level
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // === SCOPES ===

    /**
     * Order by hierarchy level
     */
    public function scopeByHierarchy($query, $direction = 'asc')
    {
        return $query->orderBy('hierarchy_level', $direction);
    }

    // === HELPER METHODS ===

    /**
     * Check if this level can manage another level
     */
    public function canManage(UserLevel $level): bool
    {
        return in_array($level->code, $this->can_manage_levels ?? []);
    }

    /**
     * Check if this level can manage a user
     */
    public function canManageUser(User $user): bool
    {
        return $user->userLevel && $this->canManage($user->userLevel);
    }

    /**
     * Get all levels this level can manage
     */
    public function getManageableLevels()
    {
        if (empty($this->can_manage_levels)) {
            return collect();
        }

        return UserLevel::whereIn('code', $this->can_manage_levels)->get();
    }

    /**
     * Check if this is a management level (can manage others)
     */
    public function isManagementLevel(): bool
    {
        return !empty($this->can_manage_levels);
    }

    public function tiers()
    {
        return $this->hasMany(UserLevelTier::class)->orderBy('tier_order');
    }


}
