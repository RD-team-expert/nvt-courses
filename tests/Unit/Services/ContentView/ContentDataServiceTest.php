<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\ContentDataService;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\Video;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContentDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentDataService();
    }

    /**
     * Test 1: Prepare content data
     */
    public function test_prepare_content_data()
{
    // Arrange
    $course = CourseOnline::factory()->create();
    $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
    $content = ModuleContent::factory()->video()->create([ // ✅ Use ->video() state
        'module_id' => $module->id,
        'title' => 'Test Content',
    ]);

    // Act
    $data = $this->service->prepareContentData($content);

    // Assert
    $this->assertEquals($content->id, $data['id']);
    $this->assertEquals('Test Content', $data['title']);
    $this->assertEquals('video', $data['content_type']);
    $this->assertArrayHasKey('module', $data);
    $this->assertEquals($module->id, $data['module']['id']);
}


    /**
     * Test 2: Prepare video data with streaming info
     */
    public function test_prepare_video_data_with_streaming_info()
    {
        // Arrange
        $video = Video::factory()->create(['name' => 'Test Video']);
        $streamingData = [
            'streaming_url' => 'https://example.com/stream',
            'key_id' => 1,
            'key_name' => 'API_KEY_1',
        ];

        // Act
        $data = $this->service->prepareVideoData($video, $streamingData);

        // Assert
        $this->assertNotNull($data);
        $this->assertEquals($video->id, $data['id']);
        $this->assertEquals('Test Video', $data['name']);
        $this->assertEquals('https://example.com/stream', $data['streaming_url']);
        $this->assertEquals(1, $data['key_id']);
    }

    /**
     * Test 3: Prepare video data returns null when no video
     */
    public function test_prepare_video_data_returns_null_when_no_video()
    {
        // Act
        $data = $this->service->prepareVideoData(null, null);

        // Assert
        $this->assertNull($data);
    }

    /**
     * Test 4: Prepare PDF data
     */
    public function test_prepare_pdf_data()
    {
        // Arrange
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create([
            'module_id' => $module->id,
            'content_type' => 'pdf',
            'pdf_page_count' => 50,
        ]);

        // Act
        $data = $this->service->preparePdfData($content);

        // Assert
        $this->assertNotNull($data);
        $this->assertEquals(50, $data['page_count']);
        $this->assertTrue($data['has_page_count']);
    }

    /**
     * Test 5: Prepare progress data with existing progress
     */
    public function test_prepare_progress_data_with_existing_progress()
    {
        // Arrange
        $progress = UserContentProgress::factory()->create([
            'completion_percentage' => 75,
            'playback_position' => 150,
            'is_completed' => false,
        ]);

        // Act
        $data = $this->service->prepareProgressData($progress);

        // Assert
        $this->assertTrue($data['exists']);
        $this->assertEquals(75, $data['completion_percentage']);
        $this->assertEquals(150, $data['playback_position']);
        $this->assertFalse($data['is_completed']);
    }

    /**
     * Test 6: Prepare progress data with no progress
     */
    public function test_prepare_progress_data_with_no_progress()
    {
        // Act
        $data = $this->service->prepareProgressData(null);

        // Assert
        $this->assertFalse($data['exists']);
        $this->assertEquals(0, $data['completion_percentage']);
        $this->assertEquals(0, $data['playback_position']);
        $this->assertFalse($data['is_completed']);
    }

    /**
     * Test 7: Prepare navigation data
     */
    public function test_prepare_navigation_data()
    {
        // Arrange
        $navigation = [
            'previous' => [
                'id' => 1,
                'title' => 'Previous Content',
                'content_type' => 'video',
                'is_completed' => true,
            ],
            'next' => [
                'id' => 3,
                'title' => 'Next Content',
                'content_type' => 'pdf',
                'is_unlocked' => true,
            ],
        ];

        // Act
        $data = $this->service->prepareNavigationData($navigation);

        // Assert
        $this->assertNotNull($data['previous']);
        $this->assertNotNull($data['next']);
        $this->assertEquals(1, $data['previous']['id']);
        $this->assertEquals('Previous Content', $data['previous']['title']);
        $this->assertTrue($data['previous']['is_completed']);
        $this->assertEquals(3, $data['next']['id']);
        $this->assertTrue($data['next']['is_unlocked']);
    }

    /**
     * Test 8: Build error response
     */
    public function test_build_error_response()
    {
        // Act
        $response = $this->service->buildErrorResponse('Access denied', 403);

        // Assert
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Access denied', $response['error']['message']);
        $this->assertEquals(403, $response['error']['code']);
    }
}
