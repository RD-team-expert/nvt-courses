<?php

namespace App\Http\Controllers\Admin;

use App\Events\AudioAssigned;
use App\Http\Controllers\Controller;
use App\Mail\AudioAssignmentManagerNotification;
use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\Department;
use App\Models\User;
use App\Services\ManagerHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AudioAssignmentController extends Controller
{
    /**
     * Display listing of audio assignments
     */
    public function index(Request $request)
    {
        $query = AudioAssignment::with(['audio', 'user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc');

        // Filter by audio
        if ($request->audio_id) {
            $query->where('audio_id', $request->audio_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Search by user name or audio name
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('audio', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        $assignments = $query->paginate(15);

        // Get filter options
        $audios = Audio::active()->orderBy('name')->get(['id', 'name']);
        $users = User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name', 'email']);

        // Calculate summary statistics
        $stats = [
            'total_assignments' => AudioAssignment::count(),
            'active_assignments' => AudioAssignment::where('status', 'in_progress')->count(),
            'completed_assignments' => AudioAssignment::where('status', 'completed')->count(),
            'average_completion_rate' => AudioAssignment::avg('progress_percentage') ?? 0,
        ];

        return Inertia::render('Admin/AudioAssignment/Index', [
            'assignments' => $assignments->through(fn($assignment) => [
                'id' => $assignment->id,
                'audio' => [
                    'id' => $assignment->audio->id,
                    'name' => $assignment->audio->name,
                    'duration' => $assignment->audio->formatted_duration,
                ],
                'user' => [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'email' => $assignment->user->email,
                ],
                'assigned_by' => [
                    'id' => $assignment->assignedBy->id,
                    'name' => $assignment->assignedBy->name,
                ],
                'status' => $assignment->status,
                'progress_percentage' => round($assignment->progress_percentage, 1),
                'assigned_at' => $assignment->assigned_at->format('M d, Y'),
                'started_at' => $assignment->started_at?->format('M d, Y'),
                'completed_at' => $assignment->completed_at?->format('M d, Y'),
            ]),
            'audios' => $audios,
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['audio_id', 'status', 'user_id', 'search']),
        ]);
    }

    /**
     * Show form for creating new assignments
     */
    public function create(Request $request)
    {
        $selectedAudioId = $request->get('audio_id');

        $audios = Audio::active()
            ->withCount('assignments')
            ->orderBy('name')
            ->get()
            ->map(fn($audio) => [
                'id' => $audio->id,
                'name' => $audio->name,
                'description' => $audio->description,
                'duration' => $audio->formatted_duration,
                'current_assignments' => $audio->assignments_count,
            ]);

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::where('role', '!=', 'admin')
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(function($user) use ($selectedAudioId) {
                $hasThisAudio = false;
                if ($selectedAudioId) {
                    $hasThisAudio = AudioAssignment::where('user_id', $user->id)
                        ->where('audio_id', $selectedAudioId)
                        ->exists();
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department_id' => $user->department_id,
                    'department_name' => $user->department?->name ?? 'No Department',
                    'has_selected_audio' => $hasThisAudio,
                ];
            });

        return Inertia::render('Admin/AudioAssignment/Create', [
            'audios' => $audios,
            'users' => $users,
            'departments' => $departments,
            'selectedAudioId' => $selectedAudioId,
        ]);
    }

    /**
     * Store newly created assignments
     */
    public function store(Request $request)
    {
        Log::info('🎯 AudioAssignmentController@store called', [
            'request_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'audio_id' => 'required|exists:audios,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'send_notification' => 'boolean',
        ]);

        try {
            $audio = Audio::findOrFail($validated['audio_id']);
            $users = User::with(['department'])->whereIn('id', $validated['user_ids'])->get();

            $assignmentCount = 0;
            $skippedCount = 0;
            $usersByManager = [];

            foreach ($users as $user) {
                Log::info('🔄 Processing user for audio assignment', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'audio_id' => $audio->id,
                    'audio_name' => $audio->name,
                ]);

                $existingAssignment = AudioAssignment::where('audio_id', $audio->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingAssignment) {
                    Log::warning('⚠️ Audio assignment already exists', [
                        'user_id' => $user->id,
                        'audio_id' => $audio->id,
                    ]);
                    $skippedCount++;
                    continue;
                }

                $assignment = AudioAssignment::create([
                    'audio_id' => $audio->id,
                    'user_id' => $user->id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'status' => 'assigned',
                    'progress_percentage' => 0,
                    'notification_sent' => false,
                ]);

                $assignmentCount++;

                // Group users by their managers
                try {
                    $managers = app(ManagerHierarchyService::class)
                        ->getDirectManagersForUser($user->id);

                    foreach ($managers as $managerData) {
                        $managerId = $managerData['manager']->id;

                        if (!isset($usersByManager[$managerId])) {
                            $usersByManager[$managerId] = [
                                'manager' => $managerData['manager'],
                                'users' => [],
                                'relationship' => $managerData['relationship'],
                                'level' => $managerData['level'],
                            ];
                        }

                        $usersByManager[$managerId]['users'][] = $user;
                    }
                } catch (\Exception $e) {
                    Log::warning('⚠️ Could not get managers for audio assignment', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Send notification to USER only (listener will handle the email)
                if ($validated['send_notification'] ?? true) {
                    try {
                        $loginLink = $this->generateUserLoginLink($user, $audio);

                        Log::info('🎯 Dispatching AudioAssigned event', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'audio_id' => $audio->id,
                            'login_link' => $loginLink,
                        ]);

                        AudioAssigned::dispatch(
                            $audio,
                            $user,
                            $loginLink,
                            auth()->user(),
                            [
                                'assignment_type' => 'audio',
                                'assignment_id' => $assignment->id,
                                'skip_manager_notification' => true, // Controller handles manager emails
                            ]
                        );

                        $assignment->update(['notification_sent' => true]);

                    } catch (\Exception $e) {
                        Log::error('❌ Audio user notification failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Send ONE email per manager with ALL their employees
            if ($validated['send_notification'] ?? true) {
                Log::info('📧 Starting manager notifications', [
                    'manager_count' => count($usersByManager),
                    'managers' => array_keys($usersByManager),
                ]);

                foreach ($usersByManager as $managerId => $data) {
                    try {
                        $manager = $data['manager'];
                        $employees = collect($data['users']);

                        // Filter out self-managed users
                        $employeesExcludingSelf = $employees->filter(function($employee) use ($manager) {
                            return $employee->id !== $manager->id;
                        });

                        if ($employeesExcludingSelf->isEmpty()) {
                            Log::info('⏭️ Skipping manager notification - no employees to notify', [
                                'manager_id' => $managerId,
                                'manager_name' => $manager->name,
                            ]);
                            continue;
                        }

                        Log::info('📧 Sending manager notification', [
                            'manager_id' => $manager->id,
                            'manager_name' => $manager->name,
                            'manager_email' => $manager->email,
                            'employee_count' => $employeesExcludingSelf->count(),
                            'employees' => $employeesExcludingSelf->pluck('name')->toArray(),
                        ]);

                        Mail::to($manager->email)->send(
                            new AudioAssignmentManagerNotification(
                                $audio,
                                $employeesExcludingSelf,
                                auth()->user(),
                                $manager,
                                [
                                    'relationship' => $data['relationship'],
                                    'level' => $data['level'],
                                ]
                            )
                        );

                        Log::info('✅ Manager notification sent successfully', [
                            'manager_email' => $manager->email,
                        ]);

                    } catch (\Exception $e) {
                        Log::error('❌ Audio manager notification failed', [
                            'manager_id' => $managerId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $message = "Successfully assigned the audio to {$assignmentCount} user(s).";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} user(s) were already assigned.";
            }

            return redirect()->route('admin.audio-assignments.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('❌ Audio assignment failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to assign audio: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate login link for users
     */
    private function generateUserLoginLink(User $user, Audio $audio): ?string
    {
        try {
            // Use the User model's method to generate a proper login link with token
            $loginUrl = $user->generateAudioLoginLink($audio->id);

            Log::info('🔗 Generated audio login link', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'audio_id' => $audio->id,
                'audio_name' => $audio->name,
                'link' => $loginUrl,
            ]);

            return $loginUrl;

        } catch (\Exception $e) {
            Log::error('❌ Audio login link generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Remove assignment
     */
    public function destroy(AudioAssignment $audioAssignment)
    {
        $audioName = $audioAssignment->audio->name;
        $userName = $audioAssignment->user->name;

        $audioAssignment->delete();

        return redirect()->route('admin.audio-assignments.index')
            ->with('success', "Assignment for {$userName} in {$audioName} has been removed.");
    }

    /**
     * Filter users by department and/or name
     */
    public function filterUsers(Request $request)
    {
        $query = User::where('role', '!=', 'admin')
            ->with('department');

        // Filter by department
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by name search
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
            ->paginate($request->per_page ?? 50)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'department_name' => $user->department?->name ?? 'No Department',
            ]);

        return response()->json($users);
    }
}
