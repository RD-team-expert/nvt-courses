<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    courses: Array,
    users: Array
})

const form = useForm({
    course_id: new URLSearchParams(window.location.search).get('course_id') || '',
    user_ids: [],
    course_availability_id: null
})

// Email progress tracking
const emailProgress = ref({
    isVisible: false,
    current: 0,
    total: 0,
    status: 'sending', // 'sending', 'success', 'error'
    message: ''
})

// Get availabilities for selected course
const selectedCourse = computed(() => {
    return props.courses.find(course => course.id == form.course_id)
})

// Calculate progress percentage
const progressPercentage = computed(() => {
    if (emailProgress.value.total === 0) return 0
    return Math.round((emailProgress.value.current / emailProgress.value.total) * 100)
})

function submit() {
    // Show progress indicator
    emailProgress.value = {
        isVisible: true,
        current: 0,
        total: form.user_ids.length,
        status: 'sending',
        message: 'Preparing to send emails...'
    }

    // Start simulating progress
    const progressInterval = setInterval(() => {
        if (emailProgress.value.current < emailProgress.value.total && emailProgress.value.status === 'sending') {
            emailProgress.value.current++
            emailProgress.value.message = `Sending email ${emailProgress.value.current} of ${emailProgress.value.total}...`
        }
    }, 3000) // 3 seconds per email (matching your sleep(3))

    form.post('/admin/assignments', {
        onSuccess: (response) => {
            clearInterval(progressInterval)
            emailProgress.value.status = 'success'
            emailProgress.value.message = 'All course assignments sent successfully!'
            emailProgress.value.current = emailProgress.value.total

            // Auto-hide after 4 seconds
            setTimeout(() => {
                emailProgress.value.isVisible = false
            }, 4000)
        },
        onError: (errors) => {
            clearInterval(progressInterval)
            emailProgress.value.status = 'error'
            emailProgress.value.message = 'Some assignments failed to send. Please check the logs.'

            // Don't auto-hide on error, let user close manually
        },
        onFinish: () => {
            clearInterval(progressInterval)
        }
    })
}

// Close progress modal
function closeProgressModal() {
    emailProgress.value.isVisible = false
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-xl sm:text-3xl font-bold text-gray-800 mb-2">Assign Course to Users</h1>
                <p class="text-gray-600">Select a course and users to send course assignment notifications with login credentials.</p>
            </div>

            <!-- Email Progress Modal/Overlay -->
            <div
                v-if="emailProgress.isVisible"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                @click="emailProgress.status !== 'sending' ? closeProgressModal() : null"
            >
                <div
                    class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl"
                    @click.stop
                >
                    <div class="text-center">
                        <!-- Progress Circle -->
                        <div class="relative w-32 h-32 mx-auto mb-6">
                            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background circle -->
                                <path
                                    class="text-gray-200"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <!-- Progress circle -->
                                <path
                                    :class="{
                                        'text-blue-500': emailProgress.status === 'sending',
                                        'text-green-500': emailProgress.status === 'success',
                                        'text-red-500': emailProgress.status === 'error'
                                    }"
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
                                <span class="text-2xl font-bold text-gray-700">{{ progressPercentage }}%</span>
                            </div>
                        </div>

                        <!-- Status Icon -->
                        <div class="mb-6">
                            <!-- Sending spinner -->
                            <div v-if="emailProgress.status === 'sending'" class="flex justify-center">
                                <div class="relative">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <!-- Success checkmark -->
                            <div v-else-if="emailProgress.status === 'success'" class="flex justify-center">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center animate-pulse">
                                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <!-- Error X -->
                            <div v-else-if="emailProgress.status === 'error'" class="flex justify-center">
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Status Message -->
                        <h3 class="text-xl font-bold mb-3 text-gray-800">
                            <span v-if="emailProgress.status === 'sending'">Sending Course Assignments</span>
                            <span v-else-if="emailProgress.status === 'success'">Assignment Complete!</span>
                            <span v-else-if="emailProgress.status === 'error'">Assignment Failed</span>
                        </h3>

                        <p class="text-gray-600 mb-4 leading-relaxed">{{ emailProgress.message }}</p>

                        <!-- Progress details -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Progress</span>
                                <span>{{ emailProgress.current }} / {{ emailProgress.total }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="h-2 rounded-full transition-all duration-300 ease-out"
                                    :class="{
                                        'bg-blue-500': emailProgress.status === 'sending',
                                        'bg-green-500': emailProgress.status === 'success',
                                        'bg-red-500': emailProgress.status === 'error'
                                    }"
                                    :style="{ width: progressPercentage + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex gap-3 justify-center">
                            <!-- Close button (only show when complete or error) -->
                            <button
                                v-if="emailProgress.status !== 'sending'"
                                @click="closeProgressModal"
                                class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium"
                            >
                                Close
                            </button>

                            <!-- View assignments button on success -->
                            <Link
                                v-if="emailProgress.status === 'success'"
                                href="/admin/assignments"
                                class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-200 font-medium"
                                @click="closeProgressModal"
                            >
                                View Assignments
                            </Link>
                        </div>

                        <!-- Cancel note for sending state -->
                        <p v-if="emailProgress.status === 'sending'" class="text-xs text-gray-500 mt-4">
                            Please wait while we send the assignment emails...
                        </p>
                    </div>
                </div>
            </div>

            <!-- Assignment Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 sm:p-8">
                    <form @submit.prevent="submit" class="max-w-4xl">
                        <!-- Course Selection -->
                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Select Course *</label>
                            <select
                                v-model="form.course_id"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                required
                                :disabled="form.processing"
                            >
                                <option value="">Choose a course to assign...</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.course_id" class="text-red-600 text-sm mt-2">{{ form.errors.course_id }}</div>
                        </div>

                        <!-- Session Schedule Selection (if course has availabilities) -->


                        <!-- User Selection -->
                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Select Users *</label>
                            <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
                                    <label
                                        v-for="user in users"
                                        :key="user.id"
                                        class="flex items-center space-x-3 p-3 bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors cursor-pointer"
                                        :class="{ 'border-blue-500 bg-blue-50': form.user_ids.includes(user.id) }"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="user.id"
                                            v-model="form.user_ids"
                                            :disabled="form.processing"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        >
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-gray-900 block truncate">{{ user.name }}</span>
                                            <span class="text-xs text-gray-500 block truncate">{{ user.email }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div v-if="form.errors.user_ids" class="text-red-600 text-sm mt-2">{{ form.errors.user_ids }}</div>
                            <div class="flex justify-between items-center mt-3">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">{{ form.user_ids.length }}</span> user{{ form.user_ids.length !== 1 ? 's' : '' }} selected
                                </p>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="form.user_ids = users.map(u => u.id)"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                        :disabled="form.processing"
                                    >
                                        Select All
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button
                                        type="button"
                                        @click="form.user_ids = []"
                                        class="text-xs text-gray-600 hover:text-gray-800 font-medium"
                                        :disabled="form.processing"
                                    >
                                        Clear All
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Info Notice -->
                        <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Assignment Information</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Selected users will receive an email with course details and login credentials</li>
                                            <li>New passwords will be automatically generated and sent to each user</li>
                                            <li>Users can accept or decline the course assignment from their email</li>
                                            <li>Email sending may take a few moments due to rate limiting</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                            <button
                                type="submit"
                                class="flex-1 sm:flex-none bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors duration-200 font-medium flex items-center justify-center gap-3"
                                :disabled="form.processing || form.user_ids.length === 0"
                            >
                                <!-- Loading spinner -->
                                <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <!-- Button text -->
                                <span v-if="form.processing">Sending Assignments...</span>
                                <span v-else-if="form.user_ids.length === 0">Select Users to Continue</span>
                                <span v-else>
                                    Assign Course to {{ form.user_ids.length }} User{{ form.user_ids.length !== 1 ? 's' : '' }}
                                </span>

                                <!-- Send icon -->
                                <svg v-if="!form.processing && form.user_ids.length > 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>

                            <Link
                                href="/admin/assignments"
                                class="flex-1 sm:flex-none bg-gray-100 text-gray-700 px-8 py-3 rounded-lg hover:bg-gray-200 focus:outline-hidden focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200 font-medium text-center flex items-center justify-center gap-2"
                                :class="{ 'pointer-events-none opacity-50': form.processing }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to Assignments
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom scrollbar for user selection */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
