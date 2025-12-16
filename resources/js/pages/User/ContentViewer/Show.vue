<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import axios from 'axios'

// ✅ vue-pdf-embed import
import VuePdfEmbed from 'vue-pdf-embed'



// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'

// Icons
import {
    ArrowLeft,
    Play,
    Pause,
    SkipBack,
    SkipForward,
    Volume2,
    VolumeX,
    Maximize,
    CheckCircle,
    Clock,
    FileText,
    Video,
    RotateCcw,
    ZoomIn,
    ZoomOut,
    Download,
    ChevronLeft,
    ChevronRight,
    RotateCw,
    Minimize,
    AlertCircle,
    Eye,
    BookOpen,
    Timer,
    TrendingUp,
    Target,
    Activity,
    Loader2
} from 'lucide-vue-next'

// ✅ ENHANCED: Interface with PDF page count
interface Content {
    id: number
    title: string
    content_type: 'video' | 'pdf'
    is_required?: boolean
    duration?: number | null
    video?: {
        id: number
        name: string
        duration: number
        thumbnail_url?: string | null
        streaming_url?: string | null
    } | null
    pdf_name?: string | null
    file_url?: string | null
    pdf_page_count?: number | null
    has_pdf_page_count?: boolean
    estimated_reading_time?: number | null
}

interface Module {
    id: number
    name: string
    order_number?: number
}

interface Course {
    id: number
    name: string
}

interface UserProgress {
    id?: number
    current_position?: number
    completion_percentage?: number
    is_completed?: boolean
    time_spent?: number
}

interface Navigation {
    previous?: {
        id: number
        title: string
        content_type: string
    } | null
    next?: {
        id: number
        title: string
        content_type: string
        is_unlocked: boolean
    } | null
}

interface Video {
    id: number
    name: string
    description: string | null
    duration: number | null
    thumbnail_url: string | null
    streaming_url: string
    key_id: number
    key_name: string
    transcode_status?: string
    available_qualities?: string[]
    has_multiple_qualities?: boolean
}


const props = defineProps<{
    content: Content
    module?: Module
    course?: Course
    userProgress?: UserProgress
    navigation?: Navigation
    video?: Video  
    pdf?: any




}>()

// console.log('📦 Props received:', {
//     content: props.content,
//     video: props.video,  // ✅ Use props.video directly!
//     has_streaming_url: !!props.video?.streaming_url,
//     streaming_url: props.video?.streaming_url
// });

// ✅ GET CSRF TOKEN
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

// ✅ Add null safety with defaults
const safeModule = computed(() => props.module || { id: 0, name: 'Unknown Module', order_number: 1 })
const safeCourse = computed(() => props.course || { id: 0, name: 'Unknown Course' })
const safeUserProgress = computed(() => props.userProgress || {
    id: 0,
    current_position: 0,
    completion_percentage: 0,
    is_completed: false,
    time_spent: 0
})
const safeNavigation = computed(() => props.navigation || { previous: null, next: null })

// ✅ Fix breadcrumbs with null safety - must be computed for reactivity
const breadcrumbs = computed(() => [
    { name: 'My Courses', href: '/courses-online' },
    { name: safeCourse.value.name, href: `/courses-online/${safeCourse.value.id}` },
    { name: props.content.title, href: '#' }
])

// ========== VIDEO STATE ==========
const videoElement = ref<HTMLVideoElement | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(1)
const previousVolume = ref(1)  // ✅ Store previous volume for mute toggle
const isMuted = ref(false)     // ✅ Track mute state
const isFullscreen = ref(false)
const isVideoLoading = ref(true)
const isVideoReady = ref(false)

// ========== QUALITY SELECTOR STATE ==========
const availableQualities = ref<string[]>(['original'])
const selectedQuality = ref<string>('original')
const isQualitySwitching = ref(false)

// ========== PDF STATE ==========
const pdfContainer = ref<HTMLDivElement | null>(null)
const currentPage = ref(1)
const totalPages = ref(0)
const pdfZoom = ref(100)
const pdfRotation = ref(0)
const isPdfLoaded = ref(false)
const isPdfFullscreen = ref(false)
const pdfReadingTime = ref(0)
const pdfScrollPercentage = ref(0)
const estimatedReadingTime = ref(0)
const averageReadingSpeed = ref(200) // words per minute
const pdfLoadingError = ref(false)
const loadingStrategy = ref('vue-pdf-embed')
const errorMessage = ref('')

// ========== SHARED STATE ==========
const sessionId = ref<number | null>(null)
const lastProgressUpdate = ref(0)
const isLoading = ref(false)
const completionPercentage = ref(safeUserProgress.value.completion_percentage || 0)
const isCompleted = ref(safeUserProgress.value.is_completed || false)
const timeSpent = ref(safeUserProgress.value.time_spent || 0)
const isCreatingSession = ref(false)  // ✅ NEW: Prevent duplicate session creation

// ✅ NEW: Skip/Seek Tracking Variables
const totalSkipCount = ref(0)
const totalSeekCount = ref(0)
const totalPauseCount = ref(0)
const totalWatchTime = ref(0)

// ✅ NEW: Incremental counters (since last heartbeat)
const skipCountSinceLastHeartbeat = ref(0)
const seekCountSinceLastHeartbeat = ref(0)
const pauseCountSinceLastHeartbeat = ref(0)
const watchTimeSinceLastHeartbeat = ref(0)
const lastCurrentTime = ref(0)

// ✅ NEW: Active Playback Time Tracking (Task 6.1)
// Only tracks NEW playback in THIS session (not including saved time from DB)
const activePlaybackTime = ref(0)           // Accumulated active playback seconds THIS SESSION
const isActivelyPlaying = ref(false)        // True only when video is actually playing
const isBuffering = ref(false)              // True during buffering
const lastActiveTimeUpdate = ref(0)         // Timestamp of last active time update

// ✅ NEW: Video Event Logging (Task 6.4)
interface VideoEvent {
    type: 'pause' | 'resume' | 'rewind'
    timestamp: number
    position: number
    startPosition?: number
    endPosition?: number
}
const videoEvents = ref<VideoEvent[]>([])

// ========== COMPUTED PROPERTIES ==========
const formattedCurrentTime = computed(() => formatTime(currentTime.value))
const formattedDuration = computed(() => formatTime(duration.value))
// ✅ FIXED: Show ONLY current session active playback time (not previous sessions)
const formattedTimeSpent = computed(() => {
    // Only show time from THIS session (activePlaybackTime is in seconds)
    return formatTime(activePlaybackTime.value)
})
const formattedPdfReadingTime = computed(() => {
    // For PDFs, we still use wall-clock time since there's no "playing" state
    const totalSeconds = pdfReadingTime.value
    return formatTime(totalSeconds)
})
const formattedEstimatedTime = computed(() => formatTime(estimatedReadingTime.value * 60))

const progressPercentage = computed(() => {
    if (props.content.content_type === 'video') {
        return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0
    } else if (props.content.content_type === 'pdf') {
        return totalPages.value > 0 ? (currentPage.value / totalPages.value) * 100 : 0
    }
    return 0
})

const contentIcon = computed(() => props.content.content_type === 'video' ? Video : FileText)

// ✅ NEW: Allowed time window (Task 6.1) - Video Duration × 2
const allowedTimeMinutes = computed(() => {
    if (props.content.content_type === 'video' && duration.value > 0) {
        return (duration.value / 60) * 2  // Duration × 2 in minutes
    }
    return 0
})

// ✅ NEW: Allowed time display text (Task 6.3)
const allowedTimeDisplay = computed(() => {
    if (props.content.content_type === 'video' && allowedTimeMinutes.value > 0) {
        const minutes = Math.ceil(allowedTimeMinutes.value)
        return `You are expected to complete this video within ${minutes} minutes`
    }
    return ''
})

// ========== PDF SOURCE DETECTION ==========
const detectPdfSource = (url: string) => {
    if (!url) return 'unknown'

    if (url.includes('drive.google.com')) {
        return 'google_drive'
    }

    if (url.includes('/storage/') || url.includes('app/public/') ||
        url.startsWith('/') || url.includes('localhost') ||
        url.includes(window.location.hostname)) {
        return 'storage'
    }

    return 'external'
}


// ✅ Normalize Google Drive URLs to /preview form for embedding
const normalizeGoogleDriveUrl = (url: string): string => {
    if (!url) return url

    // Already a preview/embed URL
    if (url.includes('/preview') || url.includes('/embedded')) {
        return url
    }

    // Pattern: https://drive.google.com/file/d/FILE_ID/view?...
    const fileMatch = url.match(/\/file\/d\/([^/]+)\//)
    if (fileMatch) {
        return `https://drive.google.com/file/d/${fileMatch[1]}/preview`
    }

    // Pattern: https://drive.google.com/open?id=FILE_ID
    const openMatch = url.match(/[?&]id=([^&]+)/)
    if (openMatch) {
        return `https://drive.google.com/file/d/${openMatch[1]}/preview`
    }

    // Fallback – keep as is
    return url
}


const effectivePdfUrl = computed(() => {
    // 1) Local storage file
    if (props.pdf?.file_url) {
        return props.pdf.file_url
    }

    // 2) Google Drive link
    if (props.pdf?.google_drive_url) {
        return normalizeGoogleDriveUrl(props.pdf.google_drive_url)
    }

    // 3) Fallback to content.file_url if it exists
    if (props.content?.file_url) {
        return props.content.file_url
    }

    return ''
})


// ✅ ENHANCED: Get PDF source for vue-pdf-embed
const pdfSource = computed(() => {
    const fileUrl = effectivePdfUrl.value

    // console.log('🔍 Initial fileUrl:', fileUrl)

    if (!fileUrl) {
        // console.log('content.file_url:', props.content.file_url)
        // console.log('pdf.file_url:', props.pdf?.file_url)
        // console.warn('⚠️ No PDF URL provided')
        return null
    }

    const source = detectPdfSource(fileUrl)
    // console.log('🔍 Detected source type:', source)

    if (source === 'storage') {
        let storageUrl = fileUrl

        if (!storageUrl.startsWith('http://') && !storageUrl.startsWith('https://')) {
            if (storageUrl.startsWith('/storage/')) {
                storageUrl = `${window.location.origin}${storageUrl}`
            } else if (storageUrl.includes('app/public/')) {
                const path = storageUrl.split('app/public/')[1]
                storageUrl = `${window.location.origin}/storage/${path}`
            } else {
                storageUrl = `${window.location.origin}/storage/${storageUrl.replace(/^\/+/, '')}`
            }
        }

        // console.log('✅ Final storage URL:', storageUrl)
        return storageUrl
    }

    return fileUrl
})





// ========== UTILITY METHODS ==========
const formatTime = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = Math.floor(seconds % 60)

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`
}

// ✅ NEW: Reset incremental counters
const resetIncrementalCounters = () => {
    skipCountSinceLastHeartbeat.value = 0
    seekCountSinceLastHeartbeat.value = 0
    pauseCountSinceLastHeartbeat.value = 0
    watchTimeSinceLastHeartbeat.value = 0
}

// ✅ NEW: Update active playback time (Task 6.2)
// Only increments when video is playing AND not buffering/loading
// ✅ Task 8.1: Skip tracking for completed videos
const updateActivePlaybackTime = () => {
    // Skip tracking if video is already completed (Task 8.1)
    if (isCompleted.value) {
        return
    }
    
    if (isActivelyPlaying.value && !isBuffering.value && !isVideoLoading.value) {
        const now = Date.now()
        const elapsed = (now - lastActiveTimeUpdate.value) / 1000
        
        // Only add reasonable increments (between 0 and 2 seconds)
        if (elapsed > 0 && elapsed < 2) {
            activePlaybackTime.value += elapsed
        }
        
        lastActiveTimeUpdate.value = now
    }
}

// ✅ NEW: Log video events (Task 6.4)
const logVideoEvent = (type: 'pause' | 'resume' | 'rewind', data?: any) => {
    const event: VideoEvent = {
        type,
        timestamp: Date.now(),
        position: currentTime.value,
        ...data
    }
    videoEvents.value.push(event)
    // console.log('📹 Video event logged:', event)
}

// ========== SESSION MANAGEMENT ==========
const startSession = async () => {
    // ✅ Task 8.1: Skip session creation for completed videos
    if (isCompleted.value && props.content.content_type === 'video') {
        // console.log('⏭️ Skipping session creation - video already completed')
        return
    }
    
    // ✅ FIXED: Prevent duplicate session creation with lock
    if (sessionId.value || isCreatingSession.value) return

    isCreatingSession.value = true
    // console.log('🚀 Starting session for content:', props.content.id)

    try {
        // ✅ Get the key_id from the video data passed from backend
        const keyId = props.video?.key_id || null

        const response = await axios.post(`/content/${props.content.id}/session`, {
            action: 'start',
            position: currentTime.value || 0,
            api_key_id: keyId  // ✅ Send the key_id from frontend
        })

        if (response.data.success) {
            sessionId.value = response.data.session_id
            // console.log('✅ Session started:', sessionId.value)
        }
    } catch (error) {
        // console.error('❌ Failed to start session:', error)
    } finally {
        isCreatingSession.value = false
    }
}


const updateProgress = async () => {
    // ✅ Task 8.1: Skip progress updates for completed videos
    if (isCompleted.value && props.content.content_type === 'video') {
        return
    }
    
    if (!sessionId.value) return

    const now = Date.now()
    if (now - lastProgressUpdate.value < 5000) return

    lastProgressUpdate.value = now

    try {
        const currentPosition = props.content.content_type === 'video'
            ? currentTime.value
            : currentPage.value

        // For videos, use active playback time; for PDFs, use reading time
        const sessionTimeMinutes = props.content.content_type === 'video'
            ? Math.floor(activePlaybackTime.value / 60)
            : Math.floor(pdfReadingTime.value / 60)

        const payload = {
            current_position: currentPosition,
            completion_percentage: progressPercentage.value,
            watch_time: sessionTimeMinutes,
        }

        // console.log('📤 Sending progress update:', payload)

        const response = await axios.post(`/content/${props.content.id}/progress`, payload, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })

        if (response.data.success) {
            completionPercentage.value = response.data.completion_percentage || progressPercentage.value
            isCompleted.value = response.data.is_completed || false
            timeSpent.value = response.data.total_watch_time || sessionTimeMinutes

            // console.log('✅ Progress updated:', completionPercentage.value + '%')
        }

        // ✅ REMOVED: Don't call sendHeartbeat here to reduce API calls
        // Heartbeat is now sent separately every 10 minutes

    } catch (error) {
        // console.error('❌ Failed to update progress:', error)
    }
}

// ✅ NEW: Heartbeat function with skip/seek tracking
const sendHeartbeat = async () => {
    // ✅ Task 8.1: Skip heartbeat for completed videos
    if (isCompleted.value && props.content.content_type === 'video') {
        return
    }
    
    if (!sessionId.value) return

    try {
        const currentPosition = props.content.content_type === 'video'
            ? currentTime.value
            : currentPage.value

        // Calculate actual watch time increment
        if (props.content.content_type === 'video' && isPlaying.value) {
            const timeDiff = currentTime.value - lastCurrentTime.value
            if (timeDiff > 0 && timeDiff < 10) { // Reasonable time diff (not seeking)
                watchTimeSinceLastHeartbeat.value += timeDiff
                totalWatchTime.value += timeDiff
            }
            lastCurrentTime.value = currentTime.value
        }

        const payload = {
            action: 'heartbeat',
            current_position: currentPosition,
            // Send incremental data since last heartbeat
            skip_count: skipCountSinceLastHeartbeat.value,
            seek_count: seekCountSinceLastHeartbeat.value,
            pause_count: pauseCountSinceLastHeartbeat.value,
            watch_time: Math.floor(watchTimeSinceLastHeartbeat.value),
            // ✅ NEW: Send active playback time (Task 6.5)
            active_playback_time: Math.floor(activePlaybackTime.value),
        }

        // console.log('💓 Sending heartbeat with skip/seek data:', payload)

        const response = await axios.post(`/content/${props.content.id}/session`, payload, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })

        if (response.data.success) {
            // console.log('✅ Heartbeat sent successfully')
            // Reset incremental counters after successful heartbeat
            resetIncrementalCounters()
        }

    } catch (error) {
        // console.error('❌ Failed to send heartbeat:', error)
    }
}

const endSession = async () => {
    if (!sessionId.value) return

    // console.log('🛑 Ending session:', sessionId.value)

    try {
        const payload = {
            action: 'end',
            current_position: props.content.content_type === 'video' ? currentTime.value : currentPage.value,
            // Send any remaining incremental data
            skip_count: skipCountSinceLastHeartbeat.value,
            seek_count: seekCountSinceLastHeartbeat.value,
            pause_count: pauseCountSinceLastHeartbeat.value,
            watch_time: Math.floor(watchTimeSinceLastHeartbeat.value),
            // ✅ NEW: Send active playback time and video events (Task 6.5)
            active_playback_time: Math.floor(activePlaybackTime.value),
            video_events: videoEvents.value,
        }

        await axios.post(`/content/${props.content.id}/session`, payload, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })

        // console.log('✅ Session ended')
        sessionId.value = null
        sessionStartTime.value = null
    } catch (error) {
        // console.error('❌ Failed to end session:', error)
    }
}

const markCompleted = async () => {
    // console.log('🎯 Marking content as completed:', props.content.id)

    try {
        isLoading.value = true

        let response;
        try {
            response = await axios.post(`/content/${props.content.id}/complete`, {}, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
        } catch (error) {
            if (error.response?.status === 404) {
                // console.log('🔄 /complete route not found, trying /mark-complete')
                response = await axios.post(`/content/${props.content.id}/mark-complete`, {}, {
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                })
            } else {
                throw error
            }
        }

        if (response.data.success) {
            isCompleted.value = true
            completionPercentage.value = 100

            // console.log('✅ Content marked as completed successfully!')

            if (safeNavigation.value.next?.is_unlocked) {
                setTimeout(() => {
                    router.visit(route('content.show', safeNavigation.value.next.id))
                }, 2000)
            } else {
                setTimeout(() => {
                    router.visit(route('courses-online.show', safeCourse.value.id))
                }, 2000)
            }
        }
    } catch (error) {
        // console.error('❌ Failed to mark as completed:', error)

        if (error.response?.data?.message) {
            alert(`Error: ${error.response.data.message}`)
        } else {
            alert('Failed to mark content as completed. Please try again.')
        }
    } finally {
        isLoading.value = false
    }
}

// ========== VIDEO METHODS ==========
const togglePlay = async () => {
    if (!videoElement.value) return

    if (isPlaying.value) {
        await videoElement.value.pause()
    } else {
        await videoElement.value.play()
    }
}

// ✅ Seek function (tracking is handled by onVideoSeeked event)
const seek = (seconds: number) => {
    if (!videoElement.value) return

    const newTime = Math.max(0, Math.min(duration.value, seconds))
    videoElement.value.currentTime = newTime
    // ✅ REMOVED: Duplicate seek tracking - onVideoSeeked event handles this
}

// ✅ Enhanced with skip tracking
const seekRelative = (seconds: number) => {
    // Track skip behavior
    if (Math.abs(seconds) >= 10) { // Skip if seeking by 10+ seconds
        skipCountSinceLastHeartbeat.value++
        totalSkipCount.value++

        // console.log('⏭️ Skip detected:', {
        //     seconds: seconds,
        //     totalSkips: totalSkipCount.value
        // })
    }

    seek(currentTime.value + seconds)
}

const setVolume = (newVolume: number) => {
    volume.value = Math.max(0, Math.min(1, newVolume))
    if (videoElement.value) {
        videoElement.value.volume = volume.value
    }
    // ✅ Update mute state based on volume
    isMuted.value = volume.value === 0
}

// ✅ NEW: Toggle mute function
const toggleMute = () => {
    if (!videoElement.value) return
    
    if (isMuted.value) {
        // Unmute - restore previous volume
        volume.value = previousVolume.value > 0 ? previousVolume.value : 0.5
        videoElement.value.volume = volume.value
        isMuted.value = false
    } else {
        // Mute - save current volume and set to 0
        previousVolume.value = volume.value
        volume.value = 0
        videoElement.value.volume = 0
        isMuted.value = true
    }
}

const toggleFullscreen = async () => {
    if (!videoElement.value) return

    try {
        if (!isFullscreen.value) {
            await videoElement.value.requestFullscreen()
        } else {
            await document.exitFullscreen()
        }
    } catch (error) {
        console.error('Fullscreen error:', error)
    }
}

// ========== PDF METHODS ==========
const tryAlternativeLoad = () => {
    // console.log('🔄 Trying alternative loading method...')

    const sourceType = detectPdfSource(props.content.file_url || '')

    if (sourceType === 'google_drive') {
        loadingStrategy.value = 'google-embed'
        // console.log('📄 Switching to Google Drive embed')
    } else {
        loadingStrategy.value = 'iframe'
        // console.log('📄 Switching to iframe loading')
    }

    pdfLoadingError.value = false
    isLoading.value = true
}

const tryDirectUrl = () => {
    if (pdfSource.value) {
        window.open(pdfSource.value, '_blank')
    }
}

const tryIframeLoad = () => {
    loadingStrategy.value = 'iframe'
    pdfLoadingError.value = false
    isLoading.value = true
    // console.log('📄 Trying iframe loading strategy')
}

const onPdfLoaded = async (pdf: any) => {
    // console.log('✅ PDF loaded successfully via vue-pdf-embed:', pdf)

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        // console.log('🎯 Using database page count:', props.content.pdf_page_count)
    } else if (detectPdfSource(props.content.file_url || '') === 'storage' && pdf.numPages > 0) {
        totalPages.value = pdf.numPages
        // console.log('📄 Using vue-pdf-embed page count for local PDF:', pdf.numPages)
    } else {
        totalPages.value = 10
        // console.warn('⚠️ No database page count found, using default: 10')
    }

    estimatedReadingTime.value = Math.ceil((totalPages.value * 2))
    isPdfLoaded.value = true
    pdfLoadingError.value = false
    isLoading.value = false

    currentPage.value = Math.max(1, Math.min(totalPages.value, safeUserProgress.value.current_position || 1))

    if (!sessionId.value) {
        startSession()
    }
}

const onPdfLoadingFailed = (error: any) => {
    // console.error('❌ vue-pdf-embed loading failed:', error)

    errorMessage.value = error.message || 'Failed to load PDF with vue-pdf-embed'
    pdfLoadingError.value = true
    isPdfLoaded.value = false
    isLoading.value = false

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        estimatedReadingTime.value = Math.ceil((totalPages.value * 2))
        isPdfLoaded.value = true
        pdfLoadingError.value = false

        // console.log('✅ Using database page count despite loading failure:', totalPages.value)

        if (!sessionId.value) {
            startSession()
        }
    } else {
        setTimeout(() => {
            // console.log('🔄 Auto-trying alternative loading method...')
            tryAlternativeLoad()
        }, 2000)
    }
}

const onPdfRendered = () => {
    // console.log('📄 PDF page rendered successfully')
    isLoading.value = false
}

const onIframeLoad = () => {
    // console.log('✅ Iframe PDF loaded successfully')

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        // console.log('🎯 Using database page count for iframe:', totalPages.value)
    } else {
        totalPages.value = 10
    }

    estimatedReadingTime.value = Math.ceil((totalPages.value * 2))
    isPdfLoaded.value = true
    pdfLoadingError.value = false
    isLoading.value = false

    if (!sessionId.value) {
        startSession()
    }
}

const onIframeError = () => {
    // console.error('❌ Iframe PDF loading failed')

    if (detectPdfSource(props.content.file_url || '') === 'google_drive') {
        // console.log('🔄 Trying Google Drive embed...')
        loadingStrategy.value = 'google-embed'
    } else {
        pdfLoadingError.value = true
        errorMessage.value = 'Iframe loading failed'
        isLoading.value = false
    }
}

const onGoogleEmbedLoad = async () => {
    // console.log('✅ Google Drive embed loaded')

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        // console.log('🎯 Using database page count for Google Drive:', totalPages.value)
    } else {
        totalPages.value = 15
        // console.log('📊 Using default page count for Google Drive embed:', totalPages.value)
    }

    estimatedReadingTime.value = Math.ceil((totalPages.value * 2))
    isPdfLoaded.value = true
    pdfLoadingError.value = false
    isLoading.value = false

    if (!sessionId.value) {
        startSession()
    }
}

const nextPdfPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
        updatePdfProgress()
    }
}

const prevPdfPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
        updatePdfProgress()
    }
}

const gotoPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        updatePdfProgress()
    }
}

const zoomPdf = (direction: 'in' | 'out') => {
    if (direction === 'in' && pdfZoom.value < 200) {
        pdfZoom.value += 25
    } else if (direction === 'out' && pdfZoom.value > 50) {
        pdfZoom.value -= 25
    }
}

const rotatePdf = () => {
    pdfRotation.value = (pdfRotation.value + 90) % 360
}

const togglePdfFullscreen = async () => {
    if (!pdfContainer.value) return

    try {
        if (!isPdfFullscreen.value) {
            await pdfContainer.value.requestFullscreen()
        } else {
            await document.exitFullscreen()
        }
    } catch (error) {
        console.error('PDF Fullscreen error:', error)
    }
}

const downloadPdf = () => {
    if (props.content.file_url) {
        const link = document.createElement('a')
        link.href = props.content.file_url
        link.download = props.content.pdf_name || props.content.title + '.pdf'
        link.target = '_blank'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    }
}

const updatePdfProgress = () => {
    pdfScrollPercentage.value = (currentPage.value / totalPages.value) * 100
    updateProgress()
}

// ========== NAVIGATION ==========
const navigateContent = (contentId: number) => {
    endSession()
    router.visit(route('content.show', contentId))
}

// ========== QUALITY SELECTOR METHODS ==========
/**
 * Change video quality
 * Maintains playback position and playing state during switch
 */
const changeQuality = async (newQuality: string) => {
    if (!videoElement.value || isQualitySwitching.value || newQuality === selectedQuality.value) {
        return
    }

    try {
        isQualitySwitching.value = true
        
        // Save current state
        const wasPlaying = isPlaying.value
        const savedTime = currentTime.value
        
        // Pause video during switch
        if (wasPlaying) {
            await videoElement.value.pause()
        }
        
        // Save quality preference to localStorage
        localStorage.setItem('preferredVideoQuality', newQuality)
        
        // Update selected quality
        selectedQuality.value = newQuality
        
        // Build new streaming URL with quality parameter
        const baseUrl = `/video/stream/${props.video?.id}`
        const newUrl = newQuality === 'original' ? baseUrl : `${baseUrl}/${newQuality}`
        
        // Switch video source
        videoElement.value.src = newUrl
        
        // Wait for video to load
        await new Promise<void>((resolve) => {
            const onLoadedData = () => {
                videoElement.value?.removeEventListener('loadeddata', onLoadedData)
                resolve()
            }
            videoElement.value?.addEventListener('loadeddata', onLoadedData)
        })
        
        // Restore playback position
        videoElement.value.currentTime = savedTime
        
        // Resume playback if it was playing
        if (wasPlaying) {
            await videoElement.value.play()
        }
        
        // console.log(`✅ Quality switched to ${newQuality}`)
    } catch (error) {
        console.error('❌ Failed to switch quality:', error)
    } finally {
        isQualitySwitching.value = false
    }
}

/**
 * Load quality preferences and available qualities on mount
 */
const initializeQualitySelector = () => {
    // Load available qualities from video props
    if (props.video?.available_qualities && props.video.available_qualities.length > 0) {
        availableQualities.value = props.video.available_qualities
    }
    
    // Load saved quality preference from localStorage
    const savedQuality = localStorage.getItem('preferredVideoQuality')
    
    // Apply saved preference if it's available for this video
    if (savedQuality && availableQualities.value.includes(savedQuality)) {
        selectedQuality.value = savedQuality
        
        // Update video source with preferred quality
        if (videoElement.value && props.video?.id) {
            const baseUrl = `/video/stream/${props.video.id}`
            const newUrl = savedQuality === 'original' ? baseUrl : `${baseUrl}/${savedQuality}`
            videoElement.value.src = newUrl
        }
    }
}

// ========== VIDEO EVENT HANDLERS ==========
const onLoadStart = () => {
    // console.log('📹 Video loading started')
    isVideoLoading.value = true
    isVideoReady.value = false
    
    // ✅ Task 6.2: Pause active playback during loading
    isActivelyPlaying.value = false
}

const onLoadedData = () => {
    // console.log('📹 Video data loaded')
    isVideoLoading.value = false
    isVideoReady.value = true
    
    // ✅ Task 6.2: Video is ready, but don't start tracking until play event
}

const onCanPlay = async () => {
    // console.log('✅ Video can start playing');
    isVideoLoading.value = false;
    isVideoReady.value = true;

    // ✅ Task 6.2: Video is ready to play, but don't start active tracking until play event

    // 🔥 NEW: Start session and increment API key usage when video is ready to play
    if (props.video?.key_id && !sessionId.value) {
        try {
            await startSession(); // This will call your backend to increment active_users
            // console.log('✅ Session started, API key incremented');
        } catch (error) {
            // console.error('❌ Failed to start session:', error);
        }
    }
};


const onWaiting = () => {
    // console.log('📹 Video waiting for more data')
    isVideoLoading.value = true
    
    // ✅ Task 6.2: Set buffering state and pause active playback tracking
    isBuffering.value = true
    isActivelyPlaying.value = false  // Pause counter during buffering
}

const onPlaying = () => {
    // console.log('📹 Video started playing')
    isVideoLoading.value = false
    
    // ✅ Task 6.2: Clear buffering state and resume active playback tracking
    isBuffering.value = false
    
    // Resume active playback tracking when video is actually playing
    if (isPlaying.value && isVideoReady.value) {
        isActivelyPlaying.value = true
        lastActiveTimeUpdate.value = Date.now()
    }
}

const onLoadedMetadata = () => {
    if (videoElement.value) {
        duration.value = videoElement.value.duration
        videoElement.value.currentTime = safeUserProgress.value.current_position || 0
    }
}

// ✅ FIXED: Throttle timeUpdate to once per second to reduce CPU usage
let lastTimeUpdate = 0
const onTimeUpdate = () => {
    if (videoElement.value) {
        const now = Date.now()
        if (now - lastTimeUpdate >= 1000) {  // Throttle to 1 second
            currentTime.value = videoElement.value.currentTime
            previousSeekPosition = currentTime.value  // ✅ NEW: Track position for rewind detection (Task 6.4)
            lastTimeUpdate = now
        }
    }
}

const onPlay = () => {
    isPlaying.value = true
    lastCurrentTime.value = currentTime.value

    // ✅ Task 8.1: Skip tracking for completed videos
    if (!isCompleted.value) {
        // ✅ Task 6.2: Start active playback tracking
        // Only start if video is ready and not buffering
        if (isVideoReady.value && !isBuffering.value && !isVideoLoading.value) {
            isActivelyPlaying.value = true
            lastActiveTimeUpdate.value = Date.now()
        }

        // ✅ NEW: Log resume event (Task 6.4)
        logVideoEvent('resume')

        if (!sessionId.value) {
            startSession()
        }
    }
}

// ✅ Enhanced with pause tracking
const onPause = () => {
    isPlaying.value = false

    // ✅ Task 8.1: Skip tracking for completed videos
    if (!isCompleted.value) {
        // ✅ NEW: Stop active playback tracking (Task 6.2)
        isActivelyPlaying.value = false

        // ✅ NEW: Log pause event (Task 6.4)
        logVideoEvent('pause')

        // Track pause count
        pauseCountSinceLastHeartbeat.value++
        totalPauseCount.value++

        // console.log('⏸️ Pause detected, total pauses:', totalPauseCount.value)

        updateProgress()
    } else {
        // Still stop playback tracking even for completed videos
        isActivelyPlaying.value = false
    }
}

const onEnded = () => {
    isPlaying.value = false
    
    // ✅ Task 6.2: Stop active playback tracking when video ends
    isActivelyPlaying.value = false
    
    updateProgress()
    if (progressPercentage.value >= 100) {
        markCompleted()
    }
}

const onVolumeChange = () => {
    if (videoElement.value) {
        volume.value = videoElement.value.volume
    }
}

const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement
    isPdfFullscreen.value = !!document.fullscreenElement
}

// ✅ Video seek event handler (will be added in onMounted)
// ✅ Track previous position for rewind detection (Task 6.4)
let previousSeekPosition = 0
const onVideoSeeked = () => {
    // console.log('🔍 Manual seek detected via timeline')
    seekCountSinceLastHeartbeat.value++
    totalSeekCount.value++
    
    // ✅ NEW: Log rewind events (Task 6.4)
    const currentPosition = currentTime.value
    if (currentPosition < previousSeekPosition) {
        // User rewound the video
        logVideoEvent('rewind', {
            startPosition: previousSeekPosition,
            endPosition: currentPosition
        })
    }
    previousSeekPosition = currentPosition
}




// ========== LIFECYCLE ==========
// LIFECYCLE
onMounted(async () => {
    document.addEventListener('fullscreenchange', onFullscreenChange)

    // Initialize quality selector for videos
    if (props.content.content_type === 'video') {
        initializeQualitySelector()
    }

    // Initialize based on content type
    if (props.content.content_type === 'pdf') {
        // Track PDF reading time (simple wall-clock time for PDFs)
        const pdfTimeInterval = setInterval(() => {
            if (isPdfLoaded.value && !isCompleted.value) {
                pdfReadingTime.value++
            }
        }, 1000)
        
        // Store interval for cleanup
        onUnmounted(() => {
            clearInterval(pdfTimeInterval)
        })
    } else if (props.content.content_type === 'video') {
        await nextTick()
        if (videoElement.value) {
            videoElement.value.currentTime = safeUserProgress.value.current_position || 0
            videoElement.value.addEventListener('seeked', onVideoSeeked)
        }
    }

    // ✅ FIXED: Progress tracking interval - changed from 3 min to 10 min to reduce server load
    const progressInterval = setInterval(() => {
        if (
            (props.content.content_type === 'video' && isPlaying.value) ||
            (props.content.content_type === 'pdf' && isPdfLoaded.value && totalPages.value > 0)
        ) {
            updateProgress()
        }
    }, 600000)  // ✅ CHANGED: 10 minutes (was 180000 = 3 minutes) - reduces API calls by 70%

    // ✅ NEW: Active playback time tracking interval (Task 6.2)
    // This is the ONLY timer we need - it tracks actual playback time
    const activePlaybackInterval = setInterval(() => {
        if (props.content.content_type === 'video' && !isCompleted.value) {
            updateActivePlaybackTime()
        }
    }, 1000)  // Update every second

    // ✅ IMPROVED: Multiple cleanup handlers for reliability
   const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    // console.log('🚨 beforeunload triggered', {
    //     hasSession: !!sessionId.value,
    //     sessionId: sessionId.value
    // })

    if (sessionId.value) {
        const formData = new FormData()
        formData.append('action', 'end')
        formData.append('final_position', currentTime.value.toString())
        formData.append('completion_percentage', progressPercentage.value.toString())
        formData.append('final_watch_time', watchTimeSinceLastHeartbeat.value.toString())
        formData.append('final_skip', skipCountSinceLastHeartbeat.value.toString())
        formData.append('final_seek', seekCountSinceLastHeartbeat.value.toString())
        formData.append('final_pause', pauseCountSinceLastHeartbeat.value.toString())
        // ✅ NEW: Send active playback time and video events (Task 6.5)
        formData.append('active_playback_time', Math.floor(activePlaybackTime.value).toString())
        formData.append('video_events', JSON.stringify(videoEvents.value))

        const url = `/content/${props.content.id}/session`

        // console.log('📤 Sending sendBeacon to:', url)
        const success = navigator.sendBeacon(url, formData)
        // console.log('📬 sendBeacon result:', success ? 'SUCCESS' : 'FAILED')
    }
}



    // ✅ FIXED: Handle visibility changes (tab switching, minimizing)
    const handleVisibilityChange = async () => {
        if (document.visibilityState === 'hidden') {
            // User left the tab - save progress only
            await updateProgress()
            // ✅ REMOVED: Don't send separate heartbeat to reduce API calls
        }
        // ✅ FIXED: Don't create new sessions when user returns to tab
        // Session should persist across tab switches
    }



    // ✅ Handle page hide (more reliable than beforeunload)
   const handlePageHide = () => {
    if (sessionId.value) {
        const currentPosition = props.content.content_type === 'video'
            ? currentTime.value
            : currentPage.value

        // ✅ CHANGED: Use FormData instead of Blob with JSON
        const formData = new FormData()
        formData.append('action', 'end')
        formData.append('final_position', currentPosition.toString())
        formData.append('completion_percentage', progressPercentage.value.toString())
        formData.append('final_watch_time', watchTimeSinceLastHeartbeat.value.toString())
        formData.append('final_skip', skipCountSinceLastHeartbeat.value.toString())
        formData.append('final_seek', seekCountSinceLastHeartbeat.value.toString())
        formData.append('final_pause', pauseCountSinceLastHeartbeat.value.toString())
        // ✅ NEW: Send active playback time and video events (Task 6.5)
        formData.append('active_playback_time', Math.floor(activePlaybackTime.value).toString())
        formData.append('video_events', JSON.stringify(videoEvents.value))

        // ✅ Add CSRF token
        if (csrfToken) {
            formData.append('_token', csrfToken)
        }

        const url = `/content/${props.content.id}/session`
        navigator.sendBeacon(url, formData)

        // console.log('🏁 Session ended via pagehide')
    }
}


    // Add all event listeners
    window.addEventListener('beforeunload', handleBeforeUnload)
    window.addEventListener('pagehide', handlePageHide)
    document.addEventListener('visibilitychange', handleVisibilityChange)

    // ✅ Cleanup on component unmount
    onUnmounted(() => {
        // Clean up all listeners
        window.removeEventListener('beforeunload', handleBeforeUnload)
        window.removeEventListener('pagehide', handlePageHide)
        document.removeEventListener('visibilitychange', handleVisibilityChange)
        document.removeEventListener('fullscreenchange', onFullscreenChange)

        // End session before unmounting
        endSession()
        clearInterval(progressInterval)
        clearInterval(activePlaybackInterval)  // ✅ Clear active playback interval (Task 6.2)

        // Cleanup video event listener
        if (videoElement.value) {
            videoElement.value.removeEventListener('seeked', onVideoSeeked)
        }
    })

// ✅ REMOVED: Duplicate event listener (line 1064) - was causing double sendBeacon calls

})

// Watch for page changes in PDF
watch(currentPage, (newPage) => {
    if (props.content.content_type === 'pdf' && (isPdfLoaded.value || totalPages.value > 0)) {
        updatePdfProgress()
    }
})


</script>

<template>

    <Head :title="content.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
            <!-- Enhanced Content Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                        <component :is="contentIcon" class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold break-words">{{ content.title }}</h1>
                        <p class="text-sm sm:text-base text-muted-foreground break-words">{{ safeModule.name }} • {{
                            safeCourse.name }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <Badge variant="outline" class="text-xs">
                                {{ content.content_type.toUpperCase() }}
                            </Badge>
                            <Badge v-if="content.is_required" variant="destructive" class="text-xs">
                                Required
                            </Badge>
                            <Badge v-if="content.content_type === 'pdf' && effectivePdfUrl" variant="outline"
                                class="text-xs" :class="{
        'bg-blue-50 text-blue-700': detectPdfSource(effectivePdfUrl) === 'google_drive',
        'bg-green-50 text-green-700': detectPdfSource(effectivePdfUrl) === 'storage',
        'bg-gray-50 text-gray-700': detectPdfSource(effectivePdfUrl) === 'external'
}">
                                {{
                                detectPdfSource(effectivePdfUrl) === 'google_drive' ? 'Google Drive' :
                                detectPdfSource(effectivePdfUrl) === 'storage' ? 'Local Storage' :
                                'External PDF'
                                }}
                            </Badge>

                            <Badge v-if="content.content_type === 'pdf' && content.pdf_page_count" variant="outline"
                                class="text-xs bg-green-50 text-green-700">
                                {{ content.pdf_page_count }} pages (DB)
                            </Badge>

                            <span v-if="content.content_type === 'video' && content.video?.duration"
                                class="text-xs text-muted-foreground flex items-center gap-1">
                                <Clock class="h-3 w-3" />
                                {{ Math.ceil(content.video.duration / 60) }} min
                            </span>
                            <span v-if="content.content_type === 'pdf' && totalPages > 0"
                                class="text-xs text-muted-foreground flex items-center gap-1">
                                <BookOpen class="h-3 w-3" />
                                {{ totalPages }} pages • ~{{ Math.ceil(totalPages * 2) }}min read
                            </span>
                        </div>
                    </div>
                </div>
                <Button asChild variant="outline" class="w-full sm:w-auto">
                    <Link :href="route('courses-online.show', safeCourse.id)">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back to Course
                    </Link>
                </Button>
            </div>

            <!-- ✅ OPTIONAL: Debug Panel to show tracking data -->
            <Card v-if="sessionId" class="mb-4 border-blue-200 bg-blue-50/30">
                <CardContent class="p-4">
                    <div
                        class="text-xs text-muted-foreground grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4">
                        <div>
                            <strong>Session ID:</strong> {{ sessionId }}
                        </div>
                        <div>
                            <strong>Total Skips:</strong> {{ totalSkipCount }}
                        </div>
                        <div>
                            <strong>Total Seeks:</strong> {{ totalSeekCount }}
                        </div>
                        <div>
                            <strong>Total Pauses:</strong> {{ totalPauseCount }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Split Layout - 3/4 for content, 1/4 for sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Main Content Area (3/4 width) -->
                <div class="lg:col-span-3">
                    <!-- Enhanced Video Player with Loading State -->
                    <Card v-if="content.content_type === 'video'" class="overflow-hidden">
                        <!-- ✅ Task 8.2: Completed Video Indicator -->
                        <div v-if="isCompleted" class="px-4 py-3 bg-green-50 border-b border-green-200">
                            <div class="flex items-center gap-2 text-sm text-green-700">
                                <CheckCircle class="h-4 w-4" />
                                <span class="font-medium">Video Completed</span>
                                <span class="text-green-600">• Re-watching is optional and untracked</span>
                            </div>
                        </div>
                        
                        <CardContent class="p-0">
                            <div class="relative bg-black aspect-video">
                                <!-- Video Element -->
                                <video ref="videoElement" :src="video?.streaming_url"
                                    :poster="video?.thumbnail_url || ''" class="w-full h-full" preload="metadata"
                                    controlsList="nodownload nofullscreen noremoteplayback"
                                    disablePictureInPicture
                                    @contextmenu.prevent
                                    @loadstart="onLoadStart" @loadeddata="onLoadedData" @canplay="onCanPlay"
                                    @waiting="onWaiting" @playing="onPlaying" @loadedmetadata="onLoadedMetadata"
                                    @timeupdate="onTimeUpdate" @play="onPlay" @pause="onPause" @ended="onEnded"
                                    @volumechange="onVolumeChange">
                                    Your browser does not support the video tag.
                                </video>

                                <!-- Video Loading Overlay -->
                                <div v-if="isVideoLoading || !isVideoReady"
                                    class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 z-10">
                                    <div class="text-center text-white">
                                        <Loader2 class="h-12 w-12 animate-spin mx-auto mb-4" />
                                        <p class="text-lg font-medium">Loading video...</p>
                                        <p class="text-sm text-gray-300 mt-2">{{ content.video?.name }}</p>
                                        <div class="mt-4">
                                            <div class="w-48 h-1 bg-white/20 rounded-full mx-auto">
                                                <div class="h-1 bg-primary rounded-full animate-pulse"
                                                    style="width: 60%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Controls -->
                                <div v-if="isVideoReady && !isVideoLoading"
                                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                                    <div class="mb-4">
                                        <input type="range" :value="currentTime" :max="duration"
                                            @input="seek(Number(($event.target as HTMLInputElement).value))"
                                            class="w-full h-1 bg-white/30 rounded-lg appearance-none cursor-pointer slider" />
                                    </div>

                                    <div class="flex items-center justify-between text-white">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <!-- Play/Pause Button - Larger and more prominent -->
                                            <Button @click="togglePlay" variant="ghost" size="sm"
                                                class="text-white hover:bg-white/20 hover:scale-110 transition-transform p-2">
                                                <Play v-if="!isPlaying" class="h-6 w-6" />
                                                <Pause v-else class="h-6 w-6" />
                                            </Button>

                                            <!-- Rewind Button -->
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button @click="seekRelative(-5)" variant="ghost" size="sm"
                                                            class="text-white hover:bg-white/30 bg-white/10 transition-all hover:scale-105 px-3 py-1.5 rounded-lg">
                                                            <RotateCcw class="h-4 w-4 mr-1.5" />
                                                            <span class="text-sm font-semibold">5s</span>
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Rewind 5 seconds</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>

                                            <!-- Time Display with better visibility -->
                                            <div class="hidden sm:flex items-center bg-black/40 rounded-lg px-3 py-1.5">
                                                <Clock class="h-3.5 w-3.5 mr-1.5 text-white/70" />
                                                <span class="text-sm font-medium whitespace-nowrap">
                                                    {{ formattedCurrentTime }} <span class="text-white/50">/</span> {{ formattedDuration }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <!-- Volume Control Group -->
                                            <div class="hidden sm:flex items-center gap-2 bg-white/10 rounded-lg px-2 py-1">
                                                <Button @click="toggleMute" variant="ghost" size="sm"
                                                    class="text-white hover:bg-white/20 p-1.5 transition-all hover:scale-105">
                                                    <VolumeX v-if="isMuted" class="h-4 w-4" />
                                                    <Volume2 v-else class="h-4 w-4" />
                                                </Button>
                                                <input type="range" :value="volume" min="0" max="1" step="0.1"
                                                    @input="setVolume(Number(($event.target as HTMLInputElement).value))"
                                                    class="w-20 h-1 bg-white/30 rounded-lg appearance-none cursor-pointer volume-slider" />
                                            </div>

                                            <!-- Mobile: Mute button only -->
                                            <Button @click="toggleMute" variant="ghost" size="sm"
                                                class="sm:hidden text-white hover:bg-white/20 p-2">
                                                <VolumeX v-if="isMuted" class="h-5 w-5" />
                                                <Volume2 v-else class="h-5 w-5" />
                                            </Button>

                                            <!-- Quality Selector (only show if multiple qualities available) -->
                                            <div v-if="availableQualities.length > 1" 
                                                class="relative bg-white/10 rounded-lg px-2 py-1">
                                                <select 
                                                    v-model="selectedQuality" 
                                                    @change="changeQuality(selectedQuality)"
                                                    :disabled="isQualitySwitching"
                                                    class="bg-transparent text-white text-sm font-medium border-none outline-none cursor-pointer pr-6 appearance-none hover:bg-white/20 rounded px-2 py-1 transition-all"
                                                    style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27white%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 0.25rem center; background-size: 1em;">
                                                    <option 
                                                        v-for="quality in availableQualities" 
                                                        :key="quality" 
                                                        :value="quality"
                                                        class="bg-gray-900 text-white">
                                                        {{ quality === 'original' ? 'Original' : quality.toUpperCase() }}
                                                    </option>
                                                </select>
                                                <Loader2 v-if="isQualitySwitching" 
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-white" />
                                            </div>

                                            <!-- Fullscreen Button -->
                                            <Button @click="toggleFullscreen" variant="ghost" size="sm"
                                                class="text-white hover:bg-white/20 p-2 transition-all hover:scale-105">
                                                <Maximize class="h-5 w-5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- PDF Viewer -->
                    <Card v-if="content.content_type === 'pdf'" class="overflow-hidden">
                        <CardHeader>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <CardTitle class="flex items-center gap-2 text-base sm:text-lg break-words">
                                    <FileText class="h-5 w-5" />
                                    {{ content.pdf_name || content.title }}
                                </CardTitle>
                                <div class="flex items-center gap-2">
                                    <Button @click="downloadPdf" variant="outline" size="sm">
                                        <Download class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                <span v-if="content.pdf_page_count" class="text-green-700 font-medium">
                                    ✅ Page count from database: {{ content.pdf_page_count }} pages
                                </span>
                                <span v-else class="text-amber-700">
                                    ⚠️ No page count in database - using detection/fallback
                                </span>
                                • Source: {{ detectPdfSource(content.file_url || '') }}
                            </div>
                        </CardHeader>

                        <!-- PDF Loading State -->
                        <div v-if="isLoading && !isPdfLoaded && !pdfLoadingError && totalPages === 0"
                            class="flex items-center justify-center py-16">
                            <div class="text-center">
                                <Loader2 class="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
                                <p class="text-muted-foreground">Loading PDF...</p>
                                <p class="text-xs text-muted-foreground mt-2">
                                    {{ content.pdf_name }} • {{ detectPdfSource(content.file_url || '') }}
                                </p>
                            </div>
                        </div>

                        <!-- PDF Error State with Solutions -->
                        <div v-else-if="pdfLoadingError && totalPages === 0"
                            class="flex items-center justify-center py-16">
                            <div class="text-center max-w-md">
                                <AlertCircle class="h-12 w-12 text-destructive mx-auto mb-4" />
                                <p class="text-destructive font-medium">Failed to load PDF</p>
                                <p class="text-xs text-muted-foreground mt-2 mb-4">
                                    {{ errorMessage || 'PDF loading failed. Trying alternative methods...' }}
                                </p>

                                <div class="space-y-2">
                                    <Button @click="tryDirectUrl" variant="outline" size="sm" class="w-full">
                                        <Eye class="h-4 w-4 mr-2" />
                                        Open in New Tab
                                    </Button>
                                    <Button @click="tryIframeLoad" variant="outline" size="sm" class="w-full">
                                        <FileText class="h-4 w-4 mr-2" />
                                        Try Iframe Load
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- PDF Controls (show when we have page count) -->
                   <!-- PDF Controls (show only when NOT Google Drive) -->
<div
  v-if="totalPages > 0 && detectPdfSource(effectivePdfUrl) !== 'google_drive'"
  class="px-3 sm:px-6 pb-3 sm:pb-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b"
>
    <div class="flex items-center gap-1 sm:gap-2 w-full sm:w-auto justify-center">
        <Button @click="prevPdfPage" variant="outline" size="sm" :disabled="currentPage <= 1">
            <ChevronLeft class="h-4 w-4" />
        </Button>

        <div class="flex items-center gap-2 px-4">
            <input
                v-model.number="currentPage"
                @change="gotoPage(currentPage)"
                type="number"
                :min="1"
                :max="totalPages"
                class="w-12 sm:w-16 px-1 sm:px-2 py-1 text-xs sm:text-sm border rounded text-center"
            />
            <span class="text-sm text-muted-foreground">of {{ totalPages }}</span>
        </div>

        <Button @click="nextPdfPage" variant="outline" size="sm" :disabled="currentPage >= totalPages">
            <ChevronRight class="h-4 w-4" />
        </Button>
    </div>

    <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-start w-full sm:w-auto">
        <Button @click="zoomPdf('out')" variant="outline" size="sm" :disabled="pdfZoom <= 50">
            <ZoomOut class="h-4 w-4" />
        </Button>

        <span class="px-2 sm:px-3 py-1 text-xs sm:text-sm bg-muted rounded whitespace-nowrap">
            {{ pdfZoom }}%
        </span>

        <Button @click="zoomPdf('in')" variant="outline" size="sm" :disabled="pdfZoom >= 200">
            <ZoomIn class="h-4 w-4" />
        </Button>

        <Separator orientation="vertical" class="hidden sm:block h-6 mx-2" />

        <Button @click="rotatePdf" variant="outline" size="sm">
            <RotateCw class="h-4 w-4" />
        </Button>

        <Button @click="togglePdfFullscreen" variant="outline" size="sm">
            <Maximize v-if="!isPdfFullscreen" class="h-4 w-4" />
            <Minimize v-else class="h-4 w-4" />
        </Button>
    </div>
</div>


                        <!-- PDF Content -->
                        <CardContent class="p-0">
                            <div ref="pdfContainer" :style="{ minHeight: isPdfFullscreen ? '100vh' : '500px' }"
                                class="sm:min-h-[800px] fixed inset-0 z-50 bg-white overflow-auto"
                                :class="isPdfFullscreen ? 'fixed inset-0' : 'relative'">
                                <!-- Strategy 1: For Local Storage PDFs ONLY - Use vue-pdf-embed -->
                                <div v-if="detectPdfSource(effectivePdfUrl) === 'storage' && pdfSource"
                                    class="flex items-center justify-center p-4 w-full h-full">
                                    <div class="bg-white shadow-lg transition-transform duration-300" :style="{
                    transform: `scale(${pdfZoom / 100}) rotate(${pdfRotation}deg)`,
                    transformOrigin: 'center center'
                }">
                                        <VuePdfEmbed :source="pdfSource" :page="currentPage" :style="{
                        height: isPdfFullscreen ? '90vh' : '500px',
                        width: '100%',
                        minWidth: '100%'
                    }" class="sm:h-[700px] sm:min-w-[800px]" @loaded="onPdfLoaded" @loading-failed="onPdfLoadingFailed"
                                            @rendered="onPdfRendered" />
                                    </div>
                                </div>

                                <!-- Strategy 2: For Google Drive PDFs - Use iframe ONLY (no vue-pdf-embed) -->
                                <div v-else-if="detectPdfSource(effectivePdfUrl) === 'google_drive' && pdfSource"
                                    class="w-full h-full">
                                    <iframe :src="pdfSource" class="w-full h-full border-0"
                                        :style="{ height: isPdfFullscreen ? '90vh' : '700px' }"
                                        @load="onGoogleEmbedLoad" allow="autoplay"
                                        sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                        title="Google Drive PDF Viewer" />
                                </div>

                                <!-- Strategy 3: For External PDFs - Try iframe -->
                                <div v-else-if="detectPdfSource(effectivePdfUrl) === 'external' && pdfSource"
                                    class="w-full h-full">
                                    <iframe :src="pdfSource" class="w-full h-full border-0"
                                        :style="{ height: isPdfFullscreen ? '90vh' : '700px' }" @load="onIframeLoad"
                                        @error="onIframeError" title="External PDF Viewer" />
                                </div>


                                <!-- Fallback: Database Page Count Available - Show Progress Even if PDF Fails -->
                                <div v-else-if="content.pdf_page_count && content.pdf_page_count > 0"
                                    class="flex items-center justify-center h-96 w-full bg-muted">
                                    <div class="text-center max-w-md">
                                        <BookOpen class="h-16 w-16 text-primary mx-auto mb-4" />
                                        <h3 class="text-lg font-medium mb-2">PDF Preview Not Available</h3>
                                        <p class="text-muted-foreground mb-4">
                                            The PDF cannot be displayed in the viewer, but you can still track your
                                            progress through the {{ content.pdf_page_count }} pages from the database.
                                        </p>
                                        <div class="space-y-2">
                                            <Button @click="tryDirectUrl" variant="outline" class="w-full">
                                                <Eye class="h-4 w-4 mr-2" />
                                                Open PDF in New Tab
                                            </Button>
                                            <p class="text-xs text-muted-foreground">
                                                Progress tracking will work based on the {{ content.pdf_page_count }}
                                                pages stored in the database.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- No Strategy Available -->
                                <div v-else class="flex items-center justify-center h-96 w-full bg-muted">
                                    <div class="text-center">
                                        <AlertCircle class="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                                        <p class="text-muted-foreground">PDF source not available</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar Cards (1/4 width) -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Progress Card -->
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium flex items-center gap-2">
                                    <TrendingUp class="h-4 w-4 text-blue-500" />
                                    Progress
                                </span>
                                <span class="text-sm font-bold text-primary">
                                    {{ Math.round(completionPercentage) }}%
                                </span>
                            </div>
                            <Progress :value="completionPercentage" class="mb-2" />
                            <div class="text-xs text-muted-foreground">
                                <span v-if="content.content_type === 'video'">
                                    {{ formattedCurrentTime }} / {{ formattedDuration }}
                                </span>
                                <span v-else-if="content.content_type === 'pdf' && totalPages > 0">
                                    Page {{ currentPage }} of {{ totalPages }}
                                    <span v-if="content.pdf_page_count" class="text-green-600">(Database)</span>
                                </span>
                                <span v-else-if="content.content_type === 'pdf'">
                                    Loading pages...
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Time Spent Card (Hidden for completed videos) -->
                    <Card v-if="!isCompleted">
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium flex items-center gap-2">
                                    <Timer class="h-4 w-4 text-green-500" />
                                    Active Time
                                </span>
                            </div>
                            <div class="text-lg font-bold text-green-600">
                                {{ content.content_type === 'pdf' ? formattedPdfReadingTime : formattedTimeSpent }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Only counts when playing
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Expected Time Card (Video only, not completed) -->
                    <Card v-if="content.content_type === 'video' && allowedTimeDisplay && !isCompleted">
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-amber-500" />
                                    Expected Time
                                </span>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ allowedTimeDisplay }}
                            </div>
                            <div class="text-xs text-muted-foreground mt-1">
                                Video Duration × 2
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status Card -->
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium flex items-center gap-2">
                                    <Target class="h-4 w-4 text-purple-500" />
                                    Status
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle v-if="isCompleted" class="h-5 w-5 text-green-500" />
                                <Activity v-else class="h-5 w-5 text-blue-500" />
                                <span class="text-sm font-medium">
                                    {{ isCompleted ? 'Completed' : 'In Progress' }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Complete Button Card -->
                    <Card v-if="!isCompleted && (progressPercentage >= 100 || completionPercentage >= 100)">
                        <CardContent class="p-4">
                            <Button @click="markCompleted" :disabled="isLoading" variant="default"
                                class="w-full bg-green-600 hover:bg-green-700">
                                <Loader2 v-if="isLoading" class="h-4 w-4 mr-2 animate-spin" />
                                <CheckCircle v-else class="h-4 w-4 mr-2" />
                                {{ isLoading ? 'Completing...' : 'Mark as Complete' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Completion Badge Card -->
                    <Card v-if="isCompleted">
                        <CardContent class="p-4 text-center">
                            <Badge variant="outline"
                                class="px-4 py-2 text-green-600 border-green-200 bg-green-50 w-full justify-center">
                                <CheckCircle class="h-4 w-4 mr-2" />
                                Completed
                            </Badge>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Navigation Section -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-4 sm:pt-6">
                <div class="w-full sm:flex-1">
                    <Button v-if="safeNavigation.previous" @click="navigateContent(safeNavigation.previous.id)"
                        variant="outline"
                        class="w-full sm:max-w-xs flex items-center gap-3 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-colors overflow-hidden">
                        <SkipBack class="h-4 w-4 flex-shrink-0" />
                        <div class="text-left min-w-0 flex-1">
                            <div class="font-medium text-sm truncate">{{ safeNavigation.previous.title }}</div>
                            <div class="text-xs text-muted-foreground">{{
                                safeNavigation.previous.content_type.toUpperCase() }}</div>
                        </div>
                    </Button>
                </div>

                <div class="w-full sm:flex-1 sm:text-right">
                    <Button v-if="safeNavigation.next" @click="navigateContent(safeNavigation.next.id)"
                        variant="outline" :disabled="!safeNavigation.next.is_unlocked"
                        class="w-full sm:max-w-xs flex items-center gap-3 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-colors sm:ml-auto overflow-hidden"
                        :class="{ 'opacity-60 cursor-not-allowed': !safeNavigation.next.is_unlocked }">
                        <div class="text-left min-w-0 flex-1">
                            <div class="font-medium text-sm truncate">{{ safeNavigation.next.title }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ safeNavigation.next.content_type.toUpperCase() }}
                                <span v-if="!safeNavigation.next.is_unlocked" class="ml-2 text-amber-600">🔒
                                    Locked</span>
                            </div>
                        </div>
                        <SkipForward class="h-4 w-4 flex-shrink-0" />
                    </Button>
                </div>
            </div>

            <!-- Enhanced Completion Alert -->
            <Alert v-if="isCompleted" class="border-green-200 bg-green-50 dark:bg-green-950">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-sm break-words">
                    <strong class="text-green-800 dark:text-green-200">Excellent work!</strong>
                    You've successfully completed this content.
                    <span v-if="safeNavigation.next && safeNavigation.next.is_unlocked">
                        Continue to the next content to keep learning.
                    </span>
                </AlertDescription>
            </Alert>

            <!-- PDF Reading Tips -->
            <Alert v-if="content.content_type === 'pdf' && !isCompleted && totalPages > 0"
                class="border-blue-200 bg-blue-50 dark:bg-blue-950">
                <Eye class="h-4 w-4 text-blue-600" />
                <AlertDescription>
                    <strong class="text-blue-800 dark:text-blue-200">Reading Progress:</strong>
                    Your progress is automatically tracked as you navigate through the {{ totalPages }} pages.
                    Current page: {{ currentPage }}/{{ totalPages }} ({{ Math.round(progressPercentage) }}% complete) •
                    <span v-if="content.pdf_page_count" class="text-green-700 font-medium">
                        Page count from database ✅
                    </span>
                    <span v-else class="text-amber-700">
                        Using detected/fallback count ⚠️
                    </span>
                </AlertDescription>
            </Alert>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom slider styles */
.slider::-webkit-slider-thumb {
    appearance: none;
    height: 15px;
    width: 15px;
    border-radius: 50%;
    background: white;
    cursor: pointer;
    border: 2px solid #000;
    box-shadow: 0 0 2px rgba(0,0,0,0.5);
    transition: transform 0.2s;
}

.slider::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}

.slider::-webkit-slider-track {
    height: 4px;
    cursor: pointer;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
}

/* Volume slider styles */
.volume-slider::-webkit-slider-thumb {
    appearance: none;
    height: 12px;
    width: 12px;
    border-radius: 50%;
    background: white;
    cursor: pointer;
    box-shadow: 0 0 4px rgba(0,0,0,0.3);
    transition: transform 0.2s;
}

.volume-slider::-webkit-slider-thumb:hover {
    transform: scale(1.3);
}

.volume-slider::-webkit-slider-track {
    height: 3px;
    cursor: pointer;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .lg\:col-span-3 {
        grid-column: span 1;
    }
    .lg\:col-span-1 {
        grid-column: span 1;
    }
}
</style>
