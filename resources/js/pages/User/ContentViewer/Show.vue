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

const props = defineProps<{
    content: Content
    module?: Module
    course?: Course
    userProgress?: UserProgress
    navigation?: Navigation
}>()

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

// ✅ Fix breadcrumbs with null safety
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Courses', href: '/courses-online' },
    { title: safeCourse.value.name, href: `/courses-online/${safeCourse.value.id}` },
    { title: props.content.title, href: '#' }
]

// ========== VIDEO STATE ==========
const videoElement = ref<HTMLVideoElement | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(1)
const isFullscreen = ref(false)
const isVideoLoading = ref(true)
const isVideoReady = ref(false)

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
const sessionStartTime = ref<number | null>(null)
const completionPercentage = ref(safeUserProgress.value.completion_percentage || 0)
const isCompleted = ref(safeUserProgress.value.is_completed || false)
const timeSpent = ref(safeUserProgress.value.time_spent || 0)

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

// ========== COMPUTED PROPERTIES ==========
const formattedCurrentTime = computed(() => formatTime(currentTime.value))
const formattedDuration = computed(() => formatTime(duration.value))
const formattedTimeSpent = computed(() => formatTime(timeSpent.value * 60))
const formattedPdfReadingTime = computed(() => formatTime(pdfReadingTime.value))
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

// ✅ ENHANCED: Get PDF source for vue-pdf-embed
const pdfSource = computed(() => {
    if (!props.content.file_url) return ''

    const url = props.content.file_url
    const source = detectPdfSource(url)

    console.log(`🔍 PDF source detected: ${source} for URL: ${url}`)

    switch (source) {
        case 'google_drive':
            const fileIdMatch = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/) ||
                url.match(/id=([a-zA-Z0-9_-]+)/) ||
                url.match(/open\?id=([a-zA-Z0-9_-]+)/)

            if (fileIdMatch && fileIdMatch[1]) {
                const fileId = fileIdMatch[1]
                return `https://drive.google.com/file/d/${fileId}/preview`
            }
            return url

        case 'storage':
            let storageUrl = url

            if (!storageUrl.startsWith('http://') && !storageUrl.startsWith('https://')) {
                if (storageUrl.startsWith('/storage/')) {
                    storageUrl = storageUrl
                } else if (storageUrl.includes('app/public/')) {
                    storageUrl = storageUrl.replace(/.*app\/public\//, '/storage/')
                } else if (storageUrl.startsWith('/')) {
                    storageUrl = storageUrl
                } else {
                    storageUrl = `/storage/${storageUrl}`
                }
            }
            return storageUrl

        case 'external':
        default:
            return url
    }
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

// ========== SESSION MANAGEMENT ==========
const startSession = async () => {
    console.log('🚀 Starting session for content:', props.content.id)

    try {
        const payload = {
            action: 'start',
            current_position: props.content.content_type === 'video'
                ? currentTime.value
                : currentPage.value,
        }

        const response = await axios.post(`/content/${props.content.id}/session`, payload, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })

        if (response.data.success) {
            sessionId.value = response.data.session_id
            sessionStartTime.value = Date.now()

            // ✅ Reset all tracking counters
            resetIncrementalCounters()
            totalSkipCount.value = 0
            totalSeekCount.value = 0
            totalPauseCount.value = 0
            totalWatchTime.value = 0
            lastCurrentTime.value = currentTime.value

            console.log('✅ Session started:', sessionId.value)
        }
    } catch (error) {
        console.error('❌ Failed to start session:', error)
    }
}

const updateProgress = async () => {
    if (!sessionId.value) return

    const now = Date.now()
    if (now - lastProgressUpdate.value < 5000) return

    lastProgressUpdate.value = now

    try {
        const currentPosition = props.content.content_type === 'video'
            ? currentTime.value
            : currentPage.value

        const sessionTimeMinutes = sessionStartTime.value
            ? Math.floor((now - sessionStartTime.value) / 60000)
            : 0

        const payload = {
            current_position: currentPosition,
            completion_percentage: progressPercentage.value,
            watch_time: sessionTimeMinutes,
        }

        console.log('📤 Sending progress update:', payload)

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

            console.log('✅ Progress updated:', completionPercentage.value + '%')
        }

        // ✅ Send heartbeat with skip/seek data
        await sendHeartbeat()

    } catch (error) {
        console.error('❌ Failed to update progress:', error)
    }
}

// ✅ NEW: Heartbeat function with skip/seek tracking
const sendHeartbeat = async () => {
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
        }

        console.log('💓 Sending heartbeat with skip/seek data:', payload)

        const response = await axios.post(`/content/${props.content.id}/session`, payload, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })

        if (response.data.success) {
            console.log('✅ Heartbeat sent successfully')
            // Reset incremental counters after successful heartbeat
            resetIncrementalCounters()
        }

    } catch (error) {
        console.error('❌ Failed to send heartbeat:', error)
    }
}

const endSession = async () => {
    if (!sessionId.value) return

    console.log('🛑 Ending session:', sessionId.value)

    try {
        const payload = {
            action: 'end',
            current_position: props.content.content_type === 'video' ? currentTime.value : currentPage.value,
            // Send any remaining incremental data
            skip_count: skipCountSinceLastHeartbeat.value,
            seek_count: seekCountSinceLastHeartbeat.value,
            pause_count: pauseCountSinceLastHeartbeat.value,
            watch_time: Math.floor(watchTimeSinceLastHeartbeat.value),
        }

        await axios.post(`/content/${props.content.id}/session`, payload, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })

        console.log('✅ Session ended')
        sessionId.value = null
        sessionStartTime.value = null
    } catch (error) {
        console.error('❌ Failed to end session:', error)
    }
}

const markCompleted = async () => {
    console.log('🎯 Marking content as completed:', props.content.id)

    try {
        isLoading.value = true

        let response;
        try {
            response = await axios.post(`/content/${props.content.id}/complete`, {}, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
        } catch (error) {
            if (error.response?.status === 404) {
                console.log('🔄 /complete route not found, trying /mark-complete')
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

            console.log('✅ Content marked as completed successfully!')

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
        console.error('❌ Failed to mark as completed:', error)

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

// ✅ Enhanced with skip/seek tracking
const seek = (seconds: number) => {
    if (!videoElement.value) return

    const oldTime = currentTime.value
    const newTime = Math.max(0, Math.min(duration.value, seconds))

    videoElement.value.currentTime = newTime

    // ✅ Track seeking behavior
    const timeDiff = Math.abs(newTime - oldTime)
    if (timeDiff > 5) { // Only count significant seeks (>5 seconds)
        seekCountSinceLastHeartbeat.value++
        totalSeekCount.value++

        console.log('🔍 Seek detected:', {
            from: oldTime,
            to: newTime,
            diff: timeDiff,
            totalSeeks: totalSeekCount.value
        })
    }
}

// ✅ Enhanced with skip tracking
const seekRelative = (seconds: number) => {
    // Track skip behavior
    if (Math.abs(seconds) >= 10) { // Skip if seeking by 10+ seconds
        skipCountSinceLastHeartbeat.value++
        totalSkipCount.value++

        console.log('⏭️ Skip detected:', {
            seconds: seconds,
            totalSkips: totalSkipCount.value
        })
    }

    seek(currentTime.value + seconds)
}

const setVolume = (newVolume: number) => {
    volume.value = Math.max(0, Math.min(1, newVolume))
    if (videoElement.value) {
        videoElement.value.volume = volume.value
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
    console.log('🔄 Trying alternative loading method...')

    const sourceType = detectPdfSource(props.content.file_url || '')

    if (sourceType === 'google_drive') {
        loadingStrategy.value = 'google-embed'
        console.log('📄 Switching to Google Drive embed')
    } else {
        loadingStrategy.value = 'iframe'
        console.log('📄 Switching to iframe loading')
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
    console.log('📄 Trying iframe loading strategy')
}

const onPdfLoaded = async (pdf: any) => {
    console.log('✅ PDF loaded successfully via vue-pdf-embed:', pdf)

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        console.log('🎯 Using database page count:', props.content.pdf_page_count)
    } else if (detectPdfSource(props.content.file_url || '') === 'storage' && pdf.numPages > 0) {
        totalPages.value = pdf.numPages
        console.log('📄 Using vue-pdf-embed page count for local PDF:', pdf.numPages)
    } else {
        totalPages.value = 10
        console.warn('⚠️ No database page count found, using default: 10')
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
    console.error('❌ vue-pdf-embed loading failed:', error)

    errorMessage.value = error.message || 'Failed to load PDF with vue-pdf-embed'
    pdfLoadingError.value = true
    isPdfLoaded.value = false
    isLoading.value = false

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        estimatedReadingTime.value = Math.ceil((totalPages.value * 2))
        isPdfLoaded.value = true
        pdfLoadingError.value = false

        console.log('✅ Using database page count despite loading failure:', totalPages.value)

        if (!sessionId.value) {
            startSession()
        }
    } else {
        setTimeout(() => {
            console.log('🔄 Auto-trying alternative loading method...')
            tryAlternativeLoad()
        }, 2000)
    }
}

const onPdfRendered = () => {
    console.log('📄 PDF page rendered successfully')
    isLoading.value = false
}

const onIframeLoad = () => {
    console.log('✅ Iframe PDF loaded successfully')

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        console.log('🎯 Using database page count for iframe:', totalPages.value)
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
    console.error('❌ Iframe PDF loading failed')

    if (detectPdfSource(props.content.file_url || '') === 'google_drive') {
        console.log('🔄 Trying Google Drive embed...')
        loadingStrategy.value = 'google-embed'
    } else {
        pdfLoadingError.value = true
        errorMessage.value = 'Iframe loading failed'
        isLoading.value = false
    }
}

const onGoogleEmbedLoad = async () => {
    console.log('✅ Google Drive embed loaded')

    if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
        totalPages.value = props.content.pdf_page_count
        console.log('🎯 Using database page count for Google Drive:', totalPages.value)
    } else {
        totalPages.value = 15
        console.log('📊 Using default page count for Google Drive embed:', totalPages.value)
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

// ========== VIDEO EVENT HANDLERS ==========
const onLoadStart = () => {
    console.log('📹 Video loading started')
    isVideoLoading.value = true
    isVideoReady.value = false
}

const onLoadedData = () => {
    console.log('📹 Video data loaded')
    isVideoLoading.value = false
    isVideoReady.value = true
}

const onCanPlay = () => {
    console.log('📹 Video can start playing')
    isVideoLoading.value = false
    isVideoReady.value = true
}

const onWaiting = () => {
    console.log('📹 Video waiting for more data')
    isVideoLoading.value = true
}

const onPlaying = () => {
    console.log('📹 Video started playing')
    isVideoLoading.value = false
}

const onLoadedMetadata = () => {
    if (videoElement.value) {
        duration.value = videoElement.value.duration
        videoElement.value.currentTime = safeUserProgress.value.current_position || 0
    }
}

const onTimeUpdate = () => {
    if (videoElement.value) {
        currentTime.value = videoElement.value.currentTime
    }
}

const onPlay = () => {
    isPlaying.value = true
    lastCurrentTime.value = currentTime.value

    if (!sessionId.value) {
        startSession()
    }
}

// ✅ Enhanced with pause tracking
const onPause = () => {
    isPlaying.value = false

    // Track pause count
    pauseCountSinceLastHeartbeat.value++
    totalPauseCount.value++

    console.log('⏸️ Pause detected, total pauses:', totalPauseCount.value)

    updateProgress()
}

const onEnded = () => {
    isPlaying.value = false
    updateProgress()
    if (progressPercentage.value >= 95) {
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
const onVideoSeeked = () => {
    console.log('🔍 Manual seek detected via timeline')
    seekCountSinceLastHeartbeat.value++
    totalSeekCount.value++
}

// ========== LIFECYCLE ==========
onMounted(async () => {
    document.addEventListener('fullscreenchange', onFullscreenChange)

    // Initialize based on content type
    if (props.content.content_type === 'pdf') {
        console.log('📄 Initializing PDF viewer with database page count priority')

        if (props.content.pdf_page_count && props.content.pdf_page_count > 0) {
            totalPages.value = props.content.pdf_page_count
            estimatedReadingTime.value = Math.ceil((totalPages.value * 2))
            console.log('✅ Pre-loaded database page count:', totalPages.value)
        }

        isLoading.value = true

        // Track reading time
        const readingTimer = setInterval(() => {
            if (isPdfLoaded.value || totalPages.value > 0) {
                pdfReadingTime.value++
            }
        }, 1000)

        onUnmounted(() => {
            clearInterval(readingTimer)
        })
    } else if (props.content.content_type === 'video') {
        await nextTick()
        if (videoElement.value) {
            videoElement.value.currentTime = safeUserProgress.value.current_position || 0

            // ✅ Add video seek event listener
            videoElement.value.addEventListener('seeked', onVideoSeeked)
        }
    }

    // Progress tracking interval
    const progressInterval = setInterval(() => {
        if ((props.content.content_type === 'video' && isPlaying.value) ||
            (props.content.content_type === 'pdf' && (isPdfLoaded.value || totalPages.value > 0))) {
            updateProgress()
        }
    }, 180000)

    // Save progress on page unload
    const handleBeforeUnload = () => {
        updateProgress()
        endSession()
    }

    window.addEventListener('beforeunload', handleBeforeUnload)

    onUnmounted(() => {
        clearInterval(progressInterval)
        endSession()
        document.removeEventListener('fullscreenchange', onFullscreenChange)
        window.removeEventListener('beforeunload', handleBeforeUnload)

        // ✅ Cleanup video event listener
        if (videoElement.value) {
            videoElement.value.removeEventListener('seeked', onVideoSeeked)
        }
    })
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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Enhanced Content Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                        <component :is="contentIcon" class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ content.title }}</h1>
                        <p class="text-muted-foreground">{{ safeModule.name }} • {{ safeCourse.name }}</p>
                        <div class="flex items-center gap-4 mt-2">
                            <Badge variant="outline" class="text-xs">
                                {{ content.content_type.toUpperCase() }}
                            </Badge>
                            <Badge v-if="content.is_required" variant="destructive" class="text-xs">
                                Required
                            </Badge>
                            <Badge v-if="content.content_type === 'pdf' && content.file_url"
                                   variant="outline"
                                   class="text-xs"
                                   :class="{
                                    'bg-blue-50 text-blue-700': detectPdfSource(content.file_url) === 'google_drive',
                                    'bg-green-50 text-green-700': detectPdfSource(content.file_url) === 'storage',
                                    'bg-gray-50 text-gray-700': detectPdfSource(content.file_url) === 'external'
                                }">
                                {{
                                    detectPdfSource(content.file_url) === 'google_drive' ? 'Google Drive' :
                                        detectPdfSource(content.file_url) === 'storage' ? 'Local Storage' :
                                            'External PDF'
                                }}
                            </Badge>
                            <Badge v-if="content.content_type === 'pdf' && content.pdf_page_count"
                                   variant="outline"
                                   class="text-xs bg-green-50 text-green-700">
                                {{ content.pdf_page_count }} pages (DB)
                            </Badge>

                            <span v-if="content.content_type === 'video' && content.video?.duration" class="text-xs text-muted-foreground flex items-center gap-1">
                                <Clock class="h-3 w-3" />
                                {{ Math.ceil(content.video.duration / 60) }} min
                            </span>
                            <span v-if="content.content_type === 'pdf' && totalPages > 0" class="text-xs text-muted-foreground flex items-center gap-1">
                                <BookOpen class="h-3 w-3" />
                                {{ totalPages }} pages • ~{{ Math.ceil(totalPages * 2) }}min read
                            </span>
                        </div>
                    </div>
                </div>
                <Button asChild variant="outline">
                    <Link :href="route('courses-online.show', safeCourse.id)">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Course
                    </Link>
                </Button>
            </div>

            <!-- ✅ OPTIONAL: Debug Panel to show tracking data -->
            <Card v-if="sessionId" class="mb-4 border-blue-200 bg-blue-50/30">
                <CardContent class="p-4">
                    <div class="text-xs text-muted-foreground grid grid-cols-2 md:grid-cols-4 gap-4">
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
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Content Area (3/4 width) -->
                <div class="lg:col-span-3">
                    <!-- Enhanced Video Player with Loading State -->
                    <Card v-if="content.content_type === 'video'" class="overflow-hidden">
                        <CardContent class="p-0">
                            <div class="relative bg-black aspect-video">
                                <!-- Video Element -->
                                <video
                                    ref="videoElement"
                                    :src="content.video?.streaming_url || ''"
                                    :poster="content.video?.thumbnail_url || ''"
                                    class="w-full h-full"
                                    preload="metadata"
                                    @loadstart="onLoadStart"
                                    @loadeddata="onLoadedData"
                                    @canplay="onCanPlay"
                                    @waiting="onWaiting"
                                    @playing="onPlaying"
                                    @loadedmetadata="onLoadedMetadata"
                                    @timeupdate="onTimeUpdate"
                                    @play="onPlay"
                                    @pause="onPause"
                                    @ended="onEnded"
                                    @volumechange="onVolumeChange"
                                >
                                    Your browser does not support the video tag.
                                </video>

                                <!-- Video Loading Overlay -->
                                <div v-if="isVideoLoading || !isVideoReady" class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 z-10">
                                    <div class="text-center text-white">
                                        <Loader2 class="h-12 w-12 animate-spin mx-auto mb-4" />
                                        <p class="text-lg font-medium">Loading video...</p>
                                        <p class="text-sm text-gray-300 mt-2">{{ content.video?.name }}</p>
                                        <div class="mt-4">
                                            <div class="w-48 h-1 bg-white/20 rounded-full mx-auto">
                                                <div class="h-1 bg-primary rounded-full animate-pulse" style="width: 60%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Controls -->
                                <div v-if="isVideoReady && !isVideoLoading" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                                    <div class="mb-4">
                                        <input
                                            type="range"
                                            :value="currentTime"
                                            :max="duration"
                                            @input="seek(Number(($event.target as HTMLInputElement).value))"
                                            class="w-full h-1 bg-white/30 rounded-lg appearance-none cursor-pointer slider"
                                        />
                                    </div>

                                    <div class="flex items-center justify-between text-white">
                                        <div class="flex items-center gap-4">
                                            <Button @click="togglePlay" variant="ghost" size="sm" class="text-white hover:bg-white/20">
                                                <Play v-if="!isPlaying" class="h-5 w-5" />
                                                <Pause v-else class="h-5 w-5" />
                                            </Button>

                                            <Button @click="seekRelative(-10)" variant="ghost" size="sm" class="text-white hover:bg-white/20">
                                                <RotateCcw class="h-4 w-4" />
                                            </Button>
                                            <Button @click="seekRelative(10)" variant="ghost" size="sm" class="text-white hover:bg-white/20">
                                                <SkipForward class="h-4 w-4" />
                                            </Button>

                                            <span class="text-sm">{{ formattedCurrentTime }} / {{ formattedDuration }}</span>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-2">
                                                <Volume2 class="h-4 w-4" />
                                                <input
                                                    type="range"
                                                    :value="volume"
                                                    min="0"
                                                    max="1"
                                                    step="0.1"
                                                    @input="setVolume(Number(($event.target as HTMLInputElement).value))"
                                                    class="w-20 h-1 bg-white/30 rounded-lg appearance-none cursor-pointer"
                                                />
                                            </div>

                                            <Button @click="toggleFullscreen" variant="ghost" size="sm" class="text-white hover:bg-white/20">
                                                <Maximize class="h-4 w-4" />
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
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2">
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
                        <div v-if="isLoading && !isPdfLoaded && !pdfLoadingError && totalPages === 0" class="flex items-center justify-center py-16">
                            <div class="text-center">
                                <Loader2 class="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
                                <p class="text-muted-foreground">Loading PDF...</p>
                                <p class="text-xs text-muted-foreground mt-2">
                                    {{ content.pdf_name }} • {{ detectPdfSource(content.file_url || '') }}
                                </p>
                            </div>
                        </div>

                        <!-- PDF Error State with Solutions -->
                        <div v-else-if="pdfLoadingError && totalPages === 0" class="flex items-center justify-center py-16">
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
                        <div v-if="totalPages > 0" class="px-6 pb-4 flex items-center justify-between border-b">
                            <div class="flex items-center gap-2">
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
                                        class="w-16 px-2 py-1 text-sm border rounded text-center"
                                    />
                                    <span class="text-sm text-muted-foreground">of {{ totalPages }}</span>
                                </div>

                                <Button @click="nextPdfPage" variant="outline" size="sm" :disabled="currentPage >= totalPages">
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button @click="zoomPdf('out')" variant="outline" size="sm" :disabled="pdfZoom <= 50">
                                    <ZoomOut class="h-4 w-4" />
                                </Button>

                                <span class="px-3 py-1 text-sm bg-muted rounded">{{ pdfZoom }}%</span>

                                <Button @click="zoomPdf('in')" variant="outline" size="sm" :disabled="pdfZoom >= 200">
                                    <ZoomIn class="h-4 w-4" />
                                </Button>

                                <Separator orientation="vertical" class="h-6 mx-2" />

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
                            <div
                                ref="pdfContainer"
                                class="relative bg-gray-100 dark:bg-gray-800"
                                :style="{ minHeight: isPdfFullscreen ? '100vh' : '800px' }"
                            >
                                <!-- Strategy 1: For Local Storage PDFs ONLY - Use vue-pdf-embed -->
                                <div v-if="detectPdfSource(content.file_url || '') === 'storage' && pdfSource"
                                     class="flex items-center justify-center p-4">
                                    <div
                                        class="bg-white shadow-lg transition-transform duration-300"
                                        :style="{
                    transform: `scale(${pdfZoom / 100}) rotate(${pdfRotation}deg)`,
                    transformOrigin: 'center center'
                }"
                                    >
                                        <VuePdfEmbed
                                            :source="pdfSource"
                                            :page="currentPage"
                                            :style="{
                        height: isPdfFullscreen ? '90vh' : '700px',
                        width: '100%',
                        minWidth: '800px'
                    }"
                                            @loaded="onPdfLoaded"
                                            @loading-failed="onPdfLoadingFailed"
                                            @rendered="onPdfRendered"
                                        />
                                    </div>
                                </div>

                                <!-- Strategy 2: For Google Drive PDFs - Use iframe ONLY (no vue-pdf-embed) -->
                                <div v-else-if="detectPdfSource(content.file_url || '') === 'google_drive' && pdfSource"
                                     class="w-full h-full">
                                    <iframe
                                        :src="pdfSource"
                                        class="w-full h-full border-0"
                                        :style="{ height: isPdfFullscreen ? '90vh' : '700px' }"
                                        @load="onGoogleEmbedLoad"
                                        allow="autoplay"
                                        sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                        title="Google Drive PDF Viewer"
                                    />
                                </div>

                                <!-- Strategy 3: For External PDFs - Try iframe -->
                                <div v-else-if="detectPdfSource(content.file_url || '') === 'external' && pdfSource"
                                     class="w-full h-full">
                                    <iframe
                                        :src="pdfSource"
                                        class="w-full h-full border-0"
                                        :style="{ height: isPdfFullscreen ? '90vh' : '700px' }"
                                        @load="onIframeLoad"
                                        @error="onIframeError"
                                        title="External PDF Viewer"
                                    />
                                </div>

                                <!-- Fallback: Database Page Count Available - Show Progress Even if PDF Fails -->
                                <div v-else-if="content.pdf_page_count && content.pdf_page_count > 0"
                                     class="flex items-center justify-center h-96 w-full bg-muted">
                                    <div class="text-center max-w-md">
                                        <BookOpen class="h-16 w-16 text-primary mx-auto mb-4" />
                                        <h3 class="text-lg font-medium mb-2">PDF Preview Not Available</h3>
                                        <p class="text-muted-foreground mb-4">
                                            The PDF cannot be displayed in the viewer, but you can still track your progress through the {{ content.pdf_page_count }} pages from the database.
                                        </p>
                                        <div class="space-y-2">
                                            <Button @click="tryDirectUrl" variant="outline" class="w-full">
                                                <Eye class="h-4 w-4 mr-2" />
                                                Open PDF in New Tab
                                            </Button>
                                            <p class="text-xs text-muted-foreground">
                                                Progress tracking will work based on the {{ content.pdf_page_count }} pages stored in the database.
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

                    <!-- Time Spent Card -->
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium flex items-center gap-2">
                                    <Timer class="h-4 w-4 text-green-500" />
                                    Time Spent
                                </span>
                            </div>
                            <div class="text-lg font-bold text-green-600">
                                {{ content.content_type === 'pdf' ? formattedPdfReadingTime : formattedTimeSpent }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Learning session
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
                    <Card v-if="!isCompleted && (progressPercentage >= 90 || completionPercentage >= 90)">
                        <CardContent class="p-4">
                            <Button
                                @click="markCompleted"
                                :disabled="isLoading"
                                variant="default"
                                class="w-full bg-green-600 hover:bg-green-700"
                            >
                                <Loader2 v-if="isLoading" class="h-4 w-4 mr-2 animate-spin" />
                                <CheckCircle v-else class="h-4 w-4 mr-2" />
                                {{ isLoading ? 'Completing...' : 'Mark as Complete' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Completion Badge Card -->
                    <Card v-if="isCompleted">
                        <CardContent class="p-4 text-center">
                            <Badge variant="outline" class="px-4 py-2 text-green-600 border-green-200 bg-green-50 w-full justify-center">
                                <CheckCircle class="h-4 w-4 mr-2" />
                                Completed
                            </Badge>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Navigation Section -->
            <div class="flex items-center justify-between pt-6">
                <div class="flex-1">
                    <Button
                        v-if="safeNavigation.previous"
                        @click="navigateContent(safeNavigation.previous.id)"
                        variant="outline"
                        class="flex items-center gap-3 max-w-xs hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-colors"
                    >
                        <SkipBack class="h-4 w-4" />
                        <div class="text-left">
                            <div class="font-medium text-sm">{{ safeNavigation.previous.title }}</div>
                            <div class="text-xs text-muted-foreground">{{ safeNavigation.previous.content_type.toUpperCase() }}</div>
                        </div>
                    </Button>
                </div>

                <div class="flex-1 text-right">
                    <Button
                        v-if="safeNavigation.next"
                        @click="navigateContent(safeNavigation.next.id)"
                        :variant="safeNavigation.next.is_unlocked ? 'default' : 'outline'"
                        :disabled="!safeNavigation.next.is_unlocked"
                        :class="[
            'group relative flex items-center justify-between',
            'px-6 py-4 rounded-xl font-medium transition-all duration-200',
            'min-h-[80px] max-w-md ml-auto shadow-lg',
            safeNavigation.next.is_unlocked
                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl'
                : 'bg-gray-100 border-2 border-dashed border-gray-300 text-gray-500 cursor-not-allowed'
        ]"
                    >
                        <!-- Progress indicator for locked content -->
                        <div v-if="!safeNavigation.next.is_unlocked"
                             class="absolute -top-2 -right-2 bg-amber-500 text-white text-xs px-2 py-1 rounded-full shadow-md">
                            Locked
                        </div>

                        <div class="text-right flex-1 mr-4">
                            <div class="font-semibold text-lg mb-1 leading-tight">
                                {{ safeNavigation.next.title }}
                            </div>
                            <div class="text-sm opacity-90 tracking-wide uppercase flex items-center justify-end gap-2">
                <span class="inline-flex items-center">
                    {{ safeNavigation.next.content_type }}
                </span>
                                <span v-if="safeNavigation.next.duration" class="text-xs bg-black/20 px-2 py-1 rounded">
                    {{ safeNavigation.next.duration }}
                </span>
                            </div>
                        </div>

                        <div :class="[
            'flex-shrink-0 transition-transform duration-200',
            safeNavigation.next.is_unlocked ? 'group-hover:translate-x-1' : ''
        ]">
                            <SkipForward class="h-6 w-6" />
                        </div>
                    </Button>
                </div>            </div>

            <!-- Enhanced Completion Alert -->
            <Alert v-if="isCompleted" class="border-green-200 bg-green-50 dark:bg-green-950">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <AlertDescription>
                    <strong class="text-green-800 dark:text-green-200">Excellent work!</strong>
                    You've successfully completed this content.
                    <span v-if="safeNavigation.next && safeNavigation.next.is_unlocked">
                        Continue to the next content to keep learning.
                    </span>
                </AlertDescription>
            </Alert>

            <!-- PDF Reading Tips -->
            <Alert v-if="content.content_type === 'pdf' && !isCompleted && totalPages > 0" class="border-blue-200 bg-blue-50 dark:bg-blue-950">
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
}

.slider::-webkit-slider-track {
    height: 4px;
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
