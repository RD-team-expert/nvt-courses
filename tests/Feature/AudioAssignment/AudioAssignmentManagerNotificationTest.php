<?php

namespace Tests\Feature\AudioAssignment;

use App\Mail\AudioAssignmentManagerNotification;
use App\Models\Audio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 6: Manager notification correctness**
 * 
 * For any audio assignment where the user has a manager (who is not themselves), 
 * a manager notification email should be sent containing the team member's name and audio details.
 * 
 * **Validates: Requirements 4.1, 4.2, 4.3**
 */
class AudioAssignmentManagerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_email_contains_team_member_name(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create(['name' => 'Manager Smith']);
        $teamMember = User::factory()->create(['name' => 'Team Member Jones']);
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            collect([$teamMember]),
            $admin,
            $manager
        );

        $mailable->assertSeeInHtml('Team Member Jones');
    }

    public function test_manager_email_contains_audio_name(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $teamMember = User::factory()->create();
        $audio = Audio::factory()->create([
            'name' => 'Important Audio Training',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            collect([$teamMember]),
            $admin,
            $manager
        );

        $mailable->assertSeeInHtml('Important Audio Training');
    }

    public function test_manager_email_contains_audio_description(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $teamMember = User::factory()->create();
        $audio = Audio::factory()->create([
            'description' => 'This is a detailed audio description for managers.',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            collect([$teamMember]),
            $admin,
            $manager
        );

        $mailable->assertSeeInHtml('This is a detailed audio description for managers.');
    }

    public function test_manager_email_contains_manager_name(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create(['name' => 'Manager Johnson']);
        $teamMember = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            collect([$teamMember]),
            $admin,
            $manager
        );

        $mailable->assertSeeInHtml('Manager Johnson');
    }

    public function test_manager_email_contains_assigned_by_name(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Assigner']);
        $manager = User::factory()->create();
        $teamMember = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            collect([$teamMember]),
            $admin,
            $manager
        );

        $mailable->assertSeeInHtml('Admin Assigner');
    }

    public function test_manager_email_has_correct_subject_for_single_member(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $teamMember = User::factory()->create();
        $audio = Audio::factory()->create([
            'name' => 'Test Audio',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            collect([$teamMember]),
            $admin,
            $manager
        );

        $mailable->assertHasSubject('Team Member Assigned to Audio: Test Audio');
    }

    public function test_manager_email_has_correct_subject_for_multiple_members(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $teamMembers = User::factory()->count(3)->create();
        $audio = Audio::factory()->create([
            'name' => 'Test Audio',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            $teamMembers,
            $admin,
            $manager
        );

        $mailable->assertHasSubject('3 Team Members Assigned to Audio: Test Audio');
    }

    public function test_manager_email_contains_all_team_members(): void
    {
        $admin = User::factory()->create();
        $manager = User::factory()->create();
        $teamMembers = collect([
            User::factory()->create(['name' => 'Alice Member']),
            User::factory()->create(['name' => 'Bob Member']),
            User::factory()->create(['name' => 'Charlie Member']),
        ]);
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentManagerNotification(
            $audio,
            $teamMembers,
            $admin,
            $manager
        );

        $mailable->assertSeeInHtml('Alice Member');
        $mailable->assertSeeInHtml('Bob Member');
        $mailable->assertSeeInHtml('Charlie Member');
    }

    public function test_manager_email_content_for_multiple_random_audios(): void
    {
        $admin = User::factory()->create();
        
        // Property test: for any audio and team members, the manager email should contain their details
        for ($i = 0; $i < 3; $i++) {
            $manager = User::factory()->create();
            $teamMember = User::factory()->create();
            $audio = Audio::factory()->create([
                'created_by' => $admin->id,
            ]);

            $mailable = new AudioAssignmentManagerNotification(
                $audio,
                collect([$teamMember]),
                $admin,
                $manager
            );

            // Verify all required content is present
            $mailable->assertSeeInHtml($audio->name);
            $mailable->assertSeeInHtml($admin->name);
            
            if ($audio->description) {
                $mailable->assertSeeInHtml($audio->description);
            }
        }
    }

    /**
     * Property-based test: Manager notification correctness
     * 
     * For any audio assignment with random data, the manager notification email
     * should always contain the team member's name and audio details.
     */
    public function test_property_manager_notification_contains_required_information(): void
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $admin = User::factory()->create();
            $manager = User::factory()->create();
            
            // Generate random number of team members (1-3)
            $teamMemberCount = rand(1, 3);
            $teamMembers = User::factory()->count($teamMemberCount)->create();
            
            $audio = Audio::factory()->create([
                'created_by' => $admin->id,
            ]);

            $mailable = new AudioAssignmentManagerNotification(
                $audio,
                $teamMembers,
                $admin,
                $manager
            );

            // Property: Subject must reflect correct count and audio name
            if ($teamMemberCount === 1) {
                $mailable->assertHasSubject("Team Member Assigned to Audio: {$audio->name}");
            } else {
                $mailable->assertHasSubject("{$teamMemberCount} Team Members Assigned to Audio: {$audio->name}");
            }
            
            // Property: Email must contain all team member emails (reliable identifier)
            foreach ($teamMembers as $member) {
                $mailable->assertSeeInHtml($member->email);
            }
            
            // Property: Email must contain audio duration
            if ($audio->duration) {
                $mailable->assertSeeInHtml($audio->formatted_duration);
            }
        }
    }
}
