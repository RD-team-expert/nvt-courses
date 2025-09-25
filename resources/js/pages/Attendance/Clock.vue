<template>
  <AttendanceLayout title="Attendance Clock">
    <template #actions>
      <Link 
        href="/attendance" 
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
      >
        View Records
      </Link>
    </template>

    <div class="flex h-full flex-1 flex-col gap-4 p-0 md:p-4">
      <!-- Top Row - Clock and Status -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-2 max-w-5xl mx-auto w-full">
        <!-- Digital Clock Card -->
        <div class="flex flex-col items-center justify-center rounded-xl border border-sidebar-border/70 p-6 bg-white dark:bg-dark-800 shadow-sm">
          <div class="text-5xl md:text-6xl font-mono font-bold text-gray-800 dark:text-dark-50 transition-all duration-300 transform hover:scale-105">
            {{ currentTime }}
          </div>
          <div class="mt-4 text-center text-gray-600 dark:text-dark-300">
            {{ currentDate }}
          </div>
        </div>

        <!-- Status Card -->
        <div class="flex flex-col rounded-xl border border-sidebar-border/70 p-6 bg-white dark:bg-dark-800 shadow-sm">
          <h2 class="text-xl font-medium text-gray-900 dark:text-dark-50 mb-4 text-center">Current Status</h2>
          <div class="flex items-center justify-center mb-4">
            <div :class="[
              'h-3 w-3 rounded-full mr-2', 
              activeSession ? 'bg-green-500 animate-pulse' : 'bg-gray-400'
            ]"></div>
            <span :class="[
              'font-medium',
              activeSession ? 'text-green-600' : 'text-gray-600'
            ]">
              {{ activeSession ? 'Clocked In' : 'Clocked Out' }}
            </span>
          </div>
          
          <div v-if="activeSession" class="text-sm text-gray-500 mb-2 text-center">
            Clocked in at: {{ formatDate(activeSessionData.clock_in) }}
          </div>
          
          <!-- Add course name if available -->
          <div v-if="activeSession && activeSessionData.course" class="text-sm text-gray-500 mb-2 text-center">
            Course: {{ activeSessionData.course.name }}
          </div>
          
          <!-- Add duration for active session -->
          <div v-if="activeSession" class="text-sm text-gray-500 mb-4 text-center">
            Duration: {{ formatDuration(activeSessionData.current_duration || 0) }}
          </div>
          
          <div class="mt-auto">
            <button
              v-if="!activeSession"
              @click="openClockInDialog"
              class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="mr-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              Clock In
            </button>
            <button
              v-else
              @click="openClockOutDialog"
              class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
              :disabled="isLoading"
            >
              Clock Out
            </button>
          </div>
        </div>
      </div>

      <!-- Recent Activity Section -->
      <div v-if="clockings.data && clockings.data.length > 0" class="relative flex-1 rounded-lg border border-gray-200 p-6 bg-white shadow-sm max-w-5xl mx-auto w-full">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Recent Activity</h3>
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <div 
            v-for="(record, i) in clockings.data" 
            :key="i" 
            class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm"
          >
            <div class="flex justify-between items-start mb-2">
              <div class="text-sm font-medium text-gray-900">
                {{ formatDateShort(record.clock_in) }}
              </div>
              <span 
                :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  record.clock_out ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                ]"
              >
                {{ record.clock_out ? 'Completed' : 'In Progress' }}
              </span>
            </div>
            
            <!-- Course name if available -->
            <div v-if="record.course_name" class="mt-2 text-sm text-gray-700">
              <div class="flex items-center">
                <svg class="h-4 w-4 text-gray-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13c-1.168-.776-2.754-1.253-4.5-1.253s-3.332.477-4.5 1.253v13c-1.168-.776-2.754-1.253-4.5-1.253s-3.332.477-4.5 1.253"></path>
                </svg>
                <span>{{ record.course_name }}</span>
              </div>
            </div>
            
            <!-- Duration information -->
            <div class="mt-2 text-sm text-gray-700">
              <div class="flex items-center">
                <svg class="h-4 w-4 text-gray-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>
                  <span v-if="record.clock_out">
                    {{ formatDuration(record.duration_in_minutes) }}
                  </span>
                  <span v-else>
                    {{ formatDuration(record.current_duration || 0) }} (ongoing)
                  </span>
                </span>
              </div>
            </div>
            
            <!-- Rating if available -->
            <div v-if="record.rating" class="mt-2 flex items-center">
              <div class="flex text-yellow-400">
                <svg v-for="star in 5" :key="star" :class="[star <= record.rating ? 'text-yellow-400' : 'text-gray-300']" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div class="mt-6">
          <div class="flex justify-center">
            <nav class="flex items-center space-x-2">
              <Link 
                :href="clockings.prev_page_url" 
                :class="[
                  'px-3 py-1 rounded border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition',
                  !clockings.prev_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                ]"
              >
                Previous
              </Link>
              <span class="text-sm text-gray-700">
                Page {{ clockings.current_page }} of {{ clockings.last_page }}
              </span>
              <Link 
                :href="clockings.next_page_url" 
                :class="[
                  'px-3 py-1 rounded border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition',
                  !clockings.next_page_url && 'opacity-50 cursor-not-allowed pointer-events-none'
                ]"
              >
                Next
              </Link>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Clock In Modal -->
    <ClockInModal
      :show="showClockInModal"
      :courses="userCourses"
      :is-submitting="isSubmitting"
      @close="showClockInModal = false"
      @submit="submitClockIn"
    />

    <!-- Clock Out Modal -->
    <ClockOutModal
      :show="showClockOutModal"
      :initial-form="clockOutForm"
      :is-submitting="isSubmitting"
      @close="showClockOutModal = false"
      @submit="submitClockOut"
    />
  </AttendanceLayout>
</template>
  
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AttendanceLayout from '@/layouts/AttendanceLayout.vue';
import ClockOutModal from '@/components/attendance/ClockOutModal.vue';
import ClockInModal from '@/components/attendance/ClockInModal.vue';

const props = defineProps({
  clockings: {
    type: Object,
    required: true,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
      prev_page_url: null,
      next_page_url: null
    })
  },
  activeSession: {
    type: Object,
    default: null
  },
  userCourses: {
    type: Array,
    default: () => []
  }
});

// State variables
const isLoading = ref(false);
const isSubmitting = ref(false);
const showClockOutModal = ref(false);
const showClockInModal = ref(false);
const clockOutForm = ref({
  rating: 3, // Default to middle rating
  comment: ''
});
const currentTime = ref('');
const currentDate = ref('');
let clockTimer = null;

// Computed properties
const activeSession = computed(() => {
  return props.activeSession !== null;
});

const activeSessionData = computed(() => {
  return props.activeSession || {};
});

// Methods
function updateClock() {
  const now = new Date();
  
  // Format time as HH:MM:SS
  currentTime.value = now.toLocaleTimeString('en-US', { 
    hour12: true,
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
  
  // Format date as Day, Month Date, Year
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function formatDate(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString();
}

function formatDateShort(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString('en-US', {
    hour12: false,
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatDuration(minutes) {
  if (!minutes || minutes < 0) return '0 minutes';
  
  const hours = Math.floor(minutes / 60);
  const mins = Math.floor(minutes % 60);
  
  if (hours > 0) {
    return `${hours}h ${mins}m`;
  }
  return `${mins}m`;
}

function openClockInDialog() {
  showClockInModal.value = true;
}

async function submitClockIn(formData) {
  try {
    isSubmitting.value = true;
    router.post('/clock-in', {
      course_id: formData.course_id
    }, {
      onSuccess: () => {
        console.log('Clock in successful');
        showClockInModal.value = false;
      },
      onError: (errors) => {
        console.error('Clock in failed:', errors);
        alert('Failed to clock in. Please try again.');
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  } catch (error) {
    console.error('Clock in exception:', error);
    isSubmitting.value = false;
  }
}

function openClockOutDialog() {
  showClockOutModal.value = true;
  clockOutForm.value = {
    rating: null,
    comment: ''
  };
}

async function submitClockOut(formData) {
  if (!formData.rating) {
    alert('Please provide a rating');
    return;
  }

  try {
    isSubmitting.value = true;
    router.post('/clock-out', {
      rating: formData.rating,
      comment: formData.comment
    }, {
      onSuccess: () => {
        console.log('Clock out successful');
        showClockOutModal.value = false;
      },
      onError: (errors) => {
        console.error('Clock out failed:', errors);
        alert('Failed to clock out. Please try again.');
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  } catch (error) {
    console.error('Clock out exception:', error);
    isSubmitting.value = false;
  }
}

// Helper function to format time
function formatTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Lifecycle hooks
onMounted(() => {
  updateClock();
  clockTimer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
  if (clockTimer) {
    clearInterval(clockTimer);
  }
});
</script>