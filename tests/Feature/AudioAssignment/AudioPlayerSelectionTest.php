<?php

namespace Tests\Feature\AudioAssignment;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 8: Storage type player selection**
 * 
 * For any audio, the player should load the correct source URL based on storage_type 
 * (google_drive_url for Google Drive, local streaming endpoint for local).
 * 
 * **Validates: Requirements 5.1**
 */
class AudioPlayerSelectionTest extends TestCase
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

    public function test_local_storage_audio_returns_streaming_endpoint(): void
    {
        Storage::fake('public');
        
        $audioFile = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');
        $storedPath = $audioFile->store('audio/files', 'public');
        
        $audio = Audio::create([
            'name' => 'Local Audio',
            'storage_type' => 'local',
            'local_path' => $storedPath,
            'google_cloud_url' => null,
            'duration' => 180,
            'created_by' => $this->admin->id,
        ]);

        $playbackUrl = $audio->getPlaybackUrl();
        
        // Should contain the streaming route
        $this->assertStringContainsString('/audio/', $playbackUrl);
        $this->assertStringContainsString('/stream', $playbackUrl);
        $this->assertStringContainsString((string)$audio->id, $playbackUrl);
    }

    public function test_google_drive_audio_returns_google_drive_url(): void
    {
        $googleDriveUrl = 'https://drive.google.com/file/d/abc123xyz/view';
        
        $audio = Audio::create([
            'name' => 'Google Drive Audio',
            'storage_type' => 'google_drive',
            'local_path' => null,
            'google_cloud_url' => $googleDriveUrl,
            'duration' => 240,
            'created_by' => $this->admin->id,
        ]);

        $playbackUrl = $audio->getPlaybackUrl();
        
        // Should return the Google Drive URL directly
        $this->assertEquals($googleDriveUrl, $playbackUrl);
    }

    public function test_player_selection_for_multiple_storage_types(): void
    {
        Storage::fake('public');
        
        // Property test: for any audio, the correct playback URL should be returned based on storage_type
        $testCases = [
            [
                'storage_type' => 'local',
                'local_path' => UploadedFile::fake()->create('audio1.mp3', 1024)->store('audio/files', 'public'),
                'google_cloud_url' => null,
                'expected_contains' => '/stream',
            ],
            [
                'storage_type' => 'google_drive',
                'local_path' => null,
                'google_cloud_url' => 'https://drive.google.com/file/d/test1/view',
                'expected_contains' => 'drive.google.com',
            ],
            [
                'storage_type' => 'local',
                'local_path' => UploadedFile::fake()->create('audio2.mp3', 2048)->store('audio/files', 'public'),
                'google_cloud_url' => null,
                'expected_contains' => '/stream',
            ],
            [
                'storage_type' => 'google_drive',
                'local_path' => null,
                'google_cloud_url' => 'https://drive.google.com/file/d/test2/view',
                'expected_contains' => 'drive.google.com',
            ],
        ];

        foreach ($testCases as $index => $testCase) {
            $audio = Audio::create([
                'name' => "Test Audio {$index}",
                'storage_type' => $testCase['storage_type'],
                'local_path' => $testCase['local_path'],
                'google_cloud_url' => $testCase['google_cloud_url'],
                'duration' => 180 + ($index * 30),
                'created_by' => $this->admin->id,
            ]);

            $playbackUrl = $audio->getPlaybackUrl();
            
            $this->assertStringContainsString(
                $testCase['expected_contains'],
                $playbackUrl,
                "Audio {$index} with storage_type '{$testCase['storage_type']}' should return URL containing '{$testCase['expected_contains']}'"
            );
        }
    }

    public function test_streaming_endpoint_requires_authentication(): void
    {
        Storage::fake('public');
        
        $audioFile = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');
        $storedPath = $audioFile->store('audio/files', 'public');
        
        $audio = Audio::create([
            'name' => 'Protected Audio',
            'storage_type' => 'local',
            'local_path' => $storedPath,
            'google_cloud_url' => null,
            'duration' => 180,
            'created_by' => $this->admin->id,
        ]);

        // Unauthenticated request should redirect to login
        $response = $this->get(route('audio.stream', $audio->id));
        $response->assertRedirect(route('login'));
    }

    public function test_streaming_endpoint_requires_assignment_or_admin(): void
    {
        Storage::fake('public');
        
        $audioFile = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');
        $storedPath = $audioFile->store('audio/files', 'public');
        
        $audio = Audio::create([
            'name' => 'Restricted Audio',
            'storage_type' => 'local',
            'local_path' => $storedPath,
            'google_cloud_url' => null,
            'duration' => 180,
            'created_by' => $this->admin->id,
        ]);

        // User without assignment should get 403
        $response = $this->actingAs($this->user)
            ->get(route('audio.stream', $audio->id));
        $response->assertStatus(403);

        // Admin should have access
        $response = $this->actingAs($this->admin)
            ->get(route('audio.stream', $audio->id));
        $response->assertStatus(200);

        // User with assignment should have access
        AudioAssignment::create([
            'audio_id' => $audio->id,
            'user_id' => $this->user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('audio.stream', $audio->id));
        $response->assertStatus(200);
    }

    /**
     * Property-based test: Storage type player selection
     * 
     * For any audio with random storage type, the getPlaybackUrl() method should
     * return the correct URL format based on storage_type.
     */
    public function test_property_storage_type_always_returns_correct_url_format(): void
    {
        Storage::fake('public');
        
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Randomly choose storage type
            $storageType = rand(0, 1) === 0 ? 'local' : 'google_drive';
            
            if ($storageType === 'local') {
                $audioFile = UploadedFile::fake()->create("audio-{$iteration}.mp3", rand(512, 2048), 'audio/mpeg');
                $storedPath = $audioFile->store('audio/files', 'public');
                
                $audio = Audio::create([
                    'name' => "Test Audio {$iteration}",
                    'storage_type' => 'local',
                    'local_path' => $storedPath,
                    'google_cloud_url' => null,
                    'duration' => rand(60, 600),
                    'created_by' => $this->admin->id,
                ]);

                $playbackUrl = $audio->getPlaybackUrl();
                
                // Property: Local storage must return streaming endpoint URL
                $this->assertStringContainsString('/audio/', $playbackUrl);
                $this->assertStringContainsString('/stream', $playbackUrl);
                $this->assertStringContainsString((string)$audio->id, $playbackUrl);
                
            } else {
                $googleDriveUrl = "https://drive.google.com/file/d/test{$iteration}xyz/view";
                
                $audio = Audio::create([
                    'name' => "Test Audio {$iteration}",
                    'storage_type' => 'google_drive',
                    'local_path' => null,
                    'google_cloud_url' => $googleDriveUrl,
                    'duration' => rand(60, 600),
                    'created_by' => $this->admin->id,
                ]);

                $playbackUrl = $audio->getPlaybackUrl();
                
                // Property: Google Drive storage must return the exact Google Drive URL
                $this->assertEquals($googleDriveUrl, $playbackUrl);
                $this->assertStringContainsString('drive.google.com', $playbackUrl);
            }
        }
    }
}
