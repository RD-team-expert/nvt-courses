<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\AnalyticsDashboardService;
use App\Services\CourseAnalyticsService;
use App\Services\UserAnalyticsService;
use App\Services\CheatingDetectionService;
use App\Services\SessionAnalyticsService;
use App\Services\ExportAnalyticsService;
use App\Services\NotificationService;

class AnalyticsController extends Controller
{
    protected $dashboardService;
    protected $courseAnalyticsService;
    protected $userAnalyticsService;
    protected $cheatingDetectionService;
    protected $sessionAnalyticsService;
    protected $exportService;
    protected $notificationService;

    public function __construct(
        AnalyticsDashboardService $dashboardService,
        CourseAnalyticsService $courseAnalyticsService,
        UserAnalyticsService $userAnalyticsService,
        CheatingDetectionService $cheatingDetectionService,
        SessionAnalyticsService $sessionAnalyticsService,
        ExportAnalyticsService $exportService,
        NotificationService $notificationService
    ) {
        $this->dashboardService = $dashboardService;
        $this->courseAnalyticsService = $courseAnalyticsService;
        $this->userAnalyticsService = $userAnalyticsService;
        $this->cheatingDetectionService = $cheatingDetectionService;
        $this->sessionAnalyticsService = $sessionAnalyticsService;
        $this->exportService = $exportService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        Log::info('🔍 === ANALYTICS DASHBOARD START ===');

        $filters = $this->dashboardService->getDateFilters($request);
        $analytics = $this->dashboardService->getDashboardAnalytics($filters);

        return Inertia::render('Admin/Analytics/Dashboard', [
            'analytics' => $analytics,
            'filters' => $filters
        ]);
    }

    public function courseAnalytics(Request $request, CourseOnline $courseOnline = null)
    {
        if (!$courseOnline) {
            $courses = $this->courseAnalyticsService->getAllCourses();
            return Inertia::render('Admin/Analytics/CourseAnalytics', [
                'courses' => $courses,
            ]);
        }

        $data = $this->courseAnalyticsService->getCourseAnalyticsData($courseOnline);
        return Inertia::render('Admin/Analytics/CourseAnalytics', $data);
    }

    public function userAnalytics(Request $request)
    {
        Log::info('🔍 === USER ANALYTICS START ===');

        $filters = $request->only(['date_from', 'date_to', 'user_id', 'course_id']);
        $data = $this->userAnalyticsService->getUserAnalyticsData($request, $filters);

        return Inertia::render('Admin/Analytics/UserAnalytics', $data);
    }

    public function cheatingDetection(Request $request)
    {
        Log::info('🕵️‍♂️ === CHEATING DETECTION START ===');

        $filters = $request->only(['course_id', 'user_id', 'min_cheating_score']);
        $data = $this->cheatingDetectionService->getCheatingDetectionData($request, $filters);

        return Inertia::render('Admin/Analytics/CheatingDetection', $data);
    }

    public function sessionDetails(Request $request, $sessionId)
    {
        Log::info('🔍 === SESSION DETAILS INVESTIGATION START ===', ['session_id' => $sessionId]);

        try {
            $data = $this->sessionAnalyticsService->getSessionDetailsData($sessionId);
            return Inertia::render('Admin/Analytics/SessionDetails', $data);
        } catch (\Exception $e) {
            Log::error('🔍 Session details error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.analytics.cheating-detection')
                ->with('error', 'Unable to load session details: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        Log::info('📊 === EXPORT REQUEST START ===', [
            'type' => $request->get('type', 'suspicious_activity'),
            'admin' => auth()->user()->name,
        ]);

        try {
            $type = $request->get('type', 'suspicious_activity');
            $filters = $request->only(['course_id', 'user_id', 'min_cheating_score', 'date_from', 'date_to']);

            $result = $this->exportService->generateExport($type, $filters);

            return response($result['content'])
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('📊 Export failed', [
                'type' => $request->get('type'),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function sendWarningEmail(Request $request, User $user)
    {
        Log::info('📧 === SEND WARNING EMAIL START ===', [
            'user_id' => $user->id,
            'admin' => auth()->user()->name,
        ]);

        try {
            $result = $this->notificationService->sendWarningEmail($user, $request, auth()->user());

            return back()->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('📧 Failed to send warning email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to send warning email: ' . $e->getMessage());
        }
    }
}
