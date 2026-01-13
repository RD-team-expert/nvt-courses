<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Department;
use App\Models\Incentive;
use App\Models\EvaluationHistory;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UserEvaluationController extends Controller
{
    public function index()
    {
        try {
            // Enhanced: Get all users with their departments, levels, and tiers
            $users = User::with(['department', 'userLevel', 'userLevelTier'])
                ->select(['id', 'name', 'email', 'department_id', 'user_level_id', 'user_level_tier_id'])
                ->get()
                ->map(function ($user) {
                    // Get assigned courses for this user (not just completed)
                    $assignedCourses = [];

                    try {
                        $assignments = DB::table('course_assignments')
                            ->join('courses', 'course_assignments.course_id', '=', 'courses.id')
                            ->where('course_assignments.user_id', $user->id)
                            ->select(
                                'courses.id', 
                                'courses.name', 
                                'course_assignments.assigned_at',
                                'course_assignments.completed_at',
                                'course_assignments.status'
                            )
                            ->orderBy('course_assignments.assigned_at', 'desc')
                            ->get();

                        foreach ($assignments as $assignment) {
                            $assignedCourses[] = [
                                'id' => $assignment->id,
                                'title' => $assignment->name,
                                'assigned_at' => $assignment->assigned_at,
                                'completed_at' => $assignment->completed_at,
                                'status' => $assignment->status,
                            ];
                        }
                    } catch (Exception $e1) {
                        try {
                            $pivotData = DB::table('course_user')
                                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                                ->where('course_user.user_id', $user->id)
                                ->select('courses.id', 'courses.name', 'course_user.completed_at')
                                ->get();

                            foreach ($pivotData as $pivot) {
                                $assignedCourses[] = [
                                    'id' => $pivot->id,
                                    'title' => $pivot->name,
                                    'assigned_at' => null,
                                    'completed_at' => $pivot->completed_at,
                                    'status' => $pivot->completed_at ? 'completed' : 'in_progress',
                                ];
                            }
                        } catch (Exception $e2) {
                            $allCourses = Course::select(['id', 'name'])->get();
                            foreach ($allCourses as $course) {
                                $assignedCourses[] = [
                                    'id' => $course->id,
                                    'title' => $course->name,
                                    'assigned_at' => now()->subDays(rand(1, 30))->toDateString(),
                                    'completed_at' => null,
                                    'status' => 'pending',
                                ];
                            }
                        }
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'department' => $user->department,
                        'department_id' => $user->department_id,
                        'user_level' => $user->userLevel ? [
                            'id' => $user->userLevel->id,
                            'name' => $user->userLevel->name,
                            'code' => $user->userLevel->code,
                        ] : null,
                        'user_level_tier' => $user->userLevelTier ? [
                            'id' => $user->userLevelTier->id,
                            'tier_name' => $user->userLevelTier->tier_name,
                            'tier_order' => $user->userLevelTier->tier_order,
                        ] : null,
                        'completed_courses' => $assignedCourses
                    ];
                });

            // NEW: Get evaluation categories that apply to REGULAR courses
            $categories = EvaluationConfig::forRegular() // Use the scope we created
            ->with(['types' => function ($query) {
                $query->orderBy('score_value', 'desc');
            }])
                ->get()
                ->map(function ($config) {
                    return [
                        'id' => $config->id,
                        'name' => $config->name,
                        'description' => $config->description ?? 'Evaluation category for ' . $config->name,
                        'weight' => $config->weight ?? 25,
                        'max_score' => $config->max_score,
                        'applies_to' => $config->applies_to, // NEW
                        'types' => $config->types->map(function ($type) {
                            return [
                                'id' => $type->id,
                                'type_name' => $type->type_name,
                                'score_value' => $type->score_value,
                                'description' => $type->description ?? $type->type_name,
                            ];
                        })->toArray()
                    ];
                });

            // Get departments
            $departments = Department::select(['id', 'name'])->get();

            // Get all courses
            $courses = Course::select(['id', 'name', 'description'])
                ->orderBy('name')
                ->get()
                ->map(function($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->name,
                        'description' => $course->description,
                    ];
                });

            // Get Level + Tier based incentives with relationship data
            $incentives = Incentive::with(['userLevel', 'userLevelTier'])
                ->orderBy('user_level_id')
                ->orderBy('user_level_tier_id')
                ->orderBy('min_score')
                ->get()
                ->map(function ($incentive) {
                    return [
                        'id' => $incentive->id,
                        'user_level_id' => $incentive->user_level_id,
                        'user_level_tier_id' => $incentive->user_level_tier_id,
                        'min_score' => $incentive->min_score,
                        'max_score' => $incentive->max_score,
                        'incentive_amount' => $incentive->incentive_amount,
                        'user_level' => $incentive->userLevel ? [
                            'id' => $incentive->userLevel->id,
                            'name' => $incentive->userLevel->name,
                            'code' => $incentive->userLevel->code,
                        ] : null,
                        'user_level_tier' => $incentive->userLevelTier ? [
                            'id' => $incentive->userLevelTier->id,
                            'tier_name' => $incentive->userLevelTier->tier_name,
                            'tier_order' => $incentive->userLevelTier->tier_order,
                        ] : null,
                    ];
                });

            // Get user levels with tiers for frontend reference
            $userLevels = UserLevel::with(['tiers' => function($query) {
                $query->orderBy('tier_order');
            }])
                ->orderBy('hierarchy_level')
                ->get()
                ->map(function ($level) {
                    return [
                        'id' => $level->id,
                        'code' => $level->code,
                        'name' => $level->name,
                        'hierarchy_level' => $level->hierarchy_level,
                        'tiers' => $level->tiers->map(function ($tier) {
                            return [
                                'id' => $tier->id,
                                'tier_name' => $tier->tier_name,
                                'tier_order' => $tier->tier_order,
                                'description' => $tier->description,
                            ];
                        }),
                    ];
                });



            return inertia('Admin/Evaluations/UserEvaluation', [
                'users' => $users,
                'categories' => $categories,
                'departments' => $departments,
                'courses' => $courses,
                'incentives' => $incentives,
                'userLevels' => $userLevels,
            ]);

        } catch (Exception $e) {


            return inertia('Admin/Evaluations/UserEvaluation', [
                'users' => [],
                'categories' => [],
                'departments' => [],
                'courses' => [],
                'incentives' => [],
                'userLevels' => [],
                'error' => 'Failed to load evaluation data: ' . $e->getMessage()
            ]);
        }
    }


    // Enhanced: Get users by department with level/tier info
    public function getUsersByDepartment(Request $request)
    {
        $departmentId = $request->get('department_id');

        if (!$departmentId) {
            return response()->json(['users' => []]);
        }

        try {
            $users = User::with(['department', 'userLevel', 'userLevelTier'])
                ->where('department_id', $departmentId)
                ->select(['id', 'name', 'email', 'department_id', 'user_level_id', 'user_level_tier_id'])
                ->get()
                ->map(function ($user) {
                    // Get assigned courses for each user (not just completed)
                    $assignedCourses = [];

                    try {
                        $assignments = DB::table('course_assignments')
                            ->join('courses', 'course_assignments.course_id', '=', 'courses.id')
                            ->where('course_assignments.user_id', $user->id)
                            ->select(
                                'courses.id', 
                                'courses.name', 
                                'course_assignments.assigned_at',
                                'course_assignments.completed_at',
                                'course_assignments.status'
                            )
                            ->orderBy('course_assignments.assigned_at', 'desc')
                            ->get();

                        foreach ($assignments as $assignment) {
                            $assignedCourses[] = [
                                'id' => $assignment->id,
                                'title' => $assignment->name,
                                'assigned_at' => $assignment->assigned_at,
                                'completed_at' => $assignment->completed_at,
                                'status' => $assignment->status,
                            ];
                        }
                    } catch (Exception $e) {
                        // Fallback to all courses
                        $allCourses = Course::select(['id', 'name'])->get();
                        foreach ($allCourses as $course) {
                            $assignedCourses[] = [
                                'id' => $course->id,
                                'title' => $course->name,
                                'assigned_at' => now()->subDays(rand(1, 30))->toDateString(),
                                'completed_at' => null,
                                'status' => 'pending',
                            ];
                        }
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'department' => $user->department,
                        'department_id' => $user->department_id,
                        // NEW: Include level and tier information
                        'user_level' => $user->userLevel ? [
                            'id' => $user->userLevel->id,
                            'name' => $user->userLevel->name,
                            'code' => $user->userLevel->code,
                        ] : null,
                        'user_level_tier' => $user->userLevelTier ? [
                            'id' => $user->userLevelTier->id,
                            'tier_name' => $user->userLevelTier->tier_name,
                            'tier_order' => $user->userLevelTier->tier_order,
                        ] : null,
                        'completed_courses' => $assignedCourses
                    ];
                });

            return response()->json(['users' => $users]);

        } catch (Exception $e) {


            return response()->json(['users' => [], 'error' => $e->getMessage()], 500);
        }
    }

    // Enhanced: Get assigned courses for a specific user (not just completed)
    public function getUserCourses(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            return response()->json(['courses' => []]);
        }

        try {
            $assignedCourses = [];

            // Get all assigned courses from course_assignments table
            try {
                $assignments = DB::table('course_assignments')
                    ->join('courses', 'course_assignments.course_id', '=', 'courses.id')
                    ->where('course_assignments.user_id', $userId)
                    ->select(
                        'courses.id', 
                        'courses.name', 
                        'courses.description', 
                        'course_assignments.assigned_at',
                        'course_assignments.completed_at',
                        'course_assignments.status'
                    )
                    ->orderBy('course_assignments.assigned_at', 'desc')
                    ->get();

                foreach ($assignments as $assignment) {
                    $assignedCourses[] = [
                        'id' => $assignment->id,
                        'title' => $assignment->name,
                        'description' => $assignment->description,
                        'assigned_at' => $assignment->assigned_at,
                        'completed_at' => $assignment->completed_at,
                        'status' => $assignment->status,
                    ];
                }
            } catch (Exception $e) {
                // Fallback: return all courses
                $allCourses = Course::select(['id', 'name', 'description'])->get();
                foreach ($allCourses as $course) {
                    $assignedCourses[] = [
                        'id' => $course->id,
                        'title' => $course->name,
                        'description' => $course->description,
                        'assigned_at' => now()->subDays(rand(1, 30))->toDateString(),
                        'completed_at' => null,
                        'status' => 'pending',
                    ];
                }
            }

            return response()->json(['courses' => $assignedCourses]);

        } catch (Exception $e) {


            return response()->json(['courses' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'department_id' => 'nullable|exists:departments,id',
            'evaluation_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'categories' => 'required|array',
            'categories.*.category_id' => 'required|exists:evaluation_configs,id',
            'categories.*.evaluation_type_id' => 'required|exists:evaluation_types,id',
            'categories.*.comments' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $totalScore = 0;

            $user = User::with(['department', 'userLevel', 'userLevelTier'])->find($validated['user_id']);
            if (!$user) {
                throw new Exception('Selected user not found.');
            }

            // Check for existing evaluation
            $existingEvaluation = Evaluation::where('user_id', $validated['user_id'])
                ->where('course_id', $validated['course_id'])
                ->where('course_type', 'regular') // NEW: Filter by course type
                ->first();

            if ($existingEvaluation) {
                $existingEvaluation->history()->delete();
                $evaluation = $existingEvaluation;


            } else {
                // NEW: Include course_type when creating
                $evaluation = Evaluation::create([
                    'user_id' => $validated['user_id'],
                    'course_id' => $validated['course_id'],
                    'course_type' => 'regular', // NEW: Set as regular course
                    'course_online_id' => null, // NEW: Explicitly null for regular courses
                    'department_id' => $validated['department_id'] ?? $user->department_id,
                    'total_score' => 0,
                    'incentive_amount' => 0,
                ]);


            }

            if (!$evaluation || !$evaluation->id) {
                throw new Exception('Failed to create evaluation record.');
            }

            // Process categories and calculate total score
            foreach ($validated['categories'] as $index => $categoryData) {
                $evaluationType = EvaluationType::find($categoryData['evaluation_type_id']);
                if (!$evaluationType) {
                    throw new Exception("Evaluation type not found for category at position " . ($index + 1));
                }

                $score = $evaluationType->score_value;
                $totalScore += $score;

                $category = EvaluationConfig::find($categoryData['category_id']);
                $categoryName = $category ? $category->name : 'Unknown Category';

                // NEW: Include course_type in history
                $historyData = [
                    'evaluation_id' => $evaluation->id,
                    'category_name' => $categoryName,
                    'type_name' => $evaluationType->type_name,
                    'score' => $score,
                    'course_type' => 'regular', // NEW
                    'course_online_id' => null, // NEW
                ];

                if (in_array('comments', (new \App\Models\EvaluationHistory)->getFillable())) {
                    $historyData['comments'] = $categoryData['comments'] ?? null;
                }

                $evaluation->history()->create($historyData);
            }

            // Calculate incentive amount using Level + Tier based system
            $incentiveAmount = $this->calculateLevelTierIncentiveAmount($user, $totalScore);



            $evaluation->update([
                'total_score' => $totalScore,
                'incentive_amount' => $incentiveAmount
            ]);

            DB::commit();

            $message = $existingEvaluation ? 'updated' : 'created';
            $levelTierInfo = '';
            if ($user->userLevel && $user->userLevelTier) {
                $levelTierInfo = ' (' . $user->userLevel->name . ' - ' . $user->userLevelTier->tier_name . ')';
            }

            return redirect()->route('admin.evaluations.user-evaluation')
                ->with('success', 'User evaluation ' . $message . ' successfully!' . $levelTierInfo . ' Total Score: ' . $totalScore . ', Incentive: $' . number_format($incentiveAmount, 2));

        } catch (Exception $e) {
            DB::rollBack();



            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }


    /**
     * NEW: Enhanced incentive calculation based on User Level + Tier + Score
     */
    private function calculateLevelTierIncentiveAmount($user, $totalScore)
    {
        try {
            if ($totalScore <= 0) {
                return 0;
            }

            // Check if user has level and tier assigned
            if (!$user->userLevel || !$user->userLevelTier) {


                // Fallback to old system if no level/tier
                return $this->calculateIncentiveAmount($totalScore);
            }

            // Find specific incentive for user's level + tier + score range
            $incentive = Incentive::where('user_level_id', $user->userLevel->id)
                ->where('user_level_tier_id', $user->userLevelTier->id)
                ->where('min_score', '<=', $totalScore)
                ->where('max_score', '>=', $totalScore)
                ->orderBy('incentive_amount', 'desc')
                ->first();

            if ($incentive) {


                return (float)$incentive->incentive_amount;
            }

            // Fallback: Try to find incentive for just the level (any tier)
            $levelIncentive = Incentive::where('user_level_id', $user->userLevel->id)
                ->whereNull('user_level_tier_id')
                ->where('min_score', '<=', $totalScore)
                ->where('max_score', '>=', $totalScore)
                ->orderBy('incentive_amount', 'desc')
                ->first();

            if ($levelIncentive) {

                return (float)$levelIncentive->incentive_amount;
            }

            // Final fallback: Use old system
            return $this->calculateIncentiveAmount($totalScore);

        } catch (Exception $e) {


            // Fallback to old system on error
            return $this->calculateIncentiveAmount($totalScore);
        }
    }

    /**
     * Legacy incentive calculation (fallback)
     */
    private function calculateIncentiveAmount($totalScore)
    {
        try {
            if ($totalScore <= 0) {
                return 0;
            }

            $incentive = Incentive::whereNull('user_level_id')
                ->whereNull('user_level_tier_id')
                ->where('min_score', '<=', $totalScore)
                ->where('max_score', '>=', $totalScore)
                ->orderBy('incentive_amount', 'desc')
                ->first();

            return $incentive ? (float)$incentive->incentive_amount : 0;

        } catch (Exception $e) {

            return 0;
        }
    }
}
