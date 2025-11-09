<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\LearningSessionService;
use App\Services\ContentView\ContentProgressService;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\LearningSession;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class LearningSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LearningSessionService $service;
    protected $progressService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock ContentProgressService
        $this->progressService = Mockery::mock(ContentProgressService::class);
        $this->service = new LearningSessionService($this->progressService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test 1: Start session creates new session successfully
     */
    public function test_start_session_creates_new_session()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create(['module_id' => $module->id]);

        // Act
        $session = $this->service->startSession($user, $content, 0);

        // Assert
        $this->assertInstanceOf(LearningSession::class, $session);
        $this->assertEquals($user->id, $session->user_id);
        $this->assertEquals($content->id, $session->content_id);
        $this->assertEquals(0, $session->current_position);
        $this->assertNull($session->session_end);
        $this->assertNotNull($session->session_start);
    }

    /**
     * Test 2: Start session ends existing active sessions
     */
    public function test_start_session_ends_existing_active_sessions()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create(['module_id' => $module->id]);
        
        // Create existing active session
        $existingSession = LearningSession::factory()->create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'session_end' => null,
        ]);

        // Act
        $newSession = $this->service->startSession($user, $content, 0);

        // Assert
        $existingSession->refresh();
        $this->assertNotNull($existingSession->session_end);
        $this->assertNotEquals($existingSession->id, $newSession->id);
    }

    /**
     * Test 3: Update heartbeat updates session data
     */
   public function test_update_heartbeat_updates_session_data()
{
    // Arrange
    $session = LearningSession::factory()->create([
        'video_watch_time' => 10,
        'video_skip_count' => 1,
        'seek_count' => 2,
        'pause_count' => 0,
    ]);

    // Act - Add incremental data
    $updated = $this->service->updateHeartbeat(
        $session->id,
        50.5,  // current position (kept for API compatibility, but not stored)
        5,     // watch time increment (+5 seconds)
        1,     // skip count increment (+1 skip)
        0,     // seek count increment
        2      // pause count increment (+2 pauses)
    );

    // Assert - Should accumulate
    // ✅ REMOVED: current_position check
    $this->assertEquals(15, $updated->video_watch_time); // 10 + 5
    $this->assertEquals(2, $updated->video_skip_count);  // 1 + 1
    $this->assertEquals(2, $updated->seek_count);        // 2 + 0
    $this->assertEquals(2, $updated->pause_count);       // 0 + 2
}


    /**
     * Test 4: Update heartbeat throws exception on ended session
     */
    public function test_update_heartbeat_throws_exception_on_ended_session()
    {
        // Arrange
        $session = LearningSession::factory()->create([
            'session_end' => now(),
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot update ended session');
        
        $this->service->updateHeartbeat($session->id, 50);
    }

    /**
     * Test 5: End session calculates scores correctly
     */
    public function test_end_session_calculates_scores()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $video = Video::factory()->create(['duration' => 600]); // 10 minutes
        $content = ModuleContent::factory()->withVideo($video)->create([
            'module_id' => $module->id,
        ]);
        
        $session = LearningSession::factory()->create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'course_online_id' => $course->id,
            'session_start' => now()->subMinutes(15),
            'video_watch_time' => 500,
            'video_skip_count' => 2,
        ]);

        // Act
        $ended = $this->service->endSession(
        $session->id,
        600,  // final position (kept for API, but not stored)
        95,   // completion %
        100,  // final watch time increment
        0,    // final skip increment
        0,    // final seek increment
        1     // final pause increment
    );

    // Assert
    $this->assertNotNull($ended->session_end);
    // ✅ REMOVED: current_position check
    $this->assertEquals(95, $ended->video_completion_percentage);
    $this->assertEquals(600, $ended->video_watch_time); // 500 + 100
    $this->assertEquals(2, $ended->video_skip_count);
    $this->assertNotNull($ended->attention_score);
    $this->assertNotNull($ended->cheating_score);
    $this->assertNotNull($ended->is_suspicious_activity);
    }

    /**
     * Test 6: Calculate attention score for good engagement
     */
    public function test_calculate_attention_score_for_good_engagement()
    {
        // Arrange
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $video = Video::factory()->create(['duration' => 600]); // 10 minutes
        $content = ModuleContent::factory()->withVideo($video)->create([
            'module_id' => $module->id,
        ]);

        // Act - Good engagement: 12 minutes for 10-minute video, 95% completion
        $score = $this->service->calculateAttentionScore(12, $content, 95);

        // Assert - Should have high score
        $this->assertGreaterThan(70, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test 7: Calculate cheating score detects suspicious activity
     */
    public function test_calculate_cheating_score_detects_suspicious_activity()
    {
        // Arrange
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $video = Video::factory()->create(['duration' => 600]); // 10 minutes
        $content = ModuleContent::factory()->withVideo($video)->create([
            'module_id' => $module->id,
        ]);

        // Act - Suspicious: 1 minute for 10-minute video, 100% completion, 20 skips
        $score = $this->service->calculateCheatingScore(1, $content, 20, 100);

        // Assert - Should have high cheating score
        $this->assertGreaterThan(80, $score);
    }

    /**
     * Test 8: Detect suspicious behavior returns true for impossible completion
     */
    public function test_detect_suspicious_behavior_for_impossible_completion()
    {
        // Arrange
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $video = Video::factory()->create(['duration' => 1200]); // 20 minutes
        $content = ModuleContent::factory()->withVideo($video)->create([
            'module_id' => $module->id,
        ]);

        // Act - 2 minutes to complete 20-minute video at 90%
        $isSuspicious = $this->service->detectSuspiciousBehavior(2, $content, 3, 90);

        // Assert
        $this->assertTrue($isSuspicious);
    }

    /**
     * Test 9: Get active session returns correct session
     */
    public function test_get_active_session_returns_correct_session()
    {
        // Arrange
        $user = User::factory()->create();
        $content = ModuleContent::factory()->create();
        
        $session = LearningSession::factory()->create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'session_end' => null,
        ]);

        // Act
        $active = $this->service->getActiveSession($user->id, $content->id);

        // Assert
        $this->assertNotNull($active);
        $this->assertEquals($session->id, $active->id);
    }

    /**
     * Test 10: Cleanup abandoned sessions
     */
    public function test_cleanup_abandoned_sessions()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Create abandoned session (3 hours old, no end)
        $abandoned = LearningSession::factory()->create([
            'user_id' => $user->id,
            'session_start' => now()->subHours(3),
            'session_end' => null,
        ]);

        // Act
        $count = $this->service->cleanupAbandonedSessions($user->id);

        // Assert
        $this->assertEquals(1, $count);
        $abandoned->refresh();
        $this->assertNotNull($abandoned->session_end);
    }
}
