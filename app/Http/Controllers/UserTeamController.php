<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDepartmentRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserTeamController extends Controller
{
    /**
     * Display the user's team management dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Check if user is a manager - SIMPLIFIED CHECK
        $hasManagerRole = UserDepartmentRole::where('user_id', $user->id)
            ->where('role_type', 'LIKE', '%manager%')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->exists();

        if (!$hasManagerRole) {
            abort(403, 'You do not have team management permissions.');
        }

        // Get direct reports - FIXED QUERY
        $directReportIds = UserDepartmentRole::where('user_id', $user->id)
            ->where('role_type', 'direct_manager')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->pluck('manages_user_id')
            ->filter(); // Remove null values

        $directReports = User::whereIn('id', $directReportIds)
            ->with([
                'department',
                'level',
                'courseRegistrations.course',
                'evaluations' => function ($query) {
                    $query->latest()->limit(3);
                }
            ])
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'email' => $report->email,
                    'employee_code' => $report->employee_code,
                    'status' => $report->status ?? 'active',
                    'department' => $report->department ? [
                        'id' => $report->department->id,
                        'name' => $report->department->name,
                        'code' => $report->department->code,
                    ] : null,
                    'level' => $report->level ? [
                        'id' => $report->level->id,
                        'code' => $report->level->code,
                        'name' => $report->level->name,
                    ] : null,
                    'active_courses' => $report->courseRegistrations
                        ->where('status', 'in_progress')
                        ->count(),
                    'completed_courses' => $report->courseRegistrations
                        ->where('status', 'completed')
                        ->count(),
                    'latest_evaluation' => $report->evaluations->first() ? [
                        'id' => $report->evaluations->first()->id,
                        'total_score' => $report->evaluations->first()->total_score,
                        'incentive_amount' => $report->evaluations->first()->incentive_amount,
                        'created_at' => $report->evaluations->first()->created_at->toISOString(),
                    ] : null,
                    'created_at' => $report->created_at->toISOString(),
                ];
            });

        // Get manager roles for context - FIXED
        $managerRoles = UserDepartmentRole::where('user_id', $user->id)
            ->with('department')
            ->where('role_type', 'LIKE', '%manager%')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_type' => $role->role_type,
                    'role_display' => $role->role_display ?? ucwords(str_replace('_', ' ', $role->role_type)),
                    'department' => $role->department->name ?? 'Unknown',
                    'authority_level' => $role->authority_level ?? 1,
                    'is_primary' => $role->is_primary ?? false,
                ];
            });

        return Inertia::render('User/Team/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'managerRoles' => $managerRoles,
            'directReports' => $directReports,
            'stats' => [
                'total_reports' => $directReports->count(),
                'active_reports' => $directReports->where('status', 'active')->count(),
                'departments' => $directReports->pluck('department.name')->filter()->unique()->count(),
                'avg_course_completion' => $directReports->avg(function ($report) {
                    $total = $report['active_courses'] + $report['completed_courses'];
                    return $total > 0 ? ($report['completed_courses'] / $total) * 100 : 0;
                }),
            ]
        ]);
    }
    /**
     * Show detailed view of a team member
     */
    public function show($id)
    {
        $user = Auth::user();

        // Get the team member and verify they report to current user
        $teamMember = $user->directReports()
            ->with([
                'department',
                'level',
                'courseRegistrations.course',
                'evaluations.course',
                'managers'
            ])
            ->findOrFail($id);

        return Inertia::render('User/Team/Show', [
            'teamMember' => [
                'id' => $teamMember->id,
                'name' => $teamMember->name,
                'email' => $teamMember->email,
                'employee_code' => $teamMember->employee_code,
                'status' => $teamMember->status,
                'department' => $teamMember->department ? [
                    'id' => $teamMember->department->id,
                    'name' => $teamMember->department->name,
                    'code' => $teamMember->department->code,
                ] : null,
                'level' => $teamMember->level ? [
                    'id' => $teamMember->level->id,
                    'code' => $teamMember->level->code,
                    'name' => $teamMember->level->name,
                ] : null,
                'created_at' => $teamMember->created_at->toISOString(),
            ],
            'courses' => $teamMember->courseRegistrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'course' => [
                        'id' => $registration->course->id,
                        'name' => $registration->course->name,
                    ],
                    'status' => $registration->status,
                    'progress' => $registration->progress ?? 0,
                    'registered_at' => $registration->created_at->toISOString(),
                    'completed_at' => $registration->completed_at?->toISOString(),
                ];
            }),
            'evaluations' => $teamMember->evaluations->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'course_name' => $evaluation->course->name ?? 'Unknown Course',
                    'total_score' => $evaluation->total_score,
                    'incentive_amount' => $evaluation->incentive_amount,
                    'notes' => $evaluation->notes,
                    'created_at' => $evaluation->created_at->toISOString(),
                ];
            }),
            'manager' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
