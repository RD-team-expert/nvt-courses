<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CourseCompletionNotification;
use App\Mail\CourseCreatedNotification;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAvailability;
use App\Models\User;
use App\Mail\CoursePublicAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CourseController extends Controller
{
    /**
     * Display a listing of courses
     */
    public function index()
    {
        $courses = Course::with(['availabilities', 'registrations'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'start_date' => $course->start_date,
                    'end_date' => $course->end_date,
                    'status' => $course->status,
                    'privacy' => $course->privacy,
                    'level' => $course->level,
                    'duration' => $course->duration,
                    'image_path' => $course->image_path,
                    'enrolled_count' => $course->total_enrollment,
                    'availabilities_count' => $course->availabilities->count(),
                    'available_spots' => $course->availabilities->sum('capacity') - $course->total_enrollment
                ];
            });

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses
        ]);
    }

    /**
     * Show the form for creating a new course
     */
    public function create()
    {
        return Inertia::render('Admin/Courses/Create');
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'privacy' => 'required|in:public,private',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'duration' => 'nullable|numeric|min:0.1',
            // Course availabilities validation
            'availabilities' => 'required|array|min:1|max:5',
            'availabilities.*.start_date' => 'required|date|after:now',
            'availabilities.*.end_date' => 'required|date|after:availabilities.*.start_date',
            'availabilities.*.capacity' => 'required|integer|min:1|max:1000', // Seats Available
            'availabilities.*.sessions' => 'nullable|integer|min:1|max:100',   // Sessions
            'availabilities.*.notes' => 'nullable|string|max:500'
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('courses', 'public');
        }

        // Format dates
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date'])->format('Y-m-d') : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date'])->format('Y-m-d') : null;

        // Create course
        $course = Course::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'privacy' => $validated['privacy'],
            'level' => $validated['level'],
            'duration' => $validated['duration'],
        ]);

        // Create course availabilities
        foreach ($validated['availabilities'] as $availability) {
            CourseAvailability::create([
                'course_id' => $course->id,
                'start_date' => Carbon::parse($availability['start_date']),
                'end_date' => Carbon::parse($availability['end_date']),
                'capacity' => $availability['capacity'],                        // Seats Available
                'sessions' => $availability['sessions'] ?? $availability['capacity'], // Sessions (defaults to capacity if not provided)
                'status' => 'active',
                'notes' => $availability['notes'] ?? null
            ]);
        }

        if ($course->privacy === 'public') {
            $this->notifyAllUsersOfPublicCourse($course);
        }

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully with ' . count($validated['availabilities']) . ' availability periods.');
    }


    /**
     * Display the specified course
     */
    public function show(Course $course)
    {
        // ✅ Use eager loading in the initial query instead of load()
        $course = Course::with([
            'availabilities.registrations.user',
            'registrations.user',
            'registrations.courseAvailability'
        ])->findOrFail($course->id);

        // ✅ Consider pagination if you expect many availabilities
        $availabilities = $course->availabilities->map(function ($availability) {
            return [
                'id' => $availability->id,
                'start_date' => $availability->start_date,
                'end_date' => $availability->end_date,
                'formatted_date_range' => $availability->formatted_date_range,
                'capacity' => $availability->capacity,
                'enrolled_count' => $availability->enrolled_count,
                'available_spots' => $availability->available_spots,
                'status' => $availability->status,
                'notes' => $availability->notes,
                'is_full' => $availability->is_full,
                'is_expired' => $availability->is_expired,
                'enrollments' => $availability->registrations->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'user' => [
                            'id' => $registration->user->id,
                            'name' => $registration->user->name,
                            'email' => $registration->user->email,
                        ],
                        'user_status' => $registration->user_status,
                        'created_at' => $registration->created_at->toDateTimeString() // ✅ Format date
                    ];
                })
            ];
        });

        return Inertia::render('Admin/Courses/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'status' => $course->status,
                'privacy' => $course->privacy,
                'level' => $course->level,
                'duration' => $course->duration,
                'created_at' => $course->created_at->toDateTimeString(),
                // ✅ Only include needed course fields
            ],
            'availabilities' => $availabilities
        ]);
    }

    /**
     * Show the form for editing the specified course
     */
    public function edit(Course $course)
    {
        $course->load('availabilities');

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course
        ]);
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'privacy' => 'required|in:public,private',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'duration' => 'nullable|numeric|min:0.1',
            // Course availabilities validation - ✅ Added sessions validation
            'availabilities' => 'required|array|min:1|max:5',
            'availabilities.*.id' => 'nullable|exists:course_availabilities,id',
            'availabilities.*.start_date' => 'required|date|after:now',
            'availabilities.*.end_date' => 'required|date|after:availabilities.*.start_date',
            'availabilities.*.capacity' => 'required|integer|min:1|max:1000',     // Seats Available
            'availabilities.*.sessions' => 'required|integer|min:1|max:100',      // ✅ Sessions validation
            'availabilities.*.notes' => 'nullable|string|max:500',
            'availabilities.*.status' => 'nullable|in:active,closed'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($course->image_path) {
                Storage::disk('public')->delete($course->image_path);
            }
            $imagePath = $request->file('image')->store('courses', 'public');
        } else {
            $imagePath = $course->image_path;
        }

        // Format dates
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date'])->format('Y-m-d') : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date'])->format('Y-m-d') : null;

        // Update course
        $course->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'privacy' => $validated['privacy'],
            'level' => $validated['level'],
            'duration' => $validated['duration'],
        ]);

        // Handle availabilities updates
        $existingAvailabilityIds = [];

        foreach ($validated['availabilities'] as $availabilityData) {
            if (isset($availabilityData['id'])) {
                // Update existing availability
                $availability = CourseAvailability::find($availabilityData['id']);
                if ($availability && $availability->course_id === $course->id) {
                    $availability->update([
                        'start_date' => Carbon::parse($availabilityData['start_date']),
                        'end_date' => Carbon::parse($availabilityData['end_date']),
                        'capacity' => $availabilityData['capacity'],                    // Seats Available
                        'sessions' => $availabilityData['sessions'],                    // ✅ Sessions
                        'status' => $availabilityData['status'] ?? 'active',
                        'notes' => $availabilityData['notes'] ?? null
                    ]);
                    $existingAvailabilityIds[] = $availability->id;
                }
            } else {
                // Create new availability
                $availability = CourseAvailability::create([
                    'course_id' => $course->id,
                    'start_date' => Carbon::parse($availabilityData['start_date']),
                    'end_date' => Carbon::parse($availabilityData['end_date']),
                    'capacity' => $availabilityData['capacity'],                        // Seats Available
                    'sessions' => $availabilityData['sessions'],                        // ✅ Sessions
                    'status' => $availabilityData['status'] ?? 'active',
                    'notes' => $availabilityData['notes'] ?? null
                ]);
                $existingAvailabilityIds[] = $availability->id;
            }
        }

        // Delete removed availabilities (only if they have no enrollments)
        $course->availabilities()
            ->whereNotIn('id', $existingAvailabilityIds)
            ->whereDoesntHave('registrations')
            ->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }


    /**
     * Remove the specified course
     */
    public function destroy(Course $course)
    {
        // Delete image if exists
        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }


    private function notifyAllUsersOfPublicCourse(Course $course)
    {
        try {
            // Get all active verified users
            $users = User::whereNotNull('email_verified_at')->get();

            Log::info('Starting public course notifications', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'total_users' => $users->count()
            ]);

            $successCount = 0;
            $failureCount = 0;

            // Send notification to each user
            foreach ($users as $user) {
                try {
                    // Generate login link for this user and course
                    $loginLink = $user->generateLoginLink($course->id);

                    // Send email with login link
                    Mail::to($user->email)->send(new CoursePublicAnnouncement($course, $user, $loginLink));

                    $successCount++;
                    Log::debug("Public course notification sent successfully to: {$user->email}");

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error("Failed to send public course notification to {$user->email}: " . $e->getMessage());
                }

                // Rate limiting to prevent mail server overload
                sleep(1); // 1 second delay between emails
            }

            Log::info('Public course notifications completed', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'total_users' => $users->count(),
                'successful_sends' => $successCount,
                'failed_sends' => $failureCount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send public course notifications', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }



}
