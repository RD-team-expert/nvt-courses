<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Feature tests for VideoStreamController
 * 
 * Tests local video streaming functionality including:
 * - Authentication requirements
 * - Storage type validation
 * - Byte-range request handling
 * - Error responses
 */
class VideoStreamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Video $localVideo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Set up fake storage
        Storage::fake('public');
        
        // Create a test video file
        $videoContent = str_repeat('0', 1024 * 100); // 100KB fake video
        Storage::disk('public')->put('videos/test-video.mp4', $videoContent);
        
        // Create local video record
        $this->localVideo = Video::factory()->create([
            'storage_type' => 'local',
            'file_path' => 'videos/test-video.mp4',
            'mime_type' => 'video/mp4',
            'name' => 'Test Video',
        ]);
    }

    /**
     * Test: Unauthenticated users cannot stream videos (redirected to login)
     */
    public function test_unauthenticated_user_cannot_stream_video(): void
    {
        $response = $this->get(route('video.stream', $this->localVideo));
        
        // Laravel redirects to login page for unauthenticated requests
        $response->assertRedirect();
    }

    /**
     * Test: Authenticated user can stream local video
     */
    public function test_authenticated_user_can_stream_local_video(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('video.stream', $this->localVideo));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Accept-Ranges', 'bytes');
    }

    /**
     * Test: Non-local videos return 400 error
     */
    public function test_cannot_stream_non_local_video(): void
    {
        $googleDriveVideo = Video::factory()->create([
            'storage_type' => 'google_drive',
            'google_drive_url' => 'https://drive.google.com/file/d/ABC123/view',
            'file_path' => null,
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('video.stream', $googleDriveVideo));
        
        $response->assertStatus(400);
    }

    /**
     * Test: Video without file path returns 404
     */
    public function test_video_without_file_path_returns_404(): void
    {
        $videoNoPath = Video::factory()->create([
            'storage_type' => 'local',
            'file_path' => null,
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('video.stream', $videoNoPath));
        
        $response->assertStatus(404);
    }

    /**
     * Test: Byte-range request returns 206 Partial Content
     */
    public function test_byte_range_request_returns_partial_content(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Range' => 'bytes=0-1023', // Request first 1KB
            ])
            ->get(route('video.stream', $this->localVideo));
        
        $response->assertStatus(206);
        $response->assertHeader('Content-Range');
        
        // Verify Content-Range format
        $contentRange = $response->headers->get('Content-Range');
        $this->assertMatchesRegularExpression('/^bytes \d+-\d+\/\d+$/', $contentRange);
    }

    /**
     * Test: Multiple byte-range requests work correctly (video seeking)
     */
    public function test_multiple_byte_range_requests_work(): void
    {
        // First chunk
        $response1 = $this->actingAs($this->user)
            ->withHeaders(['Range' => 'bytes=0-1023'])
            ->get(route('video.stream', $this->localVideo));
        
        $response1->assertStatus(206);
        
        // Middle chunk
        $response2 = $this->actingAs($this->user)
            ->withHeaders(['Range' => 'bytes=10240-20479'])
            ->get(route('video.stream', $this->localVideo));
        
        $response2->assertStatus(206);
        
        // Last chunk
        $response3 = $this->actingAs($this->user)
            ->withHeaders(['Range' => 'bytes=90000-102399'])
            ->get(route('video.stream', $this->localVideo));
        
        $response3->assertStatus(206);
    }

    /**
     * Test: Invalid range returns 416 Range Not Satisfiable
     */
    public function test_invalid_range_returns_416(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders([
                'Range' => 'bytes=1000000-2000000', // Beyond file size
            ])
            ->get(route('video.stream', $this->localVideo));
        
        $response->assertStatus(416);
    }

    /**
     * Test: Response includes correct CORS headers
     */
    public function test_response_includes_cors_headers(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('video.stream', $this->localVideo));
        
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
        $response->assertHeader('Access-Control-Allow-Headers', 'Range');
    }

    /**
     * Test: Response includes cache headers
     */
    public function test_response_includes_cache_headers(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('video.stream', $this->localVideo));
        
        $response->assertHeader('Cache-Control');
    }

    /**
     * Test: Video file not found in storage returns 404
     */
    public function test_missing_file_returns_404(): void
    {
        $videoMissingFile = Video::factory()->create([
            'storage_type' => 'local',
            'file_path' => 'videos/non-existent-video.mp4',
        ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('video.stream', $videoMissingFile));
        
        $response->assertStatus(404);
    }

    /**
     * Test: Concurrent requests don't cause issues
     * (Basic test - full concurrency testing done with k6)
     */
    public function test_rapid_sequential_requests_work(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($this->user)
                ->withHeaders(['Range' => 'bytes=0-1023'])
                ->get(route('video.stream', $this->localVideo));
            
            $response->assertStatus(206);
        }
    }
}
