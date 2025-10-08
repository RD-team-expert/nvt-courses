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
                            <Label for="video_category_id">Category</Label>
                            <Select v-model="form.video_category_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.video_category_id }">
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
                            <div v-if="form.errors.video_category_id" class="text-sm text-destructive">
                                {{ form.errors.video_category_id }}
                            </div>
                        </div>
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
                            Configure the video source and metadata
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Google Drive URL -->
                        <div class="space-y-2">
                            <Label for="google_drive_url">Google Drive Video URL</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="google_drive_url"
                                    v-model="form.google_drive_url"
                                    type="url"
                                    placeholder="https://drive.google.com/file/d/..."
                                    :class="{ 'border-destructive': form.errors.google_drive_url }"
                                    required
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
                                Video duration in seconds (optional - can be auto-detected)
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Thumbnail -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Image class="h-5 w-5" />
                            Thumbnail
                        </CardTitle>
                        <CardDescription>
                            Upload a custom thumbnail for your video course
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- File Upload -->
                        <div class="space-y-2">
                            <Label for="thumbnail">Custom Thumbnail</Label>
                            <div class="flex items-start gap-4">
                                <!-- Preview -->
                                <div class="w-32 h-24 rounded-lg border-2 border-dashed border-muted-foreground/25 bg-muted/50 flex items-center justify-center overflow-hidden shrink-0">
                                    <img
                                        v-if="thumbnailPreview"
                                        :src="thumbnailPreview"
                                        class="w-full h-full object-cover"
                                        alt="Thumbnail preview"
                                    />
                                    <div v-else class="text-center">
                                        <Image class="h-8 w-8 mx-auto text-muted-foreground mb-2" />
                                        <div class="text-xs text-muted-foreground">Preview</div>
                                    </div>
                                </div>

                                <!-- Upload Input -->
                                <div class="flex-1">
                                    <Input
                                        id="thumbnail"
                                        type="file"
                                        accept="image/*"
                                        :class="{ 'border-destructive': form.errors.thumbnail }"
                                        @change="handleThumbnailChange"
                                    />
                                    <div v-if="form.errors.thumbnail" class="text-sm text-destructive mt-1">
                                        {{ form.errors.thumbnail }}
                                    </div>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        Recommended: 1280x720px (16:9 ratio), JPG or PNG, max 2MB
                                    </div>
                                </div>
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
import { ref, watch } from 'vue'
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

// Icons
import {
    ArrowLeft,
    Save,
    PlaySquare,
    Settings,
    Link2,
    Image,
    TestTube,
    Loader2
} from 'lucide-vue-next'

interface VideoCategory {
    id: number
    name: string
}

const props = defineProps<{
    categories: VideoCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
    { title: 'Create Video Course', href: '' },
]

const form = useForm({
    name: '',
    description: '',
    google_drive_url: '',
    duration: null as number | null,
    thumbnail: null as File | null,
    video_category_id: null as string | null,
    is_active: true,
})

const isSubmitting = ref(false)
const testingUrl = ref(false)
const thumbnailPreview = ref<string | null>(null)

// Handle thumbnail file change
const handleThumbnailChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
        form.thumbnail = file

        // Create preview URL
        const reader = new FileReader()
        reader.onload = (e) => {
            thumbnailPreview.value = e.target?.result as string
        }
        reader.readAsDataURL(file)
    } else {
        form.thumbnail = null
        thumbnailPreview.value = null
    }
}

// Test video URL
const testVideoUrl = async () => {
    if (!form.google_drive_url) return

    testingUrl.value = true

    try {
        // This would be a call to your backend to test the URL
        console.log('Testing URL:', form.google_drive_url)

        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 2000))

        // Show success message or handle response
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
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}
</script>
