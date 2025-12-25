<?php

namespace Tests\Feature\AudioAssignment;

use App\Events\AudioAssigned;
use App\Listeners\SendAudioAssignmentNotifications;
use App\Mail\AudioAssignmentManagerNotification;
use App\Mail\AudioAssignmentNotification;
use App\Models\Audio;
use App\Models\Department;
use App\Models\User;
use App\Services\ManagerHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Mockery;

/**
 * **Feature: audio-assignment-system, Property 7: Self-manager notification skip**
 * 
 * For any audio assignment where the user is their own manager, 
 * no manager notification email should be sent.
 * 
 * **Validates: Requirements 4.4**
 */
class AudioAssignmentSelfManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_manager_does_not_receive_manager_notification(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $department = Department::factory()->create();
        
        // Create a user who is their own manager
        $selfManagedUser = User::factory()->create([
            'department_id' => $department->id,
        ]);
        
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        // Mock the ManagerHierarchyService to return the user as their own manager
        $mockManagerService = Mockery::mock(ManagerHierarchyService::class);
        $mockManagerService->shouldReceive('getDirectManagersForUser')
            ->with($selfManagedUser->id)
            ->andReturn([
                [
                    'manager' => $selfManagedUser, // User is their own manager
                    'relationship' => 'direct',
                    'level' => 1,
                ]
            ]);

        $listener = new SendAudioAssignmentNotifications($mockManagerService);

        $event = new AudioAssigned(
            $audio,
            $selfManagedUser,
            'https://example.com/login/token',
            $admin,
            []
        );

        $listener->handle($event);

        // User notification should be sent
        Mail::assertSent(AudioAssignmentNotification::class, function ($mail) use ($selfManagedUser) {
            return $mail->hasTo($selfManagedUser->email);
        });

        // Manager notification should NOT be sent (since user is their own manager)
        Mail::assertNotSent(AudioAssignmentManagerNotification::class);
    }

    public function test_regular_manager_receives_notification(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $department = Department::factory()->create();
        
        $manager = User::factory()->create([
            'department_id' => $department->id,
        ]);
        
        $teamMember = User::factory()->create([
            'department_id' => $department->id,
        ]);
        
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        // Mock the ManagerHierarchyService to return a different manager
        $mockManagerService = Mockery::mock(ManagerHierarchyService::class);
        $mockManagerService->shouldReceive('getDirectManagersForUser')
            ->with($teamMember->id)
            ->andReturn([
                [
                    'manager' => $manager, // Different person as manager
                    'relationship' => 'direct',
                    'level' => 1,
                ]
            ]);

        $listener = new SendAudioAssignmentNotifications($mockManagerService);

        $event = new AudioAssigned(
            $audio,
            $teamMember,
            'https://example.com/login/token',
            $admin,
            []
        );

        $listener->handle($event);

        // User notification should be sent
        Mail::assertSent(AudioAssignmentNotification::class, function ($mail) use ($teamMember) {
            return $mail->hasTo($teamMember->email);
        });

        // Manager notification SHOULD be sent
        Mail::assertSent(AudioAssignmentManagerNotification::class, function ($mail) use ($manager) {
            return $mail->hasTo($manager->email);
        });
    }

    public function test_skip_manager_notification_flag_works(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $department = Department::factory()->create();
        
        $manager = User::factory()->create([
            'department_id' => $department->id,
        ]);
        
        $teamMember = User::factory()->create([
            'department_id' => $department->id,
        ]);
        
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        // Mock the ManagerHierarchyService
        $mockManagerService = Mockery::mock(ManagerHierarchyService::class);
        // Should not even be called when skip flag is set
        $mockManagerService->shouldNotReceive('getDirectManagersForUser');

        $listener = new SendAudioAssignmentNotifications($mockManagerService);

        $event = new AudioAssigned(
            $audio,
            $teamMember,
            'https://example.com/login/token',
            $admin,
            ['skip_manager_notification' => true] // Skip flag set
        );

        $listener->handle($event);

        // User notification should still be sent
        Mail::assertSent(AudioAssignmentNotification::class, function ($mail) use ($teamMember) {
            return $mail->hasTo($teamMember->email);
        });

        // Manager notification should NOT be sent due to skip flag
        Mail::assertNotSent(AudioAssignmentManagerNotification::class);
    }

    /**
     * Property-based test: Self-manager notification skip
     * 
     * For any audio assignment where the user is their own manager,
     * no manager notification should be sent, but user notification should always be sent.
     */
    public function test_property_self_manager_never_receives_manager_notification(): void
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            Mail::fake();

            $admin = User::factory()->create();
            $department = Department::factory()->create();
            
            // Create a user who is their own manager
            $selfManagedUser = User::factory()->create([
                'department_id' => $department->id,
            ]);
            
            $audio = Audio::factory()->create([
                'created_by' => $admin->id,
            ]);

            // Mock the ManagerHierarchyService to return the user as their own manager
            $mockManagerService = Mockery::mock(ManagerHierarchyService::class);
            $mockManagerService->shouldReceive('getDirectManagersForUser')
                ->with($selfManagedUser->id)
                ->andReturn([
                    [
                        'manager' => $selfManagedUser, // User is their own manager
                        'relationship' => 'direct',
                        'level' => 1,
                    ]
                ]);

            $listener = new SendAudioAssignmentNotifications($mockManagerService);

            $event = new AudioAssigned(
                $audio,
                $selfManagedUser,
                'https://example.com/login/token',
                $admin,
                []
            );

            $listener->handle($event);

            // Property: User notification must always be sent
            Mail::assertSent(AudioAssignmentNotification::class, function ($mail) use ($selfManagedUser) {
                return $mail->hasTo($selfManagedUser->email);
            });

            // Property: Manager notification must never be sent when user is self-managed
            Mail::assertNotSent(AudioAssignmentManagerNotification::class);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
