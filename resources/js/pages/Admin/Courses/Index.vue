<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import Pagination from '@/components/Pagination.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'

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
                    <Button asChild>
                        <Link :href="route('admin.courses.create')">
                            Add New Course
                        </Link>
                    </Button>
                    <Button asChild variant="secondary">
                        <Link :href="route('admin.assignments.create')">
                            Assign Course
                        </Link>
                    </Button>
                    <Button asChild variant="secondary">
                        <Link :href="route('admin.assignments.index')">
                            Assign
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Course Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <Card v-for="course in (courses.data || courses)" :key="course.id" class="hover:shadow-lg transition-shadow">
                    <CardHeader class="pb-3">
                        <div class="flex items-center space-x-3">
                            <Avatar class="h-12 w-12">
                                <AvatarImage
                                    v-if="course.image_path"
                                    :src="`/storage/${course.image_path}`"
                                    :alt="course.name"
                                />
                                <AvatarFallback class="bg-muted text-muted-foreground">
                                    {{ course.name.charAt(0) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <CardTitle class="text-lg truncate">{{ course.name }}</CardTitle>
                                <p class="text-sm text-muted-foreground">{{ course.level }}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Status and Privacy Badges -->
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                :variant="course.status === 'in_progress' ? 'default' : course.status === 'pending' ? 'secondary' : 'outline-solid'"
                            >
                                {{ course.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                            </Badge>
                            <Badge
                                :variant="course.privacy === 'public' ? 'default' : 'destructive'"
                            >
                                {{ course.privacy === 'public' ? '🌍 Public' : '🔒 Private' }}
                            </Badge>
                        </div>
                        
                        <!-- Enrolled Count -->
                        <div class="text-sm text-muted-foreground">
                            {{ course.enrolled_count || 0 }} students enrolled
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex gap-2 pt-2">
                            <Button asChild variant="outline" size="sm" class="flex-1">
                                <Link :href="`/admin/courses/${course.id}/edit`">
                                    Edit
                                </Link>
                            </Button>
                            <Button
                                @click="deleteCourse(course.id)"
                                variant="destructive"
                                size="sm"
                                class="flex-1"
                            >
                                Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty state -->
            <Card v-if="!courses || (!courses.data && !courses.length) || (courses.data && courses.data.length === 0)" class="text-center py-12">
                <CardContent class="pt-6">
                    <div class="text-muted-foreground">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-foreground">No courses found</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Get started by creating a new course.</p>
                        <div class="mt-6">
                            <Button asChild>
                                <Link :href="route('admin.courses.create')">
                                    Add New Course
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination Section -->
            <Card v-if="courses && (courses.links || courses.last_page) && courses.total > 0" class="mt-6">
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <!-- Mobile pagination controls -->
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Button
                                v-if="courses.prev_page_url"
                                variant="outline"
                                size="sm"
                                asChild
                            >
                                <Link :href="courses.prev_page_url">
                                    Previous
                                </Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>
                                Previous
                            </Button>

                            <span class="text-sm text-muted-foreground">
                                Page {{ courses.current_page || 1 }} of {{ courses.last_page || 1 }}
                            </span>

                            <Button
                                v-if="courses.next_page_url"
                                variant="outline"
                                size="sm"
                                asChild
                            >
                                <Link :href="courses.next_page_url">
                                    Next
                                </Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>
                                Next
                            </Button>
                        </div>

                        <!-- Desktop pagination controls -->
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    Showing
                                    <span class="font-medium text-foreground">{{ courses.from || 1 }}</span>
                                    to
                                    <span class="font-medium text-foreground">{{ courses.to || (courses.data ? courses.data.length : 0) }}</span>
                                    of
                                    <span class="font-medium text-foreground">{{ courses.total || (courses.data ? courses.data.length : 0) }}</span>
                                    courses
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <!-- Previous Button -->
                                <Button
                                    v-if="courses.prev_page_url"
                                    variant="outline"
                                    size="sm"
                                    asChild
                                >
                                    <Link :href="courses.prev_page_url">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </Link>
                                </Button>
                                <Button v-else variant="outline" size="sm" disabled>
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </Button>

                                <!-- Page numbers -->
                                <template v-if="courses.links && Array.isArray(courses.links) && courses.links.length > 2">
                                    <template v-for="(link, i) in courses.links.slice(1, -1)" :key="i">
                                        <Button
                                            v-if="link.url"
                                            :variant="link.active ? 'default' : 'outline-solid'"
                                            size="sm"
                                            asChild
                                        >
                                            <Link :href="link.url" v-html="link.label" />
                                        </Button>
                                        <Button
                                            v-else
                                            :variant="link.active ? 'default' : 'outline-solid'"
                                            size="sm"
                                            disabled
                                            v-html="link.label"
                                        />
                                    </template>
                                </template>

                                <!-- If no links, show current page -->
                                <Button v-else variant="default" size="sm" disabled>
                                    {{ courses.current_page || 1 }}
                                </Button>

                                <!-- Next Button -->
                                <Button
                                    v-if="courses.next_page_url"
                                    variant="outline"
                                    size="sm"
                                    asChild
                                >
                                    <Link :href="courses.next_page_url">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </Link>
                                </Button>
                                <Button v-else variant="outline" size="sm" disabled>
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
