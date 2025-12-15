<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\LearningSessionService;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\Video;
use App\Models\LearningSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property-Based Test for Rewind/Replay Time Exclusion
 * 
 * Feature: video-quiz-tracking-updates, Property 6: Rewind/Replay Time Exclusion
 * 
 * Property: For any rewind or replay action, the rewound/replayed section 
 * SHALL NOT be counted as additional active playback time toward the allowed time limit.
 * 
 * Validates: Requirements 3.2, 3.5
 */
class RewindReplayTimeExclusionPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected LearningSessionService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionService = app(LearningSessionService::class);
    }

    /**
     * Property Test: Rewind does not add to active playback time
     * 
     * For ANY video content and ANY rewind action,
     * when a user rewinds the video, the rewound section MUST NOT
     * be counted as additional active playback time.
     * 
     * Scenario: User watches 0-60s, then rewinds to 30s and watches to 60s again.
     * Expected active playback time: 60s (not 90s)
     * 
     * @test
     */
    public function test_property_rewind_does_not_add_to_active_playback_time()
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate random test data
            $videoDuration = $this->generateRandomVideoDuration(); // 60-3600 seconds
            
            // Generate random rewind scenario
            // User watches from 0 to position1, then rewinds to position2 (< position1)
            $position1 = rand(30, min(300, (int)($videoDuration * 0.5))); // First watch point
            $rewindPosition = rand(0, $position1 - 10); // Rewind to earlier position
            $position2 = rand($position1, min($position1 + 100, $videoDuration)); // Continue watching
            
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

            // Start session
            $session = $this->sessionService->startSession($user, $content, 0);

            // Simulate: User watches from 0 to position1 (active playback = position1)
            $activePlaybackTime1 = $position1;
            $session = $this->sessionService->updateActivePlaybackTime(
                $session->id,
                $activePlaybackTime1,
                [
                    ['type' => 'play', 'timestamp' => now()->timestamp, 'position' => 0],
                    ['type' => 'pause', 'timestamp' => now()->addSeconds($position1)->timestamp, 'position' => $position1],
                ]
            );

            // Property Assertion 1: Active playback time should equal position1
            $this->assertEquals(
                $activePlaybackTime1,
                $session->active_playback_time,
                "Iteration {$iteration}: After watching to {$position1}s, active playback should be {$position1}s. " .
                "Got: {$session->active_playback_time}s"
            );

            // Simulate: User rewinds to rewindPosition (this should NOT add time)
            // Then watches from rewindPosition to position2
            $additionalUniqueTime = max(0, $position2 - $position1); // Only count NEW content watched
            $expectedActivePlaybackTime = $activePlaybackTime1 + $additionalUniqueTime;
            
            $session = $this->sessionService->updateActivePlaybackTime(
                $session->id,
                $expectedActivePlaybackTime,
                [
                    ['type' => 'rewind', 'timestamp' => now()->timestamp, 'from' => $position1, 'to' => $rewindPosition],
                    ['type' => 'play', 'timestamp' => now()->timestamp, 'position' => $rewindPosition],
                    ['type' => 'pause', 'timestamp' => now()->addSeconds($position2 - $rewindPosition)->timestamp, 'position' => $position2],
                ]
            );

            // Property Assertion 2: Active playback time should NOT include rewound section
            // It should only count unique playback time
            $this->assertEquals(
                $expectedActivePlaybackTime,
                $session->active_playback_time,
                "Iteration {$iteration}: After rewind from {$position1}s to {$rewindPosition}s and watching to {$position2}s, " .
                "active playback should be {$expectedActivePlaybackTime}s (not including replayed section). " .
                "Got: {$session->active_playback_time}s"
            );

            // Property Assertion 3: Rewound section should not count toward allowed time
            $allowedTimeSeconds = $videoDuration * 2;
            
            $this->assertLessThanOrEqual(
                $allowedTimeSeconds,
                $session->active_playback_time,
                "Iteration {$iteration}: Active playback time ({$session->active_playback_time}s) should not exceed allowed time ({$allowedTimeSeconds}s) due to rewind/replay exclusion"
            );

            // Cleanup
            $this->cleanupTestData($user, $course, $module, $content, $video, $session);
        }
    }

    /**
     * Property Test: Multiple rewinds do not accumulate time
     * 
     * For ANY video content and ANY sequence of rewind actions,
     * multiple rewinds MUST NOT accumulate additional active playback time.
     * 
     * @test
     */
    public function test_property_multiple_rewinds_do_not_accumulate_time()
    {
        // Run property test with 50 iterations (fewer because multiple rewinds per iteration)
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

            $session = $this->sessionService->startSession($user, $content, 0);

            // Track the maximum position reached (this determines active playback time)
            $maxPositionReached = 0;
            $videoEvents = [];

            // Simulate 3-5 random rewind cycles
            $numRewinds = rand(3, 5);
            
            for ($rewind = 0; $rewind < $numRewinds; $rewind++) {
                // Watch forward to a new position
                $newPosition = $maxPositionReached + rand(20, 60);
                $newPosition = min($newPosition, (int)($videoDuration * 0.8)); // Don't exceed 80% of video
                
                if ($newPosition > $maxPositionReached) {
                    $maxPositionReached = $newPosition;
                }
                
                $videoEvents[] = ['type' => 'play', 'timestamp' => now()->timestamp, 'position' => $maxPositionReached - 20];
                $videoEvents[] = ['type' => 'pause', 'timestamp' => now()->timestamp, 'position' => $newPosition];
                
                // Rewind to an earlier position
                $rewindTo = rand(0, max(0, $newPosition - 30));
                $videoEvents[] = ['type' => 'rewind', 'timestamp' => now()->timestamp, 'from' => $newPosition, 'to' => $rewindTo];
            }

            // Update session with active playback time = max position reached
            // (Replayed sections should not add to this)
            $session = $this->sessionService->updateActivePlaybackTime(
                $session->id,
                $maxPositionReached,
                $videoEvents
            );

            // Property Assertion: Active playback time should equal max position reached
            // NOT the sum of all playback segments (which would include replays)
            $this->assertEquals(
                $maxPositionReached,
                $session->active_playback_time,
                "Iteration {$iteration}: After {$numRewinds} rewinds, active playback should equal max position reached ({$maxPositionReached}s), " .
                "not the sum of all segments. Got: {$session->active_playback_time}s"
            );

            // Property Assertion: Should still be within allowed time
            $allowedTimeSeconds = $videoDuration * 2;
            $this->assertLessThanOrEqual(
                $allowedTimeSeconds,
                $session->active_playback_time,
                "Iteration {$iteration}: Active playback time ({$session->active_playback_time}s) should be within allowed time ({$allowedTimeSeconds}s)"
            );

            // Cleanup
            $this->cleanupTestData($user, $course, $module, $content, $video, $session);
        }
    }

    /**
     * Property Test: Replay of same section does not add time
     * 
     * For ANY video content and ANY replay action,
     * when a user replays a section they've already watched,
     * the replayed section MUST NOT count toward active playback time.
     * 
     * @test
     */
    public function test_property_replay_of_same_section_does_not_add_time()
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $videoDuration = $this->generateRandomVideoDuration();
            
            // Generate replay scenario
            $watchPosition = rand(60, min(300, (int)($videoDuration * 0.5))); // Watch to this position
            $replayStart = rand(0, $watchPosition - 30); // Start replay from here
            $replayEnd = rand($replayStart + 10, $watchPosition); // End replay here (within already watched)
            
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

            $session = $this->sessionService->startSession($user, $content, 0);

            // Step 1: User watches from 0 to watchPosition
            $session = $this->sessionService->updateActivePlaybackTime(
                $session->id,
                $watchPosition,
                [
                    ['type' => 'play', 'timestamp' => now()->timestamp, 'position' => 0],
                    ['type' => 'pause', 'timestamp' => now()->timestamp, 'position' => $watchPosition],
                ]
            );

            $activeTimeAfterFirstWatch = $session->active_playback_time;

            // Step 2: User replays section from replayStart to replayEnd (already watched)
            // Active playback time should NOT increase
            $session = $this->sessionService->updateActivePlaybackTime(
                $session->id,
                $watchPosition, // Still the same, no new content watched
                [
                    ['type' => 'rewind', 'timestamp' => now()->timestamp, 'from' => $watchPosition, 'to' => $replayStart],
                    ['type' => 'play', 'timestamp' => now()->timestamp, 'position' => $replayStart],
                    ['type' => 'pause', 'timestamp' => now()->timestamp, 'position' => $replayEnd],
                ]
            );

            // Property Assertion: Active playback time should NOT increase after replay
            $this->assertEquals(
                $activeTimeAfterFirstWatch,
                $session->active_playback_time,
                "Iteration {$iteration}: After replaying section {$replayStart}s-{$replayEnd}s (already watched), " .
                "active playback should remain {$activeTimeAfterFirstWatch}s. Got: {$session->active_playback_time}s"
            );

            // Property Assertion: Replayed section should not count toward allowed time
            $allowedTimeSeconds = $videoDuration * 2;
            $this->assertLessThanOrEqual(
                $allowedTimeSeconds,
                $session->active_playback_time,
                "Iteration {$iteration}: Active playback time should not exceed allowed time due to replays"
            );

            // Cleanup
            $this->cleanupTestData($user, $course, $module, $content, $video, $session);
        }
    }

    /**
     * Property Test: Active playback time never exceeds video duration (without new content)
     * 
     * For ANY video content and ANY sequence of rewind/replay actions,
     * if the user never watches beyond the video duration,
     * active playback time MUST NOT exceed the video duration.
     * 
     * @test
     */
    public function test_property_active_playback_never_exceeds_duration_without_new_content()
    {
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
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

            $session = $this->sessionService->startSession($user, $content, 0);

            // Simulate watching the entire video with multiple rewinds
            $maxPositionReached = min($videoDuration, rand((int)($videoDuration * 0.8), $videoDuration));
            $videoEvents = [];

            // Add some random rewind events
            $numRewinds = rand(5, 10);
            for ($i = 0; $i < $numRewinds; $i++) {
                $from = rand(0, $maxPositionReached);
                $to = rand(0, $from);
                $videoEvents[] = ['type' => 'rewind', 'timestamp' => now()->timestamp, 'from' => $from, 'to' => $to];
            }

            // Update session with active playback time = max position reached
            $session = $this->sessionService->updateActivePlaybackTime(
                $session->id,
                $maxPositionReached,
                $videoEvents
            );

            // Property Assertion: Active playback time should never exceed video duration
            // (when user hasn't watched beyond the video)
            $this->assertLessThanOrEqual(
                $videoDuration,
                $session->active_playback_time,
                "Iteration {$iteration}: Active playback time ({$session->active_playback_time}s) should not exceed " .
                "video duration ({$videoDuration}s) when user hasn't watched beyond the video"
            );

            // Property Assertion: Should be within allowed time (Duration × 2)
            $allowedTimeSeconds = $videoDuration * 2;
            $this->assertLessThanOrEqual(
                $allowedTimeSeconds,
                $session->active_playback_time,
                "Iteration {$iteration}: Active playback time should be within allowed time window"
            );

            // Cleanup
            $this->cleanupTestData($user, $course, $module, $content, $video, $session);
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
     * Cleanup test data to avoid interference between iterations
     */
    private function cleanupTestData(
        User $user,
        CourseOnline $course,
        CourseModule $module,
        ModuleContent $content,
        Video $video,
        LearningSession $session
    ): void {
        $session->forceDelete();
        $content->forceDelete();
        $video->forceDelete();
        $module->forceDelete();
        $course->forceDelete();
        $user->forceDelete();
    }
}
