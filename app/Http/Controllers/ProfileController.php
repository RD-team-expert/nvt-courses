<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\ManagerHierarchyService;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load([
            'department',
            'userLevel',
            'managerRoles.department',
            'directReports.managedUser.department',
            'directReports.managedUser.userLevel',
            'managers.department',
            'managers.userLevel'
        ]);

        // Get user's evaluations
        $evaluations = $user->evaluations()
            ->with('course')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'course_name' => $evaluation->course->name ?? 'Unknown Course',
                    'total_score' => $evaluation->total_score,
                    'incentive_amount' => $evaluation->incentive_amount,
                    'created_at' => $evaluation->created_at->toISOString(),
                ];
            });

        // Get user's courses
        $courses = $user->courseRegistrations()
            ->with('course')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($registration) {
                return [
                    'id' => $registration->course->id,
                    'name' => $registration->course->name,
                    'status' => $registration->status,
                    'progress' => $registration->progress ?? 0,
                    'enrolled_at' => $registration->created_at->toISOString(),
                ];
            });

        // Get direct manager using the updated service
        $managerService = new ManagerHierarchyService();
        $directManagers = $managerService->getDirectManagersForUser($user->id);
        $directManager = !empty($directManagers) ? $directManagers[0]['manager'] : null;

        return Inertia::render('User/Profile', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_code' => $user->employee_code,
                'status' => $user->status,
                'created_at' => $user->created_at->toISOString(),
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                ] : null,
                'level' => $user->userLevel ? [
                    'id' => $user->userLevel->id,
                    'code' => $user->userLevel->code,
                    'name' => $user->userLevel->name,
                    'hierarchy_level' => $user->userLevel->hierarchy_level,
                ] : null,
                'direct_manager' => $directManager ? [
                    'id' => $directManager->id,
                    'name' => $directManager->name,
                    'email' => $directManager->email,
                    'department' => $directManager->department?->name ?? 'Unknown',
                ] : null,
            ],
            'managerRoles' => $user->managerRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_type' => $role->role_type,
                    'role_display' => $role->getRoleDisplayName(),
                    'department' => $role->department->name ?? 'Unknown',
                    'authority_level' => $role->authority_level,
                    'is_primary' => $role->is_primary,
                    'is_active' => $role->isActive(),
                    'start_date' => $role->start_date?->toDateString(),
                ];
            }),
            'directReports' => $user->directReports->map(function ($role) {
                $report = $role->managedUser;
                return $report ? [
                    'id' => $report->id,
                    'name' => $report->name,
                    'email' => $report->email,
                    'level' => $report->userLevel->code ?? null,
                    'department' => $report->department->name ?? null,
                ] : null;
            })->filter(),
            'managers' => $user->managers->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'level' => $manager->userLevel->code ?? null,
                    'department' => $manager->department->name ?? null,
                ];
            }),
            'evaluations' => $evaluations,
            'courses' => $courses,
        ]);
    }
}
