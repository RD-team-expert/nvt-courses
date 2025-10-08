<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, nextTick, computed } from 'vue'
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

// Icons
import {
    ArrowLeft,
    Save,
    Volume2,
    Clock,
    Link as LinkIcon,
    Image as ImageIcon,
    Tag,
    X,
    Upload,
    AlertTriangle
} from 'lucide-vue-next'

interface AudioCategory {
    id: number
    name: string
}

const props = defineProps<{
    categories: AudioCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audio Management', href: '/admin/audio' },
    { title: 'Create Audio', href: '#' }
]

const form = useForm({
    name: '',
    description: '',
    google_cloud_url: '',
    duration: '', // HH:MM:SS format
    thumbnail: null as File | null,
    thumbnail_url: '',
    audio_category_id: '',
    is_active: true
})

const isSubmitting = ref(false)
const thumbnailPreview = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const durationInput = ref<HTMLInputElement | null>(null)

// Duration state
const durationDisplay = ref('00:00:00')

// Update form duration
const updateFormDuration = () => {
    form.duration = durationDisplay.value === '00:00:00' ? '' : durationDisplay.value
}

// Enhanced input handler with better validation
const handleDurationInput = (event: Event) => {
    const target = event.target as HTMLInputElement
    let input = target.value

    // Remove all non-digits
    let numbersOnly = input.replace(/\D/g, '')

    // Limit to 6 digits maximum (HHMMSS)
    if (numbersOnly.length > 6) {
        numbersOnly = numbersOnly.substring(0, 6)
    }

    // If empty or all zeros, reset to 00:00:00
    if (!numbersOnly || numbersOnly === '0' || numbersOnly === '00' || numbersOnly === '000') {
        durationDisplay.value = '00:00:00'
        target.value = '00:00:00'
        updateFormDuration()
        return
    }

    // Pad with leading zeros to make it 6 digits for processing
    const padded = numbersOnly.padStart(6, '0')

    // Extract components
    let hours = parseInt(padded.substring(0, 2))
    let minutes = parseInt(padded.substring(2, 4))
    let seconds = parseInt(padded.substring(4, 6))

    // Validate and adjust ranges
    if (hours > 23) hours = 23
    if (minutes > 59) minutes = 59
    if (seconds > 59) seconds = 59

    // Format as HH:MM:SS
    const formatted = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`

    // Update values
    durationDisplay.value = formatted
    target.value = formatted
    updateFormDuration()
}

// Format for readable display
const formatDurationReadable = (timeString: string): string => {
    if (!timeString || timeString === '00:00:00') return '0 seconds'

    const [hours, minutes, seconds] = timeString.split(':').map(Number)
    const parts = []

    if (hours > 0) parts.push(`${hours} hour${hours !== 1 ? 's' : ''}`)
    if (minutes > 0) parts.push(`${minutes} minute${minutes !== 1 ? 's' : ''}`)
    if (seconds > 0) parts.push(`${seconds} second${seconds !== 1 ? 's' : ''}`)

    if (parts.length === 0) return '0 seconds'
    if (parts.length === 1) return parts[0]
    if (parts.length === 2) return `${parts[0]} and ${parts[1]}`
    return `${parts[0]}, ${parts[1]}, and ${parts[2]}`
}

// Handle paste events
const handleDurationPaste = (event: ClipboardEvent) => {
    event.preventDefault()
    const pastedText = event.clipboardData?.getData('text') || ''

    // Try to parse various formats
    let numbersOnly = pastedText.replace(/\D/g, '')

    if (numbersOnly.length <= 6) {
        const fakeEvent = { target: { value: numbersOnly } }
        handleDurationInput(fakeEvent as any)
    }
}

// Set duration preset
const setDurationPreset = (timeString: string) => {
    durationDisplay.value = timeString
    updateFormDuration()
    if (durationInput.value) {
        durationInput.value.value = timeString
    }
}

// Clear duration
const clearDuration = () => {
    durationDisplay.value = '00:00:00'
    updateFormDuration()
    if (durationInput.value) {
        durationInput.value.value = '00:00:00'
    }
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

        // Clear external URL if file is selected
        form.thumbnail_url = ''
    }
}

// Remove thumbnail
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

// Submit form
const submit = async () => {
    isSubmitting.value = true

    await nextTick()

    form.transform((data) => ({
        ...data,
        duration: data.duration || null,
        audio_category_id: data.audio_category_id || null
    })).post('/admin/audio', {
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}

// Initialize form duration
updateFormDuration()
</script>

<template>
    <Head title="Create Audio Course" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button asChild variant="ghost">
                    <Link href="/admin/audio">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Audio
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Audio Course</h1>
                    <p class="text-muted-foreground">Add a new audio course to your library</p>
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
                            Enter the basic details for your audio course
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
                                <option value="">Select a category (optional)</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id.toString()">
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
                            Provide the Google Cloud Storage URL and media details
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

                        <!-- Enhanced Duration Input -->
                        <div class="space-y-2">
                            <Label for="duration">Duration</Label>
                            <Input
                                id="duration"
                                v-model="form.duration"
                                type="text"
                                step="1"
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

                <!-- Thumbnail Upload -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ImageIcon class="h-5 w-5" />
                            Thumbnail Image
                        </CardTitle>
                        <CardDescription>
                            Upload a thumbnail image or provide an external URL
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- File Upload Section -->
                        <div class="space-y-3">
                            <Label>Upload Thumbnail</Label>

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
                                v-if="!thumbnailPreview"
                                @click="triggerFileInput"
                                class="border-2 border-dashed border-input rounded-lg p-6 text-center cursor-pointer hover:border-primary transition-colors"
                                :class="{ 'border-destructive': form.errors.thumbnail }"
                            >
                                <Upload class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <div class="text-sm font-medium mb-2">Click to upload thumbnail</div>
                                <div class="text-xs text-muted-foreground">
                                    PNG, JPG, GIF, WebP up to 2MB
                                </div>
                            </div>

                            <!-- Thumbnail Preview -->
                            <div v-if="thumbnailPreview" class="space-y-3">
                                <div class="relative w-48 h-32 rounded-lg overflow-hidden border">
                                    <img
                                        :src="thumbnailPreview"
                                        alt="Thumbnail preview"
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
                                :disabled="!!form.thumbnail"
                            />
                            <div v-if="form.errors.thumbnail_url" class="text-sm text-destructive">
                                {{ form.errors.thumbnail_url }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Alternative: provide an external image URL (disabled when file is uploaded)
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
                        <strong>Important:</strong> Make sure your Google Drive file is set to "Anyone with the link can view"
                        for proper audio streaming. Duration should be entered in HH:MM:SS format (e.g., 01:30:45 for 1 hour, 30 minutes, 45 seconds).
                    </AlertDescription>
                </Alert>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <Button asChild variant="outline">
                        <Link href="/admin/audio">
                            Cancel
                        </Link>
                    </Button>

                    <Button type="submit" :disabled="isSubmitting || form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Creating...' : 'Create Audio Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
