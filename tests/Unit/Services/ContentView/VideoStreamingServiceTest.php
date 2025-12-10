<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\VideoStreamingService;
use App\Services\GoogleDriveService;
use App\Models\Video;
use App\Models\ModuleContent;
use App\Models\User;
use App\Models\VideoCategory;
use App\Models\CourseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class VideoStreamingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VideoStreamingService $service;
    protected $googleDriveService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock GoogleDriveService
        $this->googleDriveService = Mockery::mock(GoogleDriveService::class);
        $this->service = new VideoStreamingService($this->googleDriveService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test 1: Successfully get streaming URL with API key
     */
    public function test_get_streaming_url_returns_data_successfully()
    {
        // Arrange - Create video first
        $video = Video::factory()->create([
            'google_drive_url' => 'https://drive.google.com/file/d/ABC123/view',
        ]);
        
        // Create content with the video
        $content = ModuleContent::factory()->withVideo($video)->create();

        $expectedResult = [
            'streaming_url' => 'https://www.googleapis.com/drive/v3/files/ABC123?alt=media&key=TEST_KEY',
            'key_id' => 1,
            'key_name' => 'DRIVE_API_KEY_1',
            'file_id' => 'ABC123',
        ];

        $this->googleDriveService
            ->shouldReceive('processUrl')
            ->once()
            ->with($video->google_drive_url, false)  // Service now passes increment flag
            ->andReturn($expectedResult);

        // Act
        $result = $this->service->getStreamingUrl($video, $content);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($expectedResult['streaming_url'], $result['streaming_url']);
        $this->assertEquals(1, $result['key_id']);
        $this->assertEquals('DRIVE_API_KEY_1', $result['key_name']);
        
        // Verify key stored in session
        $this->assertEquals(1, session('content_' . $content->id . '_key_id'));
        $this->assertEquals('DRIVE_API_KEY_1', session('content_' . $content->id . '_key_name'));
    }

    /**
     * Test 2: Returns null when video has no URL
     */
   public function test_get_streaming_url_returns_null_when_video_has_no_url()
{
    // Arrange - Create video with empty string URL
    $video = Video::factory()->create([
        'google_drive_url' => '', // ✅ Empty string instead of null
    ]);
    $content = ModuleContent::factory()->create();

    // Act
    $result = $this->service->getStreamingUrl($video, $content);

    // Assert
    $this->assertNull($result);
}

    /**
     * Test 3: Returns null when all API keys are at capacity
     */
    public function test_get_streaming_url_returns_null_when_api_keys_full()
    {
        // Arrange
        $video = Video::factory()->create([
            'google_drive_url' => 'https://drive.google.com/file/d/ABC123/view',
        ]);
        
        $content = ModuleContent::factory()->withVideo($video)->create();

        $this->googleDriveService
            ->shouldReceive('processUrl')
            ->once()
            ->andReturn(null); // No keys available

        // Act
        $result = $this->service->getStreamingUrl($video, $content);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test 4: Process Google Drive PDF URL successfully
     */
    public function test_process_google_drive_pdf_url_successfully()
    {
        // Arrange
        $url = 'https://drive.google.com/file/d/PDF123/view';

        // Act
        $result = $this->service->processGoogleDrivePdfUrl($url);

        // Assert
        $this->assertEquals('https://drive.google.com/file/d/PDF123/preview', $result);
    }

    /**
     * Test 5: Release API key successfully
     */
    public function test_release_api_key_successfully()
    {
        // Arrange
        $content = ModuleContent::factory()->create();
        session([
            'content_' . $content->id . '_key_id' => 5,
            'content_' . $content->id . '_key_name' => 'DRIVE_API_KEY_5',
        ]);

        $this->googleDriveService
            ->shouldReceive('releaseKey')
            ->once()
            ->with(5);

        // Act
        $this->service->releaseApiKey($content->id);

        // Assert
        $this->assertFalse(session()->has('content_' . $content->id . '_key_id'));
        $this->assertFalse(session()->has('content_' . $content->id . '_key_name'));
    }

    /**
     * Test 6: Check if content has assigned key
     */
    public function test_has_assigned_key_returns_true_when_key_exists()
    {
        // Arrange
        $content = ModuleContent::factory()->create();
        session(['content_' . $content->id . '_key_id' => 3]);

        // Act
        $result = $this->service->hasAssignedKey($content->id);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test 7: Get key status returns correct data
     */
    public function test_get_key_status_returns_correct_data()
    {
        // Arrange
        $content = ModuleContent::factory()->create();
        session([
            'content_' . $content->id . '_key_id' => 2,
            'content_' . $content->id . '_key_name' => 'DRIVE_API_KEY_2',
        ]);

        // Act
        $status = $this->service->getKeyStatus($content->id);

        // Assert
        $this->assertTrue($status['has_key']);
        $this->assertEquals(2, $status['key_id']);
        $this->assertEquals('DRIVE_API_KEY_2', $status['key_name']);
    }

    // ========================================
    // LOCAL STORAGE TESTS
    // ========================================

    /**
     * Test 8: Local storage video returns correct streaming URL format
     */
    public function test_local_storage_video_returns_streaming_url()
    {
        // Arrange - Create local storage video
        $video = Video::factory()->create([
            'storage_type' => 'local',
            'file_path' => 'videos/test-video.mp4',
            'google_drive_url' => null,
        ]);
        
        $content = ModuleContent::factory()->withVideo($video)->create();

        // Act
        $result = $this->service->getStreamingUrl($video, $content);

        // Assert
        $this->assertNotNull($result);
        $this->assertArrayHasKey('streaming_url', $result);
        $this->assertNull($result['key_id']); // Local videos don't use API keys
        $this->assertEquals('local_storage', $result['key_name']);
        $this->assertStringContainsString('/video/stream/', $result['streaming_url']);
    }

    /**
     * Test 9: Local storage video with empty file path returns error
     */
    public function test_local_storage_with_empty_path_returns_error()
    {
        // Arrange - Create local video with no file path
        $video = Video::factory()->create([
            'storage_type' => 'local',
            'file_path' => null,
            'google_drive_url' => null,
        ]);
        
        $content = ModuleContent::factory()->create();

        // Act
        $result = $this->service->getStreamingUrl($video, $content);

        // Assert
        $this->assertNotNull($result);
        $this->assertNull($result['streaming_url']);
        $this->assertEquals('local_storage_error', $result['key_name']);
    }

    /**
     * Test 10: isLocalStorage returns correct values
     */
    public function test_is_local_storage_returns_correct_value()
    {
        // Arrange
        $content = ModuleContent::factory()->create();
        
        // Test with local storage session
        session([
            'content_' . $content->id . '_key_name' => 'local_storage',
        ]);

        // Act & Assert
        $this->assertTrue($this->service->isLocalStorage($content->id));
        
        // Test with Google Drive session
        $content2 = ModuleContent::factory()->create();
        session([
            'content_' . $content2->id . '_key_name' => 'DRIVE_API_KEY_1',
        ]);
        
        $this->assertFalse($this->service->isLocalStorage($content2->id));
    }

    /**
     * Test 11: getStorageType returns correct type
     */
    public function test_get_storage_type_returns_correct_type()
    {
        $content = ModuleContent::factory()->create();
        
        // Test local
        session(['content_' . $content->id . '_key_name' => 'local_storage']);
        $this->assertEquals('local', $this->service->getStorageType($content->id));
        
        // Test Google Drive
        $content2 = ModuleContent::factory()->create();
        session(['content_' . $content2->id . '_key_name' => 'DRIVE_API_KEY_1']);
        $this->assertEquals('google_drive', $this->service->getStorageType($content2->id));
        
        // Test unknown
        $content3 = ModuleContent::factory()->create();
        $this->assertEquals('unknown', $this->service->getStorageType($content3->id));
    }
}

