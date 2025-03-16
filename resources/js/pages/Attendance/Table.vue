<template>
  <AttendanceLayout title="My Attendance Records">
    <template #actions>
      <Link 
        href="/attendance/clock" 
        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
      >
        Clock In/Out
      </Link>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Mobile View (Card Layout) -->
      <div class="md:hidden space-y-4 p-4">
        <div v-if="isLoading" class="animate-pulse space-y-4">
          <div class="h-24 bg-gray-200 rounded"></div>
          <div class="h-24 bg-gray-200 rounded"></div>
        </div>
        <div v-else-if="clockings.data.length === 0" class="text-center py-8">
          <p class="text-sm text-gray-500">No attendance records found</p>
        </div>
        <div 
          v-else
          v-for="(record, i) in clockings.data" 
          :key="i" 
          class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm"
        >
          <div class="grid grid-cols-2 gap-3">
            <!-- Add course name at the top -->
            <div class="col-span-2 mb-2">
              <div class="text-xs text-gray-500">Course</div>
              <div class="text-sm font-medium text-gray-900">{{ record.course_name || 'General Attendance' }}</div>
            </div>
            
            <div>
              <div class="text-xs text-gray-500">Clock In</div>
              <div class="text-sm font-medium text-gray-900">{{ formatDate(record.clock_in) }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Clock Out</div>
              <div class="text-sm font-medium text-gray-900">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</div>
            </div>
            <!-- Duration in mobile view - Updated for better readability -->
            <div>
              <div class="text-xs text-gray-500">Duration</div>
              <div class="text-sm font-medium text-gray-900">
                <span v-if="record.clock_out">
                  {{ formatHumanDuration(record.duration_in_minutes) }}
                </span>
                <span v-else>
                  {{ formatHumanDuration(record.current_duration || 0) }} <span class="text-xs text-blue-500">(ongoing)</span>
                </span>
              </div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Rating</div>
              <div class="flex items-center text-sm font-medium text-gray-900">
                <div v-if="record.rating" class="flex items-center">
                  <span class="mr-1">{{ record.rating }}</span>
                  <div class="flex text-yellow-400">
                    <svg v-for="star in 5" :key="star" :class="[star <= record.rating ? 'text-yellow-400' : 'text-gray-300']" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                </div>
                <span v-else>—</span>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <div class="text-xs text-gray-500">Comment</div>
            <div class="text-sm font-medium text-gray-900">{{ record.comment || '—' }}</div>
          </div>
        </div>
      </div>

      <!-- Desktop View (Table Layout) -->
      <div class="hidden md:block">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <!-- Add Course column -->
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="isLoading" class="animate-pulse">
              <td colspan="6" class="px-6 py-4">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
              </td>
            </tr>
            <tr v-else-if="clockings.data.length === 0" class="text-center">
              <td colspan="6" class="px-6 py-4 text-sm text-gray-500">No attendance records found</td>
            </tr>
            <tr v-else v-for="(record, i) in clockings.data" :key="i" class="hover:bg-gray-50">
              <!-- Add Course column data -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ record.course_name || 'General Attendance' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(record.clock_in) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</td>
              
              <!-- Duration in desktop view - Updated for better readability -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span v-if="record.clock_out">
                  {{ formatHumanDuration(record.duration_in_minutes) }}
                </span>
                <span v-else>
                  {{ formatHumanDuration(record.current_duration || 0) }} <span class="text-xs text-blue-500">(ongoing)</span>
                </span>
              </td>
              
              <!-- Fixed: Changed div to td for the rating column -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div v-if="record.rating" class="flex items-center">
                  <span class="mr-1">{{ record.rating }}</span>
                  <div class="flex">
                    <svg v-for="star in 5" :key="star" :class="[star <= record.rating ? 'text-yellow-400' : 'text-gray-300']" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                </div>
                <span v-else>—</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ record.comment || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200">
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
  </AttendanceLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AttendanceLayout from '@/layouts/AttendanceLayout.vue'
import { useAttendance } from '@/composables/useAttendance'

interface ClockingData {
  clock_in: string;
  clock_out: string | null;
  duration_in_minutes: number | null;
  current_duration?: number;
  rating: number | null;
  comment: string | null;
  course_name?: string | null;
}

interface ClockingsPagination {
  data: ClockingData[];
  current_page: number;
  last_page: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}

const props = defineProps({
  clockings: {
    type: Object as () => ClockingsPagination,
    required: true,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
      prev_page_url: null,
      next_page_url: null
    })
  }
})

const { isLoading, formatDate, formatDuration } = useAttendance(props.clockings.data)

// Custom function to format duration in a more human-readable way
function formatHumanDuration(minutes: number | null | undefined): string {
  // Handle invalid or negative values
  if (!minutes || isNaN(minutes) || minutes < 0) return '0 minutes';
  
  // Round to nearest integer to avoid decimal values
  minutes = Math.round(minutes);
  
  const hours: number = Math.floor(minutes / 60);
  const mins: number = minutes % 60;
  
  if (hours > 0) {
    return `${hours} ${hours === 1 ? 'hour' : 'hours'}${mins > 0 ? ` ${mins} ${mins === 1 ? 'minute' : 'minutes'}` : ''}`;
  }
  return `${mins} ${mins === 1 ? 'minute' : 'minutes'}`;
}
</script>