<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import Pagination from '@/components/Pagination.vue'

const props = defineProps({
  completions: Object,
  courses: Array,
  filters: Object,
  debug: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Reports & Analytics', href: route('admin.reports.index') },
  { name: 'Course Completion', href: route('admin.reports.course-completion') }
]

// Filter state
const filters = ref({
  course_id: props.filters?.course_id || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || ''
})

// Apply filters with debounce
const applyFilters = debounce(() => {
  router.get(route('admin.reports.course-completion'), filters.value, {
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
    course_id: '',
    date_from: '',
    date_to: ''
  }
  applyFilters()
}

// Export to CSV
const exportToCsv = () => {
  const queryParams = new URLSearchParams(filters.value).toString();
  window.location.href = route('admin.reports.export.course-completion') + '?' + queryParams;
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

// Handle pagination
const handlePageChange = (page) => {
  router.get(route('admin.reports.course-completion'), {
    ...filters.value,
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
        <h1 class="text-xl sm:text-2xl font-bold">Course Completion Report</h1>
        <button 
          @click="exportToCsv" 
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition flex items-center w-full sm:w-auto justify-center sm:justify-start"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export to CSV
        </button>
      </div>
      
      <!-- Filters -->
      <div class="bg-white p-4 sm:p-6 rounded-lg shadow mb-4 sm:mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Filter Completions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
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
          
          <div>
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Completed From</label>
            <input
              id="date_from"
              type="date"
              v-model="filters.date_from"
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div>
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Completed To</label>
            <input
              id="date_to"
              type="date"
              v-model="filters.date_to"
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div class="flex items-end md:col-span-3">
            <button
              @click="resetFilters"
              class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
            >
              Reset Filters
            </button>
          </div>
        </div>
      </div>
      
      <!-- Completions Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                User
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                Course
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                Registered
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Completed
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                Rating
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                Feedback
              </th>
              <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">
                Comment
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="completions.data.length === 0">
              <td colspan="7" class="px-4 sm:px-6 py-4 text-center text-gray-500">No completion records found</td>
            </tr>
            <tr v-else v-for="(record, i) in completions.data" :key="i" class="hover:bg-gray-50">
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ record.user_name }}</div>
                    <div class="text-xs text-gray-500 hidden sm:block">{{ record.user_email }}</div>
                    <div class="text-xs text-gray-500 sm:hidden mt-1">{{ record.course_name }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">{{ record.course_name }}</td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">{{ formatDate(record.registered_at) }}</td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(record.completed_at) }}</td>
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
                <div class="max-w-xs truncate">{{ record.feedback || '—' }}</div>
              </td>
              <td class="px-4 sm:px-6 py-4 text-sm text-gray-900 hidden xl:table-cell">
                <div class="max-w-xs truncate">{{ record.comment || '—' }}</div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="px-4 sm:px-6 py-3 bg-white border-t border-gray-200">
          <Pagination 
            v-if="completions.data.length > 0"
            :links="completions.links" 
            @page-changed="handlePageChange" 
            class="mt-4"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>