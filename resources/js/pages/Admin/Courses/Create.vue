<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import Editor from '@tinymce/tinymce-vue'
import { ref, computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'

const imagePreview = ref(null)

// Progress tracking - only for public courses
const isCreatingCourse = ref(false)
const progressStep = ref(0)
const totalSteps = ref(4) // Course creation, availabilities, email preparation, sending emails

// ✅ Error tracking for admin notifications
const errorMessages = ref([])
const hasErrors = ref(false)
const emailFailures = ref(0)
const emailSuccesses = ref(0)

// Format date to ensure it's in YYYY-MM-DD format
const formatDate = (date) => {
    if (!date) return '';
    const dateObj = new Date(date);
    return dateObj.toISOString().split('T')[0];
}

// Calculate progress percentage
const progressPercentage = computed(() => {
    if (totalSteps.value === 0) return 0
    return Math.round((progressStep.value / totalSteps.value) * 100)
})

// Check if this is a public course
const isPublicCourse = computed(() => form.privacy === 'public')

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    status: 'pending',
    level: '',
    duration: '',
    privacy: 'public',
    image: null,
    availabilities: [
        {
            start_date: '',
            end_date: '',
            capacity: 25,
            sessions: 1,
            notes: ''
        }
    ]
})

function handleImageUpload(e) {
    const file = e.target.files[0]
    if (file) {
        form.image = file
        const reader = new FileReader()
        reader.onload = (e) => {
            imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

function removeImage() {
    form.image = null
    imagePreview.value = null
}

function addAvailability() {
    if (form.availabilities.length < 5) {
        form.availabilities.push({
            start_date: '',
            end_date: '',
            capacity: 25,
            sessions: 1,
            notes: ''
        })
    }
}

function removeAvailability(index) {
    if (form.availabilities.length > 1) {
        form.availabilities.splice(index, 1)
    }
}

// ✅ Clear error messages
function clearErrors() {
    errorMessages.value = []
    hasErrors.value = false
    emailFailures.value = 0
    emailSuccesses.value = 0
}

// ✅ Dismiss error notification
function dismissError(index) {
    errorMessages.value.splice(index, 1)
    if (errorMessages.value.length === 0) {
        hasErrors.value = false
    }
}

function submit() {
    // ✅ Only show progress modal for PUBLIC courses
    if (isPublicCourse.value) {
        isCreatingCourse.value = true
        progressStep.value = 0
        clearErrors() // ✅ Clear previous errors

        // Simulate progress steps for public course creation + email sending
        const progressInterval = setInterval(() => {
            if (progressStep.value < totalSteps.value) {
                progressStep.value++
            }
        }, 1500) // 1.5 seconds per step for public courses

        // Ensure dates are properly formatted before submission
        if (form.start_date) {
            form.start_date = formatDate(form.start_date);
        }
        if (form.end_date) {
            form.end_date = formatDate(form.end_date);
        }

        form.post('/admin/courses', {
            forceFormData: true,
            onSuccess: (response) => {
                clearInterval(progressInterval)
                progressStep.value = totalSteps.value

                // ✅ Check for email sending errors in response
                if (response.props?.flash?.emailErrors) {
                    hasErrors.value = true
                    errorMessages.value = response.props.flash.emailErrors
                    emailFailures.value = response.props.flash.emailFailures || 0
                    emailSuccesses.value = response.props.flash.emailSuccesses || 0
                }

                // Hide progress after longer time if there are errors
                setTimeout(() => {
                    isCreatingCourse.value = false
                }, hasErrors.value ? 5000 : 3000)
            },
            onError: (errors) => {
                clearInterval(progressInterval)
                isCreatingCourse.value = false

                // ✅ Handle validation errors
                hasErrors.value = true
                errorMessages.value = Object.values(errors).flat()
            },
            onFinish: () => {
                clearInterval(progressInterval)
            }
        })
    } else {
        // ✅ For private courses, submit normally without progress modal
        if (form.start_date) {
            form.start_date = formatDate(form.start_date);
        }
        if (form.end_date) {
            form.end_date = formatDate(form.end_date);
        }

        form.post('/admin/courses', {
            forceFormData: true
        })
    }
}

// Get progress message based on current step and course type
function getProgressMessage() {
    if (!isPublicCourse.value) return ''

    switch(progressStep.value) {
        case 0: return 'Preparing course data...'
        case 1: return 'Saving course information...'
        case 2: return 'Setting up session schedules...'
        case 3: return 'Sending announcement emails to all users...'
        case 4:
            if (hasErrors.value) {
                return `Course created with ${emailFailures.value} email failures!`
            }
            return 'Public course created and announced successfully!'
        default: return 'Processing...'
    }
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-4">Create New Course</h1>

            <!-- ✅ Error Notifications for Admin -->
            <div v-if="hasErrors && errorMessages.length > 0" class="fixed top-4 right-4 z-50 space-y-2 max-w-md">
                <Alert
                    v-for="(error, index) in errorMessages"
                    :key="index"
                    variant="destructive"
                    class="shadow-lg"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3">
                            <svg class="h-6 w-6 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h4 class="font-semibold text-sm">Email Sending Error</h4>
                                <AlertDescription class="mt-1">{{ error }}</AlertDescription>
                                <div v-if="emailFailures > 0" class="mt-2">
                                    <Badge variant="destructive" class="text-xs">
                                        {{ emailFailures }} failed, {{ emailSuccesses }} successful
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <Button
                            @click="dismissError(index)"
                            variant="ghost"
                            size="sm"
                            class="h-auto p-1 hover:bg-destructive/20"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </Button>
                    </div>
                </Alert>
            </div>

            <!-- ✅ Progress Circle Modal - ONLY shows for PUBLIC courses -->
            <div
                v-if="isCreatingCourse && isPublicCourse"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            >
                <Card class="max-w-md w-full mx-4 shadow-2xl">
                    <CardContent class="p-8">
                    <div class="text-center">
                        <!-- Progress Circle -->
                        <div class="relative w-32 h-32 mx-auto mb-6">
                            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background circle -->
                                <path
                                    class="text-muted"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <!-- Progress circle -->
                                <path
                                    :class="hasErrors ? 'text-orange-500' : 'text-green-500'"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${progressPercentage}, 100`"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="transition: stroke-dasharray 0.3s ease-in-out;"
                                />
                            </svg>
                            <!-- Percentage text -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-2xl font-bold text-foreground">{{ progressPercentage }}%</span>
                            </div>
                        </div>

                        <!-- Status Icon -->
                        <div class="mb-6">
                            <div class="flex justify-center">
                                <div class="relative">
                                    <div
                                        :class="hasErrors ? 'bg-orange-100' : 'bg-green-100'"
                                        class="w-12 h-12 rounded-full flex items-center justify-center"
                                    >
                                        <svg v-if="progressStep < totalSteps" class="animate-spin h-6 w-6"
                                             :class="hasErrors ? 'text-orange-500' : 'text-green-500'"
                                             fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else-if="hasErrors" class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg v-else class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Message -->
                        <h3 class="text-xl font-bold mb-3 text-foreground">
                            {{ progressStep === totalSteps
                            ? (hasErrors ? 'Course Created with Issues!' : 'Public Course Created!')
                            : 'Creating Public Course'
                            }}
                        </h3>

                        <p class="text-muted-foreground mb-4 leading-relaxed">
                            {{ getProgressMessage() }}
                        </p>

                        <!-- Progress details -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Progress</span>
                                <span>{{ progressStep }} / {{ totalSteps }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    :class="hasErrors ? 'bg-orange-500' : 'bg-green-500'"
                                    class="h-2 rounded-full transition-all duration-300 ease-out"
                                    :style="{ width: progressPercentage + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- ✅ Error Summary -->
                        <Alert v-if="hasErrors && progressStep === totalSteps" class="mb-4" variant="destructive">
                            <AlertDescription>
                                <strong>⚠️ Email Issues Detected:</strong><br>
                                {{ emailFailures }} emails failed to send, {{ emailSuccesses }} sent successfully.<br>
                                Check error notifications for details.
                            </AlertDescription>
                        </Alert>

                        <!-- Info note -->
                        <Alert v-else class="border-blue-200 bg-blue-50 mb-4">
                            <AlertDescription class="text-xs text-blue-700">
                                <strong>📧 Public Course:</strong> Announcement emails are being sent to all registered users
                            </AlertDescription>
                        </Alert>

                        <!-- Status note -->
                        <p class="text-xs text-muted-foreground mt-4">
                            {{ progressStep === totalSteps
                            ? (hasErrors
                                    ? 'Course created! Please review email sending errors above.'
                                    : 'Course created successfully with email notifications sent!'
                            )
                            : 'Please wait while we create your public course and notify users...'
                            }}
                        </p>
                    </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="shadow-lg max-w-4xl">
                <CardHeader>
                    <CardTitle class="text-2xl">Course Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                    <!-- Course Name -->
                    <div class="col-span-full ">
                        <Label for="name" class="mb-2">Course Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Enter course name"
                            :disabled="form.processing"
                            required
                        />
                        <div v-if="form.errors.name" class="text-destructive text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Course Image -->
                    <div class="col-span-full">
                        <Label class="mb-2">Course Image</Label>
                        <div class="mt-1 flex flex-col sm:flex-row items-start sm:items-center sm:space-x-4 space-y-3 sm:space-y-0">
                            <div v-if="imagePreview" class="h-32 w-full sm:w-48 overflow-hidden rounded-md">
                                <img :src="imagePreview" alt="Course image preview" class="h-full w-full object-cover" />
                            </div>
                            <div v-else class="h-32 w-full sm:w-48 flex items-center justify-center bg-gray-100 rounded-md">
                                <span class="text-gray-400">No image</span>
                            </div>

                            <div class="flex items-center">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="() => $refs.imageUpload.click()"
                                    :disabled="form.processing"
                                >
                                    Upload Image
                                </Button>
                                <input ref="imageUpload" type="file" class="hidden" @change="handleImageUpload" accept="image/*" :disabled="form.processing" />
                                <Button
                                    v-if="imagePreview"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeImage"
                                    :disabled="form.processing"
                                    class="ml-2 text-destructive hover:text-destructive/80"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                        <div v-if="form.errors.image" class="text-destructive text-sm mt-1">{{ form.errors.image }}</div>
                    </div>

                    <!-- Course Description -->
                    <div class="col-span-full">
                        <Label class="mb-2">Description</Label>
                        <Editor class="dark"
                            v-model="form.description"
                            :disabled="form.processing"
                            :api-key="'r1racrxd2joy9wp9xp9sj91ka9j4m3humenifqvwtx9s6i3y'"
                            :init="{
                toolbar_mode: 'sliding',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                mobile: {
                  menubar: false,
                  toolbar: 'undo redo | bold italic | link | bullist numlist',
                  toolbar_mode: 'scrolling'
                }
              }"
                        />
                        <div v-if="form.errors.description" class="text-destructive text-sm mt-1">{{ form.errors.description }}</div>
                    </div>

                    <!-- Start Date -->
                    <div class="col-span-1">
                        <Label class="mb-2">Overall Start Date</Label>
                        <Input
                            type="date"
                            v-model="form.start_date"
                            :disabled="form.processing"
                        />
                        <div v-if="form.errors.start_date" class="text-destructive text-sm mt-1">{{ form.errors.start_date }}</div>
                    </div>

                    <!-- End Date -->
                    <div class="col-span-1">
                        <Label class="mb-2">Overall End Date</Label>
                        <Input
                            type="date"
                            v-model="form.end_date"
                            :disabled="form.processing"
                        />
                        <div v-if="form.errors.end_date" class="text-destructive text-sm mt-1">{{ form.errors.end_date }}</div>
                    </div>

                    <!-- Status -->
                    <div class="col-span-1">
                        <Label class="mb-2">Status</Label>
                        <Select v-model="form.status" :disabled="form.processing">
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="in_progress">In Progress</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="form.errors.status" class="text-destructive text-sm mt-1">{{ form.errors.status }}</div>
                    </div>

                    <!-- ✅ Privacy Field - Key field that determines if modal shows -->
                    <div class="col-span-1">
                        <Label class="mb-2">Course Privacy</Label>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <input
                                    id="privacy-public"
                                    name="privacy"
                                    type="radio"
                                    value="public"
                                    v-model="form.privacy"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    :disabled="form.processing"
                                />
                                <Label for="privacy-public" class="ml-2 text-sm">
                                    🌍 Public
                                </Label>
                            </div>
                            <div class="flex items-center">
                                <input
                                    id="privacy-private"
                                    name="privacy"
                                    type="radio"
                                    value="private"
                                    v-model="form.privacy"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    :disabled="form.processing"
                                />
                                <Label for="privacy-private" class="ml-2 text-sm">
                                    🔒 Private
                                </Label>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Public courses are visible to everyone and trigger announcement emails. Private courses are only visible to enrolled users.
                        </p>

                        <!-- ✅ Show notification about public course behavior -->
                        <div v-if="isPublicCourse" class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
                            <strong>📧 Note:</strong> Creating a public course will automatically send announcement emails to all registered users.
                        </div>

                        <div v-if="form.errors.privacy" class="mt-1 text-sm text-destructive">
                            {{ form.errors.privacy }}
                        </div>
                    </div>

                    <!-- Level -->
                    <div class="col-span-1">
                        <Label class="mb-2">Level</Label>
                        <Select v-model="form.level" :disabled="form.processing">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Level" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="beginner">Beginner</SelectItem>
                                <SelectItem value="intermediate">Intermediate</SelectItem>
                                <SelectItem value="advanced">Advanced</SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="form.errors.level" class="text-destructive text-sm mt-1">{{ form.errors.level }}</div>
                    </div>

                    <!-- Duration (hours) -->
                    <div class="col-span-1">
                        <Label class="mb-2">Duration (hours)</Label>
                        <Input
                            type="number"
                            v-model="form.duration"
                            min="0.5"
                            step="0.5"
                            :disabled="form.processing"
                        />
                        <div v-if="form.errors.duration" class="text-destructive text-sm mt-1">{{ form.errors.duration }}</div>
                    </div>
                        </div>

                        <!-- Course Availabilities Section -->
                        <div class="mb-6 ">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Course Session Schedules</h2>
                        <Button
                            type="button"
                            @click="addAvailability"
                            :disabled="form.availabilities.length >= 5 || form.processing"
                        >
                            Add Session Schedule ({{ form.availabilities.length }}/5)
                        </Button>
                    </div>

                    <div class="space-y-4 ">
                        <div
                            v-for="(availability, index) in form.availabilities"
                            :key="index"
                            class="border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800"
                        >
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">Session Schedule {{ index + 1 }}</h3>
                                <Button
                                    v-if="form.availabilities.length > 1"
                                    type="button"
                                    @click="removeAvailability(index)"
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-600 hover:text-red-800"
                                    :disabled="form.processing"
                                >
                                    Remove
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Start Date -->
                                <div>
                                    <label class="block font-medium mb-1">Start Date</label>
                                    <Input
                                        type="datetime-local"
                                        v-model="availability.start_date"
                                        :disabled="form.processing"
                                        required
                                    />
                                    <div v-if="form.errors[`availabilities.${index}.start_date`]" class="text-destructive text-sm mt-1">
                                        {{ form.errors[`availabilities.${index}.start_date`] }}
                                    </div>
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label class="block font-medium mb-1">End Date</label>
                                    <Input
                                        type="datetime-local"
                                        v-model="availability.end_date"
                                        :disabled="form.processing"
                                        required
                                    />
                                    <div v-if="form.errors[`availabilities.${index}.end_date`]" class="text-destructive text-sm mt-1">
                                        {{ form.errors[`availabilities.${index}.end_date`] }}
                                    </div>
                                </div>

                                <!-- Seats Available (renamed from capacity) -->
                                <div>
                                    <label class="block font-medium mb-1">Seats Available</label>
                                    <Input
                                        type="number"
                                        v-model="availability.capacity"
                                        min="1"
                                        max="1000"
                                        :disabled="form.processing"
                                        required
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">Maximum number of seats available for this schedule</p>
                                    <div v-if="form.errors[`availabilities.${index}.capacity`]" class="text-destructive text-sm mt-1">
                                        {{ form.errors[`availabilities.${index}.capacity`] }}
                                    </div>
                                </div>

                                <!-- Sessions (new separate field) -->
                                <div>
                                    <label class="block font-medium mb-1">Sessions</label>
                                    <Input
                                        type="number"
                                        v-model="availability.sessions"
                                        min="1"
                                        max="100"
                                        :disabled="form.processing"
                                        required
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">Number of sessions in this schedule</p>
                                    <div v-if="form.errors[`availabilities.${index}.sessions`]" class="text-destructive text-sm mt-1">
                                        {{ form.errors[`availabilities.${index}.sessions`] }}
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="md:col-span-2">
                                    <label class="block font-medium mb-1">Notes (Optional)</label>
                                    <Input
                                        type="text"
                                        v-model="availability.notes"
                                        placeholder="Special instructions or requirements"
                                        :disabled="form.processing"
                                    />
                                    <div v-if="form.errors[`availabilities.${index}.notes`]" class="text-destructive text-sm mt-1">
                                        {{ form.errors[`availabilities.${index}.notes`] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                            <div v-if="form.errors.availabilities" class="text-destructive text-sm mt-2">
                                {{ form.errors.availabilities }}
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 mb-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                :class="{
                                    'bg-green-500 hover:bg-green-600': isPublicCourse,
                                    'bg-blue-500 hover:bg-blue-600': !isPublicCourse
                                }"
                                class="w-full sm:w-auto flex items-center justify-center gap-2"
                            >
                                <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="form.processing">
                                    {{ isPublicCourse ? 'Creating Public Course & Sending Emails...' : 'Creating Course...' }}
                                </span>
                                <span v-else>
                                    {{ isPublicCourse ? '🌍 Create Public Course' : '🔒 Create Private Course' }}
                                </span>
                            </Button>
                            <Button
                                variant="secondary"
                                as-child
                                class="w-full sm:w-auto"
                                :disabled="form.processing"
                            >
                                <Link href="/admin/courses">
                                    Cancel
                                </Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
