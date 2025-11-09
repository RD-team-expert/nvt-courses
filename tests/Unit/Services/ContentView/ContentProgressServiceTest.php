<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\ContentProgressService;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\CourseOnlineAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContentProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentProgressService();
    }

    /**
     * Test 1: Get or create progress - creates new record
     */
    public function test_get_or_create_progress_creates_new_record()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create(['module_id' => $module->id]);

        // Act
        $progress = $this->service->getOrCreateProgress($user, $content);

        // Assert
        $this->assertInstanceOf(UserContentProgress::class, $progress);
        $this->assertEquals($user->id, $progress->user_id);
        $this->assertEquals($content->id, $progress->content_id);
        $this->assertEquals(0, $progress->completion_percentage);
        $this->assertFalse($progress->is_completed);
    }

    /**
     * Test 2: Get or create progress - returns existing record
     */
    public function test_get_or_create_progress_returns_existing_record()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create(['module_id' => $module->id]);
        
        $existingProgress = UserContentProgress::factory()->create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'completion_percentage' => 50,
        ]);

        // Act
        $progress = $this->service->getOrCreateProgress($user, $content);

        // Assert
        $this->assertEquals($existingProgress->id, $progress->id);
        $this->assertEquals(50, $progress->completion_percentage);
    }

    /**
     * Test 3: Update progress successfully
     */
    public function test_update_progress_successfully()
{
    // Arrange
    $progress = UserContentProgress::factory()->create([
        'playback_position' => 10,
        'completion_percentage' => 25,
        'watch_time' => 0, // ✅ Start from 0
        'content_type' => 'video',
    ]);

    // Act
    $updated = $this->service->updateProgress(
        $progress->id,
        50,  // new position (jumped from 10 to 50 = 40 second jump)
        75,  // new completion %
        120  // watch time
    );

    // Assert
    $this->assertEquals(50, $updated->playback_position);
    $this->assertEquals(75, $updated->completion_percentage);
    
    // ✅ Skip detected: 40 second jump - 5 second buffer = 35 skipped seconds
    // Expected: 120 - 35 = 85
    $this->assertEquals(85, $updated->watch_time); // ✅ Changed from 120 to 85
    
    $this->assertFalse($updated->is_completed);
}

    /**
     * Test 4: Mark as completed sets to 100%
     */
    public function test_mark_as_completed_sets_to_100_percent()
    {
        // Arrange
        $progress = UserContentProgress::factory()->create([
            'completion_percentage' => 80,
            'is_completed' => false,
        ]);

        // Act
        $completed = $this->service->markAsCompleted($progress);

        // Assert
        $this->assertEquals(100, $completed->completion_percentage);
        $this->assertTrue($completed->is_completed);
        $this->assertNotNull($completed->completed_at);
    }

    /**
     * Test 5: Detect skip in video content
     */
    public function test_detect_skip_in_video_content()
    {
        // Arrange & Act
        $skipDetected = $this->service->detectSkip(10, 50, 'video'); // 40 second jump

        // Assert
        $this->assertTrue($skipDetected);
    }

    /**
     * Test 6: No skip detected for small jump
     */
    public function test_no_skip_detected_for_small_jump()
    {
        // Arrange & Act
        $skipDetected = $this->service->detectSkip(10, 20, 'video'); // 10 second jump

        // Assert
        $this->assertFalse($skipDetected);
    }

    /**
     * Test 7: Calculate course progress correctly
     */
    public function test_calculate_course_progress_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        
        // Create 4 content items
        $content1 = ModuleContent::factory()->create(['module_id' => $module->id]);
        $content2 = ModuleContent::factory()->create(['module_id' => $module->id]);
        $content3 = ModuleContent::factory()->create(['module_id' => $module->id]);
        $content4 = ModuleContent::factory()->create(['module_id' => $module->id]);
        
        // Complete 2 out of 4
        UserContentProgress::factory()->create([
            'user_id' => $user->id,
            'content_id' => $content1->id,
            'course_online_id' => $course->id,
            'is_completed' => true,
        ]);
        UserContentProgress::factory()->create([
            'user_id' => $user->id,
            'content_id' => $content2->id,
            'course_online_id' => $course->id,
            'is_completed' => true,
        ]);

        // Act
        $progress = $this->service->calculateCourseProgress($course->id, $user->id);

        // Assert
        $this->assertEquals(50.0, $progress); // 2/4 = 50%
    }

    /**
     * Test 8: Get progress stats returns correct data
     */
    public function test_get_progress_stats_returns_correct_data()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        
        UserContentProgress::factory()->create([
            'user_id' => $user->id,
            'course_online_id' => $course->id,
            'completion_percentage' => 100,
            'is_completed' => true,
            'watch_time' => 60,
        ]);
        UserContentProgress::factory()->create([
            'user_id' => $user->id,
            'course_online_id' => $course->id,
            'completion_percentage' => 50,
            'is_completed' => false,
            'watch_time' => 30,
        ]);

        // Act
        $stats = $this->service->getProgressStats($user->id, $course->id);

        // Assert
        $this->assertEquals(2, $stats['total_items']);
        $this->assertEquals(1, $stats['completed_items']);
        $this->assertEquals(75, $stats['avg_completion']);
        $this->assertEquals(90, $stats['total_watch_time_minutes']);
        $this->assertEquals(50, $stats['completion_rate']);
    }
}
