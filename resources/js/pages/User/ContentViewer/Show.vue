<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import axios from 'axios'

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
// ✅ NEW: Video loading states
const isVideoLoading = ref(true)
const isVideoReady = ref(false)

// ========== PDF STATE ==========
const pdfContainer = ref<HTMLDivElement | null>(null)
const pdfIframe = ref<HTMLIFrameElement | null>(null)
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

// ========== SHARED STATE ==========
const sessionId = ref<number | null>(null)
const lastProgressUpdate = ref(0)
const isLoading = ref(false)
const sessionStartTime = ref<number | null>(null)
const completionPercentage = ref(safeUserProgress.value.completion_percentage || 0)
const isCompleted = ref(safeUserProgress.value.is_completed || false)
const timeSpent = ref(safeUserProgress.value.time_spent || 0)

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
// ✅ MERGED: Smart PDF source detection
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

// ✅ MERGED: Smart PDF viewer URL generator
const getPdfViewerUrl = () => {
    if (!props.content.file_url) return ''

    const url = props.content.file_url
    const source = detectPdfSource(url)

    console.log(`🔍 PDF source detected: ${source} for URL: ${url}`)

    switch (source) {
        case 'google_drive':
            // Handle Google Drive URLs
            const fileIdMatch = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/) ||
                url.match(/id=([a-zA-Z0-9_-]+)/) ||
                url.match(/open\?id=([a-zA-Z0-9_-]+)/)

            if (fileIdMatch && fileIdMatch[1]) {
                const fileId = fileIdMatch[1]
                return `https://drive.google.com/file/d/${fileId}/preview`
            }

            // Fallback for Google Drive
            return `https://docs.google.com/viewer?embedded=true&url=${encodeURIComponent(url)}`

        case 'storage':
            // Handle Laravel storage URLs
            let storageUrl = url

            // Convert to proper storage path
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

            // For storage files, return direct URL with parameters
            return `${storageUrl}#page=${currentPage.value}&zoom=${pdfZoom.value}`

        case 'external':
        default:
            // Handle external URLs
            return `${url}#page=${currentPage.value}&zoom=${pdfZoom.value}`
    }
}

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
            console.log('✅ Session started:', sessionId.value)
        }
    } catch (error) {
        console.error('❌ Failed to start session:', error)
    }
}

const updateProgress = async () => {
    if (!sessionId.value) return

    const now = Date.now()
    if (now - lastProgressUpdate.value < 5000) return // Throttle to every 5 seconds

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

        await axios.post(`/content/${props.content.id}/session`, {
            action: 'heartbeat',
            current_position: currentPosition,
        }, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })

    } catch (error) {
        console.error('❌ Failed to update progress:', error)

        if (error.response) {
            console.error('Response status:', error.response.status)
            console.error('Response data:', error.response.data)

            if (error.response.status === 422 && error.response.data?.errors) {
                console.error('🔴 Validation errors:', error.response.data.errors)
            }
        }
    }
}

const endSession = async () => {
    if (!sessionId.value) return

    console.log('🛑 Ending session:', sessionId.value)

    try {
        await axios.post(`/content/${props.content.id}/session`, {
            action: 'end',
            current_position: props.content.content_type === 'video' ? currentTime.value : currentPage.value,
        }, {
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

const seek = (seconds: number) => {
    if (!videoElement.value) return
    videoElement.value.currentTime = Math.max(0, Math.min(duration.value, seconds))
}

const seekRelative = (seconds: number) => {
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
// ✅ MERGED: Intelligent PDF loading based on source
const loadRealPdfData = async () => {
    if (!props.content.file_url) {
        console.error('❌ No PDF URL available')
        pdfLoadingError.value = true
        return
    }

    try {
        isLoading.value = true
        console.log('📄 Loading PDF data from:', props.content.file_url)

        const url = props.content.file_url
        const source = detectPdfSource(url)

        console.log(`🔍 Detected PDF source: ${source}`)

        switch (source) {
            case 'google_drive':
                await handleGoogleDrivePdf()
                break

            case 'storage':
                await handleStoragePdf(url)
                break

            case 'external':
            default:
                await handleExternalPdf(url)
                break
        }

    } catch (error) {
        console.error('❌ Failed to load PDF data:', error)
        await handlePdfError(error)
    } finally {
        isLoading.value = false
    }
}

// ✅ MERGED: Handle Google Drive PDFs
const handleGoogleDrivePdf = async () => {
    console.log('📱 Handling Google Drive PDF')

    // Set default values for Google Drive PDFs
    totalPages.value = 5 // Default estimate
    estimatedReadingTime.value = Math.ceil((totalPages.value * 250) / averageReadingSpeed.value)
    isPdfLoaded.value = true
    currentPage.value = Math.max(1, safeUserProgress.value.current_position || 1)

    console.log('✅ Google Drive PDF loaded with defaults:', {
        totalPages: totalPages.value,
        currentPage: currentPage.value,
        estimatedReadingTime: estimatedReadingTime.value
    })

    await startSession()
}

// ✅ MERGED: Handle Laravel storage PDFs
const handleStoragePdf = async (url: string) => {
    console.log('🗃️ Handling Laravel storage PDF')

    try {
        // Try to load with PDF.js for storage files
        if (!window.pdfjsLib) {
            await loadPdfJsLibrary()
        }

        // Convert to proper storage URL
        let storageUrl = url
        if (!storageUrl.startsWith('http')) {
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

        const pdf = await window.pdfjsLib.getDocument(storageUrl).promise

        totalPages.value = pdf.numPages
        estimatedReadingTime.value = Math.ceil((totalPages.value * 250) / averageReadingSpeed.value)
        isPdfLoaded.value = true
        currentPage.value = Math.max(1, Math.min(totalPages.value, safeUserProgress.value.current_position || 1))

        console.log('✅ Storage PDF loaded with PDF.js:', {
            totalPages: totalPages.value,
            currentPage: currentPage.value,
            url: storageUrl
        })

        await startSession()

    } catch (error) {
        console.warn('⚠️ PDF.js failed for storage PDF, using fallback:', error)

        // Fallback for storage PDFs
        totalPages.value = 5
        estimatedReadingTime.value = Math.ceil((totalPages.value * 250) / averageReadingSpeed.value)
        isPdfLoaded.value = true
        currentPage.value = Math.max(1, safeUserProgress.value.current_position || 1)

        console.log('✅ Storage PDF loaded with fallback')
        await startSession()
    }
}

// ✅ MERGED: Handle external PDFs
const handleExternalPdf = async (url: string) => {
    console.log('🌐 Handling external PDF')

    if (!window.pdfjsLib) {
        await loadPdfJsLibrary()
    }

    const pdf = await window.pdfjsLib.getDocument(url).promise

    totalPages.value = pdf.numPages
    estimatedReadingTime.value = Math.ceil((totalPages.value * 250) / averageReadingSpeed.value)
    isPdfLoaded.value = true
    currentPage.value = Math.max(1, Math.min(totalPages.value, safeUserProgress.value.current_position || 1))

    console.log('✅ External PDF loaded with PDF.js:', {
        totalPages: totalPages.value,
        currentPage: currentPage.value,
        url: url
    })

    await startSession()
}

// ✅ MERGED: Handle PDF loading errors
const handlePdfError = async (error: any) => {
    console.log('🔄 PDF loading failed, trying fallback methods')

    if (error.name === 'InvalidPDFException' || error.message?.includes('CORS')) {
        console.log('🔄 CORS/Invalid PDF error, using fallback')

        // Try fallback data
        totalPages.value = 5
        estimatedReadingTime.value = Math.ceil((totalPages.value * 250) / averageReadingSpeed.value)
        isPdfLoaded.value = true
        currentPage.value = Math.max(1, safeUserProgress.value.current_position || 1)

        console.log('✅ PDF fallback activated')
        await startSession()
        return
    }

    pdfLoadingError.value = true
    setTimeout(() => {
        tryIframePageExtraction()
    }, 2000)
}

// ✅ MERGED: Fallback iframe extraction
const tryIframePageExtraction = () => {
    try {
        if (pdfIframe.value) {
            pdfIframe.value.onload = () => {
                console.log('📄 PDF iframe loaded, using fallback data')
                totalPages.value = 5
                estimatedReadingTime.value = Math.ceil((totalPages.value * 250) / averageReadingSpeed.value)
                isPdfLoaded.value = true
                currentPage.value = Math.max(1, safeUserProgress.value.current_position || 1)

                if (!sessionId.value) {
                    startSession()
                }
            }
        }
    } catch (error) {
        console.error('❌ Fallback PDF loading failed:', error)
    }
}

// ✅ Load PDF.js library
const loadPdfJsLibrary = () => {
    return new Promise((resolve, reject) => {
        if (window.pdfjsLib) {
            resolve(window.pdfjsLib)
            return
        }

        const script = document.createElement('script')
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js'
        script.onload = () => {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'
            console.log('✅ PDF.js library loaded')
            resolve(window.pdfjsLib)
        }
        script.onerror = reject
        document.head.appendChild(script)
    })
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
// ✅ NEW: Enhanced video event handlers with loading states
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
    if (!sessionId.value) {
        startSession()
    }
}

const onPause = () => {
    isPlaying.value = false
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

// ========== LIFECYCLE ==========
onMounted(async () => {
    document.addEventListener('fullscreenchange', onFullscreenChange)

    // Initialize based on content type
    if (props.content.content_type === 'pdf') {
        await loadRealPdfData()

        // Track reading time
        const readingTimer = setInterval(() => {
            if (isPdfLoaded.value) {
                pdfReadingTime.value++
            }
        }, 1000)

        onUnmounted(() => {
            clearInterval(readingTimer)
        })
    } else if (props.content.content_type === 'video' && videoElement.value) {
        await nextTick()
        videoElement.value.currentTime = safeUserProgress.value.current_position || 0
    }

    // Progress tracking interval
    const progressInterval = setInterval(() => {
        if ((props.content.content_type === 'video' && isPlaying.value) ||
            (props.content.content_type === 'pdf' && isPdfLoaded.value)) {
            updateProgress()
        }
    }, 15000)

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
    })
})

// Watch for page changes in PDF
watch(currentPage, (newPage) => {
    if (props.content.content_type === 'pdf' && isPdfLoaded.value) {
        updatePdfProgress()
    }
})

// ✅ MERGED: Watch for zoom changes to refresh iframe for some sources
watch(pdfZoom, () => {
    if (props.content.content_type === 'pdf' && isPdfLoaded.value && pdfIframe.value) {
        const source = detectPdfSource(props.content.file_url || '')

        // Only refresh for storage files
        if (source === 'storage') {
            pdfIframe.value.src = getPdfViewerUrl()
        }
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
                            <!-- Smart source indicator -->
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
                            <span v-if="content.content_type === 'video' && content.video?.duration" class="text-xs text-muted-foreground flex items-center gap-1">
                                <Clock class="h-3 w-3" />
                                {{ Math.ceil(content.video.duration / 60) }} min
                            </span>
                            <span v-if="content.content_type === 'pdf' && totalPages > 0" class="text-xs text-muted-foreground flex items-center gap-1">
                                <BookOpen class="h-3 w-3" />
                                {{ totalPages }} pages • ~{{ estimatedReadingTime }}min read
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

            <!-- ✅ NEW: Split Layout - 3/4 for content, 1/4 for sidebar -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- ✅ NEW: Main Content Area (3/4 width) -->
                <div class="lg:col-span-3">
                    <!-- ✅ NEW: Enhanced Video Player with Loading State -->
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

                                <!-- ✅ NEW: Video Loading Overlay -->
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

                    <!-- Smart Multi-Source PDF Viewer -->
                    <Card v-if="content.content_type === 'pdf'" class="overflow-hidden">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    {{ content.pdf_name || content.title }}
                                </CardTitle>
                                <div class="flex items-center gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Button @click="downloadPdf" variant="outline" size="sm">
                                                    <Download class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Download PDF</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </div>
                        </CardHeader>

                        <!-- PDF Loading State -->
                        <div v-if="isLoading && !isPdfLoaded" class="flex items-center justify-center py-16">
                            <div class="text-center">
                                <Loader2 class="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
                                <p class="text-muted-foreground">Loading PDF data...</p>
                                <p class="text-xs text-muted-foreground mt-2">
                                    Reading {{ content.pdf_name }} •
                                    {{
                                        detectPdfSource(content.file_url || '') === 'google_drive' ? 'Google Drive' :
                                            detectPdfSource(content.file_url || '') === 'storage' ? 'Local Storage' :
                                                'External PDF'
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- PDF Error State -->
                        <div v-else-if="pdfLoadingError && !isPdfLoaded" class="flex items-center justify-center py-16">
                            <div class="text-center">
                                <AlertCircle class="h-12 w-12 text-destructive mx-auto mb-4" />
                                <p class="text-destructive">Failed to load PDF metadata</p>
                                <p class="text-xs text-muted-foreground mt-2">Using fallback viewer</p>
                                <Button @click="loadRealPdfData" variant="outline" size="sm" class="mt-4">
                                    <RotateCcw class="h-4 w-4 mr-2" />
                                    Retry
                                </Button>
                            </div>
                        </div>

                        <!-- PDF Controls -->
                        <div v-if="isPdfLoaded || totalPages > 0" class="px-6 pb-4 flex items-center justify-between border-b">
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

                        <CardContent class="p-0">
                            <div
                                ref="pdfContainer"
                                class="relative bg-gray-100 dark:bg-gray-800"
                                :style="{ minHeight: isPdfFullscreen ? '100vh' : '800px' }"
                            >
                                <div v-if="isPdfLoaded || totalPages > 0" class="flex items-center justify-center p-4">
                                    <div
                                        class="bg-white shadow-lg transition-transform duration-300"
                                        :style="{
                                            transform: `scale(${pdfZoom / 100}) rotate(${pdfRotation}deg)`,
                                            transformOrigin: 'center center'
                                        }"
                                    >
                                        <!-- Smart iframe that handles all PDF sources -->
                                        <iframe
                                            v-if="content.file_url"
                                            ref="pdfIframe"
                                            :src="getPdfViewerUrl()"
                                            class="w-full border-0"
                                            :style="{ height: isPdfFullscreen ? '90vh' : '700px', width: '100%', minWidth: '800px' }"
                                            title="PDF Viewer"
                                            @load="tryIframePageExtraction"
                                        ></iframe>
                                        <div v-else class="flex items-center justify-center h-96 w-full bg-muted">
                                            <div class="text-center">
                                                <AlertCircle class="h-12 w-12 text-muted-foreground mx-auto mb-2" />
                                                <p class="text-muted-foreground">PDF not available</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ✅ NEW: Sidebar Cards (1/4 width) -->
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

                    <!-- ✅ NEW: Complete Button Card -->
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

                    <!-- ✅ NEW: Completion Badge Card -->
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

            <!-- ✅ NEW: Navigation Section -->
            <!-- ✅ Simple Enhanced Navigation Section -->
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
                'flex items-center gap-3 max-w-xs ml-auto transition-colors',
                safeNavigation.next.is_unlocked
                    ? 'bg-blue-600 hover:bg-blue-700 text-white'
                    : 'opacity-50 cursor-not-allowed'
            ]"
                    >
                        <div class="text-right">
                            <div class="font-medium text-sm">{{ safeNavigation.next.title }}</div>
                            <div class="text-xs opacity-75">{{ safeNavigation.next.content_type.toUpperCase() }}</div>
                        </div>
                        <SkipForward class="h-4 w-4" />
                    </Button>
                </div>
            </div>

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
                    Source: {{
                        detectPdfSource(content.file_url || '') === 'google_drive' ? 'Google Drive' :
                            detectPdfSource(content.file_url || '') === 'storage' ? 'Local Storage' :
                                'External PDF'
                    }}
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

/* PDF viewer enhancements */
iframe {
    transition: transform 0.3s ease;
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
