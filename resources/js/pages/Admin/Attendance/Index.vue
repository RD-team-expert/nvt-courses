<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'

const props = defineProps({
  clockings: Object,
  users: Array,
  courses: Array,
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
  course_id: props.filters?.course_id || ''
})

// Modal state
const showEditModal = ref(false)
const selectedRecord = ref(null)
const form = ref({
  user_id: '',
  course_id: null,
  clock_in: '',
  clock_out: '',
  rating: null,
  comment: ''
})
const errors = ref({})
const processing = ref(false)

// Open edit modal
const openEditModal = (record) => {
  selectedRecord.value = record
  form.value = {
    user_id: record.user_id,
    course_id: record.course_id || null,
    clock_in: formatDateForInput(record.clock_in),
    clock_out: formatDateForInput(record.clock_out),
    rating: record.rating || null,
    comment: record.comment || ''
  }
  showEditModal.value = true
}

// Close modal
const closeModal = () => {
  showEditModal.value = false
  selectedRecord.value = null
  errors.value = {}
}

// Format date for input fields (YYYY-MM-DDTHH:MM)
const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  
  // Format to local timezone for datetime-local input
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

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

// Submit form
const submitForm = () => {
  processing.value = true
  
  router.put(route('admin.attendance.update', selectedRecord.value.id), form.value, {
    onSuccess: () => {
      closeModal()
      processing.value = false
    },
    onError: (err) => {
      errors.value = err
      processing.value = false
    }
  })
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
            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
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
            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <div>
          <label for="course_filter" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
          <select
            id="course_filter"
            v-model="filters.course_id"
            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Courses</option>
            <option value="general">General Attendance</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-medium text-gray-700 hover:bg-gray-200 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
          >
            Reset Filters
          </button>
        </div>
      </div>
    </div>
    
    <!-- Attendance Records Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Mobile view for small screens -->
      <div class="block sm:hidden">
        <div v-if="clockings.data.length === 0" class="px-4 py-4 text-center text-gray-500">
          No attendance records found
        </div>
        <div v-else v-for="(record, i) in clockings.data" :key="i" class="border-b border-gray-200 p-4">
          <div class="flex justify-between items-center mb-2">
            <div class="font-medium text-gray-900">{{ record.user?.name || 'Unknown User' }}</div>
            <div class="text-sm text-gray-500">{{ record.user?.email }}</div>
          </div>
          <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
              <span class="text-gray-500">Course:</span>
              <div class="font-medium">{{ record.course_name || 'General Attendance' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Clock In:</span>
              <div class="font-medium">{{ formatDate(record.clock_in) }}</div>
            </div>
            <div>
              <span class="text-gray-500">Clock Out:</span>
              <div class="font-medium">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Duration:</span>
              <div class="font-medium">
                <span v-if="record.clock_out">
                  {{ formatHumanDuration(record.duration_in_minutes) }}
                </span>
                <span v-else class="flex items-center">
                  {{ formatHumanDuration(record.current_duration || 0) }}
                  <span class="ml-1 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">ongoing</span>
                </span>
              </div>
            </div>
            <div v-if="record.rating">
              <span class="text-gray-500">Rating:</span>
              <div class="font-medium flex items-center">
                {{ record.rating }}/5
                <div class="ml-1 flex">
                  <svg v-for="i in 5" :key="i" class="h-4 w-4" :class="i <= record.rating ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
          <div v-if="record.comment" class="mt-2">
            <span class="text-gray-500">Comment:</span>
            <div class="font-medium">{{ record.comment }}</div>
          </div>
          <div class="mt-3 flex justify-end">
            <button 
              @click="openEditModal(record)" 
              class="inline-flex items-center px-3 py-1 border border-blue-600 text-sm font-medium rounded text-blue-600 hover:bg-blue-50"
            >
              Edit
            </button>
          </div>
        </div>
      </div>
      
      <!-- Desktop view for larger screens -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                User
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Course
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Clock In
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Clock Out
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Duration
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                Rating
              </th>
              <!-- <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                Comment
              </th> -->
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="clockings.data.length === 0">
              <td colspan="8" class="px-4 sm:px-6 py-4 text-center text-gray-500">No attendance records found</td>
            </tr>
            <tr v-else v-for="(record, i) in clockings.data" :key="i" class="hover:bg-gray-50">
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ record.user?.name || 'Unknown User' }}</div>
                    <div class="text-xs text-gray-500">{{ record.user?.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ record.course_name || 'General Attendance' }}
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(record.clock_in) }}</td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</td>
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
              <!-- <td class="px-4 sm:px-6 py-4 text-sm text-gray-900 hidden lg:table-cell">
                <div class="max-w-xs truncate">{{ record.comment || '—' }}</div>
              </td> -->
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div class="flex space-x-2">
                  <button 
                    @click="openEditModal(record)" 
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
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
    
    <!-- Edit Modal - Moved inside the main template -->
    <div v-if="showEditModal" class="fixed inset-0 overflow-y-auto z-50">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  Edit Attendance Record
                </h3>
                <div class="mt-4 space-y-4">
                  <!-- User -->
                  <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                    <select
                      id="user_id"
                      v-model="form.user_id"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-hidden focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                    </select>
                    <div v-if="errors.user_id" class="text-red-500 text-sm mt-1">{{ errors.user_id }}</div>
                  </div>

                  <!-- Course -->
                  <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700">Course</label>
                    <select
                      id="course_id"
                      v-model="form.course_id"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-hidden focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option :value="null">General Attendance</option>
                      <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
                    </select>
                    <div v-if="errors.course_id" class="text-red-500 text-sm mt-1">{{ errors.course_id }}</div>
                  </div>

                  <!-- Clock In -->
                  <div>
                    <label for="clock_in" class="block text-sm font-medium text-gray-700">Clock In</label>
                    <input
                      type="datetime-local"
                      id="clock_in"
                      v-model="form.clock_in"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-hidden focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                    <div v-if="errors.clock_in" class="text-red-500 text-sm mt-1">{{ errors.clock_in }}</div>
                  </div>

                  <!-- Clock Out -->
                  <div>
                    <label for="clock_out" class="block text-sm font-medium text-gray-700">Clock Out</label>
                    <input
                      type="datetime-local"
                      id="clock_out"
                      v-model="form.clock_out"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-hidden focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                    <div v-if="errors.clock_out" class="text-red-500 text-sm mt-1">{{ errors.clock_out }}</div>
                  </div>

                  <!-- Rating -->
                  <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700">Rating</label>
                    <select
                      id="rating"
                      v-model="form.rating"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-hidden focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option :value="null">No Rating</option>
                      <option v-for="i in 5" :key="i" :value="i">{{ i }}</option>
                    </select>
                    <div v-if="errors.rating" class="text-red-500 text-sm mt-1">{{ errors.rating }}</div>
                  </div>

                  <!-- Comment -->
                  <div>
                    <label for="comment" class="block text-sm font-medium text-gray-700">Comment</label>
                    <textarea
                      id="comment"
                      v-model="form.comment"
                      rows="3"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-hidden focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    ></textarea>
                    <div v-if="errors.comment" class="text-red-500 text-sm mt-1">{{ errors.comment }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button 
              type="button" 
              @click="submitForm" 
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
              :disabled="processing"
            >
              <span v-if="processing">Saving...</span>
              <span v-else>Save Changes</span>
            </button>
            <button 
              type="button" 
              @click="closeModal" 
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
