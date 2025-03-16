<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->is_admin || $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ActivityLog  $activityLog
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ActivityLog $activityLog)
    {
        return $user->is_admin || $user->role === 'admin' || $user->id === $activityLog->user_id;
    }
}