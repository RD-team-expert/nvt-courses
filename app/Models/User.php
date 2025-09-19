<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'login_token',           // ✅ Add this
        'login_token_expires_at', // ✅ Add this
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Helper to check if user is admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Relationship examples
    /**
     * The courses that the user is enrolled in.
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_registrations')
            ->withPivot(['status', 'registered_at', 'completed_at', 'rating', 'feedback'])
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Add this relationship method to your User model
    public function clockings()
    {
        return $this->hasMany(Clocking::class);
    }

    public function courseRegistrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function assignments()
    {
        return $this->hasMany(CourseAssignment::class);
    }

    /**
     * Assignments created by this user (admin)
     */
    public function createdAssignments()
    {
        return $this->hasMany(CourseAssignment::class, 'assigned_by');
    }

    public function generateLoginLink($courseId)
    {
        // Generate a secure random token
        $token = Str::random(64);

        // Set expiration (24 hours)
        $expiresAt = now()->addHours(24);

        // Store hashed token in database
        $this->update([
            'login_token' => hash('sha256', $token),
            'login_token_expires_at' => $expiresAt,
        ]);

        // ✅ Create signed URL with both user and course parameters
        return URL::temporarySignedRoute(
            'auth.token-login',
            $expiresAt,
            [
                'user' => $this->id,
                'course' => $courseId,  // ✅ Include course ID
                'token' => $token
            ]
        );
    }

    /**
     * Check if login token has expired
     */
    public function loginTokenExpired()
    {
        // If no expiration date is set, consider it expired
        if ($this->login_token_expires_at === null) {
            return true;
        }

        // If it's already a Carbon instance, use isPast()
        if ($this->login_token_expires_at instanceof \Carbon\Carbon) {
            return $this->login_token_expires_at->isPast();
        }

        // Fallback: parse string to Carbon and check
        return \Carbon\Carbon::parse($this->login_token_expires_at)->isPast();
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * User's organizational level
     */
    public function userLevel()
    {
        return $this->belongsTo(UserLevel::class);
    }

    /**
     * Manager roles this user has
     */
    public function managerRoles()
    {
        return $this->hasMany(UserDepartmentRole::class, 'user_id');
    }

    /**
     * Active manager roles only
     */
    public function activeManagerRoles()
    {
        return $this->hasMany(UserDepartmentRole::class, 'user_id')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    /**
     * Users this person manages directly
     */
    public function directReports()
    {
        return $this->hasMany(UserDepartmentRole::class, 'user_id')
            ->where('role_type', 'direct_manager')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    /**
     * Users who manage this user
     */
    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_department_roles', 'manages_user_id', 'user_id')
            ->withPivot(['role_type', 'is_primary', 'start_date', 'end_date'])
            ->wherePivotNull('end_date');
    }

    /**
     * Direct manager of this user
     */
    public function directManager()
    {
        return $this->belongsTo(User::class, 'direct_manager_id');
    }

    /**
     * Evaluation assignments for this user
     */
    public function evaluationAssignments()
    {
        return $this->hasMany(EvaluationAssignment::class);
    }

    /**
     * Evaluations created by this user
     */
    public function createdEvaluations()
    {
        return $this->hasMany(Evaluation::class, 'created_by');
    }

// Add helper methods:

    /**
     * Check if user has specific manager role
     */
    public function hasManagerRole(string $roleType, ?int $departmentId = null): bool
    {
        $query = $this->activeManagerRoles()->where('role_type', $roleType);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->exists();
    }

    /**
     * Get all users this person can manage
     */
    public function getAllManagedUsers()
    {
        return User::whereHas('managers', function ($query) {
            $query->where('user_id', $this->id)
                ->whereNull('end_date');
        });
    }

    /**
     * Check if user can manage another user
     */
    public function canManage(User $user): bool
    {
        return $this->getAllManagedUsers()->where('id', $user->id)->exists();
    }

    /**
     * Roles where this user is managed by someone
     */
    public function managedRoles()
    {
        return $this->hasMany(UserDepartmentRole::class, 'manages_user_id');
    }
}
