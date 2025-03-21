<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
  course: Object,
})

// Format status for display
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
  { name: 'Course Management', href: route('admin.courses.index') },
  { name: props.course.name, href: route('admin.courses.show', props.course.id) }
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container px-4 mx-auto py-4 sm:py-6 max-w-7xl">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">{{ course.name }}</h1>
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
          <Link 
            :href="`/admin/courses/${course.id}/edit`" 
            class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors w-full sm:w-auto"
          >
            Edit Course
          </Link>
          <Link
            :href="`/admin/courses/${course.id}`"
            method="delete"
            as="button"
            class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors w-full sm:w-auto"
            confirm="Are you sure you want to delete this course?"
            confirm-button="Delete"
            cancel-button="Cancel"
          >
            Delete Course
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        <!-- Course Details -->
        <div class="md:col-span-2 bg-white rounded-lg shadow overflow-hidden">
          <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-2 sm:gap-0">
              <h2 class="text-lg sm:text-xl font-semibold">Course Details</h2>
              <span 
                class="px-3 py-1 text-sm rounded-full self-start sm:self-auto w-fit"
                :class="{
                  'bg-green-100 text-green-800': course.status === 'in_progress',
                  'bg-yellow-100 text-yellow-800': course.status === 'pending',
                  'bg-blue-100 text-blue-800': course.status === 'completed'
                }"
              >
                {{ formatStatus(course.status) }}
              </span>
            </div>
            
            <div class="prose prose-sm sm:prose-lg max-w-none mb-6" v-html="course.description"></div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-gray-500 mb-1">Start Date</p>
                <p class="font-medium">{{ formatDate(course.start_date) }}</p>
              </div>
              <div>
                <p class="text-gray-500 mb-1">End Date</p>
                <p class="font-medium">{{ formatDate(course.end_date) }}</p>
              </div>
              <div>
                <p class="text-gray-500 mb-1">Level</p>
                <p class="font-medium capitalize">{{ course.level || '—' }}</p>
              </div>
              <div>
                <p class="text-gray-500 mb-1">Duration</p>
                <p class="font-medium">{{ course.duration ? `${course.duration} hours` : '—' }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Enrolled Users -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-semibold mb-4">Enrolled Users</h2>
            
            <div v-if="course.users && course.users.length > 0">
              <ul class="divide-y divide-gray-200">
                <li v-for="user in course.users" :key="user.id" class="py-3">
                  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-0">
                    <div>
                      <p class="font-medium">{{ user.name }}</p>
                      <p class="text-sm text-gray-500">{{ user.email }}</p>
                    </div>
                    <span 
                      class="px-2 py-1 text-xs rounded-full self-start sm:self-auto w-fit"
                      :class="{
                        'bg-green-100 text-green-800': user.pivot.user_status === 'completed',
                        'bg-yellow-100 text-yellow-800': user.pivot.user_status === 'enrolled'
                      }"
                    >
                      {{ user.pivot.user_status === 'completed' ? 'Completed' : 'Enrolled' }}
                    </span>
                  </div>
                </li>
              </ul>
            </div>
            
            <div v-else class="text-center py-4 text-gray-500">
              No users enrolled in this course yet.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>