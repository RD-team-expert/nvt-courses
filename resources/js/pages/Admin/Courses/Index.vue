<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import Pagination from '@/components/Pagination.vue'

const props = defineProps({
  courses: Object, // Changed from Array to Object to support pagination
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
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold">Manage Courses</h1>
        <Link 
          :href="route('admin.courses.create')" 
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
        >
          Add New Course
        </Link>
      </div>
      
      <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Course
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                Status
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                Dates
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                Enrollments
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="course in courses.data" :key="course.id" class="hover:bg-gray-50">
              <!-- Table cell content remains the same -->
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0 mr-3">
                    <img 
                      v-if="course.image_path" 
                      :src="`/storage/${course.image_path}`" 
                      :alt="course.name"
                      class="h-10 w-10 rounded-md object-cover"
                    >
                    <div 
                      v-else 
                      class="h-10 w-10 rounded-md bg-gray-100 flex items-center justify-center"
                    >
                      <span class="text-xs text-gray-400">No img</span>
                    </div>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ course.name }}</div>
                    <div class="flex items-center mt-1 sm:hidden">
                      <span 
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full mr-2"
                        :class="{
                          'bg-green-100 text-green-800': course.status === 'in_progress',
                          'bg-yellow-100 text-yellow-800': course.status === 'pending',
                          'bg-blue-100 text-blue-800': course.status === 'completed'
                        }"
                      >
                        {{ formatStatus(course.status) }}
                      </span>
                      <span class="text-xs text-gray-500">{{ course.users_count || 0 }} students</span>
                    </div>
                    <div v-if="course.level" class="text-sm text-gray-500 capitalize hidden sm:block">{{ course.level }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                <span 
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': course.status === 'in_progress',
                    'bg-yellow-100 text-yellow-800': course.status === 'pending',
                    'bg-blue-100 text-blue-800': course.status === 'completed'
                  }"
                >
                  {{ formatStatus(course.status) }}
                </span>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                <div class="text-sm">
                  <div><span class="font-medium">Start:</span> {{ formatDate(course.start_date) }}</div>
                  <div><span class="font-medium">End:</span> {{ formatDate(course.end_date) }}</div>
                </div>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm hidden lg:table-cell">
                {{ course.users_count || 0 }} students
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2 justify-end">
                  <Link :href="`/admin/courses/${course.id}`" class="text-blue-600 hover:text-blue-900">
                    <span class="hidden sm:inline">View</span>
                    <span class="sm:hidden">👁️</span>
                  </Link>
                  <Link :href="`/admin/courses/${course.id}/edit`" class="text-indigo-600 hover:text-indigo-900">
                    <span class="hidden sm:inline">Edit</span>
                    <span class="sm:hidden">✏️</span>
                  </Link>
                  <Link
                    :href="`/admin/courses/${course.id}`"
                    method="delete"
                    as="button"
                    class="text-red-600 hover:text-red-900"
                    @click="e => !confirm('Are you sure you want to delete this course?') && e.preventDefault()"
                  >
                    <span class="hidden sm:inline">Delete</span>
                    <span class="sm:hidden">🗑️</span>
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!courses.data || courses.data.length === 0">
              <td colspan="5" class="p-4 text-center text-gray-500">No courses found</td>
            </tr>
          </tbody>
        </table>
        
        <!-- After the table -->
        <div class="px-4 sm:px-6 py-3 bg-white border-t border-gray-200">
          <div class="flex items-center justify-between">
            <!-- Mobile pagination controls -->
            <div class="flex-1 flex justify-between sm:hidden">
              <Link
                :href="courses.prev_page_url"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50',
                  !courses.prev_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                ]"
              >
                Previous
              </Link>
              <span class="text-sm text-gray-700">
                Page {{ courses.current_page }} of {{ courses.last_page }}
              </span>
              <Link
                :href="courses.next_page_url"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50',
                  !courses.next_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                ]"
              >
                Next
              </Link>
            </div>
            
            <!-- Desktop pagination controls -->
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing <span class="font-medium">{{ courses.from }}</span> to <span class="font-medium">{{ courses.to }}</span> of <span class="font-medium">{{ courses.total }}</span> courses
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <Link
                    :href="courses.prev_page_url"
                    :class="[
                      'relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50',
                      !courses.prev_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                    ]"
                  >
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </Link>
                  
                  <!-- Page numbers -->
                  <template v-for="(link, i) in courses.links.slice(1, -1)" :key="i">
                    <Link
                      :href="link.url"
                      :class="[
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                        link.active 
                          ? 'z-10 bg-blue-500 border-blue-500 text-blue-600' 
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                      ]"
                      v-html="link.label"
                    />
                  </template>
                  
                  <Link
                    :href="courses.next_page_url"
                    :class="[
                      'relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50',
                      !courses.next_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                    ]"
                  >
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </Link>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
  