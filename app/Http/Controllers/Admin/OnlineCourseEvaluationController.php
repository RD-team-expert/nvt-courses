<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use App\Models\User;
use App\Models\CourseOnline;
use App\Models\Department;
use App\Models\Incentive;
use App\Models\EvaluationHistory;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OnlineCourseEvaluationController extends Controller
{
    /**
     * Display the online course evaluation page
     */
    public function index()
    {
        try {
            // Get all users with their departments, levels, and tiers
            $users = User::with(['department', 'userLevel', 'userLevelTier'])
                ->select(['id', 'name', 'email', 'department_id', 'user_level_id', 'user_level_tier_id'])
                ->get()
                ->map(function ($user) {
                    // Get completed online courses for this user
                    $completedOnlineCourses = $this->getUserCompletedOnlineCourses($user->id);

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
                        'completed_courses' => $completedOnlineCourses
                    ];
                });

            // Get evaluation categories that apply to ONLINE courses
            $categories = EvaluationConfig::forOnline()
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
                        'applies_to' => $config->applies_to,
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

            // Get all ONLINE courses - FIXED: Use 'name' instead of 'title'
            $courses = CourseOnline::select(['id', 'name', 'description'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->name,  // Map 'name' to 'title' for frontend
                        'description' => $course->description,
                    ];
                });

            // Get Level + Tier based incentives
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

            // Get user levels with tiers
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

            Log::info('OnlineCourseEvaluation index data loaded', [
                'users_count' => $users->count(),
                'courses_count' => $courses->count(),
                'categories_count' => $categories->count(),
                'departments_count' => $departments->count(),
                'incentives_count' => $incentives->count(),
                'user_levels_count' => $userLevels->count()
            ]);

            return inertia('Admin/Evaluations/OnlineCourseEvaluation', [
                'users' => $users,
                'categories' => $categories,
                'departments' => $departments,
                'courses' => $courses,
                'incentives' => $incentives,
                'userLevels' => $userLevels,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to load online course evaluation page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return inertia('Admin/Evaluations/OnlineCourseEvaluation', [
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

    /**
     * Store online course evaluation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_online_id' => 'required|exists:course_online,id',
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

            // Check for existing evaluation for this ONLINE course
            $existingEvaluation = Evaluation::where('user_id', $validated['user_id'])
                ->where('course_online_id', $validated['course_online_id'])
                ->where('course_type', 'online')
                ->first();

            if ($existingEvaluation) {
                $existingEvaluation->history()->delete();
                $evaluation = $existingEvaluation;

                Log::info('Updating existing online course evaluation', [
                    'evaluation_id' => $evaluation->id,
                    'user_id' => $validated['user_id'],
                    'course_online_id' => $validated['course_online_id']
                ]);
            } else {
                // Create new evaluation for ONLINE course
                $evaluation = Evaluation::create([
                    'user_id' => $validated['user_id'],
                    'course_id' => null,
                    'course_type' => 'online',
                    'course_online_id' => $validated['course_online_id'],
                    'department_id' => $validated['department_id'] ?? $user->department_id,
                    'total_score' => 0,
                    'incentive_amount' => 0,
                ]);

                Log::info('Created new online course evaluation', [
                    'evaluation_id' => $evaluation->id,
                    'course_type' => 'online'
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

                // Create history entry with course_type
                $historyData = [
                    'evaluation_id' => $evaluation->id,
                    'category_name' => $categoryName,
                    'type_name' => $evaluationType->type_name,
                    'score' => $score,
                    'course_type' => 'online',
                    'course_online_id' => $validated['course_online_id'],
                ];

                if (in_array('comments', (new \App\Models\EvaluationHistory)->getFillable())) {
                    $historyData['comments'] = $categoryData['comments'] ?? null;
                }

                $evaluation->history()->create($historyData);
            }

            // Calculate incentive amount using Level + Tier based system
            $incentiveAmount = $this->calculateLevelTierIncentiveAmount($user, $totalScore);

            Log::info('Calculated Level+Tier incentive for online course', [
                'user_id' => $user->id,
                'user_level' => $user->userLevel?->name,
                'user_tier' => $user->userLevelTier?->tier_name,
                'total_score' => $totalScore,
                'incentive_amount' => $incentiveAmount,
                'course_type' => 'online'
            ]);

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

            return redirect()->route('admin.evaluations.online.index')
                ->with('success', 'Online course evaluation ' . $message . ' successfully!' . $levelTierInfo . ' Total Score: ' . $totalScore . ', Incentive: $' . number_format($incentiveAmount, 2));

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Online course evaluation store process failed', [
                'validated_data' => $validated,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Get completed online courses for a user
     * FIXED: Use 'name' column instead of 'title'
     */
    private function getUserCompletedOnlineCourses($userId)
    {
        $completedCourses = [];

        try {
            // Check course_online_assignments table for completed courses
            $assignments = DB::table('course_online_assignments')
                ->join('course_online', 'course_online_assignments.course_online_id', '=', 'course_online.id')
                ->where('course_online_assignments.user_id', $userId)
                ->where('course_online_assignments.status', 'completed')
                ->select(
                    'course_online.id',
                    'course_online.name as title',
                    'course_online_assignments.completed_at',
                    'course_online_assignments.progress_percentage'  // ✅ CORRECT COLUMN NAME
                )
                ->get();

            foreach ($assignments as $assignment) {
                $completedCourses[] = [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'completed_at' => $assignment->completed_at,
                    'progress' => $assignment->progress_percentage ?? 100,  // ✅ Use progress_percentage
                ];
            }

            // If no completed courses found, return all assigned online courses
            if (empty($completedCourses)) {
                $allAssignments = DB::table('course_online_assignments')
                    ->join('course_online', 'course_online_assignments.course_online_id', '=', 'course_online.id')
                    ->where('course_online_assignments.user_id', $userId)
                    ->select(
                        'course_online.id',
                        'course_online.name as title',
                        'course_online_assignments.assigned_at as completed_at',
                        'course_online_assignments.progress_percentage'  // ✅ CORRECT COLUMN NAME
                    )
                    ->get();

                foreach ($allAssignments as $assignment) {
                    $completedCourses[] = [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'completed_at' => $assignment->completed_at,
                        'progress' => $assignment->progress_percentage ?? 0,  // ✅ Use progress_percentage
                    ];
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to get completed online courses for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        return $completedCourses;
    }


    /**
     * Calculate incentive amount based on user level, tier, and score
     */
    private function calculateLevelTierIncentiveAmount($user, $totalScore)
    {
        if (!$user->user_level_id || !$user->user_level_tier_id) {
            Log::info('User missing level or tier for incentive calculation', [
                'user_id' => $user->id,
                'has_level' => !is_null($user->user_level_id),
                'has_tier' => !is_null($user->user_level_tier_id)
            ]);
            return 0;
        }

        $incentive = Incentive::where('user_level_id', $user->user_level_id)
            ->where('user_level_tier_id', $user->user_level_tier_id)
            ->where('min_score', '<=', $totalScore)
            ->where(function($query) use ($totalScore) {
                $query->where('max_score', '>=', $totalScore)
                    ->orWhereNull('max_score');
            })
            ->first();

        if ($incentive) {
            Log::info('Found matching incentive', [
                'user_id' => $user->id,
                'level_id' => $user->user_level_id,
                'tier_id' => $user->user_level_tier_id,
                'score' => $totalScore,
                'incentive_id' => $incentive->id,
                'amount' => $incentive->incentive_amount
            ]);
            return $incentive->incentive_amount;
        }

        Log::info('No matching incentive found', [
            'user_id' => $user->id,
            'level_id' => $user->user_level_id,
            'tier_id' => $user->user_level_tier_id,
            'score' => $totalScore
        ]);

        return 0;
    }

    /**
     * Get users by department for online courses
     */
    public function getUsersByDepartment(Request $request)
    {
        $departmentId = $request->input('department_id');

        $users = User::with(['department', 'userLevel', 'userLevelTier'])
            ->when($departmentId, function ($query) use ($departmentId) {
                return $query->where('department_id', $departmentId);
            })
            ->select(['id', 'name', 'email', 'department_id', 'user_level_id', 'user_level_tier_id'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department,
                    'user_level' => $user->userLevel,
                    'user_level_tier' => $user->userLevelTier,
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Get user's completed online courses
     */
    public function getUserOnlineCourses(Request $request)
    {
        $userId = $request->input('user_id');

        $completedCourses = $this->getUserCompletedOnlineCourses($userId);

        return response()->json(['courses' => $completedCourses]);
    }
}
