<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClockInRequest;
use App\Http\Requests\ClockOutRequest;
use App\Services\ClockingService;
use App\Models\Clocking;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClockingController extends Controller
{
    protected $clockingService;

    public function __construct(ClockingService $clockingService)
    {
        $this->clockingService = $clockingService;
    }

    /**
     * Show a list of the current user's clocking records
     */
    public function index(Request $request)
    {
        $clockings = Clocking::where('user_id', auth()->id())
            ->with('course') // Load the course relationship
            ->orderByDesc('clock_in')
            ->paginate(10); // Adjust the number as needed

        // Calculate current duration for any active sessions and add course name
        foreach ($clockings as $record) {
            if (!$record->clock_out) {
                $record->current_duration = now()->diffInMinutes($record->clock_in);
            }
            
            // Add course name for display
            $record->course_name = $record->course ? $record->course->name : null;
        }

        return Inertia::render('Attendance/Table', [
            'clockings' => $clockings,
        ]);
    }

    /**
     * Show the clock in/out interface
     */
    public function clockPage(Request $request)
    {
        // Get the active session (if any)
        $activeSession = Clocking::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->first();
            
        // Get paginated clockings with duration calculation
        $clockings = Clocking::where('user_id', auth()->id())
            ->orderByDesc('clock_in')
            ->paginate(4); // 4 items per page
        
        // Calculate duration for active session if it exists
        if ($activeSession) {
            // For active sessions, calculate duration from clock_in until now
            $activeSession->current_duration = now()->diffInMinutes($activeSession->clock_in);
        }

        // Calculate current duration for any active sessions in the paginated results
        foreach ($clockings as $record) {
            if (!$record->clock_out) {
                $record->current_duration = now()->diffInMinutes($record->clock_in);
            }
        }

        // Get user's enrolled courses for the clock-in modal
        // First try course_registrations table
        $userCourses = Course::join('course_registrations', 'courses.id', '=', 'course_registrations.course_id')
            ->where('course_registrations.user_id', auth()->id())
            ->select('courses.id', 'courses.name')
            ->distinct() // Ensure we don't get duplicates
            ->get();

        // If no courses found in course_registrations, try course_user table
        if ($userCourses->isEmpty()) {
            $userCourses = Course::join('course_user', 'courses.id', '=', 'course_user.course_id')
                ->where('course_user.user_id', auth()->id())
                ->select('courses.id', 'courses.name')
                ->distinct()
                ->get();
        }

        return Inertia::render('Attendance/Clock', [
            'clockings' => $clockings,
            'activeSession' => $activeSession,
            'userCourses' => $userCourses,
        ]);
    }

    /**
     * Clock In Action
     */
    public function clockIn(ClockInRequest $request)
    {
        try {
            $clocking = $this->clockingService->clockIn(
                course_id: $request->input('course_id')
            );

            return redirect()->back()->with('success', 'You have clocked in successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Clock Out Action
     */
    public function clockOut(ClockOutRequest $request)
    {
        try {
            $clocking = $this->clockingService->clockOut(
                rating: $request->input('rating'),
                comment: $request->input('comment')
            );

            return redirect()->back()->with('success', 'You have clocked out successfully! Your session lasted ' . $this->formatDuration($clocking->duration_in_minutes) . '.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Format duration in minutes to a human-readable string
     */
    private function formatDuration($minutes)
    {
        if (!$minutes) return '0 minutes';
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        $result = [];
        if ($hours > 0) {
            $result[] = $hours . ' ' . ($hours == 1 ? 'hour' : 'hours');
        }
        if ($mins > 0 || empty($result)) {
            $result[] = $mins . ' ' . ($mins == 1 ? 'minute' : 'minutes');
        }
        
        return implode(' and ', $result);
    }
}
