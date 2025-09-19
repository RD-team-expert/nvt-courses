<?php
// app/Models/Department.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'department_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // === RELATIONSHIPS ===

    /**
     * Parent department relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Child departments relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * All descendant departments (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Users directly assigned to this department
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Manager roles in this department
     */
    public function managerRoles(): HasMany
    {
        return $this->hasMany(UserDepartmentRole::class);
    }

    /**
     * Active manager roles only
     */
    public function activeManagerRoles(): HasMany
    {
        return $this->hasMany(UserDepartmentRole::class)
            ->whereNull('end_date')
            ->orWhere('end_date', '>', now());
    }

    /**
     * Managers of this department (all role types)
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_department_roles')
            ->withPivot(['role_type', 'is_primary', 'start_date', 'end_date'])
            ->wherePivotNull('end_date')
            ->orWherePivot('end_date', '>', now());
    }

    /**
     * Department heads only
     */
    public function departmentHeads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_department_roles')
            ->withPivot(['is_primary', 'start_date', 'end_date'])
            ->wherePivot('role_type', 'department_head')
            ->wherePivotNull('end_date');
    }

    /**
     * Evaluations specific to this department
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    // === SCOPES ===

    /**
     * Active departments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Top-level departments (no parent)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    // === HELPER METHODS ===

    /**
     * Get full department hierarchy path
     */
    public function getHierarchyPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Check if department is a child of another department
     */
    public function isChildOf(Department $department): bool
    {
        $parent = $this->parent;

        while ($parent) {
            if ($parent->id === $department->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Get all users in this department and sub-departments
     */
    public function getAllUsers()
    {
        $departmentIds = $this->descendants->pluck('id')->prepend($this->id);
        return User::whereIn('department_id', $departmentIds);
    }
}
