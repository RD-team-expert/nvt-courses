<template>
    <Head :title="`${video.name} - Video Course`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Back Button -->
            <Button asChild variant="ghost" class="mb-4">
                <Link href="/videos">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back to Video Courses
                </Link>
            </Button>

            <!-- Video Info Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Thumbnail -->
                        <div class="w-full md:w-64 aspect-video bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg overflow-hidden shrink-0 relative">
                            <img
                                v-if="video.thumbnail_url && !thumbnailError"
                                :src="video.thumbnail_url"
                                :alt="video.name"
                                class="w-full h-full object-cover"
                                @error="handleThumbnailError"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <PlaySquare class="h-16 w-16 text-blue-500" />
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
                                <div v-if="video.category" class="flex items-center gap-2 mb-2">
                                    <FolderOpen class="h-4 w-4 text-muted-foreground" />
                                    <Badge variant="outline">{{ video.category.name }}</Badge>
                                </div>
                                <CardTitle class="text-2xl mb-2">{{ video.name }}</CardTitle>
                                <CardDescription class="text-base">{{ video.description || 'No description available' }}</CardDescription>
                            </div>

                            <!-- Info with Content Time Watched -->
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Clock class="h-4 w-4" />
                                    <span>{{ video.formatted_duration }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-blue-600">
                                    <RotateCcw class="h-4 w-4" />
                                    <span>{{ Math.round(user_progress.completion_percentage) }}% Complete</span>
                                </div>

                                <!-- Content Time Watched (based on current position) -->
                                <div class="flex items-center gap-2 text-sm text-purple-600">
                                    <Eye class="h-4 w-4" />
                                    <span>{{ formattedContentTimeWatched }}</span>
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

                                <!-- Progress Info with Content Watched -->
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>{{ user_progress.formatted_current_time }} / {{ video.formatted_duration }}</span>
                                    <span>{{ formattedContentTimeWatched }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Video Player -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PlaySquare class="h-5 w-5" />
                        Video Player
                        <div v-if="isBuffering" class="ml-2 flex items-center gap-2 text-sm text-muted-foreground">
                            <RefreshCw class="h-4 w-4 animate-spin" />
                            Buffering...
                        </div>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Video Element -->
                    <div class="relative aspect-video bg-black rounded-lg overflow-hidden">
                        <video
                            ref="videoRef"
                            class="w-full h-full"
                            :src="video.google_drive_url"
                            :poster="video.thumbnail_url"
                            controls
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
                            @ratechange="onRateChange"
                            crossorigin="anonymous"
                        >
                            Your browser does not support the video tag.
                        </video>

                        <!-- Loading Overlay -->
                        <div v-if="isLoading && !error" class="absolute inset-0 bg-black/80 flex items-center justify-center">
                            <div class="text-center text-white">
                                <RefreshCw class="h-12 w-12 animate-spin mx-auto mb-4" />
                                <p>Loading video from Google Drive...</p>
                                <div class="text-sm text-gray-300 mt-2">This may take a moment for large files</div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <Alert v-if="error" variant="destructive">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>
                            <div class="space-y-3">
                                <p>{{ error }}</p>
                                <div class="flex gap-2">
                                    <Button @click="retryVideo" size="sm" variant="outline">
                                        <RefreshCw class="h-4 w-4 mr-2" />
                                        Retry
                                    </Button>
                                    <Button asChild size="sm" variant="outline">
                                        <a :href="video.google_drive_url" target="_blank">
                                            <ExternalLink class="h-4 w-4 mr-2" />
                                            Open Direct Link
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </AlertDescription>
                    </Alert>

                    <!-- Video Controls Bar -->
                    <div v-if="!isLoading && !error" class="space-y-4">
                        <!-- Progress and Time -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm text-muted-foreground">
                                <span>{{ formattedCurrentTime }}</span>
                                <span>{{ formattedDuration }}</span>
                            </div>
                            <Slider
                                :value="[currentTime]"
                                :max="duration"
                                :step="0.1"
                                @update:modelValue="(value) => seek(value[0])"
                                class="w-full"
                                :disabled="!canPlay"
                            />
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>{{ Math.round(progressBarValue) }}% watched</span>
                                <span>{{ formattedContentTimeWatched }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between">
                            <!-- Progress Info -->
                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                <Badge :variant="user_progress.is_completed ? 'default' : 'secondary'">
                                    {{ Math.round(user_progress.completion_percentage) }}% Complete
                                </Badge>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <!-- Bookmark Button -->
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="addBookmark"
                                    :disabled="isBookmarking || !canPlay"
                                >
                                    <Bookmark class="h-4 w-4 mr-2" />
                                    {{ isBookmarking ? 'Adding...' : 'Bookmark' }}
                                </Button>

                                <!-- Mark Complete -->
                                <Button
                                    v-if="canMarkComplete && !user_progress.is_completed"
                                    size="sm"
                                    @click="markCompleted"
                                    :disabled="isMarkingComplete"
                                >
                                    <CheckCircle class="h-4 w-4 mr-2" />
                                    {{ isMarkingComplete ? 'Marking...' : 'Mark Complete' }}
                                </Button>

                                <Badge v-else-if="user_progress.is_completed" variant="default" class="px-3 py-1">
                                    <CheckCircle class="h-4 w-4 mr-1" />
                                    Completed
                                </Badge>
                            </div>
                        </div>

                        <!-- Keyboard Shortcuts Help -->
                        <div class="text-xs text-muted-foreground text-center space-y-1">
                            <div class="font-medium">Video Controls:</div>
                            <div class="flex flex-wrap justify-center gap-x-4 gap-y-1">
                                <span><kbd class="px-1 bg-muted rounded">Space</kbd> Play/Pause</span>
                                <span><kbd class="px-1 bg-muted rounded">←/→</kbd> Skip 10s</span>
                                <span><kbd class="px-1 bg-muted rounded">M</kbd> Mute</span>
                                <span><kbd class="px-1 bg-muted rounded">F</kbd> Fullscreen</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bookmarks Section -->
            <Card v-if="bookmarks.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Bookmark class="h-5 w-5" />
                        Your Bookmarks ({{ bookmarks.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3">
                        <div
                            v-for="bookmark in bookmarks"
                            :key="bookmark.id"
                            class="flex items-start justify-between p-3 bg-muted/50 rounded-lg hover:bg-muted transition-colors cursor-pointer"
                            @click="jumpToBookmark(bookmark.timestamp)"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm">{{ bookmark.formatted_timestamp }}</div>
                                <div v-if="bookmark.note" class="text-xs text-muted-foreground mt-1">
                                    {{ bookmark.note }}
                                </div>
                            </div>
                            <div class="flex items-center gap-1 ml-2">
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click.stop="editBookmark(bookmark)"
                                >
                                    <Edit class="h-3 w-3" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click.stop="deleteBookmark(bookmark)"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bookmark Dialog -->
            <Dialog v-model:open="showBookmarkDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add Bookmark</DialogTitle>
                        <DialogDescription>
                            Add a bookmark at {{ formatTime(currentTime) }}
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div>
                            <Label for="bookmark-note">Note (optional)</Label>
                            <Textarea
                                id="bookmark-note"
                                v-model="bookmarkNote"
                                placeholder="Add a note for this bookmark..."
                                rows="3"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showBookmarkDialog = false">
                            Cancel
                        </Button>
                        <Button @click="saveBookmark" :disabled="isSavingBookmark">
                            {{ isSavingBookmark ? 'Saving...' : 'Save Bookmark' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

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
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

// Icons
import {
    PlaySquare,
    ArrowLeft,
    Clock,
    RotateCcw,
    Eye,
    CheckCircle,
    FolderOpen,
    RefreshCw,
    AlertTriangle,
    ExternalLink,
    Bookmark,
    Edit,
    Trash2
} from 'lucide-vue-next'

interface Video {
    id: number
    name: string
    description?: string
    google_drive_url: string
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
    playback_speed: number
    total_watched_time: number
    last_accessed_at?: string
}

interface VideoBookmark {
    id: number
    timestamp: number
    formatted_timestamp: string
    note?: string
    created_at: string
}

const props = defineProps<{
    video: Video
    user_progress: UserProgress
    bookmarks: VideoBookmark[]
}>()

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Video Courses', href: '/videos' },
    { title: props.video.name, href: '#' }
]

// Video player state
const videoRef = ref<HTMLVideoElement>()
const isPlaying = ref(false)
const currentTime = ref(props.user_progress.current_time || 0)
const duration = ref(props.video.duration || 0)
const isLoading = ref(true)
const error = ref('')
const isBuffering = ref(false)
const canPlay = ref(false)
const thumbnailError = ref(false)

// Bookmark state
const showBookmarkDialog = ref(false)
const bookmarkNote = ref('')
const isBookmarking = ref(false)
const isSavingBookmark = ref(false)
const isMarkingComplete = ref(false)

// Content time watched (based on current position)
const contentTimeWatched = computed(() => Math.floor(currentTime.value))

// Progress tracking
const lastSavedTime = ref(currentTime.value)
const saveInterval = ref<NodeJS.Timeout>()
const progressSaveDebounceTimeout = ref<NodeJS.Timeout>()

// Computed properties
const progress = computed(() => duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0)
const formattedCurrentTime = computed(() => formatTime(currentTime.value))
const formattedDuration = computed(() => formatTime(duration.value))
const canMarkComplete = computed(() => progress.value >= 95 || currentTime.value >= duration.value - 30)
const progressBarValue = computed(() => Math.min(progress.value, 100))

// Format content time watched (like "5m 30s watched")
const formattedContentTimeWatched = computed(() => {
    const totalSeconds = contentTimeWatched.value
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60

    if (minutes > 0) {
        if (seconds > 0) {
            return `${minutes}m ${seconds}s watched`
        }
        return `${minutes}m watched`
    }
    return seconds > 0 ? `${seconds}s watched` : '0m watched'
})

// Format time helper
function formatTime(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '00:00'

    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const remainingSeconds = Math.floor(seconds % 60)

    return hours > 0
        ? `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
        : `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
}

// Handle thumbnail error
function handleThumbnailError() {
    thumbnailError.value = true
}

// Player controls
function seek(newTime: number) {
    if (!videoRef.value || !canPlay.value) return

    isBuffering.value = true
    videoRef.value.currentTime = newTime
    currentTime.value = newTime

    // Debounce progress saving when seeking
    if (progressSaveDebounceTimeout.value) {
        clearTimeout(progressSaveDebounceTimeout.value)
    }

    progressSaveDebounceTimeout.value = setTimeout(() => {
        saveProgress()
    }, 1000)
}

// Save progress (like audio player)
async function saveProgress() {
    console.log('saveProgress called!', {
        currentTime: currentTime.value,
        lastSavedTime: lastSavedTime.value,
        videoId: props.video.id
    })

    try {
        const timeDifference = Math.abs(currentTime.value - lastSavedTime.value)

        if (timeDifference >= 1) {
            const response = await axios.post(`/videos/${props.video.id}/progress`, {
                current_time: currentTime.value,
                total_watched_time: props.user_progress.total_watched_time + timeDifference
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
    console.log('markCompleted called for video ID:', props.video.id)

    try {
        isMarkingComplete.value = true
        const response = await axios.post(`/videos/${props.video.id}/complete`, {}, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })

        console.log('Complete API response:', response.data)

        if (response.data.success) {
            console.log('Video marked as completed successfully')
            setTimeout(() => {
                window.location.reload()
            }, 1000)
        }
    } catch (error) {
        console.error('Failed to mark as completed:', error.response || error)
        error.value = 'Failed to mark as completed. Please try again.'
    } finally {
        isMarkingComplete.value = false
    }
}

// Bookmark functions
function addBookmark() {
    showBookmarkDialog.value = true
    bookmarkNote.value = ''
    isBookmarking.value = true
}

async function saveBookmark() {
    if (isSavingBookmark.value) return

    isSavingBookmark.value = true

    try {
        const response = await axios.post(`/videos/${props.video.id}/bookmarks`, {
            timestamp: currentTime.value,
            note: bookmarkNote.value.trim() || null
        }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })

        if (response.data.success) {
            // Add to local bookmarks
            props.bookmarks.push(response.data.bookmark)
            showBookmarkDialog.value = false
            bookmarkNote.value = ''
        }
    } catch (error) {
        console.error('Failed to save bookmark:', error)
    } finally {
        isSavingBookmark.value = false
        isBookmarking.value = false
    }
}

function jumpToBookmark(timestamp: number) {
    if (videoRef.value) {
        videoRef.value.currentTime = timestamp
        currentTime.value = timestamp
    }
}

function editBookmark(bookmark: VideoBookmark) {
    console.log('Edit bookmark:', bookmark)
}

async function deleteBookmark(bookmark: VideoBookmark) {
    if (!confirm('Are you sure you want to delete this bookmark?')) return

    try {
        const response = await axios.delete(`/video-bookmarks/${bookmark.id}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })

        if (response.data.success) {
            // Remove from local bookmarks
            const index = props.bookmarks.findIndex(b => b.id === bookmark.id)
            if (index > -1) {
                props.bookmarks.splice(index, 1)
            }
        }
    } catch (error) {
        console.error('Failed to delete bookmark:', error)
    }
}

// Retry loading video
function retryVideo() {
    if (!videoRef.value) return

    error.value = ''
    isLoading.value = true
    canPlay.value = false
    videoRef.value.load()
}

// Video event listeners
function onLoadStart() {
    isLoading.value = true
    isBuffering.value = true
    console.log('Video loading started')
}

function onCanPlay() {
    canPlay.value = true
    isBuffering.value = false
    console.log('Video can start playing')
}

function onLoadedData() {
    if (!videoRef.value) return

    duration.value = videoRef.value.duration || props.video.duration

    // Resume from saved position
    if (currentTime.value > 0 && currentTime.value < duration.value) {
        videoRef.value.currentTime = currentTime.value
    }

    isLoading.value = false
    isBuffering.value = false
    canPlay.value = true
    error.value = ''

    console.log('Video loaded successfully, duration:', duration.value, 'resume from:', currentTime.value)
}

function onTimeUpdate() {
    if (!videoRef.value) return
    currentTime.value = videoRef.value.currentTime
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
    console.log('Video is buffering...')
}

function onPlaying() {
    isBuffering.value = false
    isPlaying.value = true
}

function onError(event: Event) {
    console.error('Video loading error:', event)

    if (videoRef.value?.error) {
        const errorCode = videoRef.value.error.code
        console.error('Video error code:', errorCode)

        switch (errorCode) {
            case 1: // MEDIA_ERR_ABORTED
                error.value = 'Video loading was aborted. Please try again.'
                break
            case 2: // MEDIA_ERR_NETWORK
                error.value = 'Network error while loading video. Check your connection and try again.'
                break
            case 3: // MEDIA_ERR_DECODE
                error.value = 'Video file is corrupted or in an unsupported format.'
                break
            case 4: // MEDIA_ERR_SRC_NOT_SUPPORTED
                error.value = 'Video file format is not supported or file is not accessible.'
                break
            default:
                error.value = 'Failed to load video. The file might be private or moved.'
        }
    } else {
        error.value = 'Failed to load video from Google Drive. Please check if the file is publicly accessible.'
    }

    isLoading.value = false
    isBuffering.value = false
    canPlay.value = false
}

function onRateChange() {
    // Handle playback rate changes if needed
    console.log('Playback rate changed')
}

// Lifecycle management
onMounted(() => {
    // Initialize video settings
    if (videoRef.value) {
        videoRef.value.preload = 'metadata'
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
                if (videoRef.value) {
                    if (videoRef.value.paused) {
                        videoRef.value.play()
                    } else {
                        videoRef.value.pause()
                    }
                }
                break
            case 'ArrowLeft':
                event.preventDefault()
                seek(Math.max(currentTime.value - 10, 0))
                break
            case 'ArrowRight':
                event.preventDefault()
                seek(Math.min(currentTime.value + 10, duration.value))
                break
            case 'KeyM':
                event.preventDefault()
                if (videoRef.value) {
                    videoRef.value.muted = !videoRef.value.muted
                }
                break
            case 'KeyF':
                event.preventDefault()
                if (videoRef.value) {
                    if (document.fullscreenElement) {
                        document.exitFullscreen()
                    } else {
                        videoRef.value.requestFullscreen()
                    }
                }
                break
        }
    }

    document.addEventListener('keydown', handleKeyPress)

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyPress)
    })
})
</script>

<style scoped>
kbd {
    background-color: hsl(var(--muted));
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, 'DejaVu Sans Mono', monospace;
}
</style>
