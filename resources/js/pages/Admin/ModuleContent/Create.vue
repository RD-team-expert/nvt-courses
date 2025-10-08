<template>
    <Head title="Add Content" />

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('admin.module-content.index', [course.id, module.id])"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center mb-4"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Module Content
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-foreground">
                    {{ contentType === 'video' ? 'Add Video' : 'Upload PDF' }}
                </h1>
                <p class="text-gray-600 dark:text-muted-foreground mt-2">
                    {{ module.name }} - {{ course.name }}
                </p>
            </div>

            <!-- Content Type Tabs -->
            <div class="mb-8">
                <div class="sm:hidden">
                    <label for="content-type" class="sr-only">Select content type</label>
                    <select
                        id="content-type"
                        v-model="contentType"
                        class="block w-full border-gray-300 dark:border-border rounded-md bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400"
                    >
                        <option value="video">Add Video</option>
                        <option value="pdf">Upload PDF</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <button
                            @click="contentType = 'video'"
                            :class="[
                                contentType === 'video'
                                    ? 'border-green-500 text-green-600 dark:text-green-400'
                                    : 'border-transparent text-gray-500 dark:text-muted-foreground hover:text-gray-700 dark:hover:text-foreground hover:border-gray-300 dark:hover:border-border',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200'
                            ]"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Add Video</span>
                            </div>
                        </button>
                        <button
                            @click="contentType = 'pdf'"
                            :class="[
                                contentType === 'pdf'
                                    ? 'border-red-500 text-red-600 dark:text-red-400'
                                    : 'border-transparent text-gray-500 dark:text-muted-foreground hover:text-gray-700 dark:hover:text-foreground hover:border-gray-300 dark:hover:border-border',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200'
                            ]"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Upload PDF</span>
                            </div>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-card shadow rounded-lg border dark:border-border">
                <form @submit.prevent="submit" class="space-y-8 p-6">

                    <!-- Video Selection (if video type) -->
                    <div v-if="contentType === 'video'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-foreground mb-4">
                            Select Video *
                        </label>

                        <!-- Search Videos -->
                        <div class="mb-4">
                            <input
                                v-model="videoSearch"
                                type="text"
                                placeholder="Search videos..."
                                class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-2 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground"
                            />
                        </div>

                        <!-- Video Selection Grid -->
                        <div v-if="filteredVideos.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto">
                            <div
                                v-for="video in filteredVideos"
                                :key="video.id"
                                @click="selectVideo(video)"
                                :class="[
                                    'relative p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                                    form.video_id === video.id
                                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                        : 'border-gray-300 dark:border-border hover:border-green-300 dark:hover:border-green-700 hover:bg-gray-50 dark:hover:bg-muted/50'
                                ]"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-foreground">{{ video.name }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-muted-foreground">{{ video.formatted_duration }}</p>
                                        <p v-if="video.description" class="text-xs text-gray-400 dark:text-muted-foreground mt-1 truncate">{{ video.description }}</p>
                                    </div>
                                    <div v-if="form.video_id === video.id" class="flex-shrink-0">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Videos Available -->
                        <div v-else-if="availableVideos.length === 0" class="text-center py-8">
                            <svg class="w-16 h-16 text-gray-400 dark:text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-2">No videos available</h3>
                            <p class="text-gray-600 dark:text-muted-foreground">All videos are already assigned to this module or no videos exist in your library.</p>
                        </div>

                        <!-- No Search Results -->
                        <div v-else class="text-center py-8">
                            <svg class="w-16 h-16 text-gray-400 dark:text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-2">No videos found</h3>
                            <p class="text-gray-600 dark:text-muted-foreground">Try adjusting your search terms.</p>
                        </div>

                        <div v-if="form.errors.video_id" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.video_id }}</div>
                    </div>

                    <!-- PDF Upload (if pdf type) -->
                    <div v-if="contentType === 'pdf'">
                        <label for="pdf_file" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                            PDF File *
                        </label>
                        <div class="flex items-center space-x-6">
                            <div class="shrink-0">
                                <div v-if="pdfPreview" class="h-20 w-20 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center border dark:border-border">
                                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div v-else class="h-20 w-20 bg-gray-200 dark:bg-muted rounded-lg flex items-center justify-center border dark:border-border">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <input
                                    id="pdf_file"
                                    ref="pdfInput"
                                    type="file"
                                    accept=".pdf"
                                    @change="handlePDFUpload"
                                    class="block w-full text-sm text-gray-500 dark:text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-red-50 dark:file:bg-red-900/20 file:text-red-700 dark:file:text-red-400 hover:file:bg-red-100 dark:hover:file:bg-red-900/30"
                                    :class="{ 'border-red-500 dark:border-red-400': form.errors.pdf_file }"
                                />
                                <p class="text-xs text-gray-500 dark:text-muted-foreground mt-1">
                                    Maximum file size: 10MB
                                </p>
                                <div v-if="form.errors.pdf_file" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.pdf_file }}</div>
                            </div>
                        </div>

                        <!-- PDF Name -->
                        <div class="mt-6">
                            <label for="pdf_name" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                                PDF Name *
                            </label>
                            <input
                                id="pdf_name"
                                v-model="form.pdf_name"
                                type="text"
                                required
                                class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground"
                                :class="{ 'border-red-500 dark:border-red-400': form.errors.pdf_name }"
                                placeholder="Enter a descriptive name for the PDF"
                            />
                            <div v-if="form.errors.pdf_name" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.pdf_name }}</div>
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div class="space-y-6">
                        <!-- Content Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                                Content Title *
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground"
                                :class="{ 'border-red-500 dark:border-red-400': form.errors.title }"
                                :placeholder="contentType === 'video' ? 'How this content will appear in the module' : 'How students will see this PDF'"
                            />
                            <div v-if="form.errors.title" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.title }}</div>
                        </div>

                        <!-- Order Number -->
                        <div>
                            <label for="order_number" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                                Order Number *
                            </label>
                            <input
                                id="order_number"
                                v-model.number="form.order_number"
                                type="number"
                                min="1"
                                required
                                class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground"
                                :class="{ 'border-red-500 dark:border-red-400': form.errors.order_number }"
                                placeholder="Position in module sequence"
                            />
                            <p class="text-xs text-gray-500 dark:text-muted-foreground mt-1">
                                Position of this content in the module sequence
                            </p>
                            <div v-if="form.errors.order_number" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.order_number }}</div>
                        </div>

                        <!-- Content Settings -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-foreground">Content Settings</h3>

                            <!-- Required Content -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        id="is_required"
                                        v-model="form.is_required"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-400 border-gray-300 dark:border-border rounded bg-white dark:bg-background"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_required" class="font-medium text-gray-700 dark:text-foreground">
                                        Required Content
                                    </label>
                                    <p class="text-gray-500 dark:text-muted-foreground">
                                        Students must {{ contentType === 'video' ? 'watch this video' : 'view this PDF' }} to progress
                                    </p>
                                </div>
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        id="is_active"
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-400 border-gray-300 dark:border-border rounded bg-white dark:bg-background"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_active" class="font-medium text-gray-700 dark:text-foreground">
                                        Active Content
                                    </label>
                                    <p class="text-gray-500 dark:text-muted-foreground">
                                        Content is visible and accessible to students
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Context Info -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    Adding {{ contentType === 'video' ? 'Video' : 'PDF' }} to {{ module.name }}
                                </h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                    <p>This content will be added to the "{{ module.name }}" module in the "{{ course.name }}" course.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-border">
                        <Link
                            :href="route('admin.module-content.index', [course.id, module.id])"
                            class="px-6 py-3 border border-gray-300 dark:border-border rounded-md text-gray-700 dark:text-foreground bg-white dark:bg-background hover:bg-gray-50 dark:hover:bg-muted transition-colors duration-200"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !canSubmit"
                            class="px-6 py-3 border border-transparent rounded-md shadow-sm text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-white dark:focus:ring-offset-background transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Adding...' : (contentType === 'video' ? 'Add Video' : 'Upload PDF') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

// Props from controller
const props = defineProps({
    course: {
        type: Object,
        required: true
    },
    module: {
        type: Object,
        required: true
    },
    nextOrderNumber: {
        type: Number,
        required: true
    },
    availableVideos: {
        type: Array,
        default: () => []
    }
})

// URL params
const urlParams = new URLSearchParams(window.location.search)
const contentType = ref(urlParams.get('type') || 'video')

// Component state
const pdfInput = ref(null)
const pdfPreview = ref(null)
const videoSearch = ref('')

const form = useForm({
    content_type: 'video',
    title: '',
    order_number: 1,
    is_required: true,
    is_active: true,

    // Video fields
    video_id: null,

    // PDF fields
    pdf_file: null,
    pdf_name: ''
})

// Computed properties
const filteredVideos = computed(() => {
    if (!videoSearch.value) {
        return props.availableVideos
    }

    return props.availableVideos.filter(video =>
        video.name.toLowerCase().includes(videoSearch.value.toLowerCase()) ||
        (video.description && video.description.toLowerCase().includes(videoSearch.value.toLowerCase()))
    )
})

const canSubmit = computed(() => {
    if (contentType.value === 'video') {
        return form.video_id && form.title
    } else {
        return form.pdf_file && form.pdf_name && form.title
    }
})

// Initialize form
onMounted(() => {
    form.order_number = props.nextOrderNumber
    form.content_type = contentType.value
})

// Watch content type changes
watch(contentType, (newType) => {
    form.content_type = newType
    // Reset type-specific fields
    if (newType === 'video') {
        form.pdf_file = null
        form.pdf_name = ''
        pdfPreview.value = null
    } else {
        form.video_id = null
    }
})

// Video selection
const selectVideo = (video) => {
    form.video_id = video.id
    if (!form.title) {
        form.title = video.name
    }
}

// PDF upload handling
const handlePDFUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        form.pdf_file = file
        pdfPreview.value = file.name

        // Auto-suggest PDF name and title
        const fileName = file.name.replace('.pdf', '')
        if (!form.pdf_name) {
            form.pdf_name = fileName
        }
        if (!form.title) {
            form.title = fileName
        }
    }
}

const submit = () => {
    form.post(route('admin.module-content.store', [props.course.id, props.module.id]), {
        onSuccess: () => {
            form.reset()
            form.order_number = props.nextOrderNumber + 1
            pdfPreview.value = null
            if (pdfInput.value) {
                pdfInput.value.value = ''
            }
        }
    })
}
</script>
