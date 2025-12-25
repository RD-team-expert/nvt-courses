<?php

namespace App\Listeners;

use App\Events\AudioAssigned;
use App\Mail\AudioAssignmentNotification;
use App\Mail\AudioAssignmentManagerNotification;
use App\Services\ManagerHierarchyService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAudioAssignmentNotifications
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
    public function handle(AudioAssigned $event): void
    {
        try {
            Log::info('🔔 AudioAssigned event received', [
                'user' => $event->user->name,
                'audio' => $event->audio->name,
                'skip_manager' => $event->metadata['skip_manager_notification'] ?? 'not set',
            ]);

            // Step 1: Always send user notification
            $this->sendUserNotification($event);

            // Step 2: Only send manager notification if NOT skipped
            $skipManager = $event->metadata['skip_manager_notification'] ?? false;
            
            if (!$skipManager) {
                $this->sendManagerNotification($event);
            } else {
                Log::info('⚠️ Manager notification SKIPPED for audio assignment');
            }

        } catch (\Exception $e) {
            Log::error('❌ Audio notification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send notification to the assigned user
     */
    private function sendUserNotification(AudioAssigned $event): void
    {
        try {
            Log::info('📧 Sending audio assignment email to user', [
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'user_email' => $event->user->email,
                'audio_name' => $event->audio->name,
                'login_link' => $event->loginLink,
            ]);

            Mail::to($event->user->email)
                ->send(new AudioAssignmentNotification(
                    $event->audio,
                    $event->user,
                    $event->assignedBy,
                    $event->loginLink,
                    $event->metadata
                ));

            Log::info('✅ Audio assignment user notification sent', [
                'user_email' => $event->user->email,
                'audio' => $event->audio->name,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Audio user notification failed', [
                'user_email' => $event->user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to user's manager(s)
     */
    private function sendManagerNotification(AudioAssigned $event): void
    {
        try {
            // Skip if user has no department
            if (!$event->user->department) {
                Log::warning('⚠️ User has no department - skipping manager notification', [
                    'user' => $event->user->name,
                ]);
                return;
            }

            // Get user's managers
            $managers = $this->managerService->getDirectManagersForUser($event->user->id);

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

                // Skip if manager is the same person as the user (self-managed)
                if ($manager->id === $event->user->id) {
                    Log::info('⏭️ Skipping self-manager notification for audio', [
                        'user' => $event->user->name,
                        'reason' => 'User is their own manager',
                    ]);
                    continue;
                }

                try {
                    Mail::to($manager->email)
                        ->send(new AudioAssignmentManagerNotification(
                            $event->audio,
                            collect([$event->user]),
                            $event->assignedBy,
                            $manager,
                            array_merge($event->metadata, [
                                'relationship' => $managerData['relationship'],
                                'level' => $managerData['level'],
                            ])
                        ));

                    Log::info('✅ Audio manager notification sent', [
                        'manager_email' => $manager->email,
                        'team_member' => $event->user->name,
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ Audio manager notification failed', [
                        'manager_email' => $manager->email,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Rate limiting
                usleep(500000); // 0.5 second delay
            }

        } catch (\Exception $e) {
            Log::error('❌ Audio manager notification process failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
