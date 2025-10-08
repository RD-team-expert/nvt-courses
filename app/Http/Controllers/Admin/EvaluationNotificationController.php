<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationHistory;
use App\Models\NotificationTemplate;
use App\Services\ManagerHierarchyService;
use App\Services\EvaluationEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EvaluationNotificationController extends Controller
{
    protected ManagerHierarchyService $hierarchyService;
    protected EvaluationEmailService $emailService;

    public function __construct(
        ManagerHierarchyService $hierarchyService,
        EvaluationEmailService  $emailService
    )
    {
        $this->hierarchyService = $hierarchyService;
        $this->emailService = $emailService;
    }

    /**
     * Display the evaluation notification dashboard
     */
    public function index(Request $request)
    {
        // Get filter options for dropdowns
        $departments = Department::select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $courses = Course::select(['id', 'name'])
            ->orderBy('name')
            ->get();

        // Get filters from request (works for both GET and POST)
        $filters = $request->only(['department_id', 'course_id', 'start_date', 'end_date', 'search']);

        // Get L1 employees with evaluations (apply filters)
        $employees = $this->getFilteredL1Employees($filters);

        // Get recent notification history
        $recentNotifications = NotificationTemplate::evaluationReports()
            ->with(['sentBy'])
            ->orderBy('sent_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'name' => $notification->name,
                    'department_name' => $notification->department_name,
                    'employee_count' => $notification->employee_count,
                    'managers_notified' => $notification->managers_notified,
                    'target_manager_level' => $notification->target_manager_level,
                    'status' => $notification->status,
                    'status_label' => $notification->getStatusLabel(),
                    'status_class' => $notification->getStatusBadgeClass(),
                    'sent_by' => $notification->sentBy?->name ?? 'Unknown',
                    'sent_at' => $notification->getFormattedSentDate(),
                ];
            });

        return Inertia::render('Admin/Evaluations/Notifications', [
            'employees' => $employees,
            'departments' => $departments,
            'courses' => $courses,
            'filters' => $filters,
            'recentNotifications' => $recentNotifications,
        ]);
    }

    /**
     * Filter L1 employees based on request (Inertia POST)
     */
    public function filterEmployees(Request $request)
    {
        // Validate filters
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'course_id' => 'nullable|exists:courses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
        ]);

        // Redirect to index with filters as query parameters
        return redirect()->route('admin.evaluations.notifications', $validated);
    }

    /**
     * Preview notification - show which managers will receive emails
     */
    public function previewNotification(Request $request)
    {
        \Log::info('Preview notification request received', $request->all());

        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:users,id',
            'target_manager_levels' => 'required|array|min:1',
            'target_manager_levels.*' => 'in:L2,L3,L4,all',
            'email_subject' => 'nullable|string|max:255'
        ]);

        \Log::info('Validation passed', $validated);

        try {
            // Validate that selected users are L1 employees
            $validation = $this->hierarchyService->validateL1Employees($validated['employee_ids']);
            \Log::info('L1 validation result', $validation);

            if (!empty($validation['invalid'])) {
                \Log::warning('Invalid employees found', $validation['invalid']);
                return back()->withErrors([
                    'employee_selection' => 'Some selected employees are not L1 level: ' .
                        collect($validation['invalid'])->pluck('name')->implode(', ')
                ]);
            }

            // Get preview data
            $preview = $this->hierarchyService->previewNotification(
                $validated['employee_ids'],
                $validated['target_manager_levels']
            );

            \Log::info('Preview data generated', $preview);

            // Add email subject to preview
            $preview['email_subject'] = $validated['email_subject'] ??
                'Evaluation Report - ' . ($preview['summary']['departments'][0] ?? 'Multiple Departments');

            \Log::info('Returning preview data to frontend');

            // FIXED: Use same data as index() method but add preview data
            // Get the same data as index method
            $departments = Department::select(['id', 'name'])->orderBy('name')->get();
            $courses = Course::select(['id', 'name'])->orderBy('name')->get();
            $filters = $request->only(['department_id', 'course_id', 'start_date', 'end_date', 'search']);
            $employees = $this->getFilteredL1Employees($filters);

            $recentNotifications = NotificationTemplate::evaluationReports()
                ->with(['sentBy'])
                ->orderBy('sent_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'name' => $notification->name,
                        'department_name' => $notification->department_name,
                        'employee_count' => $notification->employee_count,
                        'managers_notified' => $notification->managers_notified,
                        'target_manager_level' => $notification->target_manager_level,
                        'status' => $notification->status,
                        'status_label' => $notification->getStatusLabel(),
                        'status_class' => $notification->getStatusBadgeClass(),
                        'sent_by' => $notification->sentBy?->name ?? 'Unknown',
                        'sent_at' => $notification->getFormattedSentDate(),
                    ];
                });

            // Return complete page data WITH preview
            return Inertia::render('Admin/Evaluations/Notifications', [
                'employees' => $employees,
                'departments' => $departments,
                'courses' => $courses,
                'filters' => $filters,
                'recentNotifications' => $recentNotifications,
                'preview' => $preview,           // ADD THIS
                'showPreview' => true,          // ADD THIS
            ]);

        } catch (\Exception $e) {
            \Log::error('Preview notification failed', [
                'employee_ids' => $validated['employee_ids'],
                'target_levels' => $validated['target_manager_levels'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'preview' => 'Failed to generate preview: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Send notifications to managers
     */
    /**
     * Send notifications to managers
     */
    public function sendNotifications(Request $request)
    {
        Log::info('=== SEND NOTIFICATIONS STARTED ===');

        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:users,id',
            'target_manager_levels' => 'required|array|min:1',
            'target_manager_levels.*' => 'in:L2,L3,L4,all',
            'email_subject' => 'required|string|max:255',
            'custom_message' => 'nullable|string|max:1000'
        ]);

        Log::info('Validation passed');

        DB::beginTransaction();
        try {
            Log::info('=== STEP 1: Validate L1 employees ===');

            // Validate L1 employees again
            $validation = $this->hierarchyService->validateL1Employees($validated['employee_ids']);
            if (!empty($validation['invalid'])) {
                Log::warning('Invalid employees found');
                return back()->withErrors(['send' => 'Invalid employee selection.']);
            }

            Log::info('L1 validation passed');

            Log::info('=== STEP 2: Get employee data ===');
            // Get employee and evaluation data
            $employees = User::with(['evaluations.history', 'department'])
                ->whereIn('id', $validated['employee_ids'])
                ->get();

            Log::info('Employees loaded: ' . $employees->count());

            $evaluationIds = $employees->flatMap->evaluations->pluck('id')->toArray();
            $employeeNames = $employees->pluck('name')->toArray();
            $departmentName = $employees->first()->department?->name ?? 'Multiple Departments';

            Log::info('Data prepared - Evaluations: ' . count($evaluationIds) . ', Names: ' . count($employeeNames));

            Log::info('=== STEP 3: Create notification record ===');

            // Get the first employee's department_id
            $firstEmployee = $employees->first();
            $departmentId = $firstEmployee->department_id ?? null;

            // Create notification record
            $notification = NotificationTemplate::create([
                'name' => 'Evaluation Report - ' . $departmentName . ' - ' . now()->format('M d, Y'),
                'content' => json_encode([
                    'type' => 'evaluation_report',
                    'target_manager_level' => implode(',', $validated['target_manager_levels']),
                    'employee_count' => count($validated['employee_ids']),
                    'department_name' => $departmentName,
                    'email_subject' => $validated['email_subject'],
                    'custom_message' => $validated['custom_message'] ?? '',
                    'evaluation_ids' => $evaluationIds,
                    'employee_names' => $employeeNames,
                    'created_by' => auth()->id(),
                    'status' => 'pending'
                ]),
                'department_id' => $departmentId
            ]);

            Log::info('Notification created successfully: ' . $notification->id);

            Log::info('=== STEP 4: Set evaluation data (if methods exist) ===');

            // Set evaluation and employee data (only if methods exist)
            try {
                if (method_exists($notification, 'setEvaluationIdsArray')) {
                    $notification->setEvaluationIdsArray($evaluationIds);
                    Log::info('Evaluation IDs set');
                }

                if (method_exists($notification, 'setEmployeeNamesArray')) {
                    $notification->setEmployeeNamesArray($employeeNames);
                    Log::info('Employee names set');
                }
            } catch (\Exception $e) {
                Log::warning('Failed to set evaluation data: ' . $e->getMessage());
                // Continue anyway
            }

            Log::info('=== STEP 5: Get managers ===');

            // Get managers and send emails
            $managers = $this->hierarchyService->getManagersForEmployees(
                $validated['employee_ids'],
                $validated['target_manager_levels']
            );

            Log::info('Managers found: ' . json_encode($managers));

            Log::info('=== STEP 6: Send emails ===');

            // TEMPORARILY SKIP EMAIL SENDING TO ISOLATE THE ISSUE
//            $emailResults = [
//                'success_count' => 0,
//                'failed_count' => 0,
//                'sent_to' => [],
//                'failed_to' => []
//            ];

            $emailResults = $this->emailService->sendEvaluationReport(
                $managers,
                $employees,
                $validated['email_subject'],
                $validated['custom_message'] ?? ''
            );


            Log::info('Email results: ' . json_encode($emailResults));

            Log::info('=== STEP 7: Update notification status ===');

            // Update notification status based on email results
            try {
                if ($emailResults['success_count'] > 0) {
                    $managerEmails = collect($emailResults['sent_to'])->pluck('email')->toArray();
                    if (method_exists($notification, 'markAsSent')) {
                        $notification->markAsSent($emailResults['success_count'], $managerEmails);
                    } else {
                        // Update content manually
                        $content = json_decode($notification->content, true);
                        $content['status'] = 'sent';
                        $notification->update(['content' => json_encode($content)]);
                    }

                    if ($emailResults['failed_count'] > 0) {
                        $notification->update(['content' => json_encode(array_merge(json_decode($notification->content, true), ['status' => 'partial']))]);
                    }
                } else {
                    if (method_exists($notification, 'markAsFailed')) {
                        $notification->markAsFailed('All emails failed to send');
                    } else {
                        $content = json_decode($notification->content, true);
                        $content['status'] = 'failed';
                        $notification->update(['content' => json_encode($content)]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to update notification status: ' . $e->getMessage());
            }

            Log::info('=== STEP 8: Commit transaction ===');

            DB::commit();

            Log::info('=== SUCCESS: All steps completed ===');

            // Prepare success message
            $successMessage = "Evaluation notifications processed successfully! ";
            $successMessage .= "Processed {$emailResults['success_count']} managers";

            if ($emailResults['failed_count'] > 0) {
                $successMessage .= " ({$emailResults['failed_count']} failed)";
            }

            return redirect()->route('admin.evaluations.notifications')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== SEND NOTIFICATION FAILED ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());

            return back()->withErrors([
                'send' => 'Failed to send notifications: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Show notification history
     */
    public function history(Request $request)
    {
        $filters = $request->only(['department', 'status', 'date_from', 'date_to']);

        $query = NotificationTemplate::evaluationReports()
            ->with(['sentBy', 'department']);

        // Apply filters
        if (!empty($filters['department'])) {
            $query->where('department_name', 'like', '%' . $filters['department'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('sent_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('sent_at', '<=', $filters['date_to']);
        }

        $notifications = $query->orderBy('sent_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $notifications->getCollection()->transform(function ($notification) {
            return [
                'id' => $notification->id,
                'name' => $notification->name,
                'department_name' => $notification->department_name,
                'employee_count' => $notification->employee_count,
                'employee_names' => $notification->getEmployeeNamesArray(),
                'managers_notified' => $notification->managers_notified,
                'manager_emails' => $notification->getManagerEmailsArray(),
                'target_manager_level' => $notification->target_manager_level,
                'status' => $notification->status,
                'status_label' => $notification->getStatusLabel(),
                'status_class' => $notification->getStatusBadgeClass(),
                'sent_by' => $notification->sentBy?->name ?? 'Unknown',
                'sent_at' => $notification->getFormattedSentDate(),
                'email_subject' => $notification->email_subject,
                'failure_reason' => $notification->failure_reason
            ];
        });

        return Inertia::render('Admin/Evaluations/NotificationHistory', [
            'notifications' => $notifications,
            'filters' => $filters
        ]);
    }

    /**
     * Get notification details
     */
    public function show($id)
    {
        $notification = NotificationTemplate::with(['sentBy', 'department'])
            ->findOrFail($id);

        // Get evaluation details if available
        $evaluationIds = $notification->getEvaluationIdsArray();
        $evaluations = [];

        if (!empty($evaluationIds)) {
            $evaluations = Evaluation::with(['user', 'course', 'department', 'history']) // FIXED: Changed evaluationHistory to history
            ->whereIn('id', $evaluationIds)
                ->get()
                ->map(function ($evaluation) {
                    return [
                        'id' => $evaluation->id,
                        'user' => $evaluation->user,
                        'course' => $evaluation->course,
                        'department' => $evaluation->department,
                        'total_score' => $evaluation->total_score,
                        'incentive_amount' => $evaluation->incentive_amount,
                        'created_at' => $evaluation->created_at->format('M d, Y'),
                        'categories' => $evaluation->history->map(function ($history) { // FIXED: Changed evaluationHistory to history
                            return [
                                'category_name' => $history->category_name,
                                'type_name' => $history->type_name,
                                'score' => $history->score,
                                'comments' => $history->comments ?? ''
                            ];
                        })
                    ];
                });
        }

        return Inertia::render('Admin/Evaluations/NotificationDetails', [
            'notification' => [
                'id' => $notification->id,
                'name' => $notification->name,
                'department_name' => $notification->department_name,
                'employee_count' => $notification->employee_count,
                'employee_names' => $notification->getEmployeeNamesArray(),
                'managers_notified' => $notification->managers_notified,
                'manager_emails' => $notification->getManagerEmailsArray(),
                'target_manager_level' => $notification->target_manager_level,
                'status' => $notification->status,
                'status_label' => $notification->getStatusLabel(),
                'status_class' => $notification->getStatusBadgeClass(),
                'sent_by' => $notification->sentBy?->name ?? 'Unknown',
                'sent_at' => $notification->getFormattedSentDate(),
                'email_subject' => $notification->email_subject,
                'content' => $notification->content,
                'failure_reason' => $notification->failure_reason
            ],
            'evaluations' => $evaluations
        ]);
    }
    /**
     * Get filtered L1 employees with evaluations
     */
    /**
     * Get filtered L1 employees with evaluations
     */
    /**
     * Get filtered employees with evaluations (ALL LEVELS - not just L1)
     */
    private function getFilteredL1Employees(array $filters = []): array
    {
        // CHANGED: Get ALL employees with evaluations, not just L1
        $query = User::with(['userLevel', 'evaluations.course', 'department'])
            ->has('evaluations') // Only users with evaluations
            ->where('status', 'active'); // Only active users

        // Apply department filter
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Apply course filter (through evaluations)
        if (!empty($filters['course_id'])) {
            $query->whereHas('evaluations', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        // Apply date filters (through evaluations)
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $query->whereHas('evaluations', function ($q) use ($filters) {
                if (!empty($filters['start_date'])) {
                    $q->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (!empty($filters['end_date'])) {
                    $q->whereDate('created_at', '<=', $filters['end_date']);
                }
            });
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $employees = $query->get();

        return $employees->map(function ($employee) {
            $latestEvaluation = $employee->evaluations->sortByDesc('created_at')->first();

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'department' => $employee->department?->name ?? 'No Department',
                'department_id' => $employee->department?->id ?? null,
                'level' => $employee->userLevel?->name ?? 'Unknown',
                'evaluation_count' => $employee->evaluations->count(),
                'latest_evaluation' => $latestEvaluation ? [
                    'id' => $latestEvaluation->id,
                    'course_name' => $latestEvaluation->course?->name ?? 'Unknown Course',
                    'total_score' => $latestEvaluation->total_score,
                    'incentive_amount' => $latestEvaluation->incentive_amount,
                    'created_at' => $latestEvaluation->created_at->format('M d, Y')
                ] : null
            ];
        })->values()->toArray();
    }
}
