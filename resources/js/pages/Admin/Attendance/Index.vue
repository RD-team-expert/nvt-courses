<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'

const props = defineProps({
  clockings: Object,
  users: Array,
  courses: Array, // Add courses prop
  filters: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Attendance Management', href: route('admin.attendance.index') }
]

// Filter state
const filters = ref({
  user_id: props.filters?.user_id || '',
  date: props.filters?.date || '',
  course_id: props.filters?.course_id || '' // Add course_id filter
})

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Format duration in minutes to human-readable format
const formatHumanDuration = (minutes) => {
  if (!minutes || isNaN(minutes) || minutes < 0) return '—'
  
  // Round to nearest integer to avoid decimal values
  minutes = Math.round(minutes)
  
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = minutes % 60
  
  if (hours > 0) {
    return `${hours} ${hours === 1 ? 'hour' : 'hours'}${remainingMinutes > 0 ? ` ${remainingMinutes} ${remainingMinutes === 1 ? 'minute' : 'minutes'}` : ''}`
  }
  return `${remainingMinutes} ${remainingMinutes === 1 ? 'minute' : 'minutes'}`
}

// Apply filters with debounce
const applyFilters = debounce(() => {
  router.get(route('admin.attendance.index'), filters.value, {
    preserveState: true,
    replace: true
  })
}, 500)

// Watch for filter changes
watch(filters, () => {
  applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
  filters.value = {
    user_id: '',
    date: '',
    course_id: '' // Reset course_id filter
  }
  applyFilters()
}
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
      <h1 class="text-2xl font-bold mb-2 sm:mb-0">Attendance Records</h1>
    </div>
    
    <!-- Filters -->
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow mb-6">
      <h2 class="text-lg font-medium text-gray-900 mb-4">Filter Attendance Records</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6">
        <div>
          <label for="user_filter" class="block text-sm font-medium text-gray-700 mb-1">User</label>
          <select
            id="user_filter"
            v-model="filters.user_id"
            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
        
        <div>
          <label for="date_filter" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input
            id="date_filter"
            type="date"
            v-model="filters.date"
            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <!-- Add Course Filter -->
        <div>
          <label for="course_filter" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
          <select
            id="course_filter"
            v-model="filters.course_id"
            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Courses</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
          >
            Reset Filters
          </button>
        </div>
      </div>
    </div>
    
    <!-- Attendance Records Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              User
            </th>
            <!-- Add Course Column -->
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Course
            </th>
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Clock In
            </th>
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
              Clock Out
            </th>
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Duration
            </th>
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
              Rating
            </th>
            <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
              Comment
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="clockings.data.length === 0">
            <td colspan="7" class="px-4 sm:px-6 py-4 text-center text-gray-500">No attendance records found</td>
          </tr>
          <tr v-else v-for="(record, i) in clockings.data" :key="i" class="hover:bg-gray-50">
            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ record.user?.name || 'Unknown User' }}</div>
                  <div class="text-xs text-gray-500 hidden sm:block">{{ record.user?.email }}</div>
                </div>
              </div>
            </td>
            <!-- Add Course Column Data -->
            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ record.course_name || 'General Attendance' }}
            </td>
            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(record.clock_in) }}</td>
            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</td>
            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <span v-if="record.clock_out">
                {{ formatHumanDuration(record.duration_in_minutes) }}
              </span>
              <span v-else class="flex items-center">
                {{ formatHumanDuration(record.current_duration || 0) }} 
                <span class="ml-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">ongoing</span>
              </span>
            </td>
            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">
              <div v-if="record.rating" class="flex items-center">
                <span>{{ record.rating }}/5</span>
                <div class="ml-1 flex">
                  <svg v-for="i in 5" :key="i" class="h-4 w-4" :class="i <= record.rating ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </div>
              <span v-else>—</span>
            </td>
            <td class="px-4 sm:px-6 py-4 text-sm text-gray-900 hidden lg:table-cell">
              <div class="max-w-xs truncate">{{ record.comment || '—' }}</div>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="clockings.data && clockings.data.length > 0" class="px-4 sm:px-6 py-4 bg-white border-t border-gray-200 flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link 
            :href="clockings.prev_page_url" 
            :class="[
              'relative inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50',
              !clockings.prev_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
            ]"
          >
            Previous
          </Link>
          <Link 
            :href="clockings.next_page_url" 
            :class="[
              'ml-3 relative inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50',
              !clockings.next_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
            ]"
          >
            Next
          </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ clockings.from }}</span> to <span class="font-medium">{{ clockings.to }}</span> of <span class="font-medium">{{ clockings.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <Link
                :href="clockings.prev_page_url"
                :class="[
                  'relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50',
                  !clockings.prev_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                ]"
              >
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </Link>
              <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                Page {{ clockings.current_page }} of {{ clockings.last_page }}
              </span>
              <Link
                :href="clockings.next_page_url"
                :class="[
                  'relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50',
                  !clockings.next_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
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
  </AdminLayout>
</template>
