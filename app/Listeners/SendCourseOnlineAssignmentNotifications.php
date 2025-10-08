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
        Log::info('🎧 Processing Course Online assignment notification', [
            'course_id' => $event->course->id,
            'course_name' => $event->course->name,
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'assigned_by' => $event->assignedBy->name,
        ]);

        try {
            // ✅ STEP 1: Send user notification
            $this->sendUserNotification($event);

            // ✅ STEP 2: Send manager notification (if applicable)
            $this->sendManagerNotification($event);

        } catch (\Exception $e) {
            Log::error('❌ Course Online assignment notification failed', [
                'course_id' => $event->course->id,
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send notification to the assigned user
     */
    private function sendUserNotification(CourseOnlineAssigned $event): void
    {
        try {
            Log::info('📧 Sending user notification', [
                'to_email' => $event->user->email,
                'course_name' => $event->course->name,
            ]);

            Mail::to($event->user->email)
                ->send(new CourseOnlineAssignmentNotification(
                    $event->course,
                    $event->user,
                    $event->assignedBy,
                    $event->loginLink,
                    $event->metadata
                ));

            Log::info('✅ User notification sent successfully', [
                'user_email' => $event->user->email,
                'course_name' => $event->course->name,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ User notification failed', [
                'user_email' => $event->user->email,
                'course_name' => $event->course->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to user's manager(s)
     */
    private function sendManagerNotification(CourseOnlineAssigned $event): void
    {
        try {
            // Skip if user has no department
            if (!$event->user->department) {
                Log::info('👤 User has no department, skipping manager notification', [
                    'user_id' => $event->user->id,
                    'user_email' => $event->user->email,
                ]);
                return;
            }

            // Get user's managers
            $managers = $this->managerService->getDirectManagersForUser($event->user->id);

            if (empty($managers)) {
                Log::info('👔 No managers found for user', [
                    'user_id' => $event->user->id,
                    'user_department' => $event->user->department->name,
                ]);
                return;
            }

            // Send notification to each manager
            foreach ($managers as $managerData) {
                $manager = $managerData['manager'];

                try {
                    Log::info('📧 Sending manager notification', [
                        'to_email' => $manager->email,
                        'manager_name' => $manager->name,
                        'team_member' => $event->user->name,
                        'course_name' => $event->course->name,
                    ]);

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

                    Log::info('✅ Manager notification sent successfully', [
                        'manager_email' => $manager->email,
                        'team_member' => $event->user->name,
                        'course_name' => $event->course->name,
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ Manager notification failed', [
                        'manager_email' => $manager->email,
                        'team_member' => $event->user->name,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Rate limiting
                usleep(500000); // 0.5 second delay
            }

        } catch (\Exception $e) {
            Log::error('❌ Manager notification process failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
