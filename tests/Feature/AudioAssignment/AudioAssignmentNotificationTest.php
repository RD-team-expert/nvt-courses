<?php

namespace Tests\Feature\AudioAssignment;

use App\Mail\AudioAssignmentNotification;
use App\Models\Audio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 4: User notification email content**
 * 
 * For any audio assignment, the user notification email should contain the audio name, 
 * description, duration, and a valid tokenized login link.
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3**
 */
class AudioAssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_contains_audio_name(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'name' => 'Test Audio Learning Material',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            'https://example.com/login/token123'
        );

        $mailable->assertSeeInHtml('Test Audio Learning Material');
    }

    public function test_email_contains_audio_description(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'description' => 'This is a detailed description of the audio content.',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            'https://example.com/login/token123'
        );

        $mailable->assertSeeInHtml('This is a detailed description of the audio content.');
    }

    public function test_email_contains_audio_duration(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'duration' => 300, // 5 minutes = 05:00
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            'https://example.com/login/token123'
        );

        // Duration should be formatted as MM:SS
        $mailable->assertSeeInHtml('05:00');
    }

    public function test_email_contains_login_link(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $loginLink = 'https://example.com/login/unique-token-123';

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            $loginLink
        );

        $mailable->assertSeeInHtml($loginLink);
    }

    public function test_email_contains_user_name(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['name' => 'John Doe']);
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            'https://example.com/login/token123'
        );

        $mailable->assertSeeInHtml('John Doe');
    }

    public function test_email_contains_assigned_by_name(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            'https://example.com/login/token123'
        );

        $mailable->assertSeeInHtml('Admin User');
    }

    public function test_email_has_correct_subject(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $audio = Audio::factory()->create([
            'name' => 'Important Audio',
            'created_by' => $admin->id,
        ]);

        $mailable = new AudioAssignmentNotification(
            $audio,
            $user,
            $admin,
            'https://example.com/login/token123'
        );

        $mailable->assertHasSubject('New Audio Assignment: Important Audio');
    }

    public function test_email_content_for_multiple_random_audios(): void
    {
        $admin = User::factory()->create();
        
        // Property test: for any audio, the email should contain its details
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $audio = Audio::factory()->create([
                'created_by' => $admin->id,
            ]);
            $loginLink = 'https://example.com/login/token-' . $i;

            $mailable = new AudioAssignmentNotification(
                $audio,
                $user,
                $admin,
                $loginLink
            );

            // Verify all required content is present
            $mailable->assertSeeInHtml($audio->name);
            $mailable->assertSeeInHtml($admin->name);
            $mailable->assertSeeInHtml($loginLink);
            
            if ($audio->description) {
                $mailable->assertSeeInHtml($audio->description);
            }
        }
    }
}
