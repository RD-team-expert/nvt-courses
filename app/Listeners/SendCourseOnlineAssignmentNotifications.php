<?php

namespace App\Listeners;

use App\Events\CourseOnlineAssigned;
use App\Mail\CourseOnlineAssignmentNotification;
use App\Mail\CourseOnlineAssignmentManagerNotification;
use App\Services\ManagerHierarchyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCourseOnlineAssignmentNotifications
{
    use InteractsWithQueue;

    protected ManagerHierarchyService $managerService;

    public function __construct(ManagerHierarchyService $managerService)
    {
        $this->managerService = $managerService;
    }

    /**
     * Handle the event
     */
    public function handle(CourseOnlineAssigned $event): void
{
    try {
        Log::info('🔔 CourseOnlineAssigned event received', [
            'user' => $event->user->name,
            'course' => $event->course->name,
            'skip_manager' => $event->metadata['skip_manager_notification'] ?? 'not set',
        ]);

        // ✅ STEP 1: Always send user notification
        $this->sendUserNotification($event);

        // ✅ STEP 2: Only send manager notification if NOT skipped
        $skipManager = $event->metadata['skip_manager_notification'] ?? false;
        Log::info('🔍 Checking manager notification', [
            'skip_manager_value' => $skipManager,
            'will_send' => !$skipManager,
        ]);

        if (!$skipManager) {
            Log::info('✅ Sending manager notification');
            $this->sendManagerNotification($event);
        } else {
            Log::warning('⚠️ Manager notification SKIPPED');
        }

    } catch (\Exception $e) {
        Log::error('❌ Notification failed', ['error' => $e->getMessage()]);
    }
}
    /**
     * Send notification to the assigned user
     */
    private function sendUserNotification(CourseOnlineAssigned $event): void
    {
        try {
           

            Mail::to($event->user->email)
                ->send(new CourseOnlineAssignmentNotification(
                    $event->course,
                    $event->user,
                    $event->assignedBy,
                    $event->loginLink,
                    $event->metadata
                ));

         

        } catch (\Exception $e) {
          
        }
    }

    /**
     * Send notification to user's manager(s)
     */
    private function sendManagerNotification(CourseOnlineAssigned $event): void
    {
        try {
            Log::info('📧 Starting manager notification process', [
                'user' => $event->user->name,
                'user_id' => $event->user->id,
            ]);

            // Skip if user has no department
            if (!$event->user->department) {
                Log::warning('⚠️ User has no department - skipping manager notification', [
                    'user' => $event->user->name,
                ]);
                return;
            }

            Log::info('✓ User has department', [
                'department' => $event->user->department->name,
            ]);

            // Get user's managers
            $managers = $this->managerService->getDirectManagersForUser($event->user->id);

            Log::info('🔍 Manager lookup result', [
                'managers_found' => count($managers),
            ]);

            if (empty($managers)) {
                Log::warning('⚠️ No managers found for user', [
                    'user' => $event->user->name,
                    'department' => $event->user->department->name,
                ]);
                return;
            }

            // Send notification to each manager
            foreach ($managers as $managerData) {
                $manager = $managerData['manager'];

                // ✅ Skip if manager is the same person as the user (self-managed)
                if ($manager->id === $event->user->id) {
                    Log::info('⏭️ Skipping self-manager notification', [
                        'user' => $event->user->name,
                        'reason' => 'User is their own manager',
                    ]);
                    continue;
                }

                Log::info('📤 Sending email to manager', [
                    'manager' => $manager->name,
                    'manager_email' => $manager->email,
                    'team_member' => $event->user->name,
                ]);

                try {
                    Mail::to($manager->email)
                        ->send(new CourseOnlineAssignmentManagerNotification(
                            $event->course,
                            collect([$event->user]), // Single user collection
                            $event->assignedBy,
                            $manager,
                            array_merge($event->metadata, [
                                'relationship' => $managerData['relationship'],
                                'level' => $managerData['level'],
                            ])
                        ));

                    Log::info('✅ Manager email sent successfully', [
                        'manager_email' => $manager->email,
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ Manager notification failed', [
                        'manager_email' => $manager->email,
                        'team_member' => $event->user->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // Rate limiting
                usleep(500000); // 0.5 second delay
            }

        } catch (\Exception $e) {
            Log::error('❌ Manager notification process failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
