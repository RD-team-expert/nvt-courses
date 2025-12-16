<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\ContentProgressService;
use App\Services\ContentView\LearningSessionService;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property-Based Test for Progress Persistence
 * 
 * Feature: video-quiz-tracking-updates, Property 5: Progress Persistence
 * 
 * Property: For any video session that is paused or where user logs out, 
 * the current timestamp position SHALL be persisted and restored when the user returns.
 * 
 * Validates: Requirements 3.1, 3.3, 3.4
 */
class ProgressPersistencePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected ContentProgressService $progressService;
    protected LearningSessionService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->progressService = new ContentProgressService();
        $this->sessionService = app(LearningSessionService::class);
    }

    /**
     * Property Test: Progress persistence across pause/resume cycles
     * 
     * For ANY video content and ANY valid playback position,
     * when a user pauses the video and later resumes,
     * the playback position MUST be persisted and restored correctly.
     * 
     * @test
     */
    public function test_property_progress_persists_across_pause_resume_cycles()
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random test data
            $videoDuration = $this->generateRandomVideoDuration(); // 60-3600 seconds
            $playbackPosition = $this->generateRandomPlaybackPosition($videoDuration);
            $completionPercentage = ($playbackPosition / $videoDuration) * 100;

            // Create test entities
            $user = User::factory()->create();
            $course = CourseOnline::factory()->create();
            $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
            $video = Video::factory()->create(['duration' => $videoDuration]);
            $content = ModuleContent::factory()->create([
                'module_id' => $module->id,
                'content_type' => 'video',
                'video_id' => $video->id,
                'duration' => $videoDuration,
            ]);

            // Step 1: User starts watching video
            $progress = $this->progressService->getOrCreateProgress($user, $content);
            
            // Step 2: User watches to a random position and pauses (simulating pause event)
            $updatedProgress = $this->progressService->updateProgress(
                $progress->id,
                $playbackPosition,
                $completionPercentage,
                (int) $playbackPosition // watch time equals position for simplicity
            );

            // Property Assertion 1: Position MUST be persisted after pause
            $this->assertEquals(
                $playbackPosition,
                $updatedProgress->playback_position,
                "Iteration {$iteration}: Playback position should be persisted after pause. " .
                "Expected: {$playbackPosition}, Got: {$updatedProgress->playback_position}"
            );

            // Step 3: Simulate user logging out (session ends, data persists in DB)
            $updatedProgress->refresh();

            // Property Assertion 2: Position MUST still be in database after refresh
            $this->assertEquals(
                $playbackPosition,
                $updatedProgress->playback_position,
                "Iteration {$iteration}: Playback position should persist in database. " .
                "Expected: {$playbackPosition}, Got: {$updatedProgress->playback_position}"
            );

            // Step 4: User returns and resumes (fetch progress again)
            $resumedProgress = $this->progressService->getOrCreateProgress($user, $content);

            // Property Assertion 3: Position MUST be restored when user returns
            $this->assertEquals(
                $playbackPosition,
                $resumedProgress->playback_position,
                "Iteration {$iteration}: Playback position should be restored on resume. " .
                "Expected: {$playbackPosition}, Got: {$resumedProgress->playback_position}"
            );

            // Property Assertion 4: Completion percentage MUST also be restored
            $this->assertEquals(
                round($completionPercentage, 2),
                round($resumedProgress->completion_percentage, 2),
                "Iteration {$iteration}: Completion percentage should be restored. " .
                "Expected: " . round($completionPercentage, 2) . ", Got: " . round($resumedProgress->completion_percentage, 2)
            );

            // Cleanup for next iteration
            $this->cleanupTestData($user, $course, $module, $content, $video, $progress);
        }
    }

    /**
     * Property Test: Progress persistence across multiple pause/resume cycles
     * 
     * For ANY video content and ANY sequence of playback positions,
     * each pause MUST persist the current position, and each resume MUST restore it.
     * 
     * @test
     */
    public function test_property_progress_persists_across_multiple_pause_resume_cycles()
    {
        // Run property test with 50 iterations (fewer because we do multiple cycles per iteration)
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $videoDuration = $this->generateRandomVideoDuration();
            
            // Create test entities
            $user = User::factory()->create();
            $course = CourseOnline::factory()->create();
            $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
            $video = Video::factory()->create(['duration' => $videoDuration]);
            $content = ModuleContent::factory()->create([
                'module_id' => $module->id,
                'content_type' => 'video',
                'video_id' => $video->id,
                'duration' => $videoDuration,
            ]);

            $progress = $this->progressService->getOrCreateProgress($user, $content);

            // Simulate 3-7 random pause/resume cycles
            $numCycles = rand(3, 7);
            $lastPosition = 0;

            for ($cycle = 0; $cycle < $numCycles; $cycle++) {
                // Generate a new position (always moving forward)
                $newPosition = $lastPosition + rand(10, (int)($videoDuration / $numCycles));
                $newPosition = min($newPosition, $videoDuration); // Don't exceed duration
                $completionPercentage = ($newPosition / $videoDuration) * 100;

                // Update progress (pause event)
                $progress = $this->progressService->updateProgress(
                    $progress->id,
                    $newPosition,
                    $completionPercentage,
                    (int) $newPosition
                );

                // Property Assertion: Position MUST be persisted after each pause
                $this->assertEquals(
                    $newPosition,
                    $progress->playback_position,
                    "Iteration {$iteration}, Cycle {$cycle}: Position should be persisted. " .
                    "Expected: {$newPosition}, Got: {$progress->playback_position}"
                );

                // Simulate resume (fetch progress again)
                $progress->refresh();
                $resumedProgress = $this->progressService->getOrCreateProgress($user, $content);

                // Property Assertion: Position MUST be restored after each resume
                $this->assertEquals(
                    $newPosition,
                    $resumedProgress->playback_position,
                    "Iteration {$iteration}, Cycle {$cycle}: Position should be restored on resume. " .
                    "Expected: {$newPosition}, Got: {$resumedProgress->playback_position}"
                );

                $lastPosition = $newPosition;
            }

            // Cleanup
            $this->cleanupTestData($user, $course, $module, $content, $video, $progress);
        }
    }

    /**
     * Property Test: Progress persistence with session interruption
     * 
     * For ANY video content and ANY playback position,
     * when a learning session is interrupted (user logs out),
     * the progress MUST be persisted and available when a new session starts.
     * 
     * @test
     */
    public function test_property_progress_persists_across_session_interruption()
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $videoDuration = $this->generateRandomVideoDuration();
            $playbackPosition = $this->generateRandomPlaybackPosition($videoDuration);
            $completionPercentage = ($playbackPosition / $videoDuration) * 100;

            // Create test entities
            $user = User::factory()->create();
            $course = CourseOnline::factory()->create();
            $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
            $video = Video::factory()->create(['duration' => $videoDuration]);
            $content = ModuleContent::factory()->create([
                'module_id' => $module->id,
                'content_type' => 'video',
                'video_id' => $video->id,
                'duration' => $videoDuration,
            ]);

            // Step 1: Start a learning session
            $session = $this->sessionService->startSession($user, $content, 0);
            $progress = $this->progressService->getOrCreateProgress($user, $content);

            // Step 2: User watches to a position
            $progress = $this->progressService->updateProgress(
                $progress->id,
                $playbackPosition,
                $completionPercentage,
                (int) $playbackPosition
            );

            // Step 3: Session ends (user logs out)
            $this->sessionService->endSession(
                $session->id,
                $playbackPosition,
                $completionPercentage
            );

            // Property Assertion 1: Progress MUST be persisted after session ends
            $progress->refresh();
            $this->assertEquals(
                $playbackPosition,
                $progress->playback_position,
                "Iteration {$iteration}: Progress should persist after session ends. " .
                "Expected: {$playbackPosition}, Got: {$progress->playback_position}"
            );

            // Step 4: User returns and starts a new session
            $newSession = $this->sessionService->startSession($user, $content, $progress->playback_position);
            $resumedProgress = $this->progressService->getOrCreateProgress($user, $content);

            // Property Assertion 2: Progress MUST be restored in new session
            $this->assertEquals(
                $playbackPosition,
                $resumedProgress->playback_position,
                "Iteration {$iteration}: Progress should be restored in new session. " .
                "Expected: {$playbackPosition}, Got: {$resumedProgress->playback_position}"
            );

            // Property Assertion 3: Completion percentage MUST also be restored
            $this->assertEquals(
                round($completionPercentage, 2),
                round($resumedProgress->completion_percentage, 2),
                "Iteration {$iteration}: Completion percentage should be restored in new session. " .
                "Expected: " . round($completionPercentage, 2) . ", Got: " . round($resumedProgress->completion_percentage, 2)
            );

            // Cleanup
            $session->forceDelete();
            $newSession->forceDelete();
            $this->cleanupTestData($user, $course, $module, $content, $video, $progress);
        }
    }

    /**
     * Generate random video duration between 60 and 3600 seconds (1-60 minutes)
     */
    private function generateRandomVideoDuration(): int
    {
        return rand(60, 3600);
    }

    /**
     * Generate random playback position within video duration
     * Ensures position is between 0 and duration
     */
    private function generateRandomPlaybackPosition(int $videoDuration): float
    {
        // Generate position between 0 and 95% of duration (not fully complete)
        $maxPosition = (int) ($videoDuration * 0.95);
        return (float) rand(0, $maxPosition);
    }

    /**
     * Cleanup test data to avoid interference between iterations
     */
    private function cleanupTestData(
        User $user,
        CourseOnline $course,
        CourseModule $module,
        ModuleContent $content,
        Video $video,
        UserContentProgress $progress
    ): void {
        $progress->forceDelete();
        $content->forceDelete();
        $video->forceDelete();
        $module->forceDelete();
        $course->forceDelete();
        $user->forceDelete();
    }
}
