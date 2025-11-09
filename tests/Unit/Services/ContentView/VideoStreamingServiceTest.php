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
            ->with($video->google_drive_url)
            ->andReturn($expectedResult);

        // Act
        $result = $this->service->getStreamingUrl($video, $content);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($expectedResult['streaming_url'], $result['streaming_url']);
        $this->assertEquals(1, $result['key_id']);
        $this->assertEquals('DRIVE_API_KEY_1', $result['key_name']);
        
        // Verify key stored in session
        $this->assertEquals(1, session('drive_key_id_' . $content->id));
        $this->assertEquals('DRIVE_API_KEY_1', session('drive_key_name_' . $content->id));
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
            'drive_key_id_' . $content->id => 5,
            'drive_key_name_' . $content->id => 'DRIVE_API_KEY_5',
        ]);

        $this->googleDriveService
            ->shouldReceive('releaseKey')
            ->once()
            ->with(5);

        // Act
        $this->service->releaseApiKey($content->id);

        // Assert
        $this->assertFalse(session()->has('drive_key_id_' . $content->id));
        $this->assertFalse(session()->has('drive_key_name_' . $content->id));
    }

    /**
     * Test 6: Check if content has assigned key
     */
    public function test_has_assigned_key_returns_true_when_key_exists()
    {
        // Arrange
        $content = ModuleContent::factory()->create();
        session(['drive_key_id_' . $content->id => 3]);

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
            'drive_key_id_' . $content->id => 2,
            'drive_key_name_' . $content->id => 'DRIVE_API_KEY_2',
        ]);

        // Act
        $status = $this->service->getKeyStatus($content->id);

        // Assert
        $this->assertTrue($status['has_key']);
        $this->assertEquals(2, $status['key_id']);
        $this->assertEquals('DRIVE_API_KEY_2', $status['key_name']);
    }
}
