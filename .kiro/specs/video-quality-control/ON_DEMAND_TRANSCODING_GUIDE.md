# On-Demand Video Transcoding Guide

## What is On-Demand Transcoding?

Instead of pre-creating all quality versions when a video is uploaded, the server transcodes the video **in real-time** when a user requests a specific quality.

```
┌─────────────────────────────────────────────────────────────────┐
│                ON-DEMAND TRANSCODING FLOW                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Admin uploads video (single file, original quality)         │
│     ↓                                                           │
│  2. User opens video player                                     │
│     ↓                                                           │
│  3. User selects "480p" quality                                 │
│     ↓                                                           │
│  4. Server receives request for 480p                            │
│     ↓                                                           │
│  5. Server transcodes video to 480p IN REAL-TIME                │
│     ↓                                                           │
│  6. Server streams transcoded video to user                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Why I Said "Not Recommended"

| Problem | Impact |
|---------|--------|
| **High CPU Usage** | Each user watching = server transcoding. 10 users = 10x CPU load |
| **Latency** | User waits while server transcodes (buffering) |
| **No Seeking** | Can't jump to middle of video easily |
| **Server Overload** | Can crash server with multiple users |
| **Requires FFmpeg** | Still needs FFmpeg installed |

---

## When On-Demand CAN Work

✅ **Good for:**
- Very few concurrent users (< 5)
- Short videos (< 5 minutes)
- Infrequent video watching
- Development/testing environments

❌ **Bad for:**
- Many concurrent users
- Long videos
- Production environments with traffic
- Limited server resources

---

## How to Implement On-Demand Transcoding

### Requirements
- FFmpeg installed on server (still required!)
- Powerful server (4+ CPU cores, 8GB+ RAM recommended)
- PHP `exec()` or `shell_exec()` enabled

### The Problem: You Said No FFmpeg

**Important:** On-demand transcoding STILL requires FFmpeg. There's no way to transcode video without a transcoding tool.

**Without FFmpeg, your only options are:**
1. Cloud services (Cloudflare, Bunny, etc.)
2. Manual quality uploads
3. No quality control

---

## If You CAN Install FFmpeg Later

Here's how on-demand transcoding would work:

### Option A: Simple On-Demand (Not Cached)

Every request transcodes fresh. Very resource-intensive.

**File:** `app/Http/Controllers/OnDemandVideoController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OnDemandVideoController extends Controller
{
    protected array $qualitySettings = [
        '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => '5000k'],
        '720p'  => ['width' => 1280, 'height' => 720,  'bitrate' => '2500k'],
        '480p'  => ['width' => 854,  'height' => 480,  'bitrate' => '1000k'],
        '360p'  => ['width' => 640,  'height' => 360,  'bitrate' => '600k'],
    ];

    /**
     * Stream video with on-demand transcoding
     */
    public function stream(Request $request, Video $video, string $quality = 'original')
    {
        if (!auth()->check()) {
            abort(401);
        }

        if ($video->storage_type !== 'local') {
            abort(400, 'Only local videos support quality selection');
        }

        $sourcePath = Storage::disk('public')->path($video->file_path);

        if (!file_exists($sourcePath)) {
            abort(404, 'Video file not found');
        }

        // If original quality requested, stream directly
        if ($quality === 'original') {
            return $this->streamOriginal($video, $sourcePath);
        }

        // Validate quality parameter
        if (!isset($this->qualitySettings[$quality])) {
            abort(400, 'Invalid quality setting');
        }

        // Transcode and stream
        return $this->transcodeAndStream($sourcePath, $quality);
    }

    /**
     * Stream original file (no transcoding)
     */
    protected function streamOriginal(Video $video, string $path): StreamedResponse
    {
        $fileSize = filesize($path);
        $mimeType = $video->mime_type ?? 'video/mp4';

        return response()->stream(function () use ($path) {
            $stream = fopen($path, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * Transcode video on-the-fly and stream
     * WARNING: Very resource-intensive!
     */
    protected function transcodeAndStream(string $sourcePath, string $quality): StreamedResponse
    {
        $settings = $this->qualitySettings[$quality];

        // FFmpeg command to transcode and output to stdout
        $cmd = sprintf(
            'ffmpeg -i "%s" -vf "scale=%d:%d" -c:v libx264 -preset ultrafast -b:v %s ' .
            '-c:a aac -b:a 128k -f mp4 -movflags frag_keyframe+empty_moov pipe:1 2>/dev/null',
            $sourcePath,
            $settings['width'],
            $settings['height'],
            $settings['bitrate']
        );

        return response()->stream(function () use ($cmd) {
            // Open FFmpeg process
            $process = popen($cmd, 'r');
            
            if ($process) {
                // Stream output directly to browser
                while (!feof($process)) {
                    echo fread($process, 8192);
                    flush();
                }
                pclose($process);
            }
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }
}
```

**Route:**
```php
Route::get('/video/{video}/stream/{quality?}', [OnDemandVideoController::class, 'stream'])
    ->name('video.stream.quality')
    ->middleware('auth');
```

---

### Option B: On-Demand with Caching (Better)

Transcode once, cache the result for future requests.

```php
/**
 * Transcode with caching
 */
protected function transcodeWithCache(Video $video, string $sourcePath, string $quality): StreamedResponse
{
    $settings = $this->qualitySettings[$quality];
    $cacheDir = "videos/{$video->id}/cache";
    $cachePath = "{$cacheDir}/{$quality}.mp4";
    
    // Check if cached version exists
    if (Storage::disk('public')->exists($cachePath)) {
        // Stream cached file
        $fullPath = Storage::disk('public')->path($cachePath);
        return $this->streamFile($fullPath);
    }
    
    // Create cache directory
    Storage::disk('public')->makeDirectory($cacheDir);
    
    $outputPath = Storage::disk('public')->path($cachePath);
    
    // Transcode to file (not streaming)
    $cmd = sprintf(
        'ffmpeg -i "%s" -vf "scale=%d:%d" -c:v libx264 -preset fast -b:v %s ' .
        '-c:a aac -b:a 128k "%s" -y 2>&1',
        $sourcePath,
        $settings['width'],
        $settings['height'],
        $settings['bitrate'],
        $outputPath
    );
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode !== 0) {
        abort(500, 'Transcoding failed');
    }
    
    // Stream the newly created file
    return $this->streamFile($outputPath);
}

protected function streamFile(string $path): StreamedResponse
{
    $fileSize = filesize($path);
    
    return response()->stream(function () use ($path) {
        $stream = fopen($path, 'rb');
        fpassthru($stream);
        fclose($stream);
    }, 200, [
        'Content-Type' => 'video/mp4',
        'Content-Length' => $fileSize,
        'Accept-Ranges' => 'bytes',
    ]);
}
```

**Problem with caching approach:**
- First user waits for full transcode (could be minutes)
- Still needs FFmpeg
- Uses disk space (similar to pre-transcoding)

---

### Option C: Lazy Background Transcoding (Best On-Demand Approach)

When user requests a quality that doesn't exist:
1. Return original quality immediately
2. Queue background job to create that quality
3. Next request gets the transcoded version

```php
public function stream(Request $request, Video $video, string $quality)
{
    $cachePath = "videos/{$video->id}/cache/{$quality}.mp4";
    
    // If cached version exists, stream it
    if (Storage::disk('public')->exists($cachePath)) {
        return $this->streamFile(Storage::disk('public')->path($cachePath));
    }
    
    // Queue transcoding for next time
    TranscodeQualityJob::dispatch($video, $quality);
    
    // For now, stream original
    return $this->streamOriginal($video);
}
```

---

## Frontend Quality Selector (Works with Any Backend)

**File:** `resources/js/components/SimpleQualitySelector.vue`

```vue
<template>
    <div class="relative inline-block">
        <button
            @click="isOpen = !isOpen"
            class="flex items-center gap-2 px-3 py-1.5 bg-black/60 hover:bg-black/80 
                   text-white text-sm rounded-md transition-colors"
        >
            <Settings class="w-4 h-4" />
            <span>{{ currentQualityLabel }}</span>
        </button>
        
        <!-- Dropdown Menu -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute bottom-full right-0 mb-2 w-36 bg-black/90 
                       rounded-lg shadow-lg overflow-hidden z-50"
            >
                <div class="py-1">
                    <button
                        v-for="option in qualityOptions"
                        :key="option.value"
                        @click="selectQuality(option.value)"
                        class="w-full px-4 py-2 text-left text-white text-sm 
                               hover:bg-white/20 flex items-center justify-between"
                        :class="{ 
                            'bg-white/10': option.value === currentQuality,
                            'opacity-50 cursor-not-allowed': !option.available
                        }"
                        :disabled="!option.available"
                    >
                        <span>{{ option.label }}</span>
                        <Check v-if="option.value === currentQuality" class="w-4 h-4 text-green-400" />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Settings, Check } from 'lucide-vue-next'

interface QualityOption {
    value: string
    label: string
    available: boolean
}

const props = defineProps<{
    videoId: number
    availableQualities?: string[]
}>()

const emit = defineEmits<{
    (e: 'change', quality: string, url: string): void
}>()

const isOpen = ref(false)
const currentQuality = ref('original')

const qualityOptions = computed<QualityOption[]>(() => {
    const available = props.availableQualities || ['original']
    
    return [
        { value: 'original', label: 'Original', available: true },
        { value: '1080p', label: '1080p HD', available: available.includes('1080p') },
        { value: '720p', label: '720p HD', available: available.includes('720p') },
        { value: '480p', label: '480p', available: available.includes('480p') },
        { value: '360p', label: '360p', available: available.includes('360p') },
    ]
})

const currentQualityLabel = computed(() => {
    const option = qualityOptions.value.find(o => o.value === currentQuality.value)
    return option?.label || 'Quality'
})

const selectQuality = (quality: string) => {
    if (quality === currentQuality.value) {
        isOpen.value = false
        return
    }
    
    currentQuality.value = quality
    isOpen.value = false
    
    // Save preference
    localStorage.setItem('preferredVideoQuality', quality)
    
    // Build URL and emit
    const url = `/video/${props.videoId}/stream/${quality}`
    emit('change', quality, url)
}

// Load saved preference
onMounted(() => {
    const saved = localStorage.getItem('preferredVideoQuality')
    if (saved) {
        currentQuality.value = saved
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement
    if (!target.closest('.relative')) {
        isOpen.value = false
    }
}
</script>
```

---

## Integration with ContentViewer/Show.vue

Add quality selector to video controls:

```vue
<template>
    <!-- In video controls section -->
    <div class="video-controls flex items-center gap-2">
        <!-- Existing controls... -->
        
        <!-- Quality Selector -->
        <SimpleQualitySelector
            v-if="content.content_type === 'video' && video?.storage_type === 'local'"
            :video-id="video.id"
            :available-qualities="availableQualities"
            @change="handleQualityChange"
        />
        
        <!-- Existing controls... -->
    </div>
</template>

<script setup>
import SimpleQualitySelector from '@/components/SimpleQualitySelector.vue'

const availableQualities = ref(['original']) // Will be populated from backend

const handleQualityChange = (quality: string, url: string) => {
    if (!videoElement.value) return
    
    // Save current time
    const currentTime = videoElement.value.currentTime
    const wasPlaying = !videoElement.value.paused
    
    // Change source
    videoElement.value.src = url
    videoElement.value.load()
    
    // Restore position
    videoElement.value.currentTime = currentTime
    
    if (wasPlaying) {
        videoElement.value.play()
    }
}
</script>
```

---

## The Reality Check

### Without FFmpeg, On-Demand Transcoding is IMPOSSIBLE

On-demand transcoding requires a transcoding tool. FFmpeg is the standard.

**Your actual options without FFmpeg:**

| Option | Requires FFmpeg | Cost |
|--------|-----------------|------|
| On-Demand Transcoding | ✅ YES | Free (but needs FFmpeg) |
| Pre-Transcoding | ✅ YES | Free (but needs FFmpeg) |
| Cloud Services | ❌ NO | $15-50/month |
| Manual Upload | ❌ NO | Free (admin work) |
| No Quality Control | ❌ NO | Free |

---

## Alternative: PHP-Based Video Processing (Limited)

There are some PHP libraries that can do basic video processing without FFmpeg, but they are:
- Very slow
- Limited quality
- Not suitable for production

**Example:** `php-ffmpeg/php-ffmpeg` still requires FFmpeg binary.

---

## My Honest Recommendation

Since you cannot install FFmpeg:

1. **Best Option:** Use Cloudflare Stream (~$15-50/month)
   - No FFmpeg needed
   - Automatic quality variants
   - Professional solution

2. **Free Option:** Manual quality uploads
   - Use HandBrake (free desktop app) to create quality versions
   - Upload each version separately
   - More work for admin, but free

3. **Accept Limitation:** Keep single quality
   - Users with slow internet can pause and buffer
   - Not ideal but functional

---

## Summary

**On-Demand Transcoding = Requires FFmpeg**

There's no way around this. Video transcoding needs a transcoding tool.

If you truly cannot install FFmpeg on your server, your options are:
1. Cloud service (Cloudflare, Bunny, Mux)
2. Manual quality uploads
3. No quality control

Would you like me to detail the implementation for any of these alternatives?
