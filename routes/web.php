<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ClockingController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Models\Course;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeminiController;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

// Add this route to replace the existing dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Attendance routes
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [ClockingController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/clock', [ClockingController::class, 'clockPage'])->name('attendance.clock');
    Route::post('/clock-in', [ClockingController::class, 'clockIn'])->name('clock.in');
    Route::post('/clock-out', [ClockingController::class, 'clockOut'])->name('clock.out');
});



// Admin routes with prefix to avoid conflicts with user routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin course routes
    Route::resource('courses', AdminCourseController::class);
    
    // Admin user routes - Ensure all CRUD operations are properly routed
    Route::resource('users', AdminUserController::class);
    
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
    
    // CSV Export routes
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
