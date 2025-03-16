<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Attendance Records</h1>
      <button 
        @click="openClockInModal" 
        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
      >
        Clock In
      </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Clock In
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Clock Out
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Duration
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Rating
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="record in attendanceRecords" :key="record.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ formatDate(record.date) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ formatTime(record.clock_in_time) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ record.clock_out_time ? formatTime(record.clock_out_time) : '—' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ record.duration ? formatDuration(record.duration) : '—' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div v-if="record.rating" class="flex">
                <template v-for="i in 5" :key="i">
                  <svg 
                    :class="[
                      'h-5 w-5', 
                      i <= record.rating ? 'text-yellow-400' : 'text-gray-300'
                    ]" 
                    fill="currentColor" 
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </template>
              </div>
              <div v-else class="text-sm text-gray-500">—</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="{
                  'bg-green-100 text-green-800': record.status === 'completed',
                  'bg-yellow-100 text-yellow-800': record.status === 'in_progress',
                  'bg-red-100 text-red-800': record.status === 'missed'
                }"
              >
                {{ formatStatus(record.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="flex space-x-2 justify-end">
                <button 
                  v-if="record.status === 'in_progress'"
                  @click="openClockOutModal(record)"
                  class="text-red-600 hover:text-red-900"
                >
                  Clock Out
                </button>
                <button
                  v-if="record.status === 'completed'"
                  @click="viewDetails(record)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  View
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="attendanceRecords.length === 0">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
              No attendance records found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination controls if needed -->
    <div v-if="pagination && pagination.total > pagination.per_page" class="mt-4 flex justify-center">
      <!-- Pagination component here -->
    </div>

    <!-- Clock In Modal -->
    <ClockInModal
      :show="showClockInModal"
      :is-submitting="isSubmittingClockIn"
      @close="showClockInModal = false"
      @submit="handleClockIn"
    />

    <!-- Clock Out Modal -->
    <ClockOutModal
      :show="showClockOutModal"
      :initial-form="clockOutForm"
      :is-submitting="isSubmittingClockOut"
      @close="showClockOutModal = false"
      @submit="handleClockOut"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ClockInModal from '@/components/attendance/ClockInModal.vue';
import ClockOutModal from '@/components/attendance/ClockOutModal.vue';
import { useForm } from '@inertiajs/vue3';

// Sample data - replace with your actual data fetching logic
const props = defineProps({
  attendanceRecords: {
    type: Array,
    default: () => []
  },
  pagination: {
    type: Object,
    default: null
  }
});

// Modal state
const showClockInModal = ref(false);
const showClockOutModal = ref(false);
const selectedRecord = ref(null);
const clockOutForm = ref({
  rating: 3,
  comment: ''
});

// Form submission state
const isSubmittingClockIn = ref(false);
const isSubmittingClockOut = ref(false);

// Format functions
function formatDate(dateString) {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleDateString();
}

function formatTime(timeString) {
  if (!timeString) return '—';
  const date = new Date(timeString);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDuration(minutes) {
  if (!minutes) return '—';
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hours}h ${mins}m`;
}

function formatStatus(status) {
  const statusMap = {
    'in_progress': 'In Progress',
    'completed': 'Completed',
    'missed': 'Missed'
  };
  return statusMap[status] || status;
}

// Modal handlers
function openClockInModal() {
  showClockInModal.value = true;
}

function openClockOutModal(record) {
  selectedRecord.value = record;
  clockOutForm.value = {
    rating: 3,
    comment: ''
  };
  showClockOutModal.value = true;
}

function viewDetails(record) {
  // Implement view details functionality
  console.log('View details for record:', record);
}

// Form submission handlers
function handleClockIn(data) {
  isSubmittingClockIn.value = true;
  
  // Replace with your actual API call
  setTimeout(() => {
    console.log('Clock in data:', data);
    isSubmittingClockIn.value = false;
    showClockInModal.value = false;
    // Refresh data or add new record to the list
  }, 1000);
}

function handleClockOut(data) {
  if (!selectedRecord.value) return;
  
  isSubmittingClockOut.value = true;
  
  // Replace with your actual API call
  setTimeout(() => {
    console.log('Clock out data for record:', selectedRecord.value.id, data);
    isSubmittingClockOut.value = false;
    showClockOutModal.value = false;
    selectedRecord.value = null;
    // Refresh data or update the record in the list
  }, 1000);
}
</script>
  