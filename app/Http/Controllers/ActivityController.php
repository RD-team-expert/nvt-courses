<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    /**
     * Display a listing of activities.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ActivityLog::class);
        
        $activities = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'action' => $activity->action,
                    'created_at' => $activity->created_at,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'avatar' => $activity->user->profile_photo_url ?? null,
                    ] : null,
                ];
            });
        
        return Inertia::render('Admin/Activities/Index', [
            'activities' => $activities,
        ]);
    }
    
    /**
     * Display the user's activity history.
     *
     * @return \Inertia\Response
     */
    public function userActivity(Request $request)
    {
        $user = $request->user();
        
        $activities = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'action' => $activity->action,
                    'created_at' => $activity->created_at,
                ];
            });
        
        return Inertia::render('User/Activities', [
            'activities' => $activities,
        ]);
    }
    
    /**
     * Display all recent activities (for both admin and regular users).
     *
     * @return \Inertia\Response
     */
    public function allActivities(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->is_admin || $user->role === 'admin';
        
        if ($isAdmin) {
            // Admin sees all activities
            $activities = ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->through(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'action' => $activity->action,
                        'created_at' => $activity->created_at,
                        'user' => $activity->user ? [
                            'id' => $activity->user->id,
                            'name' => $activity->user->name,
                            'avatar' => $activity->user->profile_photo_url ?? null,
                        ] : null,
                    ];
                });
                
            return Inertia::render('Activities/All', [
                'activities' => $activities,
                'isAdmin' => true
            ]);
        } else {
            // Regular user sees public activities and their own
            $activities = ActivityLog::where(function($query) use ($user) {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', $user->id);
                })
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->through(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'action' => $activity->action,
                        'created_at' => $activity->created_at,
                        'user' => $activity->user ? [
                            'id' => $activity->user->id,
                            'name' => $activity->user->name,
                            'avatar' => $activity->user->profile_photo_url ?? null,
                        ] : null,
                    ];
                });
                
            return Inertia::render('Activities/All', [
                'activities' => $activities,
                'isAdmin' => false
            ]);
        }
    }
}