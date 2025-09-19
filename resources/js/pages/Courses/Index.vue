<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination2.vue';
import { type BreadcrumbItemType } from '@/types';

// Log the entire courses prop to inspect the data
const props = defineProps({
    courses: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            meta: {},
        }),
    },
});

// Debug: Log the courses prop and links
console.log('Courses prop:', props.courses);
console.log('Pagination links:', props.courses?.links || 'No links provided');

// Format status for display
const formatStatus = (status) => {
    const statusMap = {
        pending: 'Pending',
        in_progress: 'In Progress',
        completed: 'Completed',
    };
    return statusMap[status] || status;
};

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString();
};

// Format sessions display
const formatSessions = (totalSessions, enrolledSessions = 0) => {
    if (totalSessions === 1) {
        return '1 Session';
    }
    return `${totalSessions} seats`;
};

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Courses', href: route('courses.index') },
];
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-grid {
    @apply grid gap-6;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.course-card {
    @apply bg-white rounded-xl shadow-sm hover:shadow-md border border-gray-100 flex flex-col h-full overflow-hidden;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.course-card:hover {
    transform: translateY(-2px);
}

.course-image-container {
    @apply relative bg-gray-50 overflow-hidden;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
}

.course-image {
    @apply absolute top-0 left-0 w-full h-full object-center;
    transition: transform 0.5s ease;
}

.course-image:hover {
    transform: scale(1.05);
}

.course-content {
    @apply p-4 sm:p-5 flex flex-col flex-grow;
}

.course-title {
    @apply text-lg font-semibold mb-3 text-gray-800 line-clamp-2 leading-snug;
}

.course-info {
    @apply text-sm text-gray-600 space-y-2 mb-4 flex-grow;
}

.course-status {
    @apply px-2.5 py-1 text-xs font-semibold rounded-full shadow-sm backdrop-blur-sm;
}

.course-button {
    @apply mt-auto w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg transition-colors text-center font-medium flex items-center justify-center gap-2;
}

.pagination-wrapper {
    @apply mt-8 flex justify-center items-center;
}

.session-info {
    @apply flex items-center justify-between text-xs px-2 py-1 bg-blue-50 text-blue-700 rounded-md;
}

@media (max-width: 640px) {
    .course-grid {
        @apply gap-4;
    }
    .course-card {
        @apply mb-4;
    }
    .course-content {
        @apply p-4;
    }
}
</style>

<template>
    <Head title="Courses" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:py-8 max-w-7xl">
            <div class="flex flex-col space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 tracking-tight">Available Courses</h1>
                        <p v-if="courses.data && courses.data.length > 0" class="text-sm text-gray-600 mt-1">
                            Showing {{ courses.meta?.from || courses.from }} to {{ courses.meta?.to || courses.to }} of {{ courses.meta?.total || courses.total }} courses
                        </p>
                    </div>
                </div>

                <div class="course-grid">
                    <div
                        v-for="course in courses.data"
                        :key="course.id"
                        class="course-card"
                    >
                        <div class="course-image-container">
                            <img
                                v-if="course.image_path"
                                :src="`/storage/${course.image_path}`"
                                :alt="course.name"
                                class="course-image"
                                loading="lazy"
                            >
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100"
                            >
                                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span
                                class="course-status absolute top-3 right-3"
                                :class="{
                                    'bg-green-100/90 text-green-800': course.status === 'in_progress',
                                    'bg-yellow-100/90 text-yellow-800': course.status === 'pending',
                                    'bg-blue-100/90 text-blue-800': course.status === 'completed'
                                }"
                            >
                                {{ formatStatus(course.status) }}
                            </span>
                        </div>
                        <div class="course-content">
                            <h2 class="course-title">{{ course.name }}</h2>
                            <div class="course-info">
                                <div class="flex items-center">
                                    <span class="font-medium mr-2 w-14">Start:</span>
                                    <span>{{ formatDate(course.start_date) }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-medium mr-2 w-14">End:</span>
                                    <span>{{ formatDate(course.end_date) }}</span>
                                </div>
                                <div v-if="course.level" class="flex items-center">
                                    <span class="font-medium mr-2 w-14">Level:</span>
                                    <span class="capitalize px-2 py-0.5 bg-gray-100 rounded text-xs">{{ course.level }}</span>
                                </div>
                                <!-- Changed from Duration to Sessions -->
                                <div v-if="course.duration" class="flex items-center">
                                    <span class="font-medium mr-2 w-14">Duration:</span>
                                    <span>{{ course.duration }} hours</span>
                                </div>
                                <!-- New Sessions info based on total_capacity -->
                                <div v-if="course.total_capacity" class="flex items-center">
                                    <span class="font-medium mr-2 w-15"> availability: </span>
                                    <span>{{ formatSessions(course.total_capacity) }} </span>
                                </div>
                            </div>


                            <!-- User enrollment status -->
                            <div v-if="course.user_status" class="mb-4">
                                <span
                                    class="px-3 py-1.5 text-sm rounded-md inline-flex items-center"
                                    :class="{
                                        'bg-blue-100 text-blue-800': course.user_status === 'enrolled',
                                        'bg-green-100 text-green-800': course.user_status === 'completed'
                                    }"
                                >
                                    <span v-if="course.user_status === 'enrolled'" class="flex items-center">
                                        <i class="fas fa-user-check mr-1.5"></i> Enrolled
                                    </span>
                                    <span v-else-if="course.user_status === 'completed'" class="flex items-center">
                                        <i class="fas fa-check-circle mr-1.5"></i> Completed
                                    </span>
                                </span>
                            </div>

                            <!-- Assignment status -->
                            <div v-if="course.user_assignment" class="mb-4">
                                <span
                                    class="px-3 py-1.5 text-sm rounded-md inline-flex items-center"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': course.user_assignment.status === 'pending',
                                        'bg-green-100 text-green-800': course.user_assignment.status === 'accepted',
                                        'bg-red-100 text-red-800': course.user_assignment.status === 'declined',
                                        'bg-blue-100 text-blue-800': course.user_assignment.status === 'completed'
                                    }"
                                >
                                    <i class="fas fa-clipboard-list mr-1.5"></i>
                                    Assigned by {{ course.user_assignment.assigned_by }}
                                </span>
                            </div>

                            <Link
                                :href="`/courses/${course.id}`"
                                class="course-button"
                            >
                                View Details
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!courses.data || courses.data.length === 0" class="col-span-full text-center py-16 px-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="text-lg text-gray-500 font-medium">No courses available at the moment</p>
                        <p class="text-gray-400 mt-2">Check back later for new courses</p>
                    </div>
                </div>

                <!-- Pagination Component -->
                <div v-if="courses.data && courses.data.length > 0 && courses.links && courses.links.length > 0" class="pagination-wrapper">
                    <Pagination :links="courses.links" @rendered="console.log('Pagination component rendered with links:', courses.links)" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
