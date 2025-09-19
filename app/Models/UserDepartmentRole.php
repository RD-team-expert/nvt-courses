<?php
// app/Models/UserDepartmentRole.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserDepartmentRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'role_type',
        'manages_user_id',
        'is_primary',
        'authority_level',
        'start_date',
        'end_date',
        'created_by',
        'notes'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'authority_level' => 'integer'
    ];

    // === RELATIONSHIPS ===

    /**
     * The manager (user who has the role)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Department where role applies
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * User being managed (if specific user management)
     */
    public function managedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manages_user_id');
    }

    /**
     * Admin who created this role assignment
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // === SCOPES ===

    /**
     * Active roles only
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>', now());
        });
    }

    /**
     * Expired roles
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<=', now());
    }

    /**
     * Primary roles only
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Filter by role type
     */
    public function scopeByRoleType($query, $roleType)
    {
        return $query->where('role_type', $roleType);
    }

    /**
     * Direct manager roles
     */
    public function scopeDirectManagers($query)
    {
        return $query->where('role_type', 'direct_manager');
    }

    /**
     * Department head roles
     */
    public function scopeDepartmentHeads($query)
    {
        return $query->where('role_type', 'department_head');
    }

    // === HELPER METHODS ===

    /**
     * Check if role is currently active
     */
    public function isActive(): bool
    {
        if (is_null($this->end_date)) {
            return true;
        }

        return $this->end_date->isFuture();
    }

    /**
     * Check if role has expired
     */
    public function hasExpired(): bool
    {
        return !is_null($this->end_date) && $this->end_date->isPast();
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role_type) {
            'direct_manager' => 'Direct Manager',
            'project_manager' => 'Project Manager',
            'department_head' => 'Department Head',
            'senior_manager' => 'Senior Manager',
            'team_lead' => 'Team Lead',
            default => ucwords(str_replace('_', ' ', $this->role_type))
        };
    }

    /**
     * Get authority level description
     */
    public function getAuthorityLevelDescription(): string
    {
        return match($this->authority_level) {
            1 => 'High Authority',
            2 => 'Medium Authority',
            3 => 'Low Authority',
            default => 'Standard Authority'
        };
    }

    /**
     * Extend role end date
     */
    public function extend(Carbon $newEndDate): bool
    {
        $this->end_date = $newEndDate;
        return $this->save();
    }

    /**
     * Terminate role (set end date to now)
     */
    public function terminate(): bool
    {
        $this->end_date = now();
        return $this->save();
    }
}
