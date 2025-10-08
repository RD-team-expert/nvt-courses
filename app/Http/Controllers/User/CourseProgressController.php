<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\LearningSession;
use App\Models\CourseAnalytics;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CourseProgressController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Start a learning session
     */
    public function startSession(Request $request, ModuleContent $content)
    {
        $validated = $request->validate([
            'user_agent' => 'nullable|string',
            'screen_resolution' => 'nullable|string',
            'timezone' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Check if user has access to this content
        if (!$this->hasContentAccess($content, $user->id)) {
            return response()->json(['error' => 'Access denied to this content'], 403);
        }

        // End any existing active session for this user
        $this->endActiveSession($user->id);

        // Create new learning session
        $session = LearningSession::create([
            'user_id' => $user->id,
            'module_content_id' => $content->id,
            'session_start' => now(),
            'user_agent' => $validated['user_agent'] ?? request()->header('User-Agent'),
            'ip_address' => request()->ip(),
            'screen_resolution' => $validated['screen_resolution'],
            'timezone' => $validated['timezone'],
            'engagement_score' => 100, // Start with full engagement
            'attention_score' => 100,
            'is_active' => true,
        ]);

        // Create or update content progress
        $contentProgress = UserContentProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'module_content_id' => $content->id,
            ],
            [
                'started_at' => now(),
                'last_accessed_at' => now(),
                'current_position' => 0,
                'is_active' => true,
            ]
        );

        Log::info('Learning session started', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'session_id' => $session->id,
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'content_progress_id' => $contentProgress->id,
            'streaming_url' => $this->getContentStreamingUrl($content),
        ]);
    }

    /**
     * Update learning session progress (heartbeat)
     */
    public function updateSession(Request $request, LearningSession $session)
    {
        $validated = $request->validate([
            'current_position' => 'required|numeric|min:0',
            'duration_watched' => 'nullable|numeric|min:0',
            'engagement_events' => 'nullable|array',
            'attention_events' => 'nullable|array',
            'is_focused' => 'boolean',
            'playback_speed' => 'nullable|numeric|min:0.25|max:3',
        ]);

        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Calculate session duration
        $sessionDuration = now()->diffInMinutes($session->session_start);

        // Update session with latest progress
        $session->update([
            'current_position' => $validated['current_position'],
            'total_duration_minutes' => $sessionDuration,
            'last_heartbeat' => now(),
            'engagement_score' => $this->calculateEngagementScore($validated),
            'attention_score' => $this->calculateAttentionScore($validated),
            'is_focused' => $validated['is_focused'] ?? true,
            'playback_speed' => $validated['playback_speed'] ?? 1.0,
        ]);

        // Update content progress
        $contentProgress = UserContentProgress::where('user_id', $user->id)
            ->where('module_content_id', $session->module_content_id)
            ->first();

        if ($contentProgress) {
            $completionPercentage = $this->calculateCompletionPercentage(
                $session->moduleContent,
                $validated['current_position']
            );

            $contentProgress->update([
                'current_position' => $validated['current_position'],
                'completion_percentage' => $completionPercentage,
                'time_spent' => $sessionDuration,
                'last_accessed_at' => now(),
                'is_completed' => $completionPercentage >= 95, // Consider 95% as completed
            ]);

            // Check if content is completed and update module/course progress
            if ($contentProgress->is_completed && !$contentProgress->completed_at) {
                $this->markContentCompleted($contentProgress);
            }
        }

        // Detect suspicious activity
        $this->detectSuspiciousActivity($session, $validated);

        return response()->json([
            'success' => true,
            'completion_percentage' => $completionPercentage ?? 0,
            'session_duration' => $sessionDuration,
            'is_completed' => $contentProgress?->is_completed ?? false,
        ]);
    }

    /**
     * End learning session
     */
    public function endSession(Request $request, LearningSession $session)
    {
        $validated = $request->validate([
            'final_position' => 'required|numeric|min:0',
            'reason' => 'nullable|string|in:completed,paused,interrupted,closed',
        ]);

        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Calculate final metrics
        $totalDuration = now()->diffInMinutes($session->session_start);
        $finalPosition = $validated['final_position'];

        $session->update([
            'session_end' => now(),
            'current_position' => $finalPosition,
            'total_duration_minutes' => $totalDuration,
            'end_reason' => $validated['reason'] ?? 'completed',
            'is_active' => false,
        ]);

        // Update content progress final state
        $contentProgress = UserContentProgress::where('user_id', $user->id)
            ->where('module_content_id', $session->module_content_id)
            ->first();

        if ($contentProgress) {
            $completionPercentage = $this->calculateCompletionPercentage(
                $session->moduleContent,
                $finalPosition
            );

            $contentProgress->update([
                'current_position' => $finalPosition,
                'completion_percentage' => $completionPercentage,
                'time_spent' => $totalDuration,
                'is_completed' => $completionPercentage >= 95,
                'is_active' => false,
            ]);

            if ($contentProgress->is_completed && !$contentProgress->completed_at) {
                $this->markContentCompleted($contentProgress);
            }
        }

        // Update course assignment progress
        $this->updateCourseProgress($session->moduleContent->module->courseOnline, $user->id);

        Log::info('Learning session ended', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'duration' => $totalDuration,
            'completion' => $completionPercentage ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'session_duration' => $totalDuration,
            'completion_percentage' => $completionPercentage ?? 0,
        ]);
    }

    /**
     * Get user's current progress for a course
     */
    public function getCourseProgress(CourseOnline $courseOnline)
    {
        $user = Auth::user();

        // Check access
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseOnline->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Get detailed progress
        $modules = $courseOnline->modules()->orderBy('order_number')->get()->map(function ($module) use ($user) {
            $moduleProgress = $this->getModuleProgressData($module, $user->id);

            return [
                'id' => $module->id,
                'name' => $module->name,
                'order_number' => $module->order_number,
                'is_required' => $module->is_required,
                'is_unlocked' => $this->isModuleUnlocked($module, $user->id),
                'progress' => $moduleProgress,
                'content' => $module->content()->orderBy('order_number')->get()->map(function ($content) use ($user) {
                    return [
                        'id' => $content->id,
                        'title' => $content->title,
                        'content_type' => $content->content_type,
                        'is_required' => $content->is_required,
                        'is_unlocked' => $this->isContentUnlocked($content, $user->id),
                        'progress' => $this->getContentProgressData($content, $user->id),
                    ];
                }),
            ];
        });

        $overallProgress = $this->calculateOverallProgress($courseOnline->id, $user->id);

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
                'overall_progress' => $overallProgress,
                'assignment_status' => $assignment->status,
                'started_at' => $assignment->started_at,
                'time_spent' => $this->calculateTimeSpent($courseOnline->id, $user->id),
            ],
            'modules' => $modules,
        ]);
    }

    /**
     * Get user's learning analytics
     */
    public function getAnalytics(Request $request)
    {
        $user = Auth::user();

        $period = $request->get('period', '30'); // days
        $startDate = now()->subDays($period);

        // Learning sessions analytics
        $sessions = LearningSession::where('user_id', $user->id)
            ->where('session_start', '>=', $startDate)
            ->get();

        // Course progress analytics
        $assignments = CourseOnlineAssignment::where('user_id', $user->id)->get();

        $analytics = [
            'summary' => [
                'total_sessions' => $sessions->count(),
                'total_time_spent' => $sessions->sum('total_duration_minutes'),
                'average_session_duration' => $sessions->avg('total_duration_minutes'),
                'completion_rate' => $assignments->avg('progress_percentage'),
                'engagement_score' => $sessions->avg('engagement_score'),
                'attention_score' => $sessions->avg('attention_score'),
            ],
            'daily_activity' => $this->getDailyActivity($user->id, $startDate),
            'content_breakdown' => $this->getContentBreakdown($user->id, $startDate),
            'learning_streaks' => $this->getLearningStreaks($user->id),
            'achievements' => $this->getUserAchievements($user->id),
        ];

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Report suspicious activity
     */
    public function reportSuspiciousActivity(Request $request, LearningSession $session)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|in:tab_switch,window_blur,rapid_seeking,unusual_speed,copy_attempt',
            'details' => 'nullable|array',
            'timestamp' => 'required|date',
        ]);

        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update session with suspicious activity flag
        $session->update([
            'is_suspicious' => true,
            'cheating_risk' => $this->calculateCheatingRisk($session, $validated),
            'suspicious_activities' => array_merge(
                $session->suspicious_activities ?? [],
                [$validated]
            ),
        ]);

        // Log for admin review
        Log::warning('Suspicious learning activity detected', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'activity_type' => $validated['activity_type'],
            'details' => $validated['details'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get content streaming URL (for videos)
     */
    public function getStreamingUrl(ModuleContent $content)
    {
        $user = Auth::user();

        if (!$this->hasContentAccess($content, $user->id)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $streamingUrl = $this->getContentStreamingUrl($content);

        if (!$streamingUrl) {
            return response()->json(['error' => 'Content not available'], 404);
        }

        return response()->json([
            'success' => true,
            'streaming_url' => $streamingUrl,
            'expires_at' => now()->addHours(4)->toISOString(), // URL expires in 4 hours
        ]);
    }

    // ===== HELPER METHODS =====

    /**
     * Check if user has access to content
     */
    private function hasContentAccess(ModuleContent $content, int $userId): bool
    {
        $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
            ->where('user_id', $userId)
            ->first();

        return $assignment && $this->isContentUnlocked($content, $userId);
    }

    /**
     * Check if content is unlocked for user
     */
    private function isContentUnlocked(ModuleContent $content, int $userId): bool
    {
        // Check if module is unlocked
        if (!$this->isModuleUnlocked($content->module, $userId)) {
            return false;
        }

        // First content in module is always unlocked if module is unlocked
        if ($content->order_number === 1) {
            return true;
        }

        // Check if previous required content is completed
        $previousContent = $content->module->content()
            ->where('order_number', '<', $content->order_number)
            ->where('is_required', true)
            ->get();

        foreach ($previousContent as $prevContent) {
            if (!$this->isContentCompleted($prevContent, $userId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if module is unlocked
     */
    private function isModuleUnlocked(CourseModule $module, int $userId): bool
    {
        // First module is always unlocked
        if ($module->order_number === 1) {
            return true;
        }

        // Check if previous required modules are completed
        $previousModules = $module->courseOnline->modules()
            ->where('order_number', '<', $module->order_number)
            ->where('is_required', true)
            ->get();

        foreach ($previousModules as $prevModule) {
            if (!$this->isModuleCompleted($prevModule, $userId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if content is completed
     */
    private function isContentCompleted(ModuleContent $content, int $userId): bool
    {
        return UserContentProgress::where('user_id', $userId)
            ->where('module_content_id', $content->id)
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Check if module is completed
     */
    private function isModuleCompleted(CourseModule $module, int $userId): bool
    {
        $requiredContent = $module->content()->where('is_required', true)->count();
        $completedContent = UserContentProgress::where('user_id', $userId)
            ->whereHas('moduleContent', function ($query) use ($module) {
                $query->where('module_id', $module->id)->where('is_required', true);
            })
            ->where('is_completed', true)
            ->count();

        return $requiredContent > 0 && $completedContent >= $requiredContent;
    }

    /**
     * End any active session for user
     */
    private function endActiveSession(int $userId): void
    {
        LearningSession::where('user_id', $userId)
            ->where('is_active', true)
            ->update([
                'session_end' => now(),
                'is_active' => false,
                'end_reason' => 'new_session_started',
            ]);
    }

    /**
     * Calculate completion percentage based on content type and position
     */
    private function calculateCompletionPercentage(ModuleContent $content, float $currentPosition): float
    {
        if ($content->content_type === 'video' && $content->video) {
            $duration = $content->video->duration; // in seconds
            return $duration > 0 ? min(100, ($currentPosition / $duration) * 100) : 0;
        }

        if ($content->content_type === 'pdf') {
            // For PDFs, position might represent page number or scroll percentage
            return min(100, $currentPosition);
        }

        return 0;
    }

    /**
     * Calculate engagement score based on user behavior
     */
    private function calculateEngagementScore(array $data): float
    {
        $score = 100;

        // Reduce score for low focus
        if (isset($data['is_focused']) && !$data['is_focused']) {
            $score -= 20;
        }

        // Reduce score for unusual playback speed
        if (isset($data['playback_speed'])) {
            $speed = $data['playback_speed'];
            if ($speed > 2.0 || $speed < 0.75) {
                $score -= 15;
            }
        }

        // Process engagement events
        if (isset($data['engagement_events'])) {
            foreach ($data['engagement_events'] as $event) {
                switch ($event['type'] ?? '') {
                    case 'pause':
                        $score -= 5;
                        break;
                    case 'seek':
                        $score -= 3;
                        break;
                    case 'volume_change':
                        $score -= 1;
                        break;
                }
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate attention score
     */
    private function calculateAttentionScore(array $data): float
    {
        $score = 100;

        // Process attention events
        if (isset($data['attention_events'])) {
            foreach ($data['attention_events'] as $event) {
                switch ($event['type'] ?? '') {
                    case 'tab_switch':
                        $score -= 10;
                        break;
                    case 'window_blur':
                        $score -= 15;
                        break;
                    case 'idle':
                        $score -= 5;
                        break;
                }
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Mark content as completed
     */
    private function markContentCompleted(UserContentProgress $contentProgress): void
    {
        $contentProgress->update([
            'completed_at' => now(),
            'is_completed' => true,
            'completion_percentage' => 100,
        ]);

        // Check if this completion unlocks new content or modules
        $this->checkUnlockProgress($contentProgress->moduleContent, $contentProgress->user_id);
    }

    /**
     * Check and unlock next content/modules
     */
    private function checkUnlockProgress(ModuleContent $content, int $userId): void
    {
        // Check if module is now completed
        if ($this->isModuleCompleted($content->module, $userId)) {
            // Update course progress
            $this->updateCourseProgress($content->module->courseOnline, $userId);
        }
    }

    /**
     * Update overall course progress
     */
    private function updateCourseProgress(CourseOnline $courseOnline, int $userId): void
    {
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseOnline->id)
            ->where('user_id', $userId)
            ->first();

        if ($assignment) {
            $overallProgress = $this->calculateOverallProgress($courseOnline->id, $userId);

            $updateData = [
                'progress_percentage' => $overallProgress,
                'last_accessed_at' => now(),
            ];

            // Check if course is completed
            if ($overallProgress >= 100) {
                $updateData['status'] = 'completed';
                $updateData['completed_at'] = now();
            } elseif ($assignment->status === 'assigned') {
                $updateData['status'] = 'in_progress';
                $updateData['started_at'] = now();
            }

            $assignment->update($updateData);
        }
    }

    /**
     * Calculate overall course progress
     */
    private function calculateOverallProgress(int $courseId, int $userId): float
    {
        $totalContent = ModuleContent::whereHas('module', function ($query) use ($courseId) {
            $query->where('course_online_id', $courseId)->where('is_required', true);
        })->where('is_required', true)->count();

        $completedContent = UserContentProgress::where('user_id', $userId)
            ->whereHas('moduleContent', function ($query) use ($courseId) {
                $query->whereHas('module', function ($subQuery) use ($courseId) {
                    $subQuery->where('course_online_id', $courseId)->where('is_required', true);
                })->where('is_required', true);
            })
            ->where('is_completed', true)
            ->count();

        return $totalContent > 0 ? round(($completedContent / $totalContent) * 100, 2) : 0;
    }

    /**
     * Get content streaming URL
     */
    private function getContentStreamingUrl(ModuleContent $content): ?string
    {
        if ($content->content_type === 'video' && $content->video) {
            return $this->googleDriveService->processUrl($content->video->google_drive_url);
        }

        if ($content->content_type === 'pdf' && $content->file_path) {
            return asset('storage/' . $content->file_path);
        }

        return null;
    }

    /**
     * Detect suspicious activity patterns
     */
    private function detectSuspiciousActivity(LearningSession $session, array $data): void
    {
        $suspicious = false;
        $reasons = [];

        // Check for rapid seeking
        if (isset($data['current_position'])) {
            $positionJump = abs($data['current_position'] - $session->current_position);
            if ($positionJump > 60) { // More than 1 minute jump
                $suspicious = true;
                $reasons[] = 'rapid_seeking';
            }
        }

        // Check for unusual playback speed
        if (isset($data['playback_speed']) && ($data['playback_speed'] > 3.0 || $data['playback_speed'] < 0.5)) {
            $suspicious = true;
            $reasons[] = 'unusual_speed';
        }

        // Check attention patterns
        if (isset($data['attention_events'])) {
            $tabSwitches = collect($data['attention_events'])->where('type', 'tab_switch')->count();
            if ($tabSwitches > 5) { // More than 5 tab switches in one heartbeat
                $suspicious = true;
                $reasons[] = 'excessive_tab_switching';
            }
        }

        if ($suspicious) {
            $session->update([
                'is_suspicious' => true,
                'cheating_risk' => $this->calculateCheatingRisk($session, $data),
                'suspicious_activities' => array_merge(
                    $session->suspicious_activities ?? [],
                    [['timestamp' => now(), 'reasons' => $reasons]]
                ),
            ]);
        }
    }

    /**
     * Calculate cheating risk level
     */
    private function calculateCheatingRisk(LearningSession $session, array $data): string
    {
        $riskScore = 0;

        // Add risk for various factors
        if ($session->engagement_score < 50) $riskScore += 20;
        if ($session->attention_score < 30) $riskScore += 30;
        if (isset($data['playback_speed']) && $data['playback_speed'] > 2.5) $riskScore += 25;

        // Check for patterns in suspicious activities
        $suspiciousCount = count($session->suspicious_activities ?? []);
        $riskScore += min(25, $suspiciousCount * 5);

        if ($riskScore >= 70) return 'very_high';
        if ($riskScore >= 50) return 'high';
        if ($riskScore >= 30) return 'medium';
        if ($riskScore >= 10) return 'low';

        return 'none';
    }

    /**
     * Get module progress data
     */
    private function getModuleProgressData(CourseModule $module, int $userId): array
    {
        $totalContent = $module->content()->count();
        $completedContent = UserContentProgress::where('user_id', $userId)
            ->whereHas('moduleContent', function ($query) use ($module) {
                $query->where('module_id', $module->id);
            })
            ->where('is_completed', true)
            ->count();

        return [
            'total_content' => $totalContent,
            'completed_content' => $completedContent,
            'completion_percentage' => $totalContent > 0 ? round(($completedContent / $totalContent) * 100, 2) : 0,
            'is_completed' => $this->isModuleCompleted($module, $userId),
        ];
    }

    /**
     * Get content progress data
     */
    private function getContentProgressData(ModuleContent $content, int $userId): array
    {
        $progress = UserContentProgress::where('user_id', $userId)
            ->where('module_content_id', $content->id)
            ->first();

        return [
            'is_completed' => $progress?->is_completed ?? false,
            'completion_percentage' => $progress?->completion_percentage ?? 0,
            'time_spent' => $progress?->time_spent ?? 0,
            'current_position' => $progress?->current_position ?? 0,
            'last_accessed' => $progress?->last_accessed_at?->toISOString(),
        ];
    }

    /**
     * Calculate total time spent on course
     */
    private function calculateTimeSpent(int $courseId, int $userId): int
    {
        return LearningSession::where('user_id', $userId)
            ->whereHas('moduleContent', function ($query) use ($courseId) {
                $query->whereHas('module', function ($subQuery) use ($courseId) {
                    $subQuery->where('course_online_id', $courseId);
                });
            })
            ->sum('total_duration_minutes') ?? 0;
    }

    /**
     * Get daily activity data
     */
    private function getDailyActivity(int $userId, $startDate): array
    {
        return LearningSession::where('user_id', $userId)
            ->where('session_start', '>=', $startDate)
            ->selectRaw('DATE(session_start) as date, COUNT(*) as sessions, SUM(total_duration_minutes) as total_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get content type breakdown
     */
    private function getContentBreakdown(int $userId, $startDate): array
    {
        return LearningSession::where('user_id', $userId)
            ->where('session_start', '>=', $startDate)
            ->join('module_content', 'learning_sessions.module_content_id', '=', 'module_content.id')
            ->selectRaw('module_content.content_type, COUNT(*) as sessions, SUM(total_duration_minutes) as total_time')
            ->groupBy('module_content.content_type')
            ->get()
            ->toArray();
    }

    /**
     * Get learning streaks
     */
    private function getLearningStreaks(int $userId): array
    {
        // Implement learning streak calculation
        // This would analyze consecutive days of learning activity
        return [
            'current_streak' => 0,
            'longest_streak' => 0,
            'streak_this_week' => 0,
            'streak_this_month' => 0,
        ];
    }

    /**
     * Get user achievements
     */
    private function getUserAchievements(int $userId): array
    {
        // Implement achievement system
        return [
            'courses_completed' => CourseOnlineAssignment::where('user_id', $userId)->where('status', 'completed')->count(),
            'total_hours' => $this->calculateTotalHours($userId),
            'perfect_scores' => 0, // Implement based on quiz scores
            'consistency_badges' => 0, // Based on learning streaks
        ];
    }

    /**
     * Calculate total learning hours
     */
    private function calculateTotalHours(int $userId): float
    {
        $totalMinutes = LearningSession::where('user_id', $userId)
            ->whereNotNull('session_end')
            ->sum('total_duration_minutes');

        return round($totalMinutes / 60, 1);
    }
}
