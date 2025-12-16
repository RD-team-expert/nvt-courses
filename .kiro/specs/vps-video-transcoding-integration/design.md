# Design Document: VPS Video Transcoding Integration

## Overview

This design describes how the school project integrates with an external VPS-based video transcoding service. The integration follows a webhook-based asynchronous pattern where:

1. Admin uploads video via existing ChunkUploadController → Video saved locally
2. VideoController triggers transcoding request to VPS
3. VPS downloads video via VideoStreamController, transcodes to multiple qualities
4. VPS sends webhook callback with download URLs
5. School project downloads transcoded files and stores them locally

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         VIDEO TRANSCODING FLOW                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────┐    1. Upload Video    ┌──────────────────┐               │
│  │    Admin     │ ──────────────────────▶│  ChunkUploader   │               │
│  └──────────────┘    (chunked upload)    └────────┬─────────┘               │
│                                                   │                         │
│                                    2. VideoController.store()               │
│                                       triggers transcoding                  │
│                                                   │                         │
│                                    3. POST /api/transcode                   │
│                                    (video_id, video_url,                    │
│                                     callback_url, qualities)                │
│                                                   │                         │
│                                                   ▼                         │
│                                          ┌───────────────┐                  │
│                                          │  VPS Service  │                  │
│                                          └───────┬───────┘                  │
│                                                  │                          │
│                                    4. GET /video/stream/{video}             │
│                                       (downloads original)                  │
│                                    5. Transcode to 720p, 480p, 360p         │
│                                                  │                          │
│                                    6. POST callback_url                     │
│                                    (download_urls, status)                  │
│                                                  │                          │
│                                                  ▼                          │
│  ┌──────────────┐    8. Watch Video     ┌──────────────────┐               │
│  │    User      │ ◀─────────────────────│  School Project  │               │
│  └──────────────┘   (select quality)     └──────────────────┘               │
│                                    7. Download & store                      │
│                                       transcoded files                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Architecture

### Existing Project Structure

Your project already has these relevant components:
- `app/Http/Controllers/Admin/VideoController.php` - Handles video CRUD operations
- `app/Http/Controllers/Admin/ChunkUploadController.php` - Handles chunked video uploads
- `app/Http/Controllers/VideoStreamController.php` - Streams local videos to users
- `app/Models/Video.php` - Video model with `storage_type`, `file_path`, `file_size`
- `app/Services/VideoStorageService.php` - Manages video storage statistics
- `resources/js/pages/User/ContentViewer/Show.vue` - Video player component

### New Components to Add

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           SCHOOL PROJECT                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  EXISTING:                                                                  │
│  ┌─────────────────────┐     ┌─────────────────────────────────────────┐   │
│  │  VideoController    │     │  ChunkUploadController                  │   │
│  │  (Admin/Video)      │────▶│  (handles chunked uploads)              │   │
│  └─────────────────────┘     └─────────────────────────────────────────┘   │
│           │                                                                 │
│           │ NEW: After video creation                                       │
│           ▼                                                                 │
│  NEW COMPONENTS:                                                            │
│  ┌─────────────────────┐     ┌─────────────────────────────────────────┐   │
│  │  VpsTranscoding     │     │  VpsApiClient                           │   │
│  │  Service            │────▶│  - sendTranscodeRequest()               │   │
│  │  (app/Services/)    │     │  - downloadFile()                       │   │
│  └─────────────────────┘     └─────────────────────────────────────────┘   │
│                                              │                              │
│                                              ▼                              │
│  ┌─────────────────────┐     ┌─────────────────────────────────────────┐   │
│  │  TranscodeCallback  │     │  VideoQuality Model (NEW)               │   │
│  │  Controller (NEW)   │◀────│  - video_id, quality, file_path         │   │
│  │  (Webhook Handler)  │     │  - file_size                            │   │
│  └─────────────────────┘     └─────────────────────────────────────────┘   │
│                                                                             │
│  UPDATED:                                                                   │
│  ┌─────────────────────┐     ┌─────────────────────────────────────────┐   │
│  │  Video Model        │     │  VideoStreamController                  │   │
│  │  + transcode_status │     │  + streamQuality() method               │   │
│  │  + qualities()      │     │  (stream specific quality)              │   │
│  └─────────────────────┘     └─────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### External VPS Service

The VPS transcoding service (already deployed on your VPS) is a separate Laravel application that:
- Receives transcoding requests via REST API at `POST /api/transcode`
- Downloads videos from the school project via `GET /video/stream/{video}`
- Uses FFmpeg to transcode to multiple qualities (720p, 480p, 360p)
- Sends webhook callbacks when complete
- Provides download endpoints for transcoded files at `GET /api/download/{projectKey}/{videoId}/{quality}`

## Components and Interfaces

### 1. VpsApiClient Service (NEW)

Location: `app/Services/VpsApiClient.php`

Handles all HTTP communication with the VPS transcoding service.

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpsApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $projectKey;

    public function __construct()
    {
        $this->baseUrl = config('services.transcoding.url');
        $this->apiKey = config('services.transcoding.api_key');
        $this->projectKey = config('services.transcoding.project_key');
    }

    /**
     * Send transcoding request to VPS
     */
    public function sendTranscodeRequest(array $data): array
    {
        $response = Http::timeout(120)
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->post("{$this->baseUrl}/api/transcode", $data);

        if (!$response->successful()) {
            throw new \Exception("VPS request failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Download transcoded file from VPS
     */
    public function downloadFile(string $url, string $savePath): bool
    {
        // Create directory if needed
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $resource = fopen($savePath, 'wb');

        $response = Http::timeout(3600)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->withOptions(['sink' => $resource])
            ->get($url);

        fclose($resource);

        return $response->successful();
    }

    public function getProjectKey(): string
    {
        return $this->projectKey;
    }
}
```

### 2. VpsTranscodingService (NEW)

Location: `app/Services/VpsTranscodingService.php`

Orchestrates the transcoding workflow. Integrates with existing VideoController.

```php
<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VpsTranscodingService
{
    public function __construct(
        protected VpsApiClient $vpsClient
    ) {}

    /**
     * Request transcoding for a video (called from VideoController::store)
     * Only works for local storage videos
     */
    public function requestTranscoding(Video $video): bool
    {
        // Only transcode local videos
        if (!$video->isLocal()) {
            Log::info("Skipping transcoding for non-local video {$video->id}");
            return false;
        }

        try {
            $response = $this->vpsClient->sendTranscodeRequest([
                'video_id' => (string) $video->id,
                'video_url' => route('video.stream', $video->id), // Uses existing VideoStreamController
                'callback_url' => route('transcode.callback'),
                'qualities' => ['720p', '480p', '360p'],
            ]);

            $video->update(['transcode_status' => 'processing']);
            
            Log::info("Transcoding requested for video {$video->id}");
            return true;

        } catch (\Exception $e) {
            $video->update(['transcode_status' => 'failed']);
            Log::error("Transcoding request failed for video {$video->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle transcoding callback from VPS
     */
    public function handleCallback(array $data): bool
    {
        $video = Video::find($data['video_id']);
        
        if (!$video) {
            Log::error("Video not found for callback: {$data['video_id']}");
            return false;
        }

        if ($data['status'] === 'failed') {
            $video->update(['transcode_status' => 'failed']);
            Log::error("Transcoding failed for video {$video->id}: " . ($data['error'] ?? 'Unknown'));
            return false;
        }

        // Download each quality variant
        foreach ($data['download_urls'] as $quality => $url) {
            $this->downloadAndStoreQuality($video, $quality, $url);
        }

        $video->update(['transcode_status' => 'completed']);
        Log::info("Transcoding completed for video {$video->id}");
        return true;
    }

    /**
     * Download and store a quality variant
     */
    protected function downloadAndStoreQuality(Video $video, string $quality, string $url): void
    {
        $directory = "videos/transcoded/{$video->id}";
        $fileName = "{$quality}.mp4";
        $relativePath = "{$directory}/{$fileName}";
        $savePath = storage_path("app/public/{$relativePath}");

        // Create directory
        Storage::disk('public')->makeDirectory($directory);

        // Download file from VPS
        $success = $this->vpsClient->downloadFile($url, $savePath);

        if ($success && file_exists($savePath)) {
            // Create or update quality record
            VideoQuality::updateOrCreate(
                ['video_id' => $video->id, 'quality' => $quality],
                [
                    'file_path' => $relativePath,
                    'file_size' => filesize($savePath),
                ]
            );
            Log::info("Downloaded {$quality} for video {$video->id}");
        } else {
            Log::error("Failed to download {$quality} for video {$video->id}");
        }
    }

    /**
     * Retry failed transcoding
     */
    public function retryTranscoding(Video $video): bool
    {
        // Delete existing quality records
        $video->qualities()->delete();
        
        $video->update(['transcode_status' => 'pending']);
        return $this->requestTranscoding($video);
    }
}
```

### 3. TranscodeCallbackController (NEW)

Location: `app/Http/Controllers/TranscodeCallbackController.php`

Handles webhook callbacks from VPS.

```php
<?php

namespace App\Http\Controllers;

use App\Services\VpsTranscodingService;
use App\Services\VpsApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranscodeCallbackController extends Controller
{
    public function __construct(
        protected VpsTranscodingService $transcodingService,
        protected VpsApiClient $vpsClient
    ) {}

    /**
     * Handle webhook callback from VPS transcoding service
     * Route: POST /api/transcode/callback
     */
    public function handle(Request $request)
    {
        Log::info('Transcode callback received', $request->all());

        // Verify project key matches our configured key
        if ($request->project_key !== $this->vpsClient->getProjectKey()) {
            Log::warning('Invalid project key in transcode callback', [
                'received' => $request->project_key,
                'expected' => $this->vpsClient->getProjectKey(),
            ]);
            return response()->json(['error' => 'Invalid project key'], 403);
        }

        $success = $this->transcodingService->handleCallback($request->all());

        return response()->json(['success' => $success]);
    }
}
```

## Data Models

### Video Model Updates

Location: `app/Models/Video.php`

Add to existing Video model (which already has `storage_type`, `file_path`, `file_size`):

```php
// Add to $fillable array (existing fields shown for context)
protected $fillable = [
    'name',
    'description',
    'google_drive_url',
    'duration',
    'content_category_id',
    'thumbnail_path',
    'is_active',
    'created_by',
    'video_category_id',
    'storage_type',      // existing: 'google_drive' or 'local'
    'file_path',         // existing
    'file_size',         // existing
    'mime_type',         // existing
    'duration_seconds',  // existing
    'transcode_status',  // NEW: 'pending', 'processing', 'completed', 'failed'
];

// Add relationship to VideoQuality
public function qualities(): HasMany
{
    return $this->hasMany(VideoQuality::class);
}

// Add helper methods
public function hasMultipleQualities(): bool
{
    return $this->qualities()->count() > 0;
}

public function getAvailableQualities(): array
{
    $qualities = $this->qualities()->pluck('quality')->toArray();
    // Always include 'original' as an option
    return array_merge(['original'], $qualities);
}

public function getQualityPath(string $quality): ?string
{
    if ($quality === 'original') {
        return $this->file_path;
    }
    $qualityRecord = $this->qualities()->where('quality', $quality)->first();
    return $qualityRecord?->file_path;
}

public function isTranscoding(): bool
{
    return $this->transcode_status === 'processing';
}

public function isTranscodeComplete(): bool
{
    return $this->transcode_status === 'completed';
}

public function isTranscodeFailed(): bool
{
    return $this->transcode_status === 'failed';
}
```

### VideoQuality Model (NEW)

Location: `app/Models/VideoQuality.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VideoQuality extends Model
{
    protected $fillable = [
        'video_id',
        'quality',
        'file_path',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get formatted file size (e.g., "125.5 MB")
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Delete the physical file
     */
    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }
        return true;
    }
}
```

### Database Migration (NEW)

Location: `database/migrations/xxxx_xx_xx_create_video_qualities_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create video_qualities table
        Schema::create('video_qualities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('quality'); // '720p', '480p', '360p'
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();
            
            $table->unique(['video_id', 'quality']);
        });

        // Add transcode_status to videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->enum('transcode_status', ['pending', 'processing', 'completed', 'failed', 'skipped'])
                  ->default('pending')
                  ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_qualities');
        
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('transcode_status');
        });
    }
};
```

### Configuration

Location: `config/services.php`

```php
return [
    // ... existing services ...

    'transcoding' => [
        'url' => env('TRANSCODING_URL', 'http://your-vps-ip:2680'),
        'api_key' => env('TRANSCODING_API_KEY'),
        'project_key' => env('TRANSCODING_PROJECT_KEY', 'school_project'),
    ],
];
```

Location: `.env`

```
TRANSCODING_URL=http://your-vps-ip:2680
TRANSCODING_API_KEY=your-64-char-api-key-from-vps
TRANSCODING_PROJECT_KEY=school_project
```

## Routes

Add to `routes/web.php`:

```php
// Transcode callback (no auth - VPS calls this)
Route::post('/api/transcode/callback', [TranscodeCallbackController::class, 'handle'])
    ->name('transcode.callback');

// Admin: Retry transcoding
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/videos/{video}/retry-transcode', [VideoController::class, 'retryTranscode'])
        ->name('admin.videos.retry-transcode');
});

// User: Stream specific quality
Route::middleware(['auth'])->group(function () {
    Route::get('/video/stream/{video}/{quality?}', [VideoStreamController::class, 'stream'])
        ->name('video.stream.quality');
});
```

## Frontend Updates

### VideoStreamController Updates

Add quality parameter support to existing `VideoStreamController`:

```php
public function stream(Request $request, Video $video, ?string $quality = null)
{
    // ... existing auth checks ...

    // Determine which file to stream
    $filePath = $video->file_path; // Default to original
    
    if ($quality && $quality !== 'original') {
        $qualityPath = $video->getQualityPath($quality);
        if ($qualityPath) {
            $filePath = $qualityPath;
        }
    }

    // ... rest of existing streaming logic using $filePath ...
}
```

### ContentViewer/Show.vue Updates

Add quality selector to the video player:

```vue
<!-- Quality selector in video controls -->
<div v-if="availableQualities.length > 1" class="quality-selector">
    <select v-model="selectedQuality" @change="changeQuality">
        <option v-for="q in availableQualities" :key="q" :value="q">
            {{ q === 'original' ? 'Original' : q }}
        </option>
    </select>
</div>

<script setup>
// Add to existing script
const availableQualities = ref(['original'])
const selectedQuality = ref('original')

// Load available qualities from props
onMounted(() => {
    if (props.video?.available_qualities) {
        availableQualities.value = props.video.available_qualities
    }
    // Load saved preference
    const saved = localStorage.getItem('preferredVideoQuality')
    if (saved && availableQualities.value.includes(saved)) {
        selectedQuality.value = saved
    }
})

const changeQuality = () => {
    localStorage.setItem('preferredVideoQuality', selectedQuality.value)
    // Update video source
    const currentTime = videoElement.value?.currentTime || 0
    videoElement.value.src = `/video/stream/${props.video.id}/${selectedQuality.value}`
    videoElement.value.currentTime = currentTime
    videoElement.value.play()
}
</script>
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Transcoding request contains required fields
*For any* video that is sent for transcoding, the request to the VPS SHALL contain video_id, video_url, callback_url, and qualities array, and SHALL include the X-API-Key header.
**Validates: Requirements 1.1, 1.4**

### Property 2: Status transitions are valid
*For any* video, the transcode_status SHALL only transition through valid states: pending → processing → completed OR pending → processing → failed OR failed → pending (on retry).
**Validates: Requirements 1.2, 2.4, 2.5, 7.3**

### Property 3: Callback project_key verification
*For any* callback request received, if the project_key does not match the configured value, the request SHALL be rejected with a 403 status.
**Validates: Requirements 2.2, 5.2**

### Property 4: All quality variants are downloaded
*For any* successful transcoding callback containing N download URLs, exactly N quality variant files SHALL be downloaded and stored.
**Validates: Requirements 2.3**

### Property 5: File storage path structure
*For any* transcoded video quality file, the file SHALL be stored at the path: videos/transcoded/{video_id}/{quality}.mp4
**Validates: Requirements 6.1**

### Property 6: Video deletion cascades to quality files
*For any* video that is deleted, all associated quality variant files and database records SHALL be removed.
**Validates: Requirements 6.2**

### Property 7: Quality selection returns correct file
*For any* video with multiple quality variants, selecting a specific quality SHALL return the file path for that quality variant.
**Validates: Requirements 4.2**

### Property 8: Completed videos expose available qualities
*For any* video with transcode_status 'completed', the API response SHALL include the list of available quality variants.
**Validates: Requirements 3.1, 3.3**

## Error Handling

### VPS Communication Errors

| Error Type | Handling |
|------------|----------|
| Connection timeout | Set transcode_status to 'failed', log error |
| HTTP 401 (Unauthorized) | Log API key issue, set status to 'failed' |
| HTTP 5xx (Server error) | Set status to 'failed', allow retry |
| Download failure | Log error, continue with other qualities |

### Callback Errors

| Error Type | Handling |
|------------|----------|
| Invalid project_key | Return 403, do not process |
| Video not found | Return 404, log error |
| Download URL invalid | Log error, mark quality as failed |

## Integration with Existing Code

### VideoController Updates

In `app/Http/Controllers/Admin/VideoController.php`, update the `store()` method:

```php
// After: $video = Video::create($videoData);

// Trigger transcoding for local videos
if ($validated['storage_type'] === 'local') {
    app(VpsTranscodingService::class)->requestTranscoding($video);
}
```

Add retry method:

```php
public function retryTranscode(Video $video)
{
    if (!$video->isLocal()) {
        return back()->with('error', 'Only local videos can be transcoded');
    }

    $success = app(VpsTranscodingService::class)->retryTranscoding($video);

    return back()->with(
        $success ? 'success' : 'error',
        $success ? 'Transcoding restarted' : 'Failed to restart transcoding'
    );
}
```

### Video Model Updates

In `app/Models/Video.php`, update `deleteStoredFile()`:

```php
public function deleteStoredFile(): bool
{
    $deletedMain = true;
    $deletedThumb = true;
    $deletedQualities = true;

    // Delete main video file
    if ($this->isLocal() && $this->file_path) {
        if (Storage::disk('public')->exists($this->file_path)) {
            $deletedMain = Storage::disk('public')->delete($this->file_path);
        }
    }

    // Delete thumbnail
    if (!empty($this->thumbnail_path)) {
        if (Storage::disk('public')->exists($this->thumbnail_path)) {
            $deletedThumb = Storage::disk('public')->delete($this->thumbnail_path);
        }
    }

    // NEW: Delete all quality variants
    foreach ($this->qualities as $quality) {
        if (!$quality->deleteFile()) {
            $deletedQualities = false;
        }
    }
    
    // Delete quality directory
    $qualityDir = "videos/transcoded/{$this->id}";
    if (Storage::disk('public')->exists($qualityDir)) {
        Storage::disk('public')->deleteDirectory($qualityDir);
    }

    return $deletedMain && $deletedThumb && $deletedQualities;
}
```

## Testing Strategy

### Unit Testing

Unit tests will verify individual component behavior:
- VpsApiClient request formatting
- VpsTranscodingService state transitions
- VideoQuality model relationships
- File path generation

### Property-Based Testing

Using PHPUnit with data providers for property-based testing:

**Library:** PHPUnit with custom generators

**Test Configuration:** Each property test will run with at least 100 iterations using randomized inputs.

**Test Annotation Format:** Each property-based test will be tagged with:
```php
/**
 * **Feature: vps-video-transcoding-integration, Property {number}: {property_text}**
 */
```

### Integration Testing

Integration tests will verify:
- End-to-end transcoding flow (mocked VPS)
- Callback handling with various payloads
- File download and storage

### Test Files

| Test File | Purpose |
|-----------|---------|
| `tests/Unit/Services/VpsApiClientTest.php` | API client unit tests |
| `tests/Unit/Services/VpsTranscodingServiceTest.php` | Service logic tests |
| `tests/Feature/TranscodeCallbackTest.php` | Callback endpoint tests |
| `tests/Property/TranscodingPropertyTest.php` | Property-based tests |

