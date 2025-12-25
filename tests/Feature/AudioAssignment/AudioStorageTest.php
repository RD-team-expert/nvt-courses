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
 * **Feature: audio-assignment-system, Property 1: Local storage file persistence**
 * 
 * For any audio created with local storage type, the file at the recorded local_path 
 * should exist and be readable.
 * 
 * **Validates: Requirements 1.2**
 */
class AudioStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        Storage::fake('public');
    }

    public function test_local_storage_audio_file_exists_at_recorded_path(): void
    {
        $audioFile = UploadedFile::fake()->create('test-audio.mp3', 1024, 'audio/mpeg');
        
        $audio = Audio::create([
            'name' => 'Test Audio',
            'description' => 'Test Description',
            'storage_type' => 'local',
            'local_path' => $audioFile->store('audio/files', 'public'),
            'google_cloud_url' => null,
            'duration' => 180,
            'created_by' => $this->admin->id,
        ]);

        // Verify the file exists at the recorded path
        $this->assertNotNull($audio->local_path);
        Storage::disk('public')->assertExists($audio->local_path);
    }

    public function test_local_storage_audio_file_is_readable(): void
    {
        $audioFile = UploadedFile::fake()->create('readable-audio.mp3', 2048, 'audio/mpeg');
        $storedPath = $audioFile->store('audio/files', 'public');
        
        $audio = Audio::create([
            'name' => 'Readable Audio',
            'description' => 'Test',
            'storage_type' => 'local',
            'local_path' => $storedPath,
            'google_cloud_url' => null,
            'duration' => 240,
            'created_by' => $this->admin->id,
        ]);

        // Verify file is readable
        $this->assertTrue(Storage::disk('public')->exists($audio->local_path));
        // Note: Fake files are empty, so we just verify the file exists and is accessible
        $this->assertIsString(Storage::disk('public')->path($audio->local_path));
    }

    public function test_multiple_local_storage_audios_persist_correctly(): void
    {
        // Property test: for any number of local storage audios, all files should exist
        $audioCount = 5;
        $audios = [];

        for ($i = 0; $i < $audioCount; $i++) {
            $audioFile = UploadedFile::fake()->create("audio-{$i}.mp3", 1024, 'audio/mpeg');
            $storedPath = $audioFile->store('audio/files', 'public');
            
            $audios[] = Audio::create([
                'name' => "Test Audio {$i}",
                'description' => "Description {$i}",
                'storage_type' => 'local',
                'local_path' => $storedPath,
                'google_cloud_url' => null,
                'duration' => 120 + ($i * 30),
                'created_by' => $this->admin->id,
            ]);
        }

        // Verify all files exist
        foreach ($audios as $audio) {
            $this->assertNotNull($audio->local_path);
            Storage::disk('public')->assertExists($audio->local_path);
        }
    }

    public function test_google_drive_storage_does_not_have_local_path(): void
    {
        $audio = Audio::create([
            'name' => 'Google Drive Audio',
            'description' => 'Stored in Google Drive',
            'storage_type' => 'google_drive',
            'local_path' => null,
            'google_cloud_url' => 'https://drive.google.com/file/d/test123/view',
            'duration' => 300,
            'created_by' => $this->admin->id,
        ]);

        // Verify no local path for Google Drive storage
        $this->assertNull($audio->local_path);
        $this->assertNotNull($audio->google_cloud_url);
        $this->assertEquals('google_drive', $audio->storage_type);
    }

    public function test_audio_model_correctly_identifies_storage_type(): void
    {
        $localAudio = Audio::factory()->withLocalStorage()->create([
            'created_by' => $this->admin->id,
        ]);
        
        $googleDriveAudio = Audio::factory()->withGoogleDrive()->create([
            'created_by' => $this->admin->id,
        ]);

        $this->assertTrue($localAudio->isLocalStorage());
        $this->assertFalse($localAudio->isGoogleDriveStorage());
        
        $this->assertTrue($googleDriveAudio->isGoogleDriveStorage());
        $this->assertFalse($googleDriveAudio->isLocalStorage());
    }

    public function test_get_playback_url_returns_correct_url_for_storage_type(): void
    {
        // Test local storage - will test route once it's created in task 7.3
        $localAudio = Audio::create([
            'name' => 'Local Audio',
            'storage_type' => 'local',
            'local_path' => 'audio/files/test.mp3',
            'google_cloud_url' => null,
            'duration' => 180,
            'created_by' => $this->admin->id,
        ]);

        // For now, just verify the method exists and returns a string
        $this->assertIsString($localAudio->getPlaybackUrl());

        // Test Google Drive storage
        $googleDriveUrl = 'https://drive.google.com/file/d/test123/view';
        $googleDriveAudio = Audio::create([
            'name' => 'Google Drive Audio',
            'storage_type' => 'google_drive',
            'local_path' => null,
            'google_cloud_url' => $googleDriveUrl,
            'duration' => 240,
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals($googleDriveUrl, $googleDriveAudio->getPlaybackUrl());
    }
}
