<!-- Admin Edit Audio - Complete with Image Upload -->
<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, nextTick } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Checkbox } from '@/components/ui/checkbox'

// Icons
import {
    ArrowLeft,
    Save,
    Volume2,
    Link as LinkIcon,
    Tag,
    X,
    Upload,
    AlertTriangle,
    Image as ImageIcon,
    Eye,
    Trash2
} from 'lucide-vue-next'

interface Audio {
    id: number
    name: string
    description?: string
    google_cloud_url: string
    duration?: number
    thumbnail_url?: string
    thumbnail_path?: string // For uploaded files
    audio_category_id?: number
    is_active: boolean
}

interface AudioCategory {
    id: number
    name: string
}

const props = defineProps<{
    audio: Audio
    categories: AudioCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audio Management', href: '/admin/audio' },
    { title: props.audio.name, href: `/admin/audio/${props.audio.id}` },
    { title: 'Edit', href: '#' }
]

// Convert seconds to MM:SS format
const formatDurationForEdit = (seconds?: number): string => {
    if (!seconds) return ''
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const form = useForm({
    name: props.audio.name || '',
    description: props.audio.description || '',
    google_cloud_url: props.audio.google_cloud_url || '',
    duration: formatDurationForEdit(props.audio.duration),
    thumbnail: null as File | null, // For new file upload
    thumbnail_url: props.audio.thumbnail_url || '',
    remove_thumbnail: false, // Option to remove current thumbnail
    audio_category_id: props.audio.audio_category_id?.toString() || '',
    is_active: props.audio.is_active ?? true
})

const isSubmitting = ref(false)
const thumbnailPreview = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const showRemoveThumbnail = ref(false)

// Check if current audio has a thumbnail
const hasCurrentThumbnail = props.audio.thumbnail_url || props.audio.thumbnail_path

// Parse time helpers
const parseTimeToSeconds = (timeString: string): number => {
    if (!timeString) return 0

    const parts = timeString.split(':')
    if (parts.length === 2) {
        const minutes = parseInt(parts[0]) || 0
        const seconds = parseInt(parts[1]) || 0
        return minutes * 60 + seconds
    }
    return 0
}

const convertMinutesToSeconds = (minutes: number): number => {
    return Math.round(minutes * 60)
}

// Handle thumbnail file selection
function handleThumbnailChange(event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB')
            target.value = ''
            return
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file')
            target.value = ''
            return
        }

        form.thumbnail = file

        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            thumbnailPreview.value = e.target?.result as string
        }
        reader.readAsDataURL(file)

        // Clear external URL and remove options when file is selected
        form.thumbnail_url = ''
        form.remove_thumbnail = false
        showRemoveThumbnail.value = false
    }
}

// Remove new thumbnail upload
function removeThumbnail() {
    form.thumbnail = null
    thumbnailPreview.value = null

    // Reset file input
    if (fileInputRef.value) {
        fileInputRef.value.value = ''
    }
}

// Trigger file input
function triggerFileInput() {
    fileInputRef.value?.click()
}

// Toggle remove current thumbnail
function toggleRemoveThumbnail() {
    showRemoveThumbnail.value = !showRemoveThumbnail.value
    form.remove_thumbnail = showRemoveThumbnail.value

    if (showRemoveThumbnail.value) {
        // Clear new upload if removing current thumbnail
        form.thumbnail = null
        thumbnailPreview.value = null
        if (fileInputRef.value) {
            fileInputRef.value.value = ''
        }
    }
}

const submit = async () => {
    isSubmitting.value = true

    // Convert duration to seconds if it's provided
    let durationInSeconds = null
    if (form.duration) {
        if (form.duration.includes(':')) {
            durationInSeconds = parseTimeToSeconds(form.duration)
        } else {
            durationInSeconds = convertMinutesToSeconds(parseFloat(form.duration))
        }
    }

    // Wait for next tick to ensure DOM is stable
    await nextTick()

    // ✅ CHANGE FROM PUT TO POST WITH METHOD SPOOFING
    form.transform((data) => ({
        ...data,
        _method: 'PUT', // ✅ ADD THIS - Laravel will treat it as PUT
        duration: durationInSeconds,
        audio_category_id: data.audio_category_id || null
    })).post(`/admin/audio/${props.audio.id}`, { // ✅ CHANGE FROM PUT TO POST
        forceFormData: true, // ✅ ADD THIS - Forces multipart/form-data
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}
</script>

<template>
    <Head :title="`Edit ${audio.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button asChild variant="ghost">
                    <Link :href="`/admin/audio/${audio.id}`">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Audio
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Edit Audio Course</h1>
                    <p class="text-muted-foreground">Update the details for "{{ audio.name }}"</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Volume2 class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription>
                            Update the basic details for your audio course
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Course Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter audio course name"
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
                                placeholder="Describe what this audio course covers..."
                                :rows="4"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <Label for="category">Category</Label>
                            <select
                                id="category"
                                v-model="form.audio_category_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': form.errors.audio_category_id }"
                            >
                                <option value="">No Category</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                    :selected="category.id.toString() === form.audio_category_id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.audio_category_id" class="text-sm text-destructive">
                                {{ form.errors.audio_category_id }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Media Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <LinkIcon class="h-5 w-5" />
                            Media Information
                        </CardTitle>
                        <CardDescription>
                            Update the Google Drive/Cloud Storage URL and media details
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Google Cloud URL -->
                        <div class="space-y-2">
                            <Label for="google_cloud_url">Google Drive/Cloud URL *</Label>
                            <Input
                                id="google_cloud_url"
                                v-model="form.google_cloud_url"
                                type="url"
                                placeholder="https://drive.google.com/file/d/YOUR_FILE_ID/view"
                                :class="{ 'border-destructive': form.errors.google_cloud_url }"
                                required
                            />
                            <div v-if="form.errors.google_cloud_url" class="text-sm text-destructive">
                                {{ form.errors.google_cloud_url }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Enter the Google Drive sharing URL or Google Cloud Storage URL
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="space-y-2">
                            <Label for="duration">Duration</Label>
                            <Input
                                id="duration"
                                v-model="form.duration"
                                type="text"
                                placeholder="30 (minutes) or 30:45 (MM:SS)"
                                :class="{ 'border-destructive': form.errors.duration }"
                            />
                            <div v-if="form.errors.duration" class="text-sm text-destructive">
                                {{ form.errors.duration }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Enter duration in minutes (e.g., "30") or MM:SS format (e.g., "30:45")
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Thumbnail Management -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ImageIcon class="h-5 w-5" />
                            Thumbnail Image
                        </CardTitle>
                        <CardDescription>
                            Update the thumbnail image or provide an external URL
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Current Thumbnail Display -->
                        <div v-if="hasCurrentThumbnail && !showRemoveThumbnail && !thumbnailPreview" class="space-y-3">
                            <Label>Current Thumbnail</Label>
                            <div class="relative w-48 h-32 rounded-lg overflow-hidden border">
                                <img
                                    :src="audio.thumbnail_url"
                                    alt="Current thumbnail"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex gap-2">
                                <Button @click="triggerFileInput" variant="outline" size="sm" type="button">
                                    <Upload class="h-4 w-4 mr-2" />
                                    Replace Image
                                </Button>
                                <Button @click="toggleRemoveThumbnail" variant="outline" size="sm" type="button">
                                    <Trash2 class="h-4 w-4 mr-2" />
                                    Remove Thumbnail
                                </Button>
                            </div>
                        </div>

                        <!-- Remove Thumbnail Confirmation -->
                        <div v-if="showRemoveThumbnail" class="space-y-3">
                            <Alert variant="destructive">
                                <Trash2 class="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Remove current thumbnail?</strong><br>
                                    This action will remove the current thumbnail image.
                                </AlertDescription>
                            </Alert>
                            <div class="flex gap-2">
                                <Button @click="toggleRemoveThumbnail" variant="outline" size="sm" type="button">
                                    <X class="h-4 w-4 mr-2" />
                                    Keep Thumbnail
                                </Button>
                                <Button @click="triggerFileInput" variant="outline" size="sm" type="button">
                                    <Upload class="h-4 w-4 mr-2" />
                                    Upload New Image
                                </Button>
                            </div>
                        </div>

                        <!-- File Upload Section -->
                        <div class="space-y-3">
                            <Label>Upload New Thumbnail</Label>

                            <!-- Hidden file input -->
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                @change="handleThumbnailChange"
                                class="hidden"
                            />

                            <!-- Upload Area -->
                            <div
                                v-if="!thumbnailPreview && (!hasCurrentThumbnail || showRemoveThumbnail)"
                                @click="triggerFileInput"
                                class="border-2 border-dashed border-input rounded-lg p-6 text-center cursor-pointer hover:border-primary transition-colors"
                                :class="{ 'border-destructive': form.errors.thumbnail }"
                            >
                                <Upload class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <div class="text-sm font-medium mb-2">Click to upload new thumbnail</div>
                                <div class="text-xs text-muted-foreground">
                                    PNG, JPG, GIF, WebP up to 2MB
                                </div>
                            </div>

                            <!-- New Thumbnail Preview -->
                            <div v-if="thumbnailPreview" class="space-y-3">
                                <div class="relative w-48 h-32 rounded-lg overflow-hidden border">
                                    <img
                                        :src="thumbnailPreview"
                                        alt="New thumbnail preview"
                                        class="w-full h-full object-cover"
                                    />
                                    <Button
                                        @click="removeThumbnail"
                                        variant="destructive"
                                        size="sm"
                                        class="absolute top-2 right-2 h-8 w-8 p-0 rounded-full"
                                        type="button"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <div class="flex gap-2">
                                    <Button @click="triggerFileInput" variant="outline" size="sm" type="button">
                                        <Upload class="h-4 w-4 mr-2" />
                                        Change Image
                                    </Button>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="form.errors.thumbnail" class="text-sm text-destructive">
                                {{ form.errors.thumbnail }}
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="flex items-center gap-4 my-4">
                            <div class="flex-1 border-t border-border"></div>
                            <span class="text-sm text-muted-foreground bg-background px-2">OR</span>
                            <div class="flex-1 border-t border-border"></div>
                        </div>

                        <!-- External URL Option -->
                        <div class="space-y-2">
                            <Label for="thumbnail_url">External Thumbnail URL</Label>
                            <Input
                                id="thumbnail_url"
                                v-model="form.thumbnail_url"
                                type="url"
                                placeholder="https://example.com/thumbnail.jpg"
                                :class="{ 'border-destructive': form.errors.thumbnail_url }"
                                :disabled="!!form.thumbnail || showRemoveThumbnail"
                            />
                            <div v-if="form.errors.thumbnail_url" class="text-sm text-destructive">
                                {{ form.errors.thumbnail_url }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Alternative: provide an external image URL
                                {{ (form.thumbnail || showRemoveThumbnail) ? '(disabled due to file upload/removal)' : '' }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Tag class="h-5 w-5" />
                            Settings
                        </CardTitle>
                        <CardDescription>
                            Configure the visibility and availability of this audio course
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <Label>Active Status</Label>
                                <div class="text-sm text-muted-foreground">
                                    Make this audio course available to users
                                </div>
                            </div>
                            <Switch
                                :checked="form.is_active"
                                @update:checked="form.is_active = $event"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Guidelines -->
                <Alert>
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <strong>Important:</strong> When updating the Google Drive file, make sure it's set to
                        "Anyone with the link can view" for proper audio streaming.
                    </AlertDescription>
                </Alert>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <Button asChild variant="outline">
                        <Link :href="`/admin/audio/${audio.id}`">
                            Cancel
                        </Link>
                    </Button>

                    <Button type="submit" :disabled="isSubmitting || form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Updating...' : 'Update Audio Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
