<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import Pagination from '@/components/Pagination.vue'

const props = defineProps({
    courses: Object, // Changed from Array to Object to support pagination
})

// ✅ Fixed deleteCourse function (was missing 'c' in 'const')
const deleteCourse = (courseId: number) => {
    if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        return;
    }

    router.delete(route('admin.courses.destroy', courseId), {
        preserveState: true,
        onSuccess: () => {
            alert('Course deleted successfully!');
        },
        onError: (errors) => {
            console.error('Delete failed:', errors);
            alert('Failed to delete course. Please try again.');
        }
    });
}

// ✅ Single formatStatus function (removed duplicates)
const formatStatus = (status) => {
    const statusMap = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    }
    return statusMap[status] || status
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'

    // Create a date object with the date string
    // Use the split method to ensure we're only using the date part
    const dateParts = dateString.split('T')[0].split('-');
    if (dateParts.length !== 3) {
        // If not in expected format, try standard parsing
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Create a date object with the year, month, day
    // Note: months are 0-indexed in JavaScript Date
    const date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
    return date.toLocaleDateString();
}

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Course Management', href: route('admin.courses.index') }
]

// Handle pagination
const handlePageChange = (page) => {
    router.get(route('admin.courses.index'), {
        page
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    })
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">Manage Courses</h1>

                <!-- Button container -->
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.courses.create')"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                    >
                        Add New Course
                    </Link>
                    <Link
                        :href="route('admin.assignments.create')"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto text-center"
                    >
                        Assign Course
                    </Link>

                    <Link
                        :href="route('admin.assignments.index')"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto text-center"
                    >
                        Assign
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Course
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Privacy
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Enrolled
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="course in (courses.data || courses)" :key="course.id">
                        <!-- Course name column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img
                                        v-if="course.image_path"
                                        class="h-10 w-10 rounded-full object-cover"
                                        :src="`/storage/${course.image_path}`"
                                        :alt="course.name"
                                    />
                                    <div
                                        v-else
                                        class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center"
                                    >
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ course.name.charAt(0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ course.name }}</div>
                                    <div class="text-sm text-gray-500">{{ course.level }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Status column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': course.status === 'in_progress',
                                    'bg-yellow-100 text-yellow-800': course.status === 'pending',
                                    'bg-blue-100 text-blue-800': course.status === 'completed'
                                }"
                            >
                                {{ course.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                            </span>
                        </td>

                        <!-- Privacy column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': course.privacy === 'public',
                                    'bg-red-100 text-red-800': course.privacy === 'private'
                                }"
                            >
                                {{ course.privacy === 'public' ? '🌍 Public' : '🔒 Private' }}
                            </span>
                        </td>

                        <!-- Enrolled count column -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ course.enrolled_count || 0 }} students
                        </td>

                        <!-- Actions column -->
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <Link
                                :href="`/admin/courses/${course.id}/edit`"
                                class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors"
                            >
                                Edit
                            </Link>

                            <button
                                @click="deleteCourse(course.id)"
                                class="text-red-600 hover:text-red-900 transition-colors"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div v-if="!courses || (!courses.data && !courses.length) || (courses.data && courses.data.length === 0)" class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No courses found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new course.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('admin.courses.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Add New Course
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pagination Section -->
                <div v-if="courses && (courses.links || courses.last_page) && courses.total > 0" class="px-4 sm:px-6 py-3 bg-white border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <!-- Mobile pagination controls -->
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="courses.prev_page_url"
                                :href="courses.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Previous
                            </Link>
                            <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                Previous
                            </span>

                            <span class="text-sm text-gray-700">
                                Page {{ courses.current_page || 1 }} of {{ courses.last_page || 1 }}
                            </span>

                            <Link
                                v-if="courses.next_page_url"
                                :href="courses.next_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Next
                            </Link>
                            <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                Next
                            </span>
                        </div>

                        <!-- Desktop pagination controls -->
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ courses.from || 1 }}</span>
                                    to
                                    <span class="font-medium">{{ courses.to || (courses.data ? courses.data.length : 0) }}</span>
                                    of
                                    <span class="font-medium">{{ courses.total || (courses.data ? courses.data.length : 0) }}</span>
                                    courses
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <!-- Previous Button -->
                                    <Link
                                        v-if="courses.prev_page_url"
                                        :href="courses.prev_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors"
                                    >
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </Link>
                                    <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>

                                    <!-- Page numbers -->
                                    <template v-if="courses.links && Array.isArray(courses.links) && courses.links.length > 2">
                                        <template v-for="(link, i) in courses.links.slice(1, -1)" :key="i">
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                :class="[
                                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors',
                                                    link.active
                                                        ? 'z-10 bg-blue-500 border-blue-500 text-white'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                ]"
                                                v-html="link.label"
                                            />
                                            <span
                                                v-else
                                                :class="[
                                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                    link.active
                                                        ? 'z-10 bg-blue-500 border-blue-500 text-white'
                                                        : 'bg-white border-gray-300 text-gray-500'
                                                ]"
                                                v-html="link.label"
                                            />
                                        </template>
                                    </template>

                                    <!-- If no links, show current page -->
                                    <span v-else class="relative inline-flex items-center px-4 py-2 border bg-blue-500 border-blue-500 text-white text-sm font-medium">
                                        {{ courses.current_page || 1 }}
                                    </span>

                                    <!-- Next Button -->
                                    <Link
                                        v-if="courses.next_page_url"
                                        :href="courses.next_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors"
                                    >
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </Link>
                                    <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
