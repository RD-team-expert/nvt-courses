<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'

const props = defineProps({
  registrations: Array,
  courses: Array,
  filters: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Reports & Analytics', href: route('admin.reports.index') },
  { name: 'Course Registrations', href: route('admin.reports.course-registrations') }
]

// Filter state
const filters = ref({
  course_id: props.filters?.course_id || '',
  status: props.filters?.status || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || ''
})

// Apply filters with debounce
const applyFilters = debounce(() => {
  // Make a copy of the filters to avoid reactivity issues
  const filterParams = { ...filters.value };
  
  // Format dates if needed
  if (filterParams.date_from) {
    filterParams.date_from = filterParams.date_from.trim();
  }
  if (filterParams.date_to) {
    filterParams.date_to = filterParams.date_to.trim();
  }
  
  router.get(route('admin.reports.course-registrations'), filterParams, {
    preserveState: true,
    replace: true
  });
}, 500);

// Watch for filter changes
watch(filters, () => {
  applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
  filters.value = {
    course_id: '',
    status: '',
    date_from: '',
    date_to: ''
  }
  applyFilters()
}

// Export to CSV
const exportToCsv = () => {
  const queryParams = new URLSearchParams(filters.value).toString();
  window.location.href = route('admin.reports.export.course-registrations') + '?' + queryParams;
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
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Course Registrations Report</h1>
      <button 
      @click="exportToCsv" 
      class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition flex items-center"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      Export to CSV
    </button>
  </div>
  
  <!-- Filters -->
  <div class="bg-white p-6 rounded-lg shadow mb-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Filter Registrations</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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
        <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
          id="status_filter"
          v-model="filters.status"
          class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      
      <div>
        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
        <input
          id="date_from"
          type="date"
          v-model="filters.date_from"
          class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      
      <div>
        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
        <input
          id="date_to"
          type="date"
          v-model="filters.date_to"
          class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      
      <div class="flex items-end md:col-span-4">
        <button
          @click="resetFilters"
          class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
        >
          Reset Filters
        </button>
      </div>
    </div>
  </div>
  
  <!-- Registrations Table -->
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            User
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Course
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Status
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Registered At
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Completed At
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Rating
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-if="registrations.length === 0">
          <td colspan="6" class="px-6 py-4 text-center text-gray-500">No registration records found</td>
        </tr>
        <tr v-else v-for="(record, i) in registrations" :key="i" class="hover:bg-gray-50">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <div>
                <div class="text-sm font-medium text-gray-900">{{ record.user_name }}</div>
                <div class="text-sm text-gray-500">{{ record.user_email }}</div>
              </div>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ record.course_name }}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span 
              class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-800': record.status === 'pending',
                'bg-blue-100 text-blue-800': record.status === 'in_progress',
                'bg-green-100 text-green-800': record.status === 'completed'
              }"
            >
              {{ record.status.replace('_', ' ').charAt(0).toUpperCase() + record.status.replace('_', ' ').slice(1) }}
            </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(record.registered_at) }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ record.completed_at ? formatDate(record.completed_at) : '—' }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
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
        </tr>
      </tbody>
    </table>
  </div>
</AdminLayout>
</template>