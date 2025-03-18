<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clocking;
use App\Models\User;
use App\Models\Course;
use App\Http\Requests\Admin\AttendanceUpdateRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display a listing of attendance records
     */
    public function index(Request $request)
    {
        // Get all users for the filter dropdown
        $users = User::select('id', 'name')->orderBy('name')->get();
        
        // Get all courses for the filter dropdown
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        
        // Build the query with filters
        $clockings = Clocking::query()
            ->with(['user:id,name,email', 'course:id,name']) // Load course relationship
            ->when($request->user_id, function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            })
            ->when($request->date, function ($query) use ($request) {
                $query->whereDate('clock_in', $request->date);
            })
            ->when($request->course_id, function ($query) use ($request) {
                // If 'general' is selected, find records with null course_id
                if ($request->course_id === 'general') {
                    $query->whereNull('course_id');
                } else {
                    $query->where('course_id', $request->course_id);
                }
            })
            ->orderByDesc('clock_in')
            ->paginate(10);
        
        // Calculate current duration for any active sessions
        foreach ($clockings as $record) {
            if (!$record->clock_out) {
                $record->current_duration = now()->diffInMinutes($record->clock_in);
            }
            
            // Add course name for display
            $record->course_name = $record->course ? $record->course->name : null;
        }
        
        return Inertia::render('Admin/Attendance/Index', [
            'clockings' => $clockings,
            'users' => $users,
            'courses' => $courses,
            'filters' => $request->only(['user_id', 'date', 'course_id'])
        ]);
    }

    /**
     * Update the specified attendance record
     */
    public function update(AttendanceUpdateRequest $request, Clocking $attendance)
    {
        $this->attendanceService->updateAttendance($attendance, $request->validated());
        
        return redirect()->back()->with('success', 'Attendance record updated successfully');
    }

    /**
     * Delete the specified attendance record
     */
    public function destroy(Clocking $attendance)
    {
        $this->attendanceService->deleteAttendance($attendance);
        
        return redirect()->back()->with('success', 'Attendance record deleted successfully');
    }
}