<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed, ref, watch } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    course: Object,
    isEnrolled: Boolean,
    userStatus: String,
    selectedAvailability: Object,
    availabilities: Array,
    userAssignment: Object,
    completion: Object,
})

// Reactive key for forcing re-render
const componentKey = ref(0)

// Watch for prop changes to trigger re-render
watch(() => props.completion, (newVal, oldVal) => {
    console.log('Completion changed:', oldVal, '->', newVal);
    componentKey.value += 1;
}, { deep: true })

watch(() => props.userStatus, (newVal, oldVal) => {
    console.log('UserStatus changed:', oldVal, '->', newVal);
    componentKey.value += 1;
})

const ratingForm = useForm({
    rating: 0,
    feedback: ''
})

// Check if user has already rated
const hasRated = computed(() => {
    const result = props.completion && props.completion.rating !== null;
    console.log('hasRated computed:', result, 'completion:', props.completion);
    return result;
})

// Check if user can mark complete (must be enrolled AND rated)
const canMarkComplete = computed(() => {
    return props.isEnrolled &&
        (props.userStatus === 'pending' || props.userStatus === 'in_progress') &&
        hasRated.value
})

// Submit rating function
function submitRating() {
    console.log('=== VUE SUBMIT RATING START ===');
    console.log('Rating form data:', ratingForm.data());

    ratingForm.post(route('courses.submitRating', props.course.id), {
        onSuccess: (page) => {
            console.log('✅ Rating submitted successfully');
            router.reload({
                only: ['completion', 'userStatus', 'isEnrolled']
            });
        },
        onError: (errors) => {
            console.error('❌ Rating submission failed:', errors);
        }
    });
}

// Create a form for enrollment
const enrollForm = useForm({
    course_availability_id: null
})

// Function to handle enrollment
function enroll() {
    console.log('=== ENROLLMENT DEBUG START ===');

    if (props.availabilities && props.availabilities.length > 0 && !enrollForm.course_availability_id) {
        alert('Please select a date range before enrolling.');
        return;
    }

    enrollForm.post(route('courses.enroll', props.course.id), {
        preserveState: true,
        onSuccess: (page) => {
            console.log('✅ Enrollment SUCCESS!');
            router.reload({ only: ['isEnrolled', 'userStatus', 'selectedAvailability'] });
        },
        onError: (errors) => {
            console.error('❌ Enrollment FAILED!', errors);
        }
    });
}

// Function to mark course as completed
const completeForm = useForm({})
function markCompleted() {
    console.log('=== VUE MARK COMPLETED START ===');

    completeForm.post(route('courses.markCompleted', props.course.id), {
        onSuccess: (page) => {
            console.log('✅ Course marked as completed');
            router.reload({ only: ['userStatus'] });
        },
        onError: (errors) => {
            console.error('❌ Failed to mark course as completed:', errors);
        }
    });
}

// ✅ Format capacity (total sessions)
const formatCapacity = (capacity) => {
    if (!capacity) return 'Capacity not specified';
    if (capacity === 1) {
        return '1 Total Session';
    }
    return `${capacity} seats available`;
}

// ✅ Format available sessions
const formatSessions = (sessions) => {
    if (sessions === undefined || sessions === null) return 'Sessions not available';
    if (sessions === 0) return 'No sessions available';
    if (sessions === 1) {
        return '1 Session Available';
    }
    return `${sessions} Total Sessions`;
}

// ✅ Availability status methods based on sessions
const getAvailabilityStatusClass = (availability) => {
    const sessions = availability?.sessions || 0;

    if (sessions <= 0) {
        return 'bg-red-100 text-red-800';
    } else if (sessions <= 2) {
        return 'bg-yellow-100 text-yellow-800';
    } else {
        return 'bg-green-100 text-green-800';
    }
};

const getAvailabilityStatusText = (availability) => {
    const sessions = availability?.sessions || 0;

    if (sessions <= 0) {
        return 'Fully Booked';
    } else if (sessions === 1) {
        return '1 Session Available';
    } else {
        return `${sessions} Sessions Available`;
    }
};

// Format status for display
const formatStatus = (status) => {
    const statusMap = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    }
    return statusMap[status] || status
}

const formatDateTime = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleDateString() + ' at ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleDateString()
}

// ✅ Check if availability is disabled based on sessions
const isAvailabilityDisabled = (availability) => {
    const sessions = availability?.sessions || 0;
    return sessions <= 0 || !availability?.is_available || (props.userAssignment && props.userAssignment.status === 'pending');
}

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Courses', href: route('courses.index') },
    { name: props.course.name, href: route('courses.show', props.course.id) }
]
</script>

<style>
.course-image-container {
    position: relative;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
    overflow: hidden;
    background-color: #f3f4f6;
}

.course-image-container img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: fill;
    transition: transform 0.5s ease;
}

.course-image-container:hover img {
    transform: scale(1.05);
}

.enrollment-card {
    transition: all 0.3s ease;
}

.enrollment-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.button-primary {
    @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 disabled:bg-gray-400 disabled:cursor-not-allowed;
}

.button-success {
    @apply bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200;
}

.button-warning {
    @apply bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200;
}

.session-info-card {
    @apply bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm;
}

.session-status {
    @apply inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full;
}

.session-details {
    @apply text-xs text-gray-600 space-y-1;
}

@media (max-width: 768px) {
    .course-details-grid {
        grid-template-columns: 1fr;
    }
    .course-image-container {
        padding-top: 75%; /* 4:3 Aspect Ratio for mobile */
    }
}
</style>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container px-4 mx-auto py-6 sm:py-8 max-w-7xl">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ course.name }}</h1>
                <span
                    class="px-3 py-1.5 text-sm rounded-full shadow-sm transition-colors duration-200"
                    :class="{
                        'bg-green-100 text-green-800': course.status === 'in_progress',
                        'bg-yellow-100 text-yellow-800': course.status === 'pending',
                        'bg-blue-100 text-blue-800': course.status === 'completed'
                    }"
                >
                    {{ formatStatus(course.status) }}
                </span>
            </div>

            <!-- Course Assignment Notification -->
            <div v-if="userAssignment" class="mb-6">
                <div
                    class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 shadow-sm"
                    :class="{
                        'from-yellow-50 to-orange-50 border-yellow-200': userAssignment.status === 'pending',
                        'from-green-50 to-emerald-50 border-green-200': userAssignment.status === 'accepted',
                        'from-red-50 to-pink-50 border-red-200': userAssignment.status === 'declined',
                    }"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 mt-0.5"
                                    :class="{
                                        'text-yellow-500': userAssignment.status === 'pending',
                                        'text-green-500': userAssignment.status === 'accepted',
                                        'text-blue-500': userAssignment.status !== 'pending' && userAssignment.status !== 'accepted',
                                        'text-red-500': userAssignment.status === 'declined'
                                    }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium">
                                    <span v-if="userAssignment.status === 'pending'">📋 Course Assignment - Action Required</span>
                                    <span v-else-if="userAssignment.status === 'accepted'">✅ Course Assignment - Accepted</span>
                                    <span v-else-if="userAssignment.status === 'declined'">❌ Course Assignment - Declined</span>
                                    <span v-else-if="userAssignment.status === 'completed'">🎉 Course Assignment - Completed</span>
                                </h3>

                                <div class="mt-1 text-sm text-gray-600">
                                    <p v-if="userAssignment.assigned_by">
                                        <span class="font-medium">Assigned by:</span> {{ userAssignment.assigned_by }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Status Badge -->
                        <span
                            v-if="userAssignment.status"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="{
                                'bg-yellow-100 text-yellow-800': userAssignment.status === 'pending',
                                'bg-green-100 text-green-800': userAssignment.status === 'accepted',
                                'bg-red-100 text-red-800': userAssignment.status === 'declined',
                                'bg-blue-100 text-blue-800': userAssignment.status === 'completed'
                            }"
                        >
                            {{ userAssignment.status.charAt(0).toUpperCase() + userAssignment.status.slice(1) }}
                        </span>
                    </div>

                    <!-- Assignment Action Buttons -->
                    <div v-if="userAssignment.status === 'pending'" class="mt-4 flex gap-3">
                        <Link
                            :href="route('assignments.accept', userAssignment.id)"
                            method="post"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors duration-200"
                        >
                            Accept Assignment
                        </Link>
                        <Link
                            :href="route('assignments.decline', userAssignment.id)"
                            method="post"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200"
                        >
                            Decline Assignment
                        </Link>
                    </div>
                </div>
            </div>

            <div class="course-details-grid grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- Course Details -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden transition-shadow duration-200 hover:shadow-md">
                    <!-- Course Image -->
                    <div class="course-image-container">
                        <img
                            v-if="course.image_path"
                            :src="`/storage/${course.image_path}`"
                            :alt="course.name"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        >
                        <div
                            v-else
                            class="absolute inset-0 flex items-center justify-center bg-gray-100"
                        >
                            <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <!-- Course description with proper HTML rendering -->
                        <div class="prose max-w-none mb-8" v-html="course.description"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                <p class="text-gray-500 mb-1.5 font-medium">Start Date</p>
                                <p class="font-semibold text-gray-900">{{ formatDate(course.start_date) }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                <p class="text-gray-500 mb-1.5 font-medium">End Date</p>
                                <p class="font-semibold text-gray-900">{{ formatDate(course.end_date) }}</p>
                            </div>
                            <div v-if="course.level" class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                <p class="text-gray-500 mb-1.5 font-medium">Level</p>
                                <p class="font-semibold text-gray-900 capitalize">{{ course.level }}</p>
                            </div>
                            <div v-if="course.duration" class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                <p class="text-gray-500 mb-1.5 font-medium">Duration</p>
                                <p class="font-semibold text-gray-900">{{ course.duration }} hours</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Card -->
                <div class="enrollment-card bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <h2 class="text-xl sm:text-2xl font-semibold mb-6 text-gray-800">Course Enrollment</h2>

                        <!-- Not Enrolled State -->
                        <div v-if="!isEnrolled" class="mb-6">
                            <!-- Assignment-based enrollment message -->
                            <div v-if="userAssignment && userAssignment.status === 'accepted'">
                                <p class="text-gray-600 mb-6 leading-relaxed">
                                    This course has been assigned to you. Select a session schedule to complete your enrollment.
                                </p>
                            </div>

                            <!-- Pending assignment message -->
                            <div v-else-if="userAssignment && userAssignment.status === 'pending'">
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                    <p class="text-yellow-800 font-medium mb-2">Assignment Pending</p>
                                    <p class="text-yellow-700 text-sm">
                                        Please accept your course assignment above to proceed with enrollment.
                                    </p>
                                </div>
                            </div>

                            <!-- Declined assignment message -->
                            <div v-else-if="userAssignment && userAssignment.status === 'declined'">
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                                    <p class="text-red-800 font-medium mb-2">Assignment Declined</p>
                                    <p class="text-red-700 text-sm">
                                        You have declined this course assignment. Contact your administrator if you'd like to reconsider.
                                    </p>
                                </div>
                            </div>

                            <!-- Regular enrollment message -->
                            <div v-else>
                                <p class="text-gray-600 mb-6 leading-relaxed">
                                    Select a session schedule and enroll in this course to track your progress.
                                </p>
                            </div>

                            <!-- ✅ Available Session Schedules with BOTH Capacity and Sessions -->
                            <div v-if="availabilities && availabilities.length > 0" class="mb-6" :class="{ 'opacity-50': userAssignment && userAssignment.status === 'pending' }">
                                <h3 class="font-semibold mb-4 text-gray-800">Available Session Schedules:</h3>

                                <div class="space-y-3" :class="{ 'pointer-events-none': userAssignment && userAssignment.status === 'pending' }">
                                    <label
                                        v-for="availability in availabilities"
                                        :key="availability.id"
                                        class="block cursor-pointer"
                                    >
                                        <div
                                            class="border rounded-lg p-4 transition-colors"
                                            :class="{
                                                'border-blue-500 bg-blue-50': enrollForm.course_availability_id === availability.id,
                                                'border-gray-300 hover:border-gray-400': enrollForm.course_availability_id !== availability.id && !isAvailabilityDisabled(availability),
                                                'border-gray-200 bg-gray-50 cursor-not-allowed': isAvailabilityDisabled(availability)
                                            }"
                                        >
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-start">
                                                    <input
                                                        type="radio"
                                                        :value="availability.id"
                                                        v-model="enrollForm.course_availability_id"
                                                        :disabled="isAvailabilityDisabled(availability)"
                                                        class="mt-1 mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                                    >
                                                    <div>
                                                        <p class="font-medium text-gray-900">
                                                            {{ availability.formatted_date_range }}
                                                        </p>
                                                        <p class="text-sm text-gray-600 mt-1">
                                                            {{ formatDateTime(availability.start_date) }} - {{ formatDateTime(availability.end_date) }}
                                                        </p>
                                                        <p v-if="availability.notes" class="text-sm text-gray-500 mt-1">
                                                            {{ availability.notes }}
                                                        </p>

                                                        <!-- ✅ Show BOTH capacity and sessions -->
                                                        <div class="session-details mt-2 space-y-1">
                                                            <p class="text-blue-600 font-medium">
                                                                {{ formatCapacity(availability.capacity) }}
                                                            </p>
                                                            <p class="text-green-600 font-medium">
                                                                {{ formatSessions(availability.sessions) }}
                                                            </p>
                                                            <p v-if="(availability.capacity - availability.sessions) > 0" class="text-sm text-gray-600">
                                                                {{ (availability.capacity - availability.sessions) }} sessions enrolled
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- ✅ Availability status based on sessions -->
                                                <div class="text-right ml-4">
                                                    <span
                                                        class="session-status"
                                                        :class="getAvailabilityStatusClass(availability)"
                                                    >
                                                        {{ getAvailabilityStatusText(availability) }}
                                                    </span>

                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Simple enrollment for courses without availabilities -->
                            <div v-else-if="!availabilities || availabilities.length === 0">
                                <p class="text-gray-600 mb-6 leading-relaxed">
                                    Enroll in this course to track your progress and get access to course materials.
                                </p>
                            </div>

                            <button
                                @click="enroll"
                                class="button-primary w-full flex items-center justify-center gap-2"
                                :disabled="enrollForm.processing || (availabilities && availabilities.length > 0 && !enrollForm.course_availability_id) || (userAssignment && userAssignment.status === 'pending')"
                            >
                                <span v-if="enrollForm.processing">Enrolling...</span>
                                <span v-else-if="userAssignment && userAssignment.status === 'pending'">Accept Assignment First</span>
                                <span v-else-if="userAssignment && userAssignment.status === 'declined'">Assignment Declined</span>
                                <span v-else>Enroll Now</span>
                                <svg v-if="!enrollForm.processing && (!userAssignment || userAssignment.status === 'accepted')" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </button>
                        </div>

                        <!-- Enrolled State -->
                        <div v-else class="space-y-6" :key="componentKey">
                            <div class="space-y-4">
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg transition-colors duration-200">
                                    <div class="w-3 h-3 rounded-full mr-3" :class="{
                                        'bg-green-500': userStatus === 'completed',
                                        'bg-blue-500': userStatus === 'pending' || userStatus === 'in_progress'
                                    }"></div>
                                    <span class="font-medium text-gray-800">
                                        Status: {{ userStatus === 'completed' ? 'Completed' : 'Enrolled' }}
                                    </span>
                                </div>

                                <!-- Step 1: Rating Section (Show if enrolled but not rated) -->
                                <div v-if="(userStatus === 'pending' || userStatus === 'in_progress') && !hasRated" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <h3 class="font-semibold text-yellow-800 mb-3">📋 Rate This Course First</h3>
                                    <p class="text-yellow-700 text-sm mb-4">Please rate this course before marking it as complete.</p>

                                    <!-- Star Rating -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating</label>
                                        <div class="flex space-x-1">
                                            <button
                                                v-for="star in 5"
                                                :key="star"
                                                type="button"
                                                @click="ratingForm.rating = star"
                                                class="text-2xl transition-colors duration-200"
                                                :class="{
                                                    'text-yellow-400': star <= ratingForm.rating,
                                                    'text-gray-300 hover:text-yellow-400': star > ratingForm.rating
                                                }"
                                            >
                                                ★
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Feedback (Required) -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Feedback <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="ratingForm.feedback"
                                            rows="3"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :class="{ 'border-red-500': ratingForm.errors.feedback }"
                                            placeholder="Please share your thoughts about this course... (required)"
                                            required
                                        ></textarea>
                                        <div v-if="ratingForm.errors.feedback" class="text-red-500 text-sm mt-1">
                                            {{ ratingForm.errors.feedback }}
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
                                    </div>

                                    <!-- Submit Rating Button -->
                                    <button
                                        @click="submitRating"
                                        :disabled="ratingForm.processing || ratingForm.rating === 0 || !ratingForm.feedback?.trim() || ratingForm.feedback.trim().length < 10"
                                        class="bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg transition-colors duration-200 w-full"
                                    >
                                        <span v-if="ratingForm.processing">Submitting Rating...</span>
                                        <span v-else>Submit Rating</span>
                                    </button>
                                </div>

                                <!-- Step 2: Mark Complete Section (Show if rated but not completed) -->
                                <div v-else-if="(userStatus === 'pending' || userStatus === 'in_progress') && hasRated" class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h3 class="font-semibold text-green-800 mb-3">✅ Ready to Complete</h3>
                                    <p class="text-green-700 text-sm mb-4">
                                        Thank you for rating! You can now mark this course as completed.
                                    </p>
                                    <p v-if="completion && completion.rating" class="text-xs text-green-600 mb-4">
                                        Your rating:
                                        <span class="text-yellow-500">
                                            {{ '★'.repeat(completion.rating) }}{{ '☆'.repeat(5 - completion.rating) }}
                                        </span>
                                    </p>

                                    <button
                                        @click="markCompleted"
                                        :disabled="completeForm.processing"
                                        class="button-success w-full flex items-center justify-center gap-2"
                                    >
                                        <span v-if="completeForm.processing">Processing...</span>
                                        <span v-else>Mark as Completed</span>
                                        <svg v-if="!completeForm.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Step 3: Completed Section -->
                                <div v-else-if="userStatus === 'completed'" class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h3 class="font-semibold text-green-800 mb-3">🎉 Course Completed!</h3>
                                    <p class="text-green-700 font-medium">
                                        Congratulations! You've successfully completed all sessions in this course.
                                    </p>

                                    <div class="mt-4 space-y-4">
                                        <Link
                                            href="/courses"
                                            class="button-primary w-full flex items-center justify-center gap-2"
                                        >
                                            Browse More Courses
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>

                                <!-- ✅ Show selected session schedule with BOTH capacity and sessions -->
                                <div v-if="selectedAvailability" class="session-info-card">
                                    <p class="text-sm text-blue-600 font-medium mb-1">Your Selected Session Schedule:</p>
                                    <p class="font-semibold text-blue-900">{{ selectedAvailability.formatted_date_range }}</p>
                                    <p class="text-sm text-blue-700 mt-1">
                                        {{ formatDateTime(selectedAvailability.start_date) }} - {{ formatDateTime(selectedAvailability.end_date) }}
                                    </p>

                                    <!-- ✅ Display BOTH capacity and sessions -->
                                    <div class="mt-2 space-y-1">
                                        <p class="text-xs text-blue-600">
                                            Total Capacity: {{ selectedAvailability.capacity }} sessions
                                        </p>
                                        <p class="text-xs text-blue-500">
                                            Available: {{ selectedAvailability.sessions }} sessions
                                        </p>
                                        <p class="text-xs text-gray-600">
                                            Enrolled: {{ (selectedAvailability.capacity - selectedAvailability.sessions) }} sessions
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
