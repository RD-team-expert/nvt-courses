<template>
    <Head :title="`Edit Subtitles - ${video.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button as-child variant="ghost">
                        <Link href="/admin/videos">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Videos
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold flex items-center gap-2">
                            <Captions class="h-6 w-6 text-blue-600" />
                            Edit Subtitles
                        </h1>
                        <p class="text-muted-foreground text-sm">{{ video.name }}</p>
                    </div>
                </div>
                <Badge :variant="getStatusVariant(video.subtitle_status)" class="gap-1 text-sm px-3 py-1">
                    <component :is="getStatusIcon(video.subtitle_status)" class="h-3 w-3" />
                    {{ getStatusLabel(video.subtitle_status) }}
                </Badge>
            </div>

            <!-- Main layout: Editor left, Preview right -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                <!-- LEFT: VTT Editor -->
               <!-- LEFT: VTT Editor -->
                <Card class="bg-card border-border flex flex-col">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <FileText class="h-4 w-4" />
                            VTT Content Editor
                        </CardTitle>
                        <CardDescription>
                            Edit the raw WebVTT subtitle file. Each cue needs a timestamp line and text line, separated by a blank line.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 flex flex-col flex-1">

                        <!-- Flash messages -->
                        <div v-if="$page.props.flash?.success"
                            class="flex items-center gap-2 p-3 rounded-lg bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm">
                            <CheckCircle2 class="h-4 w-4 shrink-0" />
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash?.error"
                            class="flex items-center gap-2 p-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm">
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            {{ $page.props.flash.error }}
                        </div>

                        <!-- Stats + dirty indicator -->
                        <div class="flex items-center gap-4 text-xs text-muted-foreground px-3 py-2 bg-muted/50 rounded-lg border border-border">
                            <span>Cues: <strong class="text-foreground">{{ cueCount }}</strong></span>
                            <span>Lines: <strong class="text-foreground">{{ lineCount }}</strong></span>
                            <span>Characters: <strong class="text-foreground">{{ charCount }}</strong></span>
                            <span v-if="isDirty" class="text-amber-500 font-semibold ml-auto flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                                Unsaved changes
                            </span>
                            <span v-else class="text-green-500 ml-auto flex items-center gap-1">
                                <CheckCircle2 class="h-3 w-3" />
                                Saved
                            </span>
                        </div>

                        <!-- Toolbar: quick actions above the textarea -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <Button variant="outline" size="sm" type="button" @click="goToLine(1)" class="text-xs h-7 px-2">
                                Top
                            </Button>
                            <Button variant="outline" size="sm" type="button" @click="goToLine(-1)" class="text-xs h-7 px-2">
                                Bottom
                            </Button>
                            <div class="h-4 w-px bg-border mx-1" />
                            <Button variant="outline" size="sm" type="button" @click="increaseFontSize" class="text-xs h-7 px-2">
                                A+
                            </Button>
                            <Button variant="outline" size="sm" type="button" @click="decreaseFontSize" class="text-xs h-7 px-2">
                                A-
                            </Button>
                            <div class="h-4 w-px bg-border mx-1" />
                            <span class="text-xs text-muted-foreground">Font: {{ fontSize }}px</span>
                        </div>

                        <!-- The textarea — tall and comfortable -->
                        <div class="relative flex-1">
                            <textarea
                                ref="editorTextarea"
                                v-model="vttContent"
                                @click="updateCursor"
                                @input="onContentChange"
                                @keyup="updateCursor"
                                :style="{ fontSize: fontSize + 'px', lineHeight: '1.8' }"
                                class="w-full font-mono p-4 rounded-lg border border-border bg-background text-foreground resize-none focus:outline-none focus:ring-2 focus:ring-ring"
                                style="height: 65vh; min-height: 400px;"
                                placeholder="WEBVTT&#10;&#10;00:00:01.000 --> 00:00:04.000&#10;Your subtitle text here"
                                spellcheck="false"
                                dir="auto"
                            />
                            <!-- Line/col indicator bottom right of textarea -->
                            <div class="absolute bottom-3 right-3 text-xs text-muted-foreground bg-background/80 px-2 py-0.5 rounded pointer-events-none">
                                Ln {{ currentLine }} · Col {{ currentCol }}
                            </div>
                        </div>

                        <!-- VTT Format help — collapsible -->
                        <details class="text-xs rounded-lg border border-blue-200 dark:border-blue-800 overflow-hidden">
                            <summary class="px-3 py-2 bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 cursor-pointer font-semibold select-none">
                                📋 VTT Format reminder (click to expand)
                            </summary>
                            <div class="px-3 py-2 bg-blue-50/50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300 space-y-1">
                                <p>Line 1 must be <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">WEBVTT</code></p>
                                <p>Timestamps: <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">00:00:01.000 --> 00:00:04.000</code></p>
                                <p>Then subtitle text, then a <strong>blank line</strong> between cues.</p>
                            </div>
                        </details>

                        <!-- Save / Reset row -->
                        <div class="flex items-center justify-between pt-1">
                            <Button
                                variant="outline"
                                type="button"
                                @click="resetContent"
                                :disabled="!isDirty || saving"
                            >
                                <RotateCcw class="h-4 w-4 mr-2" />
                                Reset Changes
                            </Button>
                            <Button
                                @click="saveSubtitle"
                                :disabled="saving || !isDirty"
                                class="bg-primary text-primary-foreground hover:bg-primary/90 min-w-[130px]"
                            >
                                <Loader2 v-if="saving" class="h-4 w-4 mr-2 animate-spin" />
                                <Save v-else class="h-4 w-4 mr-2" />
                                {{ saving ? 'Saving...' : 'Save Subtitles' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- RIGHT: Video Preview -->
                <div class="space-y-4">
                    <Card class="bg-card border-border">
                        <CardHeader>
                            <CardTitle class="flex items-center justify-between text-base">
                                <span class="flex items-center gap-2">
                                    <PlaySquare class="h-4 w-4" />
                                    Live Preview
                                </span>
                                <div class="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="refreshPreview"
                                        class="text-xs"
                                    >
                                        <RefreshCw class="h-3 w-3 mr-1" />
                                        Refresh
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="togglePreviewSubtitles"
                                        :class="{ 'bg-primary text-primary-foreground': previewSubtitlesOn }"
                                        class="text-xs"
                                    >
                                        <Captions class="h-3 w-3 mr-1" />
                                        CC {{ previewSubtitlesOn ? 'ON' : 'OFF' }}
                                    </Button>
                                </div>
                            </CardTitle>
                            <CardDescription>
                                Click "Refresh" after editing to reload subtitles in the preview player.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="relative bg-black rounded-lg overflow-hidden aspect-video">
                                <video
                                    ref="previewVideo"
                                    :src="videoSrc"
                                    class="w-full h-full"
                                    controls
                                    @loadedmetadata="onVideoLoaded"
                                >
                                    <track
                                        v-if="previewVttUrl"
                                        :key="trackKey"
                                        kind="subtitles"
                                        :src="previewVttUrl"
                                        srclang="ar"
                                        label="العربية"
                                        default
                                    />
                                </video>
                                <div v-if="!videoSrc"
                                    class="absolute inset-0 flex items-center justify-center text-muted-foreground text-sm">
                                    <div class="text-center">
                                        <PlaySquare class="h-12 w-12 mx-auto mb-2 opacity-30" />
                                        <p>No video preview available</p>
                                        <p class="text-xs mt-1">Google Drive videos cannot be previewed here</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Cue list -->
                    <Card class="bg-card border-border">
                        <CardHeader>
                            <CardTitle class="text-base flex items-center gap-2">
                                <List class="h-4 w-4" />
                                Cue List
                                <Badge variant="outline" class="ml-auto text-xs">{{ cueCount }} cues</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="max-h-[300px] overflow-y-auto">
                                <div
                                    v-for="(cue, index) in parsedCues"
                                    :key="index"
                                    class="flex items-start gap-3 px-4 py-3 border-b border-border last:border-0 hover:bg-accent/50 cursor-pointer text-sm"
                                    @click="seekToTime(cue.startSeconds)"
                                >
                                    <span class="text-xs text-muted-foreground font-mono shrink-0 mt-0.5 w-5 text-right">
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-mono shrink-0 mt-0.5">
                                        {{ cue.start }}
                                    </span>
                                    <span class="text-foreground flex-1 leading-relaxed" dir="rtl">
                                        {{ cue.text }}
                                    </span>
                                </div>
                                <div v-if="parsedCues.length === 0"
                                    class="px-4 py-8 text-center text-muted-foreground text-sm">
                                    No cues found. Check your VTT format.
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    ArrowLeft, Captions, FileText, PlaySquare, Save, RotateCcw,
    Loader2, AlertCircle, CheckCircle2, RefreshCw, List,
} from 'lucide-vue-next'

interface VideoData {
    id: number
    name: string
    subtitle_status: string | null
    subtitle_vtt_path: string | null
    streaming_url: string | null
    file_path: string | null
    storage_type: string
}

const props = defineProps<{
    video: VideoData
    vttContent: string
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
    { title: props.video.name, href: `/admin/videos/${props.video.id}/edit` },
    { title: 'Edit Subtitles', href: '#' },
]

// ── State ──────────────────────────────────────────────
const vttContent        = ref(props.vttContent)
const originalContent   = ref(props.vttContent)
const saving            = ref(false)
const previewVideo      = ref<HTMLVideoElement | null>(null)
const previewSubtitlesOn = ref(true)
const trackKey          = ref(0)   // incrementing this forces Vue to re-render the <track>
const editorTextarea   = ref<HTMLTextAreaElement | null>(null)
const fontSize         = ref(13)
const currentLine      = ref(1)
const currentCol       = ref(1)

// The VTT blob URL used by the preview player
const previewVttUrl = ref<string | null>(null)

// ── Computed stats ─────────────────────────────────────
const isDirty   = computed(() => vttContent.value !== originalContent.value)
const lineCount = computed(() => vttContent.value.split('\n').length)
const charCount = computed(() => vttContent.value.length)
const cueCount  = computed(() => parsedCues.value.length)

// ── Video src ──────────────────────────────────────────
const videoSrc = computed(() => {
    if (props.video.storage_type === 'local' && props.video.file_path) {
        return `/storage/${props.video.file_path}`
    }
    return null
})

// ── Parse cues from VTT text ───────────────────────────
interface Cue {
    start: string
    startSeconds: number
    text: string
}

function parseVtt(raw: string): Cue[] {
    const cues: Cue[] = []
    const lines = raw.split('\n')
    let i = 0

    while (i < lines.length) {
        const line = lines[i].trim()
        // Look for timestamp line
        if (line.includes('-->')) {
            const parts = line.split('-->')
            const start = parts[0].trim()
            const textLines: string[] = []
            i++
            while (i < lines.length && lines[i].trim() !== '') {
                textLines.push(lines[i].trim())
                i++
            }
            cues.push({
                start,
                startSeconds: timestampToSeconds(start),
                text: textLines.join(' '),
            })
        }
        i++
    }
    return cues
}

function timestampToSeconds(ts: string): number {
    const parts = ts.replace(',', '.').split(':')
    if (parts.length === 3) {
        return parseFloat(parts[0]) * 3600 + parseFloat(parts[1]) * 60 + parseFloat(parts[2])
    }
    if (parts.length === 2) {
        return parseFloat(parts[0]) * 60 + parseFloat(parts[1])
    }
    return 0
}

const parsedCues = computed(() => parseVtt(vttContent.value))

// ── Build blob URL from current VTT text ───────────────
function buildBlobUrl(): string {
    const blob = new Blob([vttContent.value], { type: 'text/vtt' })
    return URL.createObjectURL(blob)
}

function refreshPreview() {
    // Revoke old blob URL to avoid memory leak
    if (previewVttUrl.value && previewVttUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewVttUrl.value)
    }
    previewVttUrl.value = buildBlobUrl()
    trackKey.value++   // force <track> element to re-mount with new src

    // Re-apply mode after track reloads
    setTimeout(() => {
        applyTrackMode()
    }, 300)
}

function applyTrackMode() {
    if (previewVideo.value) {
        const tracks = previewVideo.value.textTracks
        if (tracks.length > 0) {
            tracks[0].mode = previewSubtitlesOn.value ? 'showing' : 'hidden'
        }
    }
}

function togglePreviewSubtitles() {
    previewSubtitlesOn.value = !previewSubtitlesOn.value
    applyTrackMode()
}

function onVideoLoaded() {
    applyTrackMode()
}

function seekToTime(seconds: number) {
    if (previewVideo.value) {
        previewVideo.value.currentTime = seconds
        previewVideo.value.play()
    }
}

// ── Editor actions ─────────────────────────────────────
function onContentChange() {
    updateCursor()
}

function resetContent() {
    vttContent.value = originalContent.value
}

function saveSubtitle() {
    saving.value = true
    router.post(
        `/admin/videos/${props.video.id}/subtitle/update`,
        { vtt_content: vttContent.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                originalContent.value = vttContent.value
                saving.value = false
            },
            onError: () => {
                saving.value = false
            },
        }
    )
}

// ── Status helpers ─────────────────────────────────────
function getStatusVariant(status: string | null) {
    switch (status) {
        case 'completed':  return 'default'
        case 'processing': return 'secondary'
        case 'failed':     return 'destructive'
        case 'pending':    return 'outline'
        default:           return 'outline'
    }
}

function getStatusIcon(status: string | null) {
    switch (status) {
        case 'completed':  return CheckCircle2
        case 'processing': return Loader2
        case 'failed':     return AlertCircle
        default:           return AlertCircle
    }
}

function getStatusLabel(status: string | null): string {
    switch (status) {
        case 'completed':  return 'Completed'
        case 'processing': return 'Processing'
        case 'failed':     return 'Failed'
        case 'pending':    return 'Pending'
        default:           return 'Not Generated'
    }
}

// ── On mount: build initial blob URL ──────────────────
onMounted(() => {
    if (vttContent.value) {
        previewVttUrl.value = buildBlobUrl()
    }
})

// ── Editor toolbar helpers ─────────────────────────────
function increaseFontSize() {
    if (fontSize.value < 20) fontSize.value++
}

function decreaseFontSize() {
    if (fontSize.value > 10) fontSize.value--
}

function goToLine(line: number) {
    if (!editorTextarea.value) return
    if (line === 1) {
        editorTextarea.value.scrollTop = 0
        editorTextarea.value.setSelectionRange(0, 0)
        editorTextarea.value.focus()
    } else {
        editorTextarea.value.scrollTop = editorTextarea.value.scrollHeight
        const len = vttContent.value.length
        editorTextarea.value.setSelectionRange(len, len)
        editorTextarea.value.focus()
    }
}

function updateCursor() {
    if (!editorTextarea.value) return
    const pos = editorTextarea.value.selectionStart
    const textBefore = vttContent.value.substring(0, pos)
    const lines = textBefore.split('\n')
    currentLine.value = lines.length
    currentCol.value  = lines[lines.length - 1].length + 1
}

</script>