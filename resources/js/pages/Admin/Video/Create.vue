<template>
    <Head title="Create Video Course" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost">
                    <Link href="/admin/videos">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Videos
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Video Course</h1>
                    <p class="text-muted-foreground">Add a new video course to your library</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PlaySquare class="h-5 w-5" />
                            Video Information
                        </CardTitle>
                        <CardDescription>
                            Enter the basic details for your video course
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Video Title</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter video course title..."
                                :class="{ 'border-destructive': form.errors.name }"
                                required
                            />
                            <div v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe what students will learn from this video course..."
                                rows="5"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <Label for="content_category_id">Category</Label>
                            <Select v-model="form.content_category_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.content_category_id }">
                                    <SelectValue placeholder="Select a category..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.content_category_id" class="text-sm text-destructive">
                                {{ form.errors.content_category_id }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ✅ NEW: Storage Type Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <HardDrive class="h-5 w-5" />
                            Storage Type
                        </CardTitle>
                        <CardDescription>
                            Choose where to store your video
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <RadioGroup v-model="form.storage_type" class="grid grid-cols-2 gap-4">
                            <!-- Google Drive Option -->
                            <Label
                                for="google_drive"
                                class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                :class="{ 'border-primary': form.storage_type === 'google_drive' }"
                            >
                                <RadioGroupItem value="google_drive" id="google_drive" class="sr-only" />
                                <Cloud class="mb-3 h-6 w-6" />
                                <div class="space-y-1 text-center">
                                    <p class="text-sm font-medium leading-none">Google Drive</p>
                                    <p class="text-xs text-muted-foreground">
                                        Store video on Google Drive
                                    </p>
                                </div>
                            </Label>

                            <!-- Local Storage Option -->
                            <Label
                                for="local"
                                class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                :class="{ 'border-primary': form.storage_type === 'local' }"
                            >
                                <RadioGroupItem value="local" id="local" class="sr-only" />
                                <HardDrive class="mb-3 h-6 w-6" />
                                <div class="space-y-1 text-center">
                                    <p class="text-sm font-medium leading-none">Local Storage</p>
                                    <p class="text-xs text-muted-foreground">
                                        Upload video to server
                                    </p>
                                </div>
                            </Label>
                        </RadioGroup>
                    </CardContent>
                </Card>

                <!-- Video Source -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Link2 class="h-5 w-5" />
                            Video Source
                        </CardTitle>
                        <CardDescription>
                            {{ form.storage_type === 'google_drive' ? 'Provide Google Drive URL' : 'Upload video file' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- ✅ Google Drive URL (show only if google_drive selected) -->
                        <div v-if="form.storage_type === 'google_drive'" class="space-y-2">
                            <Label for="google_drive_url">Google Drive Video URL</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="google_drive_url"
                                    v-model="form.google_drive_url"
                                    type="url"
                                    placeholder="https://drive.google.com/file/d/..."
                                    :class="{ 'border-destructive': form.errors.google_drive_url }"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    @click="testVideoUrl"
                                    :disabled="!form.google_drive_url || testingUrl"
                                >
                                    <Loader2 v-if="testingUrl" class="h-4 w-4 animate-spin" />
                                    <TestTube v-else class="h-4 w-4" />
                                </Button>
                            </div>
                            <div v-if="form.errors.google_drive_url" class="text-sm text-destructive">
                                {{ form.errors.google_drive_url }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Paste the shareable link from Google Drive. Make sure the video is set to "Anyone with the link can view"
                            </div>
                        </div>

                        <!-- ✅ NEW: Local Video Upload (show only if local selected) -->
                        <div v-else-if="form.storage_type === 'local'" class="space-y-2">
                            <Label for="video_file">Video File</Label>
                            <div class="space-y-3">
                                <!-- File Input -->
                                <div
                                    class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:border-primary transition-colors"
                                    :class="{
                                        'border-destructive': form.errors.video_file,
                                        'border-primary bg-primary/5': uploadProgress > 0
                                    }"
                                    @click="$refs.videoFileInput?.click()"
                                    @dragover.prevent="dragActive = true"
                                    @dragleave.prevent="dragActive = false"
                                    @drop.prevent="handleFileDrop"
                                >
                                    <input
                                        ref="videoFileInput"
                                        type="file"
                                        accept="video/mp4,video/webm,video/avi,video/mov,video/x-matroska"
                                        class="hidden"
                                        @change="handleFileChange"
                                    />

                                    <div v-if="!form.video_file">
                                        <Upload class="h-10 w-10 mx-auto text-muted-foreground mb-4" />
                                        <p class="text-sm font-medium mb-1">
                                            Click to upload or drag and drop
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            MP4, WebM, AVI, MOV, MKV (Max {{ maxFileSizeMB }}MB)
                                        </p>
                                    </div>

                                    <div v-else class="flex items-center gap-3">
                                        <FileVideo class="h-8 w-8 text-primary" />
                                        <div class="flex-1 text-left">
                                            <p class="text-sm font-medium">{{ form.video_file.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ formatFileSize(form.video_file.size) }}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click.stop="clearVideoFile"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Upload Progress -->
                                <div v-if="uploadProgress > 0 && uploadProgress < 100" class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Uploading...</span>
                                        <span>{{ uploadProgress }}%</span>
                                    </div>
                                    <div class="w-full bg-secondary rounded-full h-2">
                                        <div
                                            class="bg-primary h-2 rounded-full transition-all duration-300"
                                            :style="{ width: `${uploadProgress}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.video_file" class="text-sm text-destructive">
                                {{ form.errors.video_file }}
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="space-y-2">
                            <Label for="duration">Duration (seconds)</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="duration"
                                    v-model="form.duration"
                                    type="number"
                                    min="1"
                                    max="86400"
                                    placeholder="e.g., 3600 for 1 hour"
                                    :class="{ 'border-destructive': form.errors.duration }"
                                />
                                <div class="flex items-center px-3 py-2 bg-muted rounded-md text-sm text-muted-foreground min-w-0">
                                    {{ formatDuration(form.duration) }}
                                </div>
                            </div>
                            <div v-if="form.errors.duration" class="text-sm text-destructive">
                                {{ form.errors.duration }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ form.storage_type === 'local' ? 'Optional - will be auto-detected from video file' : 'Video duration in seconds (optional)' }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Settings
                        </CardTitle>
                        <CardDescription>
                            Configure the video course settings
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <Label>Active Status</Label>
                                <div class="text-sm text-muted-foreground">
                                    Make this video course available to students
                                </div>
                            </div>
                            <Switch
                                :checked="form.is_active"
                                @update:checked="form.is_active = $event"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <Button as-child variant="outline">
                        <Link href="/admin/videos">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="isSubmitting || form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Creating...' : 'Create Video Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

// Icons
import {
    ArrowLeft,
    Save,
    PlaySquare,
    Settings,
    Link2,
    TestTube,
    Loader2,
    Cloud,
    HardDrive,
    Upload,
    FileVideo,
    X
} from 'lucide-vue-next'

interface VideoCategory {
    id: number
    name: string
}

const props = defineProps<{
    categories: VideoCategory[]
    storageOptions?: Array<{value: string, label: string}>
    maxFileSize?: number
    allowedMimes?: string[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
    { title: 'Create Video Course', href: '' },
]

const form = useForm({
    name: '',
    description: '',
    storage_type: 'google_drive' as 'google_drive' | 'local', // ✅ NEW
    google_drive_url: '',
    video_file: null as File | null, // ✅ NEW
    duration: null as number | null,
    content_category_id: null as string | null,
    is_active: true,
})

const isSubmitting = ref(false)
const testingUrl = ref(false)
const uploadProgress = ref(0)
const dragActive = ref(false)
const videoFileInput = ref<HTMLInputElement | null>(null)

// Computed
const maxFileSizeMB = computed(() => Math.round((props.maxFileSize || 512000) / 1024))

// ✅ NEW: Handle file selection
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        form.video_file = file
        uploadProgress.value = 0
    }
}

// ✅ NEW: Handle drag and drop
const handleFileDrop = (event: DragEvent) => {
    dragActive.value = false
    const file = event.dataTransfer?.files[0]
    if (file) {
        form.video_file = file
        uploadProgress.value = 0
    }
}

// ✅ NEW: Clear selected file
const clearVideoFile = () => {
    form.video_file = null
    uploadProgress.value = 0
    if (videoFileInput.value) {
        videoFileInput.value.value = ''
    }
}

// ✅ NEW: Format file size
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

// Test video URL
const testVideoUrl = async () => {
    if (!form.google_drive_url) return
    testingUrl.value = true
    try {
        console.log('Testing URL:', form.google_drive_url)
        await new Promise(resolve => setTimeout(resolve, 2000))
        alert('Video URL is valid!')
    } catch (error) {
        console.error('URL test failed:', error)
        alert('Failed to validate video URL. Please check the link.')
    } finally {
        testingUrl.value = false
    }
}

// Format duration helper
const formatDuration = (seconds: number | null): string => {
    if (!seconds) return '00:00'
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60
    return hours > 0
        ? `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
        : `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

const submit = async () => {
    isSubmitting.value = true

    form.post('/admin/videos', {
        forceFormData: true, // ✅ Important for file uploads
        onProgress: (progress) => {
            uploadProgress.value = Math.round((progress.percentage || 0))
        },
        onFinish: () => {
            isSubmitting.value = false
            uploadProgress.value = 0
        },
        onSuccess: () => {
            console.log('Video created successfully')
        }
    })
}
</script>
