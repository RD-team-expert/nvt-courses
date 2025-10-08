<template>
    <Head title="Edit Course" />

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('admin.course-online.index')"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center mb-4"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Courses
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-foreground">Edit Course</h1>
                <p class="text-gray-600 dark:text-muted-foreground mt-2">Update course information and settings</p>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-card shadow rounded-lg border dark:border-border">
                <form @submit.prevent="submit" class="space-y-8 p-6">
                    <!-- Course Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                            Course Name *
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground"
                            :class="{ 'border-red-500 dark:border-red-400': form.errors.name }"
                            placeholder="Enter course name"
                        />
                        <div v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground resize-none"
                            :class="{ 'border-red-500 dark:border-red-400': form.errors.description }"
                            placeholder="Describe what students will learn in this course"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.description }}</div>
                    </div>

                    <!-- Course Image -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                            Course Image
                        </label>
                        <div class="flex items-center space-x-6">
                            <div class="shrink-0">
                                <img
                                    v-if="imagePreview"
                                    :src="imagePreview"
                                    alt="Course preview"
                                    class="h-20 w-20 object-cover rounded-lg border dark:border-border"
                                />
                                <img
                                    v-else-if="course.image_path && !imageChanged"
                                    :src="course.image_path"
                                    alt="Current course image"
                                    class="h-20 w-20 object-cover rounded-lg border dark:border-border"
                                />
                                <div v-else class="h-20 w-20 bg-gray-200 dark:bg-muted rounded-lg flex items-center justify-center border dark:border-border">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <input
                                    id="image"
                                    ref="imageInput"
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload"
                                    class="block w-full text-sm text-gray-500 dark:text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/30"
                                    :class="{ 'border-red-500 dark:border-red-400': form.errors.image }"
                                />
                                <p class="text-xs text-gray-500 dark:text-muted-foreground mt-1">
                                    Leave empty to keep current image
                                </p>
                                <div v-if="form.errors.image" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.image }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Difficulty Level -->
                        <div>
                            <label for="difficulty_level" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                                Difficulty Level *
                            </label>
                            <select
                                id="difficulty_level"
                                v-model="form.difficulty_level"
                                required
                                class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent"
                                :class="{ 'border-red-500 dark:border-red-400': form.errors.difficulty_level }"
                            >
                                <option value="">Select difficulty</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                            <div v-if="form.errors.difficulty_level" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.difficulty_level }}</div>
                        </div>

                        <!-- Estimated Duration -->
                        <div>
                            <label for="estimated_duration" class="block text-sm font-medium text-gray-700 dark:text-foreground mb-2">
                                Estimated Duration (minutes)
                            </label>
                            <input
                                id="estimated_duration"
                                v-model.number="form.estimated_duration"
                                type="number"
                                min="1"
                                class="w-full border border-gray-300 dark:border-border rounded-md px-4 py-3 bg-white dark:bg-background text-gray-900 dark:text-foreground focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent placeholder:text-gray-500 dark:placeholder:text-muted-foreground"
                                :class="{ 'border-red-500 dark:border-red-400': form.errors.estimated_duration }"
                                placeholder="e.g. 120"
                            />
                            <div v-if="form.errors.estimated_duration" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.estimated_duration }}</div>
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-400 border-gray-300 dark:border-border rounded bg-white dark:bg-background"
                        />
                        <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-foreground">
                            Make this course active (students can be assigned)
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-border">
                        <Link
                            :href="route('admin.course-online.index')"
                            class="px-6 py-3 border border-gray-300 dark:border-border rounded-md text-gray-700 dark:text-foreground bg-white dark:bg-background hover:bg-gray-50 dark:hover:bg-muted transition-colors duration-200"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 border border-transparent rounded-md shadow-sm text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-white dark:focus:ring-offset-background transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Updating...' : 'Update Course' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

// Props from controller
const props = defineProps({
    course: {
        type: Object,
        required: true
    }
})

const imageInput = ref(null)
const imagePreview = ref(null)
const imageChanged = ref(false)

// Initialize form with existing course data
const form = useForm({
    name: '',
    description: '',
    image: null,
    difficulty_level: '',
    estimated_duration: null,
    is_active: true
})

// Populate form with existing course data
onMounted(() => {
    form.name = props.course.name || ''
    form.description = props.course.description || ''
    form.difficulty_level = props.course.difficulty_level || ''
    form.estimated_duration = props.course.estimated_duration || null
    form.is_active = props.course.is_active ?? true
})

const handleImageUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        form.image = file
        imageChanged.value = true

        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

const submit = () => {
    form.put(route('admin.course-online.update', props.course.id), {
        onSuccess: () => {
            // Reset image-related states
            imagePreview.value = null
            imageChanged.value = false
            if (imageInput.value) {
                imageInput.value.value = ''
            }
        }
    })
}
</script>
