# Video Quality Control Implementation Guide

## Overview

This document explains how to add YouTube-like video quality selection to your course platform. Users will be able to choose video quality (1080p, 720p, 480p, 360p, Auto) when watching videos.

---

## Current System Analysis

### How Videos Work Now

```
┌─────────────────────────────────────────────────────────────────┐
│                    CURRENT VIDEO FLOW                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Admin uploads video (Create.vue)                            │
│     ↓                                                           │
│  2. ChunkUploader sends to ChunkUploadController.php            │
│     ↓                                                           │
│  3. Video saved to: storage/app/public/videos/                  │
│     (Single file, single quality - original upload)             │
│     ↓                                                           │
│  4. User watches video (ContentViewer/Show.vue)                 │
│     ↓                                                           │
│  5. VideoStreamController.php streams the single file           │
│                                                                 │
│  PROBLEM: Only ONE quality available (whatever was uploaded)    │
└─────────────────────────────────────────────────────────────────┘
```

### Files Currently Involved

| File | Purpose |
|------|---------|
| `resources/js/pages/Admin/Video/Create.vue` | Admin uploads videos |
| `app/Http/Controllers/Admin/ChunkUploadController.php` | Handles chunked uploads |
| `app/Http/Controllers/Admin/VideoController.php` | Stores video metadata |
| `app/Models/Video.php` | Video model |
| `resources/js/pages/User/ContentViewer/Show.vue` | User watches videos |
| `app/Http/Controllers/VideoStreamController.php` | Streams video to user |
| `app/Services/ContentView/VideoStreamingService.php` | Manages streaming URLs |

---

## Solution Options

### Option A: HLS Streaming with FFmpeg (Recommended - YouTube-like)

**How it works:**
```
┌─────────────────────────────────────────────────────────────────┐
│                    HLS STREAMING FLOW                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Admin uploads video                                         │
│     ↓                                                           │
│  2. Background job transcodes video into multiple qualities:    │
│     - 1080p (5000 kbps)                                         │
│     - 720p  (2500 kbps)                                         │
│     - 480p  (1000 kbps)                                         │
│     - 360p  (600 kbps)                                          │
│     ↓                                                           │
│  3. Creates HLS playlist (.m3u8) linking all qualities          │
│     ↓                                                           │
│  4. User selects quality in video player                        │
│     ↓                                                           │
│  5. Player loads appropriate quality stream                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Pros:**
- True adaptive streaming (like YouTube/Netflix)
- Smooth quality switching without buffering
- Industry standard

**Cons:**
- Requires FFmpeg installed on server
- Needs background job processing (queues)
- Uses 3-4x more storage space
- Transcoding takes time (5-30 min per video)

---

### Option B: Multiple File Upload (Simpler)

**How it works:**
```
┌─────────────────────────────────────────────────────────────────┐
│                 MULTIPLE FILE APPROACH                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Admin uploads MULTIPLE versions of same video:              │
│     - video_1080p.mp4                                           │
│     - video_720p.mp4                                            │
│     - video_480p.mp4                                            │
│     ↓                                                           │
│  2. System stores all versions                                  │
│     ↓                                                           │
│  3. User selects quality → loads different file                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Pros:**
- No FFmpeg needed
- Simple implementation
- Works immediately

**Cons:**
- Admin must manually create quality versions
- More work for admins
- Quality switch requires video reload

---

### Option C: Server-Side Transcoding on Demand (Not Recommended)

Transcodes video in real-time when user requests different quality. Very resource-intensive, not practical.

---

## Recommended Solution: Option A (HLS Streaming)

This is what YouTube, Netflix, and all major platforms use.

---

## Implementation Plan

### Phase 1: Server Setup

#### 1.1 Install FFmpeg on Server

**Windows (XAMPP):**
```bash
# Download from: https://ffmpeg.org/download.html
# Extract to: C:\ffmpeg
# Add to PATH: C:\ffmpeg\bin
```

**Linux (Production):**
```bash
sudo apt update
sudo apt install ffmpeg
```

**Verify installation:**
```bash
ffmpeg -version
```

---

### Phase 2: Database Changes

#### 2.1 Create Migration for Video Qualities

**File:** `database/migrations/2025_12_11_000001_create_video_qualities_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_qualities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('quality'); // '1080p', '720p', '480p', '360p'
            $table->integer('width');
            $table->integer('height');
            $table->integer('bitrate'); // in kbps
            $table->string('file_path'); // path to quality variant
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();
            
            $table->unique(['video_id', 'quality']);
        });
        
        // Add HLS fields to videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->string('hls_playlist_path')->nullable()->after('file_path');
            $table->enum('transcoding_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')->after('hls_playlist_path');
            $table->integer('original_width')->nullable();
            $table->integer('original_height')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_qualities');
        
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['hls_playlist_path', 'transcoding_status', 'original_width', 'original_height']);
        });
    }
};
```

---

### Phase 3: Backend Services

#### 3.1 Create Video Transcoding Service

**File:** `app/Services/VideoTranscodingService.php`

```php
<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoTranscodingService
{
    /**
     * Quality presets for transcoding
     */
    protected array $qualityPresets = [
        '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => 5000],
        '720p'  => ['width' => 1280, 'height' => 720,  'bitrate' => 2500],
        '480p'  => ['width' => 854,  'height' => 480,  'bitrate' => 1000],
        '360p'  => ['width' => 640,  'height' => 360,  'bitrate' => 600],
    ];

    /**
     * Transcode video to multiple qualities
     */
    public function transcodeVideo(Video $video): bool
    {
        if ($video->storage_type !== 'local' || !$video->file_path) {
            Log::error('Cannot transcode: Video is not local storage', ['video_id' => $video->id]);
            return false;
        }

        $video->update(['transcoding_status' => 'processing']);

        try {
            $sourcePath = Storage::disk('public')->path($video->file_path);
            $outputDir = "videos/{$video->id}/hls";
            
            // Create output directory
            Storage::disk('public')->makeDirectory($outputDir);
            
            // Get original video dimensions
            $dimensions = $this->getVideoDimensions($sourcePath);
            $video->update([
                'original_width' => $dimensions['width'],
                'original_height' => $dimensions['height'],
            ]);

            // Transcode each quality that's <= original resolution
            foreach ($this->qualityPresets as $quality => $preset) {
                if ($preset['height'] <= $dimensions['height']) {
                    $this->transcodeToQuality($video, $sourcePath, $outputDir, $quality, $preset);
                }
            }

            // Generate master playlist
            $this->generateMasterPlaylist($video, $outputDir);

            $video->update(['transcoding_status' => 'completed']);
            
            Log::info('Video transcoding completed', ['video_id' => $video->id]);
            return true;

        } catch (\Exception $e) {
            $video->update(['transcoding_status' => 'failed']);
            Log::error('Video transcoding failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get video dimensions using FFprobe
     */
    protected function getVideoDimensions(string $path): array
    {
        $cmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 \"{$path}\"";
        $output = shell_exec($cmd);
        
        if ($output) {
            [$width, $height] = explode('x', trim($output));
            return ['width' => (int)$width, 'height' => (int)$height];
        }
        
        return ['width' => 1920, 'height' => 1080]; // Default fallback
    }

    /**
     * Transcode to specific quality
     */
    protected function transcodeToQuality(Video $video, string $sourcePath, string $outputDir, string $quality, array $preset): void
    {
        $qualityDir = "{$outputDir}/{$quality}";
        Storage::disk('public')->makeDirectory($qualityDir);
        
        $outputPath = Storage::disk('public')->path("{$qualityDir}/playlist.m3u8");
        $segmentPath = Storage::disk('public')->path("{$qualityDir}/segment_%03d.ts");

        // FFmpeg command for HLS transcoding
        $cmd = sprintf(
            'ffmpeg -i "%s" -vf "scale=%d:%d" -c:v libx264 -b:v %dk -c:a aac -b:a 128k ' .
            '-hls_time 10 -hls_list_size 0 -hls_segment_filename "%s" "%s" -y 2>&1',
            $sourcePath,
            $preset['width'],
            $preset['height'],
            $preset['bitrate'],
            $segmentPath,
            $outputPath
        );

        Log::info("Transcoding {$quality}", ['command' => $cmd]);
        
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("FFmpeg failed for {$quality}: " . implode("\n", $output));
        }

        // Calculate file size of all segments
        $files = Storage::disk('public')->files($qualityDir);
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += Storage::disk('public')->size($file);
        }

        // Save quality record
        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => $quality,
            'width' => $preset['width'],
            'height' => $preset['height'],
            'bitrate' => $preset['bitrate'],
            'file_path' => "{$qualityDir}/playlist.m3u8",
            'file_size' => $totalSize,
        ]);
    }

    /**
     * Generate HLS master playlist
     */
    protected function generateMasterPlaylist(Video $video, string $outputDir): void
    {
        $qualities = VideoQuality::where('video_id', $video->id)->get();
        
        $playlist = "#EXTM3U\n#EXT-X-VERSION:3\n\n";
        
        foreach ($qualities as $q) {
            $bandwidth = $q->bitrate * 1000; // Convert to bps
            $playlist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$q->width}x{$q->height}\n";
            $playlist .= "{$q->quality}/playlist.m3u8\n\n";
        }

        $masterPath = "{$outputDir}/master.m3u8";
        Storage::disk('public')->put($masterPath, $playlist);
        
        $video->update(['hls_playlist_path' => $masterPath]);
    }
}
```

---

#### 3.2 Create Background Job for Transcoding

**File:** `app/Jobs/TranscodeVideoJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\VideoTranscodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour timeout
    public int $tries = 1;

    public function __construct(
        public Video $video
    ) {}

    public function handle(VideoTranscodingService $service): void
    {
        $service->transcodeVideo($this->video);
    }
}
```

---

#### 3.3 Create VideoQuality Model

**File:** `app/Models/VideoQuality.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoQuality extends Model
{
    protected $fillable = [
        'video_id',
        'quality',
        'width',
        'height',
        'bitrate',
        'file_path',
        'file_size',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) return '0 MB';
        return round($this->file_size / 1024 / 1024, 2) . ' MB';
    }
}
```

---

### Phase 4: Update Video Model

**File:** `app/Models/Video.php` (Add these methods)

```php
// Add to existing Video model

use App\Models\VideoQuality;

// Add to $fillable array:
'hls_playlist_path',
'transcoding_status',
'original_width',
'original_height',

// Add relationship:
public function qualities(): HasMany
{
    return $this->hasMany(VideoQuality::class);
}

// Add helper methods:
public function hasHlsPlaylist(): bool
{
    return !empty($this->hls_playlist_path) && $this->transcoding_status === 'completed';
}

public function getAvailableQualities(): array
{
    return $this->qualities()->pluck('quality')->toArray();
}

public function getHlsUrl(): ?string
{
    if (!$this->hasHlsPlaylist()) {
        return null;
    }
    return Storage::disk('public')->url($this->hls_playlist_path);
}
```

---

### Phase 5: Update VideoController to Trigger Transcoding

**File:** `app/Http/Controllers/Admin/VideoController.php`

Add after video creation in `store()` method:

```php
// After: $video = Video::create($videoData);

// Dispatch transcoding job for local videos
if ($validated['storage_type'] === 'local') {
    \App\Jobs\TranscodeVideoJob::dispatch($video);
}
```

---

### Phase 6: Create HLS Streaming Controller

**File:** `app/Http/Controllers/HlsStreamController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class HlsStreamController extends Controller
{
    /**
     * Serve HLS playlist or segment files
     */
    public function serve(Request $request, Video $video, string $path)
    {
        if (!auth()->check()) {
            abort(401);
        }

        $fullPath = "videos/{$video->id}/hls/{$path}";
        
        if (!Storage::disk('public')->exists($fullPath)) {
            abort(404, 'File not found');
        }

        $content = Storage::disk('public')->get($fullPath);
        $mimeType = $this->getMimeType($path);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function getMimeType(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        return match($extension) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            default => 'application/octet-stream',
        };
    }
}
```

---

### Phase 7: Add Routes

**File:** `routes/web.php`

```php
// Add HLS streaming route
Route::get('/hls/{video}/{path}', [App\Http\Controllers\HlsStreamController::class, 'serve'])
    ->where('path', '.*')
    ->name('hls.serve')
    ->middleware('auth');
```

---

### Phase 8: Frontend - Video Player with Quality Selector

#### 8.1 Install HLS.js

```bash
npm install hls.js
```

#### 8.2 Create Quality Selector Component

**File:** `resources/js/components/VideoQualitySelector.vue`

```vue
<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            class="flex items-center gap-1 px-2 py-1 text-white bg-black/50 rounded hover:bg-black/70"
        >
            <Settings class="w-4 h-4" />
            <span class="text-sm">{{ currentQuality }}</span>
        </button>
        
        <div
            v-if="isOpen"
            class="absolute bottom-full right-0 mb-2 bg-black/90 rounded-lg overflow-hidden min-w-[120px]"
        >
            <button
                v-for="quality in availableQualities"
                :key="quality"
                @click="selectQuality(quality)"
                class="w-full px-4 py-2 text-left text-white text-sm hover:bg-white/20 flex items-center justify-between"
                :class="{ 'bg-white/10': quality === currentQuality }"
            >
                {{ quality }}
                <Check v-if="quality === currentQuality" class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Settings, Check } from 'lucide-vue-next'

const props = defineProps<{
    availableQualities: string[]
    currentQuality: string
}>()

const emit = defineEmits<{
    (e: 'change', quality: string): void
}>()

const isOpen = ref(false)

const selectQuality = (quality: string) => {
    emit('change', quality)
    isOpen.value = false
    
    // Save preference to localStorage
    localStorage.setItem('preferredVideoQuality', quality)
}
</script>
```

---

#### 8.3 Update ContentViewer/Show.vue

Add HLS support to the video player. Key changes:

```vue
<script setup lang="ts">
import Hls from 'hls.js'
import VideoQualitySelector from '@/components/VideoQualitySelector.vue'

// Add new refs
const hls = ref<Hls | null>(null)
const currentQuality = ref('Auto')
const availableQualities = ref<string[]>(['Auto'])

// Add to onMounted for video content
if (props.content.content_type === 'video' && props.video?.hls_url) {
    initHlsPlayer()
}

const initHlsPlayer = () => {
    if (!videoElement.value || !props.video?.hls_url) return
    
    if (Hls.isSupported()) {
        hls.value = new Hls({
            startLevel: -1, // Auto quality
        })
        
        hls.value.loadSource(props.video.hls_url)
        hls.value.attachMedia(videoElement.value)
        
        hls.value.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
            // Get available qualities
            availableQualities.value = ['Auto', ...data.levels.map(l => `${l.height}p`)]
            
            // Load saved preference
            const saved = localStorage.getItem('preferredVideoQuality')
            if (saved && availableQualities.value.includes(saved)) {
                setQuality(saved)
            }
        })
        
        hls.value.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
            if (currentQuality.value === 'Auto') {
                // Show current auto-selected quality
                const level = hls.value?.levels[data.level]
                if (level) {
                    currentQuality.value = `Auto (${level.height}p)`
                }
            }
        })
    } else if (videoElement.value.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari native HLS support
        videoElement.value.src = props.video.hls_url
    }
}

const setQuality = (quality: string) => {
    if (!hls.value) return
    
    if (quality === 'Auto') {
        hls.value.currentLevel = -1 // Auto
        currentQuality.value = 'Auto'
    } else {
        const height = parseInt(quality)
        const levelIndex = hls.value.levels.findIndex(l => l.height === height)
        if (levelIndex !== -1) {
            hls.value.currentLevel = levelIndex
            currentQuality.value = quality
        }
    }
}

// Cleanup on unmount
onUnmounted(() => {
    if (hls.value) {
        hls.value.destroy()
    }
})
</script>

<!-- Add to video controls template -->
<VideoQualitySelector
    v-if="availableQualities.length > 1"
    :available-qualities="availableQualities"
    :current-quality="currentQuality"
    @change="setQuality"
/>
```

---

## File Changes Summary

| Action | File | Description |
|--------|------|-------------|
| CREATE | `database/migrations/xxx_create_video_qualities_table.php` | New table for quality variants |
| CREATE | `app/Models/VideoQuality.php` | Model for quality variants |
| CREATE | `app/Services/VideoTranscodingService.php` | FFmpeg transcoding logic |
| CREATE | `app/Jobs/TranscodeVideoJob.php` | Background job for transcoding |
| CREATE | `app/Http/Controllers/HlsStreamController.php` | Serve HLS files |
| CREATE | `resources/js/components/VideoQualitySelector.vue` | Quality selector UI |
| UPDATE | `app/Models/Video.php` | Add HLS fields and relationships |
| UPDATE | `app/Http/Controllers/Admin/VideoController.php` | Trigger transcoding on upload |
| UPDATE | `resources/js/pages/User/ContentViewer/Show.vue` | Add HLS player support |
| UPDATE | `routes/web.php` | Add HLS route |
| INSTALL | `hls.js` | NPM package for HLS playback |

---

## Storage Structure After Implementation

```
storage/app/public/videos/
├── {video_id}/
│   ├── original.mp4          (original uploaded file)
│   └── hls/
│       ├── master.m3u8       (master playlist)
│       ├── 1080p/
│       │   ├── playlist.m3u8
│       │   ├── segment_000.ts
│       │   ├── segment_001.ts
│       │   └── ...
│       ├── 720p/
│       │   ├── playlist.m3u8
│       │   └── ...
│       ├── 480p/
│       │   └── ...
│       └── 360p/
│           └── ...
```

---

## Important Notes

### 1. Server Requirements
- FFmpeg must be installed
- Queue worker must be running: `php artisan queue:work`
- Sufficient disk space (3-4x original video size)

### 2. Transcoding Time
- 10 min video ≈ 5-15 min transcoding
- 1 hour video ≈ 30-60 min transcoding

### 3. Storage Considerations
- 100MB video → ~300-400MB total (all qualities)
- Consider cleanup of original file after transcoding

### 4. Google Drive Videos
- This solution only works for LOCAL storage videos
- Google Drive videos cannot be transcoded (no file access)

---

## Alternative Solutions (No FFmpeg Required)

Since FFmpeg cannot be installed, here are other options:

---

### Option 1: Cloud Transcoding Services (Recommended)

Use a cloud service to handle transcoding automatically. You upload once, they create all qualities.

#### A) Cloudflare Stream (Best Value)
- **Cost:** $5/month for 1,000 minutes stored + $1 per 1,000 minutes watched
- **How it works:**
  1. Upload video to Cloudflare Stream API
  2. They automatically transcode to multiple qualities
  3. Embed their player OR use HLS URL with your player
  4. Users get automatic quality selection

```
Your Server → Upload → Cloudflare → Auto-transcode → HLS Stream → User
```

**Pros:**
- No server processing
- Automatic quality variants
- Global CDN (fast worldwide)
- Simple API

**Implementation:**
```php
// Upload to Cloudflare Stream
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.cloudflare.api_token'),
])->post('https://api.cloudflare.com/client/v4/accounts/{account_id}/stream', [
    'url' => $videoUrl, // or upload file directly
]);

$streamId = $response->json()['result']['uid'];
$hlsUrl = "https://videodelivery.net/{$streamId}/manifest/video.m3u8";
```

---

#### B) Bunny.net Stream
- **Cost:** $5/month + $0.005 per GB stored + $0.01 per GB delivered
- Similar to Cloudflare but often cheaper for high traffic

---

#### C) AWS MediaConvert + S3 + CloudFront
- **Cost:** Pay per minute transcoded (~$0.015/min)
- More complex setup but very scalable
- Good if you're already on AWS

---

#### D) Mux Video
- **Cost:** $0.00096 per second stored + $0.00096 per second delivered
- Developer-friendly API
- Automatic quality selection

---

### Option 2: Client-Side Quality Reduction (Simplest - No Cost)

**How it works:** 
The browser requests the full video but you use JavaScript to reduce playback quality. This doesn't save bandwidth but can help with slow playback.

**Limitation:** User still downloads full quality, just displays lower. Not a true solution.

---

### Option 3: Video Compression on Upload (Partial Solution)

Compress videos when admin uploads using browser-based compression before sending to server.

**File:** `resources/js/components/VideoCompressor.vue`

```vue
<template>
    <div>
        <input type="file" @change="handleFile" accept="video/*" />
        <div v-if="compressing">
            Compressing: {{ progress }}%
        </div>
        <select v-model="targetQuality">
            <option value="high">High Quality (1080p)</option>
            <option value="medium">Medium Quality (720p)</option>
            <option value="low">Low Quality (480p)</option>
        </select>
    </div>
</template>

<script setup>
// Uses browser's MediaRecorder API to re-encode video
// Limited browser support and quality control
</script>
```

**Limitation:** Browser compression is slow and quality is inconsistent.

---

### Option 4: External Transcoding Tool (One-Time Setup)

Use a free online tool or desktop app to pre-transcode videos before uploading.

**Free Tools:**
- **HandBrake** (Desktop) - Free, powerful
- **VLC** (Desktop) - Can convert videos
- **Online converters** - cloudconvert.com, convertio.co

**Workflow:**
1. Admin uses HandBrake to create 720p and 480p versions
2. Upload all versions to your system
3. System stores them as quality variants

This is manual but requires no server changes.

---

### Option 5: Hybrid Approach (Recommended for Your Case)

Combine cloud transcoding with your existing system:

```
┌─────────────────────────────────────────────────────────────────┐
│                    HYBRID APPROACH                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  LOCAL STORAGE (Current):                                       │
│  - Keep for small videos                                        │
│  - Single quality only                                          │
│                                                                 │
│  CLOUD STREAMING (New):                                         │
│  - Use for videos needing quality options                       │
│  - Cloudflare/Bunny handles transcoding                         │
│  - Store only the stream URL in database                        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Recommended Solution: Cloudflare Stream

Here's why Cloudflare Stream is the best option for you:

| Feature | Cloudflare Stream |
|---------|-------------------|
| Cost | ~$5-20/month for typical usage |
| Setup | Simple API, 1-2 days implementation |
| Transcoding | Automatic (1080p, 720p, 480p, 360p) |
| Quality Selection | Built-in or use with HLS.js |
| CDN | Global, fast delivery |
| No FFmpeg | ✅ Not needed |
| No Server Load | ✅ All processing on Cloudflare |

---

## Cloudflare Stream Implementation Plan

### Step 1: Create Cloudflare Account
1. Go to cloudflare.com
2. Create account (free)
3. Enable Stream ($5/month base)
4. Get API token

### Step 2: Database Changes

```php
// Migration: Add cloud streaming fields to videos table
Schema::table('videos', function (Blueprint $table) {
    $table->string('cloud_stream_id')->nullable();
    $table->string('cloud_stream_url')->nullable();
    $table->enum('cloud_status', ['pending', 'processing', 'ready', 'error'])->nullable();
});
```

### Step 3: Create CloudflareStreamService

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareStreamService
{
    protected string $accountId;
    protected string $apiToken;
    
    public function __construct()
    {
        $this->accountId = config('services.cloudflare.account_id');
        $this->apiToken = config('services.cloudflare.stream_token');
    }
    
    /**
     * Upload video to Cloudflare Stream
     */
    public function uploadVideo(string $filePath, string $name): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->attach(
            'file', 
            file_get_contents($filePath), 
            $name
        )->post("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream");
        
        if ($response->successful()) {
            $data = $response->json()['result'];
            return [
                'stream_id' => $data['uid'],
                'hls_url' => "https://customer-{$this->accountId}.cloudflarestream.com/{$data['uid']}/manifest/video.m3u8",
                'thumbnail' => $data['thumbnail'] ?? null,
                'duration' => $data['duration'] ?? null,
            ];
        }
        
        Log::error('Cloudflare upload failed', ['response' => $response->json()]);
        return null;
    }
    
    /**
     * Get video status
     */
    public function getVideoStatus(string $streamId): string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->get("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream/{$streamId}");
        
        if ($response->successful()) {
            $status = $response->json()['result']['status']['state'] ?? 'unknown';
            return match($status) {
                'ready' => 'ready',
                'inprogress' => 'processing',
                'error' => 'error',
                default => 'pending',
            };
        }
        
        return 'error';
    }
    
    /**
     * Delete video from Cloudflare
     */
    public function deleteVideo(string $streamId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
        ])->delete("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream/{$streamId}");
        
        return $response->successful();
    }
}
```

### Step 4: Update VideoController

Add option to upload to cloud:

```php
// In store() method, add cloud option
if ($validated['storage_type'] === 'cloud') {
    $cloudService = app(CloudflareStreamService::class);
    
    // Upload to Cloudflare
    $result = $cloudService->uploadVideo($tempFilePath, $validated['name']);
    
    if ($result) {
        $videoData['cloud_stream_id'] = $result['stream_id'];
        $videoData['cloud_stream_url'] = $result['hls_url'];
        $videoData['cloud_status'] = 'processing';
        $videoData['storage_type'] = 'cloud';
    }
}
```

### Step 5: Update Frontend Player

The HLS.js implementation from earlier works with Cloudflare Stream URLs.

---

## Cost Estimate for Cloudflare Stream

| Usage | Monthly Cost |
|-------|--------------|
| 100 videos (avg 10 min each) | ~$10 storage |
| 1,000 views/month | ~$5 delivery |
| **Total** | **~$15/month** |

For 500 videos and 5,000 views: ~$30-50/month

---

## Summary of Options

| Option | Cost | Effort | Quality Control |
|--------|------|--------|-----------------|
| Cloudflare Stream | $5-50/mo | Medium | ✅ Automatic |
| Bunny.net Stream | $5-30/mo | Medium | ✅ Automatic |
| Manual Upload (HandBrake) | Free | High (admin work) | ✅ Manual |
| FFmpeg on Server | Free | High (setup) | ✅ Automatic |
| No Change | Free | None | ❌ None |

---

## My Recommendation

**Use Cloudflare Stream** because:
1. No FFmpeg needed
2. No server processing load
3. Automatic quality variants
4. Fast global delivery
5. Reasonable cost ($15-50/month)
6. Simple implementation (2-3 days)

Would you like me to create the full implementation for Cloudflare Stream integration?
