<?php

namespace Tests\Feature\AudioAssignment;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 9: Progress tracking persistence**
 * 
 * For any audio playback progress update, the progress_percentage should be 
 * persisted and retrievable.
 * 
 * **Validates: Requirements 5.4**
 */
class AudioProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_progress_percentage_is_persisted(): void
    {
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'duration' => 300, // 5 minutes
        ]);

        $assignment = AudioAssignment::create([
            'audio_id' => $audio->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Update progress to 50%
        $assignment->updateProgress(50);

        // Retrieve from database
        $assignment->refresh();

        $this->assertEquals(50, $assignment->progress_percentage);
        $this->assertEquals('in_progress', $assignment->status);
    }

    public function test_progress_marks_as_in_progress_when_started(): void
    {
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'duration' => 300,
        ]);

        $assignment = AudioAssignment::create([
            'audio_id' => $audio->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        $this->assertEquals('assigned', $assignment->status);

        // Update progress to 10%
        $assignment->updateProgress(10);
        $assignment->refresh();

        $this->assertEquals('in_progress', $assignment->status);
        $this->assertNotNull($assignment->started_at);
    }

    public function test_progress_marks_as_completed_when_100_percent(): void
    {
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'duration' => 300,
        ]);

        $assignment = AudioAssignment::create([
            'audio_id' => $audio->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Update progress to 100%
        $assignment->updateProgress(100);
        $assignment->refresh();

        $this->assertEquals(100, $assignment->progress_percentage);
        $this->assertEquals('completed', $assignment->status);
        $this->assertNotNull($assignment->completed_at);
    }

    public function test_progress_is_retrievable_after_update(): void
    {
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'duration' => 600,
        ]);

        $assignment = AudioAssignment::create([
            'audio_id' => $audio->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Update progress multiple times
        $assignment->updateProgress(25);
        $assignment->refresh();
        $this->assertEquals(25, $assignment->progress_percentage);

        $assignment->updateProgress(50);
        $assignment->refresh();
        $this->assertEquals(50, $assignment->progress_percentage);

        $assignment->updateProgress(75);
        $assignment->refresh();
        $this->assertEquals(75, $assignment->progress_percentage);
    }

    public function test_progress_clamps_to_valid_range(): void
    {
        $audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'duration' => 300,
        ]);

        $assignment = AudioAssignment::create([
            'audio_id' => $audio->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Test negative value
        $assignment->updateProgress(-10);
        $assignment->refresh();
        $this->assertEquals(0, $assignment->progress_percentage);

        // Test value over 100
        $assignment->updateProgress(150);
        $assignment->refresh();
        $this->assertEquals(100, $assignment->progress_percentage);
    }

    /**
     * Property-based test: Progress tracking persistence
     * 
     * For any audio playback progress update, the progress_percentage should be
     * persisted and retrievable from the database.
     */
    public function test_property_progress_always_persists_and_is_retrievable(): void
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $audio = Audio::factory()->create([
                'created_by' => $this->admin->id,
                'duration' => rand(60, 3600), // Random duration 1-60 minutes
            ]);

            $assignment = AudioAssignment::create([
                'audio_id' => $audio->id,
                'user_id' => $this->user->id,
                'assigned_by' => $this->admin->id,
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);

            // Generate random progress percentage
            $randomProgress = rand(0, 100);

            // Update progress
            $assignment->updateProgress($randomProgress);

            // Retrieve from database
            $retrievedAssignment = AudioAssignment::find($assignment->id);

            // Property: Progress must be persisted and retrievable
            $this->assertEquals(
                $randomProgress,
                $retrievedAssignment->progress_percentage,
                "Progress {$randomProgress}% should be persisted and retrievable"
            );

            // Property: Status must reflect progress
            if ($randomProgress >= 100) {
                $this->assertEquals('completed', $retrievedAssignment->status);
                $this->assertNotNull($retrievedAssignment->completed_at);
            } elseif ($randomProgress > 0) {
                $this->assertEquals('in_progress', $retrievedAssignment->status);
                $this->assertNotNull($retrievedAssignment->started_at);
            }
        }
    }
}
