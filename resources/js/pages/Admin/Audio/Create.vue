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
    storage_type: 'google_drive' as 'google_drive' | 'local',
    google_cloud_url: '',
    audio_file: null as File | null,
    duration: '', // HH:MM:SS format
    thumbnail: null as File | null,
    thumbnail_url: '',
    audio_category_id: '',
    is_active: true
})

const isSubmitting = ref(false)
const thumbnailPreview = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const audioFileInputRef = ref<HTMLInputElement | null>(null)

// Duration state - separate inputs for hours, minutes, seconds
const durationHours = ref<number>(0)
const durationMinutes = ref<number>(0)
const durationSeconds = ref<number>(0)

// Update form duration from separate inputs
const updateFormDuration = () => {
    const h = durationHours.value.toString().padStart(2, '0')
    const m = durationMinutes.value.toString().padStart(2, '0')
    const s = durationSeconds.value.toString().padStart(2, '0')
    
    // Only set duration if at least one value is non-zero
    if (durationHours.value > 0 || durationMinutes.value > 0 || durationSeconds.value > 0) {
        form.duration = `${h}:${m}:${s}`
    } else {
        form.duration = ''
    }
}

// Handle duration input changes with validation
const handleDurationChange = (type: 'hours' | 'minutes' | 'seconds', value: string) => {
    let numValue = parseInt(value) || 0
    
    // Validate ranges
    if (type === 'hours') {
        numValue = Math.max(0, Math.min(99, numValue))
        durationHours.value = numValue
    } else if (type === 'minutes') {
        numValue = Math.max(0, Math.min(59, numValue))
        durationMinutes.value = numValue
    } else if (type === 'seconds') {
        numValue = Math.max(0, Math.min(59, numValue))
        durationSeconds.value = numValue
    }
    
    updateFormDuration()
}

// Quick preset buttons
const setDurationPreset = (hours: number, minutes: number, seconds: number) => {
    durationHours.value = hours
    durationMinutes.value = minutes
    durationSeconds.value = seconds
    updateFormDuration()
}

// Clear duration
const clearDuration = () => {
    durationHours.value = 0
    durationMinutes.value = 0
    durationSeconds.value = 0
    updateFormDuration()
}

// Computed readable duration
const readableDuration = computed(() => {
    const parts = []
    if (durationHours.value > 0) parts.push(`${durationHours.value}h`)
    if (durationMinutes.value > 0) parts.push(`${durationMinutes.value}m`)
    if (durationSeconds.value > 0) parts.push(`${durationSeconds.value}s`)
    return parts.length > 0 ? parts.join(' ') : 'Not set'
})

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

// Handle audio file selection
function handleAudioFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
        // Validate file size (50MB)
        if (file.size > 50 * 1024 * 1024) {
            alert('Audio file size must be less than 50MB')
            target.value = ''
            return
        }

        // Validate file type
        if (!file.type.startsWith('audio/')) {
            alert('Please select an audio file')
            target.value = ''
            return
        }

        form.audio_file = file
    }
}

// Remove audio file
function removeAudioFile() {
    form.audio_file = null

    // Reset file input
    if (audioFileInputRef.value) {
        audioFileInputRef.value.value = ''
    }
}

// Trigger audio file input
function triggerAudioFileInput() {
    audioFileInputRef.value?.click()
}

// Submit form
const submit = async () => {
    isSubmitting.value = true

    await nextTick()

    form.transform((data) => ({
        ...data,
        duration: data.duration || null,
        audio_category_id: data.audio_category_id || null,
        google_cloud_url: data.storage_type === 'google_drive' ? data.google_cloud_url : null
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
                            Choose storage type and provide media details
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Storage Type Selection -->
                        <div class="space-y-3">
                            <Label>Storage Type *</Label>
                            <div class="grid grid-cols-2 gap-4">
                                <div
                                    @click="form.storage_type = 'google_drive'"
                                    class="relative flex items-center space-x-3 rounded-lg border p-4 cursor-pointer transition-all"
                                    :class="{
                                        'border-primary bg-primary/5': form.storage_type === 'google_drive',
                                        'border-input hover:border-primary/50': form.storage_type !== 'google_drive'
                                    }"
                                >
                                    <input
                                        type="radio"
                                        name="storage_type"
                                        value="google_drive"
                                        :checked="form.storage_type === 'google_drive'"
                                        class="h-4 w-4 text-primary"
                                    />
                                    <div class="flex-1">
                                        <div class="font-medium">Google Drive</div>
                                        <div class="text-xs text-muted-foreground">Store audio in Google Drive</div>
                                    </div>
                                </div>

                                <div
                                    @click="form.storage_type = 'local'"
                                    class="relative flex items-center space-x-3 rounded-lg border p-4 cursor-pointer transition-all"
                                    :class="{
                                        'border-primary bg-primary/5': form.storage_type === 'local',
                                        'border-input hover:border-primary/50': form.storage_type !== 'local'
                                    }"
                                >
                                    <input
                                        type="radio"
                                        name="storage_type"
                                        value="local"
                                        :checked="form.storage_type === 'local'"
                                        class="h-4 w-4 text-primary"
                                    />
                                    <div class="flex-1">
                                        <div class="font-medium">Local Storage</div>
                                        <div class="text-xs text-muted-foreground">Upload audio file to server</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Google Cloud URL (shown when Google Drive is selected) -->
                        <div v-if="form.storage_type === 'google_drive'" class="space-y-2">
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

                        <!-- Local File Upload (shown when Local is selected) -->
                        <div v-if="form.storage_type === 'local'" class="space-y-3">
                            <Label>Upload Audio File *</Label>

                            <!-- Hidden file input -->
                            <input
                                ref="audioFileInputRef"
                                type="file"
                                accept="audio/*"
                                @change="handleAudioFileChange"
                                class="hidden"
                            />

                            <!-- Upload Area -->
                            <div
                                v-if="!form.audio_file"
                                @click="triggerAudioFileInput"
                                class="border-2 border-dashed border-input rounded-lg p-6 text-center cursor-pointer hover:border-primary transition-colors"
                                :class="{ 'border-destructive': form.errors.audio_file }"
                            >
                                <Upload class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <div class="text-sm font-medium mb-2">Click to upload audio file</div>
                                <div class="text-xs text-muted-foreground">
                                    MP3, WAV, OGG, M4A up to 50MB
                                </div>
                            </div>

                            <!-- File Preview -->
                            <div v-if="form.audio_file" class="space-y-3">
                                <div class="flex items-center gap-3 p-4 rounded-lg border bg-muted/50">
                                    <Volume2 class="h-8 w-8 text-primary" />
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium truncate">{{ form.audio_file.name }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ (form.audio_file.size / (1024 * 1024)).toFixed(2) }} MB
                                        </div>
                                    </div>
                                    <Button
                                        @click="removeAudioFile"
                                        variant="destructive"
                                        size="sm"
                                        type="button"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                                <div class="flex gap-2">
                                    <Button @click="triggerAudioFileInput" variant="outline" size="sm" type="button">
                                        <Upload class="h-4 w-4 mr-2" />
                                        Change File
                                    </Button>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="form.errors.audio_file" class="text-sm text-destructive">
                                {{ form.errors.audio_file }}
                            </div>
                        </div>

                        <!-- User-Friendly Duration Input -->
                        <div class="space-y-3">
                            <Label>Duration</Label>
                            
                            <!-- Duration Input Grid -->
                            <div class="grid grid-cols-3 gap-3">
                                <!-- Hours -->
                                <div class="space-y-2">
                                    <Label for="hours" class="text-xs text-muted-foreground">Hours</Label>
                                    <Input
                                        id="hours"
                                        type="number"
                                        min="0"
                                        max="99"
                                        :value="durationHours"
                                        @input="handleDurationChange('hours', ($event.target as HTMLInputElement).value)"
                                        placeholder="0"
                                        class="text-center"
                                    />
                                </div>
                                
                                <!-- Minutes -->
                                <div class="space-y-2">
                                    <Label for="minutes" class="text-xs text-muted-foreground">Minutes</Label>
                                    <Input
                                        id="minutes"
                                        type="number"
                                        min="0"
                                        max="59"
                                        :value="durationMinutes"
                                        @input="handleDurationChange('minutes', ($event.target as HTMLInputElement).value)"
                                        placeholder="0"
                                        class="text-center"
                                    />
                                </div>
                                
                                <!-- Seconds -->
                                <div class="space-y-2">
                                    <Label for="seconds" class="text-xs text-muted-foreground">Seconds</Label>
                                    <Input
                                        id="seconds"
                                        type="number"
                                        min="0"
                                        max="59"
                                        :value="durationSeconds"
                                        @input="handleDurationChange('seconds', ($event.target as HTMLInputElement).value)"
                                        placeholder="0"
                                        class="text-center"
                                    />
                                </div>
                            </div>
                            
                            <!-- Duration Display -->
                            <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50 border">
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <span class="text-sm font-medium">Total Duration:</span>
                                    <span class="text-sm text-muted-foreground">{{ readableDuration }}</span>
                                </div>
                                <Button
                                    v-if="durationHours > 0 || durationMinutes > 0 || durationSeconds > 0"
                                    @click="clearDuration"
                                    variant="ghost"
                                    size="sm"
                                    type="button"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            
                            <!-- Quick Presets -->
                            <div class="space-y-2">
                                <Label class="text-xs text-muted-foreground">Quick Presets:</Label>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        @click="setDurationPreset(0, 5, 0)"
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                    >
                                        5 min
                                    </Button>
                                    <Button
                                        @click="setDurationPreset(0, 10, 0)"
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                    >
                                        10 min
                                    </Button>
                                    <Button
                                        @click="setDurationPreset(0, 15, 0)"
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                    >
                                        15 min
                                    </Button>
                                    <Button
                                        @click="setDurationPreset(0, 30, 0)"
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                    >
                                        30 min
                                    </Button>
                                    <Button
                                        @click="setDurationPreset(1, 0, 0)"
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                    >
                                        1 hour
                                    </Button>
                                    <Button
                                        @click="setDurationPreset(2, 0, 0)"
                                        variant="outline"
                                        size="sm"
                                        type="button"
                                    >
                                        2 hours
                                    </Button>
                                </div>
                            </div>
                            
                            <div v-if="form.errors.duration" class="text-sm text-destructive">
                                {{ form.errors.duration }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Enter the audio duration using separate fields for hours, minutes, and seconds
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
                        for proper audio streaming.
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
