<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EvaluationAssignmentController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\EvaluationNotificationController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\OrganizationalController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResendLoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserDepartmentRoleController;
use App\Http\Controllers\Admin\UserEvaluationController;
use App\Http\Controllers\Admin\UserLevelController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\AuthVaiEmailController;
use App\Http\Controllers\ClockingController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserTeamController;
use App\Models\Course;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

// Add this route to replace the existing dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/login/token/{user}/{course}', [AuthVaiEmailController::class, 'tokenLogin'])
    ->name('auth.token-login')
    ->middleware('signed');
// Attendance routes
Route::middleware(['auth'])->group(function () {
    Route::post('/courses/{course}/rating', [CourseController::class, 'submitRating'])->name('courses.submitRating');
    Route::get('/attendance', [ClockingController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/clock', [ClockingController::class, 'clockPage'])->name('attendance.clock');
    Route::post('/clock-in', [ClockingController::class, 'clockIn'])->name('clock.in');
    Route::post('/clock-out', [ClockingController::class, 'clockOut'])->name('clock.out');
    Route::get('my-assignments', [App\Http\Controllers\AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'store'])->name('quizzes.store');
    Route::get('quizzes', [\App\Http\Controllers\QuizController::class, 'index'])->name('quizzes.index');
    Route::get('quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'show'])->name('quizzes.show');
    Route::get('quiz-attempts/{attempt}/results', [\App\Http\Controllers\QuizController::class, 'results'])->name('quiz-attempts.results');
    Route::post('/audio/{audio}/progress', [AudioController::class, 'updateProgress'])->name('audio.progress');
    Route::post('/audio/{audio}/complete', [AudioController::class, 'markCompleted'])->name('audio.complete');
    Route::get('/audio', [AudioController::class, 'index'])->name('audio.index');
    Route::get('/audio/{audio}', [AudioController::class, 'show'])->name('audio.show');


});



// Admin routes with prefix to avoid conflicts with user routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {


    Route::get('/users/assignment', [UserController::class, 'assignment'])->name('users.assignment');
    Route::post('/users/bulk-assign', [UserController::class, 'bulkAssign'])->name('users.bulk-assign');
    Route::post('/users/{user}/assign-level', [UserController::class, 'assignLevel'])->name('users.assign-level');
    Route::post('/users/{user}/assign-department', [UserController::class, 'assignDepartment'])->name('users.assign-department');

    Route::resource('audio-categories', App\Http\Controllers\Admin\AudioCategoryController::class);
    Route::post('audio-categories/{audioCategory}/toggle-active', [App\Http\Controllers\Admin\AudioCategoryController::class, 'toggleActive'])
        ->name('audio-categories.toggle-active');

    Route::resource('audio', App\Http\Controllers\Admin\AudioController::class);
    Route::post('/audio/{audio}/toggle-active', [App\Http\Controllers\Admin\AudioController::class, 'toggleActive'])->name('audio.toggle-active');


    // Admin course routes
    Route::resource('courses', AdminCourseController::class);

    // Admin user routes - Ensure all CRUD operations are properly routed
    Route::resource('users', AdminUserController::class);

    Route::get('/resend-login-links', [ResendLoginController::class, 'index'])->name('resend-login-links.index');
    Route::post('/resend-login-links/{user}', [ResendLoginController::class, 'resend'])->name('resend-login-links.resend');
    Route::post('/resend-login-links/bulk', [ResendLoginController::class, 'bulkResend'])->name('resend-login-links.bulk');

    // Assignment routes
    Route::get('assignments', [App\Http\Controllers\Admin\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/create', [App\Http\Controllers\Admin\AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('assignments', [App\Http\Controllers\Admin\AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('assignments/{assignment}', [App\Http\Controllers\Admin\AssignmentController::class, 'show'])->name('assignments.show');
    Route::get('assignments/{assignment}/edit', [App\Http\Controllers\Admin\AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('assignments/{assignment}', [App\Http\Controllers\Admin\AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('assignments/{assignment}', [App\Http\Controllers\Admin\AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::post('assignments/bulk', [App\Http\Controllers\Admin\AssignmentController::class, 'bulkAssign'])->name('assignments.bulk');
    Route::get('quiz-attempts', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'index'])->name('quiz-attempts.index');
    Route::get('quiz-attempts/{attempt}', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'show'])->name('quiz-attempts.show');
    Route::put('quiz-attempts/{attempt}', [\App\Http\Controllers\Admin\QuizAttemptController::class, 'update'])->name('quiz-attempts.update');


    Route::resource('quizzes', \App\Http\Controllers\Admin\QuizController::class);




    // Admin attendance routes
    Route::get('attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');

    // Instruction management routes
    Route::resource('instructions', \App\Http\Controllers\Admin\InstructionController::class);
});
Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('admin.attendance.update');
Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('admin.attendance.destroy');

// User course routes
Route::middleware('auth')->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::post('/courses/{course}/mark-completed', [CourseController::class, 'markCompleted'])->name('courses.markCompleted');

    // Temporary debug route
    Route::get('/debug/course/{course}', function (Course $course) {
        return [
            'course' => $course,
            'route_works' => true
        ];
    });
});


// Add these routes to your web.php file
Route::middleware(['auth'])->group(function () {



    Route::get('/profile', [ProfileController::class, 'index'])
        ->name('user.profile.index');

    Route::get('/evaluations', [App\Http\Controllers\User\UserEvaluationController::class, 'index'])
        ->name('user.evaluations.index');

    Route::get('/evaluations/{id}', [App\Http\Controllers\User\UserEvaluationController::class, 'show'])
        ->name('user.evaluations.show');

    Route::get('/my-team', [UserTeamController::class, 'index'])
        ->name('user.team.index');

    // Course completion routes
    Route::get('/courses/{id}/completion', [CourseController::class, 'showCompletionPage'])->name('courses.completion');
    Route::post('/courses/{id}/rating', [CourseController::class, 'submitRating'])->name('courses.rating.submit');
});
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Reports routes
    Route::get('/reports', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/course-registrations', [App\Http\Controllers\Admin\ReportController::class, 'courseRegistrations'])->name('reports.course-registrations');
    Route::get('/reports/attendance', [App\Http\Controllers\Admin\ReportController::class, 'attendance'])->name('reports.attendance');
    Route::get('/reports/course-completion', [App\Http\Controllers\Admin\ReportController::class, 'courseCompletion'])->name('reports.course-completion');
    Route::get('/reports/export-monthly-kpi-csv', [App\Http\Controllers\Admin\ReportController::class, 'exportMonthlyKpiCsv'])
        ->name('reports.export-monthly-kpi-csv');
    Route::get('/reports/monthly-kpi-screenshot', [App\Http\Controllers\Admin\ReportController::class, 'monthlyKpiScreenshot'])
        ->name('reports.monthly-kpi-screenshot');

    // CSV Export routes
    Route::get('/reports/quiz-attempts', [ReportController::class, 'quizAttempts'])->name('reports.quiz-attempts');
    Route::get('/reports/quiz-attempts/export', [ReportController::class, 'exportQuizAttempts'])->name('reports.export.quiz-attempts');
    Route::get('/reports/export/course-registrations', [App\Http\Controllers\Admin\ReportController::class, 'exportCourseRegistrations'])->name('reports.export.course-registrations');
    Route::get('/reports/export/attendance', [App\Http\Controllers\Admin\ReportController::class, 'exportAttendance'])->name('reports.export.attendance');
    Route::get('/reports/export/course-completion', [App\Http\Controllers\Admin\ReportController::class, 'exportCourseCompletion'])->name('reports.export.course-completion');
});

 // Activity routes
 Route::middleware(['auth', 'verified'])->group(function () {
    // All activities view (for both admin and regular users)
    Route::get('/activities', [ActivityController::class, 'allActivities'])
        ->name('activities.all');

    // Admin activity routes
    Route::get('/admin/activity', [ActivityController::class, 'index'])
        ->middleware('can:viewAny,App\Models\ActivityLog')
        ->name('admin.activity.index');

    // User activity routes
    Route::get('/activity', [ActivityController::class, 'userActivity'])
        ->name('user.activity');
});


// Gemini API routes
Route::middleware(['auth'])->group(function () {
    Route::get('/gemini', [GeminiController::class, 'index'])->name('gemini.index');
    // Add these routes if they don't exist
    Route::post('/gemini/generate', [GeminiController::class, 'generate'])->name('gemini.generate');
    // Add this with your other Gemini routes
    Route::get('/gemini/instructions', [App\Http\Controllers\GeminiController::class, 'getInstructions'])->name('gemini.instructions');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/departments/{department}/employees', [UserDepartmentRoleController::class, 'getEmployees'])->name('api.departments.employees');

    // ==========================================
    // ORGANIZATIONAL DASHBOARD
    // ==========================================
    Route::get('/organizational', [OrganizationalController::class, 'index'])->name('organizational.index');
    Route::get('/organizational/overview', [OrganizationalController::class, 'overview'])->name('organizational.overview');


    // Evaluation Configuration Routes
    Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');

    Route::post('/evaluations/notifications/preview', [EvaluationNotificationController::class, 'previewNotification'])
        ->name('evaluations.notifications.preview');

    Route::post('/evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::put('/evaluations/{evaluationConfig}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::delete('/evaluations/{evaluationConfig}', [EvaluationController::class, 'destroy'])->name('evaluations.destroy');
    Route::post('/evaluations/{evaluationConfig}/types', [EvaluationController::class, 'configureTypes'])->name('evaluations.types.store');
    Route::post('/evaluations/set-total-score', [EvaluationController::class, 'setTotalScore'])->name('evaluations.set-total-score');
    Route::post('/evaluations/set-incentives', [EvaluationController::class, 'setIncentives'])->name('evaluations.set-incentives');
    Route::get('/evaluations/users-by-department', [UserEvaluationController::class, 'getUsersByDepartment'])
        ->name('evaluations.users-by-department');
    Route::get('/evaluations/user-courses', [UserEvaluationController::class, 'getUserCourses'])
        ->name('evaluations.user-courses');
    Route::post('/evaluations/{evaluationConfig}/types', [EvaluationController::class, 'configureTypes'])->name('evaluations.types.store');
    Route::delete('/evaluations/types/{evaluationType}', [EvaluationController::class, 'destroyType'])->name('evaluations.types.destroy');
    // User Evaluation Routes
    Route::get('/evaluations/user-evaluation', [UserEvaluationController::class, 'index'])->name('evaluations.user-evaluation');
    Route::get('/evaluations/user-evaluation/{userId}', [UserEvaluationController::class, 'show'])->name('evaluations.user-evaluation.show');
    Route::post('/evaluations/user-evaluation', [UserEvaluationController::class, 'store'])->name('evaluations.user-evaluation.store');
    Route::post('/evaluations/user-evaluation/bulk', [UserEvaluationController::class, 'bulkStore'])->name('evaluations.user-evaluation.bulk-store');
    Route::post('/evaluations/user-evaluation/filter', [UserEvaluationController::class, 'filterUsers'])->name('evaluations.user-evaluation.filter');
    Route::get('/evaluations/history/export-summary', [HistoryController::class, 'exportSummary'])->name('evaluations.history.export-summary');

    // Evaluation History Routes
    Route::get('/evaluations/history', [HistoryController::class, 'index'])->name('evaluations.history');
    Route::get('/evaluations/history/export', [HistoryController::class, 'export'])->name('evaluations.history.export');
    Route::get('/evaluations/history/{evaluationId}', [HistoryController::class, 'details'])->name('evaluations.history.details');


    // Main notification dashboard
    Route::get('/evaluations/notifications', [EvaluationNotificationController::class, 'index'])
        ->name('evaluations.notifications');

    // Filter employees (Inertia POST)
    Route::post('/evaluations/notifications/filter', [EvaluationNotificationController::class, 'filterEmployees'])
        ->name('evaluations.notifications.filter');

    // Preview notification (Inertia POST)

    // Send notifications (Inertia POST)
    Route::post('/evaluations/notifications/send', [EvaluationNotificationController::class, 'sendNotifications'])
        ->name('evaluations.notifications.send');

    // Notification history
    Route::get('/evaluations/notifications/history', [EvaluationNotificationController::class, 'history'])
        ->name('evaluations.notifications.history');

    // View notification details
    Route::get('/evaluations/notifications/{id}', [EvaluationNotificationController::class, 'show'])
        ->name('evaluations.notifications.show');


    Route::get('/reports/monthly-kpi', [ReportController::class, 'monthlyKpiDashboard'])
        ->name('reports.monthly-kpi');

    // AJAX Endpoints for KPI Dashboard
    Route::get('/reports/kpi-data', [ReportController::class, 'getKpiData'])
        ->name('reports.kpi-data');

    Route::get('/reports/kpi-comparison', [ReportController::class, 'getKpiComparison'])
        ->name('reports.kpi-comparison');

    Route::get('/reports/kpi-section/{section}', [ReportController::class, 'getKpiSection'])
        ->name('reports.kpi-section');

    Route::get('/reports/kpi-trends', [ReportController::class, 'getKpiTrends'])
        ->name('reports.kpi-trends');

    Route::get('/reports/live-kpi-stats', [ReportController::class, 'getLiveKpiStats'])
        ->name('reports.live-kpi-stats');

    // Export Endpoints
    Route::post('/reports/export-monthly-kpi', [ReportController::class, 'exportMonthlyKpiReport'])
        ->name('reports.export-monthly-kpi');




    // Notification Routes

//    Route::get('/api/departments/{department}/employees', [DepartmentController::class, 'getEmployees'])->name('api.departments.employees');

    // ==========================================
    // DEPARTMENTS MANAGEMENT
    // ==========================================
    Route::resource('departments', DepartmentController::class);

    // Additional department routes
    Route::get('/departments/{department}/managers', [DepartmentController::class, 'getManagerCandidates'])->name('departments.manager-candidates');
    Route::post('/departments/{department}/assign-manager', [DepartmentController::class, 'assignManager'])->name('departments.assign-manager');
    Route::delete('/departments/{department}/remove-manager/{role}', [DepartmentController::class, 'removeManager'])->name('departments.remove-manager');
    Route::get('/departments/{department}/hierarchy', [DepartmentController::class, 'getHierarchy'])->name('departments.hierarchy');

    // ==========================================
    // USER LEVELS MANAGEMENT (L1, L2, L3, L4)
    // ==========================================
    Route::resource('user-levels', UserLevelController::class);

    Route::get('/users/available', [UserController::class, 'getAvailableUsers'])->name('users.available');


    // Additional user level routes
    Route::post('/user-levels/bulk-assign', [UserLevelController::class, 'bulkAssign'])->name('user-levels.bulk-assign');
    Route::get('/user-levels/{userLevel}/users', [UserLevelController::class, 'getUsers'])->name('user-levels.users');

    // ==========================================
    // MANAGER ROLES ASSIGNMENT
    // ==========================================

    Route::get('/admin/manager-roles/{userDepartmentRole}/edit', [UserDepartmentRoleController::class, 'edit'])
        ->name('admin.manager-roles.edit');

    Route::get('/manager-roles', [UserDepartmentRoleController::class, 'index'])->name('manager-roles.index');
    Route::get('/manager-roles/create', [UserDepartmentRoleController::class, 'create'])->name('manager-roles.create');
    Route::post('/manager-roles', [UserDepartmentRoleController::class, 'store'])->name('manager-roles.store');
    Route::get('/manager-roles/{userDepartmentRole}', [UserDepartmentRoleController::class, 'show'])->name('manager-roles.show');
    Route::get('/manager-roles/{userDepartmentRole}/edit', [UserDepartmentRoleController::class, 'edit'])->name('manager-roles.edit');
    Route::put('/manager-roles/{userDepartmentRole}', [UserDepartmentRoleController::class, 'update'])->name('manager-roles.update');
    Route::delete('/manager-roles/{userDepartmentRole}', [UserDepartmentRoleController::class, 'destroy'])->name('manager-roles.destroy');
    // Additional manager role routes
    Route::get('/manager-roles/matrix/view', [UserDepartmentRoleController::class, 'matrix'])->name('manager-roles.matrix');
    Route::get('/departments/{department}/employees', [UserDepartmentRoleController::class, 'getDepartmentEmployees'])->name('departments.employees');
    Route::post('/manager-roles/{userDepartmentRole}/extend', [UserDepartmentRoleController::class, 'extend'])->name('manager-roles.extend');
    Route::post('/manager-roles/{userDepartmentRole}/terminate', [UserDepartmentRoleController::class, 'terminate'])->name('manager-roles.terminate');
    Route::get('/manager-roles/user/{user}/roles', [UserDepartmentRoleController::class, 'getUserRoles'])->name('manager-roles.user-roles');

    // ==========================================
    // USER MANAGEMENT (Enhanced)
    // ==========================================
    // User assignment routes
    Route::get('/users/{user}/assign', [UserController::class, 'assignForm'])->name('users.assign-form');
    Route::post('/users/{user}/assign-department', [UserController::class, 'assignDepartment'])->name('users.assign-department');
    Route::post('/users/{user}/assign-level', [UserController::class, 'assignLevel'])->name('users.assign-level');
    Route::post('/users/{user}/assign-manager', [UserController::class, 'assignManager'])->name('users.assign-manager');

    // Bulk user operations
    Route::post('/users/bulk-assign-department', [UserController::class, 'bulkAssignDepartment'])->name('users.bulk-assign-department');
    Route::post('/users/bulk-assign-level', [UserController::class, 'bulkAssignLevel'])->name('users.bulk-assign-level');
    Route::get('/users/import', [UserController::class, 'importForm'])->name('users.import');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import.process');
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('/user-levels/remove-user', [UserLevelController::class, 'removeUserFromLevel'])->name('user-levels.remove-user');

    // User organizational info
    Route::get('/users/{user}/organizational', [UserController::class, 'organizationalInfo'])->name('users.organizational');
    Route::get('/users/{user}/reporting-chain', [UserController::class, 'reportingChain'])->name('users.reporting-chain');
    Route::get('/users/{user}/direct-reports', [UserController::class, 'directReports'])->name('users.direct-reports');

    // ==========================================
    // EVALUATIONS MANAGEMENT
    // ==========================================

    // ==========================================
    // REPORTS & ANALYTICS
    // ==========================================
    Route::prefix('reports')->name('reports.')->group(function () {
        // Organizational reports
        Route::get('/organizational', [OrganizationalController::class, 'reports'])->name('organizational');
        Route::get('/department-structure', [OrganizationalController::class, 'departmentStructure'])->name('department-structure');
        Route::get('/management-hierarchy', [OrganizationalController::class, 'managementHierarchy'])->name('management-hierarchy');
        Route::get('/user-assignments', [OrganizationalController::class, 'userAssignments'])->name('user-assignments');

        // Evaluation reports

    });
});

// ==========================================
// USER-FACING ROUTES (All authenticated users)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // User evaluation routes


    // User organizational info
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/organizational-info', [UserController::class, 'myOrganizationalInfo'])->name('organizational-info');
        Route::get('/manager', [UserController::class, 'myManager'])->name('manager');
        Route::get('/team', [UserController::class, 'myTeam'])->name('team');
        Route::get('/department', [UserController::class, 'myDepartment'])->name('department');
    });
});

// ==========================================
// MANAGER-SPECIFIC ROUTES
// ==========================================
Route::middleware(['auth'])->prefix('manager')->name('manager.')->group(function () {

    // Manager dashboard
    Route::get('/dashboard', [UserDepartmentRoleController::class, 'managerDashboard'])->name('dashboard');

    // Team management
    Route::get('/team', [UserDepartmentRoleController::class, 'myTeam'])->name('team');
    Route::get('/team/{user}', [UserDepartmentRoleController::class, 'teamMember'])->name('team.member');

    // Team reports
    Route::get('/reports/team-performance', [UserDepartmentRoleController::class, 'teamPerformance'])->name('reports.team-performance');
});

// ==========================================
// API ROUTES FOR AJAX CALLS
// ==========================================
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {

    // Department API
    Route::get('/departments/search', [DepartmentController::class, 'search'])->name('departments.search');
    Route::get('/departments/{department}/children', [DepartmentController::class, 'getChildren'])->name('departments.children');
    Route::get('/departments/hierarchy', [DepartmentController::class, 'getFullHierarchy'])->name('departments.full-hierarchy');

    // User API
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/users/by-department/{department}', [UserController::class, 'getByDepartment'])->name('users.by-department');
    Route::get('/users/managers', [UserController::class, 'getManagers'])->name('users.managers');
    Route::get('/users/{user}/manager-chain', [UserController::class, 'getManagerChain'])->name('users.manager-chain');

    // Manager role API
    Route::get('/manager-roles/by-department/{department}', [UserDepartmentRoleController::class, 'getByDepartment'])->name('manager-roles.by-department');
    Route::get('/manager-roles/check-conflict', [UserDepartmentRoleController::class, 'checkConflict'])->name('manager-roles.check-conflict');

    // Evaluation API

});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
