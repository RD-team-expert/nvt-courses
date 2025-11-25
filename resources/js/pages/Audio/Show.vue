<!-- Audio Player Page - Complete with Content Time Listened -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import axios from 'axios'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Slider } from '@/components/ui/slider'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'

// Icons
import {
    Play,
    Pause,
    Volume2,
    VolumeX,
    SkipForward,
    SkipBack,
    CheckCircle,
    Clock,
    BookOpen,
    ArrowLeft,
    RotateCcw,
    AlertTriangle,
    RefreshCw,
    ExternalLink,
    Headphones
} from 'lucide-vue-next'

interface Audio {
    id: number
    name: string
    description: string
    google_cloud_url: string
    duration: number
    formatted_duration: string
    thumbnail_url?: string
    category?: {
        id: number
        name: string
    }
}

interface UserProgress {
    id: number
    current_time: number
    formatted_current_time: string
    completion_percentage: number
    is_completed: boolean
    total_listened_time: number
    last_accessed_at: string
}

const props = defineProps<{
    audio: Audio
    user_progress: UserProgress
}>()

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Audio Courses', href: '/audio' },
    { title: props.audio.name, href: '#' }
]

// Audio player state
const audioRef = ref<HTMLAudioElement>()
const isPlaying = ref(false)
const currentTime = ref(props.user_progress.current_time || 0)
const duration = ref(props.audio.duration || 0)
const volume = ref(1)
const isMuted = ref(false)
const playbackRate = ref(1)
const isLoading = ref(true)
const error = ref('')
const isBuffering = ref(false)
const canPlay = ref(false)

// ✅ CONTENT TIME LISTENED (based on current position)
const contentTimeListened = computed(() => Math.floor(currentTime.value))

// ✅ THUMBNAIL ERROR HANDLING
const thumbnailError = ref(false)

// Progress tracking
const lastSavedTime = ref(currentTime.value)
const saveInterval = ref<NodeJS.Timeout>()
const progressSaveDebounceTimeout = ref<NodeJS.Timeout>()

// Playback rates
const playbackRates = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]

// ✅ COMPUTED PROPERTIES
const progress = computed(() => duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0)
const formattedCurrentTime = computed(() => formatTime(currentTime.value))
const formattedDuration = computed(() => formatTime(duration.value))
const canMarkComplete = computed(() => progress.value >= 95 || currentTime.value >= duration.value - 30)
const progressBarValue = computed(() => Math.min(progress.value, 100))

// ✅ FORMAT CONTENT TIME LISTENED (like "1m listened")
const formattedContentTimeListened = computed(() => {
    const totalSeconds = contentTimeListened.value
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60

    if (minutes > 0) {
        if (seconds > 0) {
            return `${minutes}m ${seconds}s listened`
        }
        return `${minutes}m listened`
    }
    return seconds > 0 ? `${seconds}s listened` : '0m listened'
})

// Format time helper
function formatTime(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '00:00'

    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = Math.floor(seconds % 60)
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
}

// ✅ HANDLE THUMBNAIL ERROR
function handleThumbnailError() {
    thumbnailError.value = true
}

// Player controls
function togglePlay() {
    if (!audioRef.value || !canPlay.value) return

    if (isPlaying.value) {
        audioRef.value.pause()
    } else {
        isBuffering.value = true
        audioRef.value.play().catch(err => {
            console.error('Play failed:', err)
            error.value = 'Unable to play audio. The file might be restricted or your connection is slow.'
            isBuffering.value = false
        })
    }
}

function seek(newTime: number) {
    if (!audioRef.value || !canPlay.value) return

    isBuffering.value = true
    audioRef.value.currentTime = newTime
    currentTime.value = newTime

    // Debounce progress saving when seeking
    if (progressSaveDebounceTimeout.value) {
        clearTimeout(progressSaveDebounceTimeout.value)
    }

    progressSaveDebounceTimeout.value = setTimeout(() => {
        saveProgress()
    }, 1000)
}

function skipForward() {
    const newTime = Math.min(currentTime.value + 15, duration.value)
    seek(newTime)
}

function skipBackward() {
    const newTime = Math.max(currentTime.value - 15, 0)
    seek(newTime)
}

function toggleMute() {
    if (!audioRef.value) return
    isMuted.value = !isMuted.value
    audioRef.value.muted = isMuted.value
}

function changeVolume(newVolume: number[]) {
    if (!audioRef.value) return
    volume.value = newVolume[0]
    audioRef.value.volume = volume.value
    if (volume.value > 0 && isMuted.value) {
        isMuted.value = false
        audioRef.value.muted = false
    }
}

function changePlaybackRate(rate: number) {
    if (!audioRef.value) return
    playbackRate.value = rate
    audioRef.value.playbackRate = rate
}

// ✅ SAVE PROGRESS (keep total_listened_time for backend compatibility)
async function saveProgress() {
    console.log('saveProgress called!', {
        currentTime: currentTime.value,
        lastSavedTime: lastSavedTime.value,
        audioId: props.audio.id
    })

    try {
        const timeDifference = Math.abs(currentTime.value - lastSavedTime.value)

        if (timeDifference >= 1) {
            const response = await axios.post(`/audio/${props.audio.id}/progress`, {
                current_time: currentTime.value,
                total_listened_time: props.user_progress.total_listened_time + timeDifference
            })

            console.log('Progress API response:', response.data)

            if (response.data.success && response.data.progress) {
                // Update local progress data
                props.user_progress.current_time = response.data.progress.current_time
                props.user_progress.completion_percentage = response.data.progress.completion_percentage
                props.user_progress.is_completed = response.data.progress.is_completed

                console.log('✅ Local progress updated:', {
                    completion: props.user_progress.completion_percentage,
                    is_completed: props.user_progress.is_completed
                })

                lastSavedTime.value = currentTime.value
            }
        }
    } catch (error) {
        console.error('Failed to save progress:', error.response || error)
    }
}

async function markCompleted() {
    console.log('markCompleted called for audio ID:', props.audio.id)

    try {
        const response = await axios.post(`/audio/${props.audio.id}/complete`, {}, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })

        console.log('Complete API response:', response.data)

        if (response.data.success) {
            console.log('Audio marked as completed successfully')
            setTimeout(() => {
                window.location.reload()
            }, 1000)
        }
    } catch (error) {
        console.error('Failed to mark as completed:', error.response || error)
        error.value = 'Failed to mark as completed. Please try again.'
    }
}

// Retry loading audio
function retryAudio() {
    if (!audioRef.value) return

    error.value = ''
    isLoading.value = true
    canPlay.value = false

    audioRef.value.load()
}

// Audio event listeners
function onLoadStart() {
    isLoading.value = true
    isBuffering.value = true
    console.log('Audio loading started')
}

function onCanPlay() {
    canPlay.value = true
    isBuffering.value = false
    console.log('Audio can start playing')
}

function onLoadedData() {
    if (!audioRef.value) return

    duration.value = audioRef.value.duration || props.audio.duration

    // Resume from saved position
    if (currentTime.value > 0 && currentTime.value < duration.value) {
        audioRef.value.currentTime = currentTime.value
    }

    isLoading.value = false
    isBuffering.value = false
    canPlay.value = true
    error.value = ''

    console.log('Audio loaded successfully, duration:', duration.value, 'resume from:', currentTime.value)
}

function onTimeUpdate() {
    if (!audioRef.value) return
    currentTime.value = audioRef.value.currentTime
    isBuffering.value = false
}

function onPlay() {
    isPlaying.value = true
    isBuffering.value = false
}

function onPause() {
    isPlaying.value = false
    saveProgress()
}

function onEnded() {
    isPlaying.value = false
    currentTime.value = duration.value

    // Auto-mark as completed if not already
    if (!props.user_progress.is_completed) {
        markCompleted()
    }
}

function onWaiting() {
    isBuffering.value = true
    console.log('Audio is buffering...')
}

function onPlaying() {
    isBuffering.value = false
    isPlaying.value = true
}

function onError(event: Event) {
    console.error('Audio loading error:', event)

    if (audioRef.value?.error) {
        const errorCode = audioRef.value.error.code
        const errorMessage = audioRef.value.error.message

        console.error('Audio error details:', { code: errorCode, message: errorMessage })

        switch (errorCode) {
            case 1: // MEDIA_ERR_ABORTED
                error.value = 'Audio loading was aborted. Please try again.'
                break
            case 2: // MEDIA_ERR_NETWORK
                error.value = 'Network error while loading audio. Check your connection and try again.'
                break
            case 3: // MEDIA_ERR_DECODE
                error.value = 'Audio file is corrupted or in an unsupported format.'
                break
            case 4: // MEDIA_ERR_SRC_NOT_SUPPORTED
                error.value = 'Audio file format is not supported or file is not accessible.'
                break
            default:
                error.value = 'Failed to load audio. The file might be private or moved.'
        }
    } else {
        error.value = 'Failed to load audio from Google Drive. Please check if the file is publicly accessible.'
    }

    isLoading.value = false
    isBuffering.value = false
    canPlay.value = false
}

// Lifecycle management
onMounted(() => {
    // Initialize audio settings
    if (audioRef.value) {
        audioRef.value.volume = volume.value
        audioRef.value.playbackRate = playbackRate.value
        audioRef.value.preload = 'metadata'
    }

    // Auto-save progress every 10 seconds
    saveInterval.value = setInterval(() => {
        if (isPlaying.value) {
            saveProgress()
        }
    }, 10000)

    // Save progress on page unload
    const handleBeforeUnload = () => {
        saveProgress()
    }

    window.addEventListener('beforeunload', handleBeforeUnload)

    // Cleanup function
    onUnmounted(() => {
        if (saveInterval.value) {
            clearInterval(saveInterval.value)
        }
        if (progressSaveDebounceTimeout.value) {
            clearTimeout(progressSaveDebounceTimeout.value)
        }
        window.removeEventListener('beforeunload', handleBeforeUnload)
        saveProgress() // Final save
    })
})

// Keyboard shortcuts
onMounted(() => {
    function handleKeyPress(event: KeyboardEvent) {
        // Don't trigger if user is typing in an input field
        const target = event.target as HTMLElement
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.contentEditable === 'true') {
            return
        }

        switch (event.code) {
            case 'Space':
                event.preventDefault()
                togglePlay()
                break
            case 'ArrowLeft':
                event.preventDefault()
                skipBackward()
                break
            case 'ArrowRight':
                event.preventDefault()
                skipForward()
                break
            case 'KeyM':
                event.preventDefault()
                toggleMute()
                break
            case 'ArrowUp':
                event.preventDefault()
                changeVolume([Math.min(volume.value + 0.1, 1)])
                break
            case 'ArrowDown':
                event.preventDefault()
                changeVolume([Math.max(volume.value - 0.1, 0)])
                break
        }
    }

    document.addEventListener('keydown', handleKeyPress)

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyPress)
    })
})
</script>

<template>
    <Head :title="`${audio.name} - Audio Course`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Back Button -->
            <Button asChild variant="ghost" class="mb-4">
                <Link href="/audio">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back to Audio Courses
                </Link>
            </Button>

            <!-- Google Drive Notice -->


            <!-- Audio Info Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- ✅ THUMBNAIL WITH ERROR HANDLING -->
                        <div class="w-full md:w-64 aspect-video bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg overflow-hidden shrink-0 relative">
                            <img
                                v-if="audio.thumbnail_url && !thumbnailError"
                                :src="audio.thumbnail_url"
                                :alt="audio.name"
                                class="w-full h-full object-cover"
                                @error="handleThumbnailError"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Volume2 class="h-16 w-16 text-blue-500" />
                            </div>

                            <!-- Status overlay -->
                            <div v-if="isBuffering || isLoading" class="absolute inset-0 bg-black/20 flex items-center justify-center">
                                <div class="bg-white/90 rounded-full p-3">
                                    <RefreshCw class="h-8 w-8 text-blue-600 animate-spin" />
                                </div>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 space-y-4">
                            <div>
                                <div v-if="audio.category" class="flex items-center gap-2 mb-2">
                                    <BookOpen class="h-4 w-4 text-muted-foreground" />
                                    <Badge variant="outline">{{ audio.category.name }}</Badge>
                                </div>
                                <CardTitle class="text-2xl mb-2">{{ audio.name }}</CardTitle>
                                <CardDescription class="text-base">{{ audio.description || 'No description available' }}</CardDescription>
                            </div>

                            <!-- ✅ INFO WITH CONTENT TIME LISTENED -->
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Clock class="h-4 w-4" />
                                    <span>{{ audio.formatted_duration }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-blue-600">
                                    <RotateCcw class="h-4 w-4" />
                                    <span>{{ Math.round(user_progress.completion_percentage) }}% Complete</span>
                                </div>

                                <!-- ✅ CONTENT TIME LISTENED (based on current position) -->
                                <div class="flex items-center gap-2 text-sm text-purple-600">
                                    <Headphones class="h-4 w-4" />
                                    <span>{{ formattedContentTimeListened }}</span>
                                </div>

                                <div v-if="user_progress.is_completed" class="flex items-center gap-2 text-sm text-green-600">
                                    <CheckCircle class="h-4 w-4" />
                                    <span>Completed</span>
                                </div>
                            </div>

                            <!-- Overall Progress -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-muted-foreground">Overall Progress</span>
                                    <span class="font-medium">{{ Math.round(user_progress.completion_percentage) }}%</span>
                                </div>
                                <Progress :value="user_progress.completion_percentage" class="h-2" />

                                <!-- ✅ PROGRESS INFO WITH CONTENT LISTENED -->
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>{{ user_progress.formatted_current_time }} / {{ audio.formatted_duration }}</span>
                                    <span>{{ formattedContentTimeListened }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Audio Player -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Volume2 class="h-5 w-5" />
                        Audio Player
                        <div v-if="isBuffering" class="ml-2 flex items-center gap-2 text-sm text-muted-foreground">
                            <RefreshCw class="h-4 w-4 animate-spin" />
                            Buffering...
                        </div>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Audio Element -->
                    <audio
                        ref="audioRef"
                        :src="audio.google_cloud_url"
                        preload="metadata"
                        @loadstart="onLoadStart"
                        @canplay="onCanPlay"
                        @loadeddata="onLoadedData"
                        @timeupdate="onTimeUpdate"
                        @play="onPlay"
                        @pause="onPause"
                        @ended="onEnded"
                        @waiting="onWaiting"
                        @playing="onPlaying"
                        @error="onError"
                        crossorigin="anonymous"
                    ></audio>

                    <!-- Error Message -->
                    <Alert v-if="error" variant="destructive">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>
                            <div class="space-y-3">
                                <p>{{ error }}</p>
                                <div class="flex gap-2">
                                    <Button @click="retryAudio" size="sm" variant="outline">
                                        <RefreshCw class="h-4 w-4 mr-2" />
                                        Retry
                                    </Button>
                                    <Button asChild size="sm" variant="outline">
                                        <a :href="audio.google_cloud_url" target="_blank">
                                            <ExternalLink class="h-4 w-4 mr-2" />
                                            Open Direct Link
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </AlertDescription>
                    </Alert>

                    <!-- Loading State -->
                    <div v-if="isLoading && !error" class="text-center py-8">
                        <div class="flex items-center justify-center gap-2 text-muted-foreground mb-4">
                            <RefreshCw class="h-5 w-5 animate-spin" />
                            <span>Loading audio from Google Drive...</span>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            This may take a moment for large files
                        </div>
                    </div>

                    <!-- Player Controls -->
                    <div v-if="!isLoading && !error" class="space-y-6">
                        <!-- Main Controls -->
                        <div class="flex items-center justify-center gap-4">
                            <Button @click="skipBackward" variant="outline" size="sm" :disabled="!canPlay">
                                <SkipBack class="h-4 w-4" />
                            </Button>

                            <Button
                                @click="togglePlay"
                                size="lg"
                                class="h-14 w-14 rounded-full relative"
                                :disabled="!canPlay"
                            >
                                <RefreshCw v-if="isBuffering" class="h-6 w-6 animate-spin" />
                                <Play v-else-if="!isPlaying" class="h-6 w-6 ml-0.5" />
                                <Pause v-else class="h-6 w-6" />
                            </Button>

                            <Button @click="skipForward" variant="outline" size="sm" :disabled="!canPlay">
                                <SkipForward class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm text-muted-foreground">
                                <span>{{ formattedCurrentTime }}</span>
                                <span>{{ formattedDuration }}</span>
                            </div>
                            <Slider
                                :value="[currentTime]"
                                :max="duration"
                                :step="1"
                                @update:modelValue="(value) => seek(value[0])"
                                class="w-full"
                                :disabled="!canPlay"
                            />
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>{{ Math.round(progressBarValue) }}% played</span>
                                <!-- ✅ SHOW CONTENT LISTENED -->
                                <span>{{ formattedContentTimeListened }}</span>
                            </div>
                        </div>

                        <!-- Additional Controls -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <!-- Volume Control -->
                            <div class="flex items-center gap-3">
                                <Button @click="toggleMute" variant="ghost" size="sm">
                                    <Volume2 v-if="!isMuted && volume > 0" class="h-4 w-4" />
                                    <VolumeX v-else class="h-4 w-4" />
                                </Button>
                                <Slider
                                    :value="[volume]"
                                    :max="1"
                                    :step="0.1"
                                    @update:modelValue="changeVolume"
                                    class="w-24"
                                />
                                <span class="text-xs text-muted-foreground min-w-[3ch]">
                                    {{ Math.round(volume * 100) }}%
                                </span>
                            </div>

                            <!-- Playback Speed -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground">Speed:</span>
                                <div class="flex gap-1">
                                    <Button
                                        v-for="rate in playbackRates"
                                        :key="rate"
                                        @click="changePlaybackRate(rate)"
                                        :variant="playbackRate === rate ? 'default' : 'outline'"
                                        size="sm"
                                        class="text-xs px-2 py-1 h-auto"
                                    >
                                        {{ rate }}x
                                    </Button>
                                </div>
                            </div>

                            <!-- Mark Complete -->
                            <Button
                                v-if="canMarkComplete && !user_progress.is_completed"
                                @click="markCompleted"
                                variant="outline"
                                size="sm"
                                class="gap-2"
                            >
                                <CheckCircle class="h-4 w-4" />
                                Mark Complete
                            </Button>
                        </div>

                        <!-- Keyboard Shortcuts Help -->
                        <div class="text-xs text-muted-foreground text-center space-y-1">
                            <div class="font-medium">Keyboard Shortcuts:</div>
                            <div class="flex flex-wrap justify-center gap-x-4 gap-y-1">
                                <span><kbd class="px-1 bg-muted rounded">Space</kbd> Play/Pause</span>
                                <span><kbd class="px-1 bg-muted rounded">←/→</kbd> Skip 15s</span>
                                <span><kbd class="px-1 bg-muted rounded">M</kbd> Mute</span>
                                <span><kbd class="px-1 bg-muted rounded">↑/↓</kbd> Volume</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
kbd {
    background-color: hsl(var(--muted));
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, 'DejaVu Sans Mono', monospace;
}
</style>
