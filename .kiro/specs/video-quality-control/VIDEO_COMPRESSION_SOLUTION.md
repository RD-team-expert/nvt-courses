# Video Compression Solution (No FFmpeg Required)

## Your Goal
Reduce video file sizes when uploading to local storage, so videos load faster for users with slow internet.

---

## Solution: Browser-Based Video Compression

Compress videos **in the browser** before uploading to your server. No FFmpeg needed!

```
┌─────────────────────────────────────────────────────────────────┐
│                    COMPRESSION FLOW                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Admin selects video file                                    │
│     ↓                                                           │
│  2. Browser compresses video (using JavaScript)                 │
│     - Reduces resolution (e.g., 1080p → 720p)                   │
│     - Reduces bitrate                                           │
│     - Shows progress bar                                        │
│     ↓                                                           │
│  3. Compressed video uploads to server                          │
│     - Smaller file = faster upload                              │
│     - Less storage used                                         │
│     ↓                                                           │
│  4. Users watch smaller, faster-loading video                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## How It Works

We'll use a JavaScript library called **FFmpeg.wasm** - it runs FFmpeg directly in the browser!

**Benefits:**
- No server-side FFmpeg installation needed
- Compression happens on admin's computer
- Server receives already-compressed video
- Works on any hosting (including XAMPP)

**Limitations:**
- Compression is slower (browser vs server)
- Works best in Chrome/Edge (WebAssembly support)
- Large videos (>1GB) may be slow

---

## Implementation

### Step 1: Install FFmpeg.wasm

```bash
npm install @ffmpeg/ffmpeg @ffmpeg/util
```

### Step 2: Create Video Compressor Component

**File:** `resources/js/components/VideoCompressor.vue`

```vue
<template>
    <div class="space-y-4">
        <!-- File Input -->
        <div 
            class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer
                   hover:border-primary transition-colors"
            :class="{ 'border-primary bg-primary/5': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleDrop"
            @click="triggerFileInput"
        >
            <input
                ref="fileInput"
                type="file"
                accept="video/*"
                class="hidden"
                @change="handleFileSelect"
            />
            
            <div v-if="!selectedFile">
                <Upload class="w-12 h-12 mx-auto text-muted-foreground mb-2" />
                <p class="text-sm text-muted-foreground">
                    Drag & drop video or click to browse
                </p>
                <p class="text-xs text-muted-foreground mt-1">
                    Supports MP4, WebM, MOV, AVI
                </p>
            </div>
            
            <div v-else class="space-y-2">
                <Video class="w-12 h-12 mx-auto text-primary" />
                <p class="font-medium">{{ selectedFile.name }}</p>
                <p class="text-sm text-muted-foreground">
                    Original: {{ formatFileSize(selectedFile.size) }}
                </p>
            </div>
        </div>

        <!-- Compression Settings -->
        <div v-if="selectedFile && !isCompressing && !compressedFile" class="space-y-4">
            <div class="space-y-2">
                <Label>Target Quality</Label>
                <Select v-model="targetQuality">
                    <SelectTrigger>
                        <SelectValue placeholder="Select quality..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="high">High (720p) - Good quality, moderate size</SelectItem>
                        <SelectItem value="medium">Medium (480p) - Balanced</SelectItem>
                        <SelectItem value="low">Low (360p) - Smallest size</SelectItem>
                        <SelectItem value="original">Original - No compression</SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                    Lower quality = smaller file = faster loading for users
                </p>
            </div>

            <div class="flex gap-2">
                <Button @click="startCompression" :disabled="!targetQuality">
                    <Zap class="w-4 h-4 mr-2" />
                    Compress Video
                </Button>
                <Button variant="outline" @click="resetSelection">
                    Cancel
                </Button>
            </div>
        </div>

        <!-- Compression Progress -->
        <div v-if="isCompressing" class="space-y-3">
            <div class="flex items-center gap-2">
                <Loader2 class="w-5 h-5 animate-spin text-primary" />
                <span class="font-medium">{{ compressionStage }}</span>
            </div>
            
            <Progress :value="compressionProgress" class="h-2" />
            
            <p class="text-sm text-muted-foreground">
                {{ compressionProgress }}% complete
                <span v-if="estimatedTimeRemaining">
                    • ~{{ estimatedTimeRemaining }} remaining
                </span>
            </p>
            
            <Alert v-if="compressionProgress < 10">
                <AlertCircle class="w-4 h-4" />
                <AlertDescription>
                    First-time compression may take a moment to initialize...
                </AlertDescription>
            </Alert>
        </div>

        <!-- Compression Complete -->
        <div v-if="compressedFile" class="space-y-3">
            <Alert class="border-green-500 bg-green-50">
                <CheckCircle class="w-4 h-4 text-green-600" />
                <AlertDescription class="text-green-800">
                    Compression complete!
                </AlertDescription>
            </Alert>
            
            <div class="grid grid-cols-2 gap-4 p-4 bg-muted rounded-lg">
                <div>
                    <p class="text-sm text-muted-foreground">Original Size</p>
                    <p class="font-medium">{{ formatFileSize(selectedFile.size) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Compressed Size</p>
                    <p class="font-medium text-green-600">{{ formatFileSize(compressedFile.size) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Reduction</p>
                    <p class="font-medium text-green-600">{{ compressionRatio }}% smaller</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Quality</p>
                    <p class="font-medium">{{ qualityLabels[targetQuality] }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button @click="useCompressedVideo">
                    <Check class="w-4 h-4 mr-2" />
                    Use Compressed Video
                </Button>
                <Button variant="outline" @click="resetSelection">
                    Start Over
                </Button>
            </div>
        </div>

        <!-- Error State -->
        <Alert v-if="error" variant="destructive">
            <AlertCircle class="w-4 h-4" />
            <AlertDescription>
                {{ error }}
                <Button variant="link" size="sm" @click="resetSelection" class="ml-2">
                    Try again
                </Button>
            </AlertDescription>
        </Alert>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { FFmpeg } from '@ffmpeg/ffmpeg'
import { fetchFile, toBlobURL } from '@ffmpeg/util'

// UI Components
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

// Icons
import { 
    Upload, Video, Zap, Loader2, Check, CheckCircle, AlertCircle 
} from 'lucide-vue-next'

const emit = defineEmits<{
    (e: 'compressed', file: File): void
    (e: 'original', file: File): void
}>()

// State
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const compressedFile = ref<File | null>(null)
const targetQuality = ref<string>('high')
const isCompressing = ref(false)
const compressionProgress = ref(0)
const compressionStage = ref('')
const estimatedTimeRemaining = ref('')
const error = ref('')
const isDragging = ref(false)
const ffmpeg = ref<FFmpeg | null>(null)
const ffmpegLoaded = ref(false)

// Quality settings
const qualitySettings = {
    high: { resolution: '1280x720', bitrate: '2500k', crf: '23' },
    medium: { resolution: '854x480', bitrate: '1000k', crf: '28' },
    low: { resolution: '640x360', bitrate: '600k', crf: '32' },
    original: null
}

const qualityLabels = {
    high: '720p HD',
    medium: '480p',
    low: '360p',
    original: 'Original'
}

// Computed
const compressionRatio = computed(() => {
    if (!selectedFile.value || !compressedFile.value) return 0
    const ratio = ((selectedFile.value.size - compressedFile.value.size) / selectedFile.value.size) * 100
    return Math.round(ratio)
})

// Methods
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement
    if (input.files && input.files[0]) {
        selectFile(input.files[0])
    }
}

const handleDrop = (event: DragEvent) => {
    isDragging.value = false
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        selectFile(event.dataTransfer.files[0])
    }
}

const selectFile = (file: File) => {
    if (!file.type.startsWith('video/')) {
        error.value = 'Please select a video file'
        return
    }
    selectedFile.value = file
    compressedFile.value = null
    error.value = ''
}

const resetSelection = () => {
    selectedFile.value = null
    compressedFile.value = null
    compressionProgress.value = 0
    compressionStage.value = ''
    error.value = ''
    isCompressing.value = false
}

const loadFFmpeg = async () => {
    if (ffmpegLoaded.value) return

    compressionStage.value = 'Loading compression engine...'
    
    ffmpeg.value = new FFmpeg()
    
    ffmpeg.value.on('progress', ({ progress, time }) => {
        compressionProgress.value = Math.round(progress * 100)
        
        // Estimate remaining time
        if (progress > 0 && progress < 1) {
            const elapsed = time / 1000000 // Convert to seconds
            const total = elapsed / progress
            const remaining = total - elapsed
            
            if (remaining > 60) {
                estimatedTimeRemaining.value = `${Math.round(remaining / 60)} min`
            } else {
                estimatedTimeRemaining.value = `${Math.round(remaining)} sec`
            }
        }
    })

    // Load FFmpeg WASM files
    const baseURL = 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/esm'
    
    await ffmpeg.value.load({
        coreURL: await toBlobURL(`${baseURL}/ffmpeg-core.js`, 'text/javascript'),
        wasmURL: await toBlobURL(`${baseURL}/ffmpeg-core.wasm`, 'application/wasm'),
    })
    
    ffmpegLoaded.value = true
}

const startCompression = async () => {
    if (!selectedFile.value) return
    
    // If original selected, skip compression
    if (targetQuality.value === 'original') {
        emit('original', selectedFile.value)
        return
    }

    isCompressing.value = true
    compressionProgress.value = 0
    error.value = ''

    try {
        // Load FFmpeg if not loaded
        await loadFFmpeg()
        
        if (!ffmpeg.value) {
            throw new Error('Failed to load compression engine')
        }

        compressionStage.value = 'Preparing video...'
        
        // Write input file to FFmpeg virtual filesystem
        const inputFileName = 'input.mp4'
        const outputFileName = 'output.mp4'
        
        await ffmpeg.value.writeFile(
            inputFileName, 
            await fetchFile(selectedFile.value)
        )

        compressionStage.value = 'Compressing video...'
        
        const settings = qualitySettings[targetQuality.value as keyof typeof qualitySettings]
        
        if (!settings) {
            throw new Error('Invalid quality setting')
        }

        // Run FFmpeg compression
        await ffmpeg.value.exec([
            '-i', inputFileName,
            '-vf', `scale=${settings.resolution}:force_original_aspect_ratio=decrease`,
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', settings.crf,
            '-b:v', settings.bitrate,
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            outputFileName
        ])

        compressionStage.value = 'Finalizing...'
        
        // Read output file
        const data = await ffmpeg.value.readFile(outputFileName)
        
        // Create compressed file
        const blob = new Blob([data], { type: 'video/mp4' })
        compressedFile.value = new File(
            [blob], 
            selectedFile.value.name.replace(/\.[^/.]+$/, '_compressed.mp4'),
            { type: 'video/mp4' }
        )

        compressionStage.value = 'Complete!'
        compressionProgress.value = 100

    } catch (err: any) {
        console.error('Compression error:', err)
        error.value = err.message || 'Compression failed. Please try again.'
    } finally {
        isCompressing.value = false
    }
}

const useCompressedVideo = () => {
    if (compressedFile.value) {
        emit('compressed', compressedFile.value)
    }
}
</script>
```

---

### Step 3: Update Video Create Page

**File:** `resources/js/pages/Admin/Video/Create.vue`

Replace the current file upload section with the compressor:

```vue
<template>
    <!-- ... existing code ... -->
    
    <!-- Replace the ChunkUploader section with this -->
    <div v-if="form.storage_type === 'local'" class="space-y-4">
        <Label>Video File</Label>
        
        <!-- Compression Toggle -->
        <div class="flex items-center gap-2 p-3 bg-muted rounded-lg">
            <Switch v-model="enableCompression" />
            <div>
                <p class="font-medium text-sm">Enable Compression</p>
                <p class="text-xs text-muted-foreground">
                    Reduce file size for faster loading (recommended)
                </p>
            </div>
        </div>

        <!-- Video Compressor (when compression enabled) -->
        <VideoCompressor
            v-if="enableCompression"
            @compressed="handleCompressedVideo"
            @original="handleOriginalVideo"
        />

        <!-- Regular Upload (when compression disabled) -->
        <ChunkUploader
            v-else
            name="file"
            accept="video/*"
            :maxFileSize="maxFileSizeMB + 'MB'"
            @uploaded="handleUploadComplete"
            @error="handleUploadError"
        />
    </div>
    
    <!-- ... rest of existing code ... -->
</template>

<script setup lang="ts">
import VideoCompressor from '@/components/VideoCompressor.vue'

const enableCompression = ref(true)
const compressedVideoFile = ref<File | null>(null)

const handleCompressedVideo = async (file: File) => {
    compressedVideoFile.value = file
    
    // Now upload the compressed file using ChunkUploader logic
    // Or create a FormData and upload directly
    await uploadVideoFile(file)
}

const handleOriginalVideo = async (file: File) => {
    // Upload original without compression
    await uploadVideoFile(file)
}

const uploadVideoFile = async (file: File) => {
    // Create FormData for chunked upload
    const formData = new FormData()
    formData.append('file', file)
    
    // Use your existing chunk upload logic
    // Or implement direct upload for smaller files
}
</script>
```

---

## Simpler Alternative: Just Recommend Compression

If the FFmpeg.wasm approach is too complex, you can simply:

1. **Show a recommendation** to admins to compress videos before uploading
2. **Provide a link** to free online compression tools

**Add to Create.vue:**

```vue
<Alert class="mb-4">
    <Info class="w-4 h-4" />
    <AlertDescription>
        <strong>Tip:</strong> Compress videos before uploading for faster loading.
        <br />
        Free tools: 
        <a href="https://www.freeconvert.com/video-compressor" target="_blank" class="underline">FreeConvert</a>,
        <a href="https://handbrake.fr/" target="_blank" class="underline">HandBrake (Desktop)</a>
    </AlertDescription>
</Alert>
```

---

## Expected Results

| Original Size | Compressed (720p) | Compressed (480p) |
|---------------|-------------------|-------------------|
| 100 MB | ~40-50 MB | ~20-30 MB |
| 500 MB | ~200-250 MB | ~100-150 MB |
| 1 GB | ~400-500 MB | ~200-300 MB |

---

## Browser Compatibility

| Browser | FFmpeg.wasm Support |
|---------|---------------------|
| Chrome 79+ | ✅ Full support |
| Edge 79+ | ✅ Full support |
| Firefox 79+ | ✅ Full support |
| Safari 15+ | ⚠️ Limited (SharedArrayBuffer issues) |

---

## Summary

**What this solution does:**
1. Admin selects video file
2. Browser compresses video (no server FFmpeg needed)
3. Smaller file uploads to server
4. Users get faster-loading videos

**Pros:**
- No FFmpeg installation on server
- Works on any hosting
- Reduces storage usage
- Faster video loading for users

**Cons:**
- Compression happens on admin's browser (slower than server)
- Large videos (>1GB) may be slow to compress
- Safari has limited support

Would you like me to implement this solution?
