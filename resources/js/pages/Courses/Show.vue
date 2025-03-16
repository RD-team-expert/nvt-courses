<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
  course: Object,
  isEnrolled: Boolean,
  userStatus: String
})

// Create a form for enrollment
const enrollForm = useForm({})

// Function to handle enrollment
function enroll() {
  console.log('Enrolling in course:', props.course.id);
  enrollForm.post(route('courses.enroll', props.course.id), {
    onSuccess: () => {
      console.log('Enrollment successful');
      // Refresh the page using Inertia (smoother experience)
      router.reload({ only: ['isEnrolled', 'userStatus'] });
    },
    onError: (errors) => {
      console.error('Enrollment failed:', errors);
    }
  });
}

// Function to mark course as completed
const completeForm = useForm({})
function markCompleted() {
  completeForm.post(route('courses.markCompleted', props.course.id), {
    onSuccess: () => {
      console.log('Course marked as completed');
      // Refresh the page using Inertia
      router.reload({ only: ['userStatus'] });
    },
    onError: (errors) => {
      console.error('Failed to mark course as completed:', errors);
    }
  });
}

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
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Courses', href: route('courses.index') },
  { name: props.course.name, href: route('courses.show', props.course.id) }
]
</script>

<style>
.course-image-container {
  position: relative;
  padding-top: 56.25%; /* 16:9 Aspect Ratio */
  overflow: hidden;
  background-color: #f3f4f6;
}

.course-image-container img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.course-image-container:hover img {
  transform: scale(1.05);
}

.enrollment-card {
  transition: all 0.3s ease;
}

.enrollment-card:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.button-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200;
}

.button-success {
  @apply bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200;
}

.button-warning {
  @apply bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200;
}

@media (max-width: 768px) {
  .course-details-grid {
    grid-template-columns: 1fr;
  }
  
  .course-image-container {
    padding-top: 75%; /* 4:3 Aspect Ratio for mobile */
  }
}
</style>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container px-4 mx-auto py-6 sm:py-8 max-w-7xl">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ course.name }}</h1>
        <span 
          class="px-3 py-1.5 text-sm rounded-full shadow-sm transition-colors duration-200" 
          :class="{
            'bg-green-100 text-green-800': course.status === 'in_progress',
            'bg-yellow-100 text-yellow-800': course.status === 'pending',
            'bg-blue-100 text-blue-800': course.status === 'completed'
          }"
        >
          {{ formatStatus(course.status) }}
        </span>
      </div>

      <div class="course-details-grid grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        <!-- Course Details -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden transition-shadow duration-200 hover:shadow-md">
          <!-- Course Image -->
          <div class="course-image-container">
            <img 
              v-if="course.image_path" 
              :src="`/storage/${course.image_path}`" 
              :alt="course.name"
              class="w-full h-full object-cover"
              loading="lazy"
            >
            <div 
              v-else 
              class="absolute inset-0 flex items-center justify-center bg-gray-100"
            >
              <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          
          <div class="p-6 sm:p-8">
            <!-- Course description with proper HTML rendering -->
            <div class="prose max-w-none mb-8" v-html="course.description"></div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
              <div class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                <p class="text-gray-500 mb-1.5 font-medium">Start Date</p>
                <p class="font-semibold text-gray-900">{{ formatDate(course.start_date) }}</p>
              </div>
              <div class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                <p class="text-gray-500 mb-1.5 font-medium">End Date</p>
                <p class="font-semibold text-gray-900">{{ formatDate(course.end_date) }}</p>
              </div>
              <div v-if="course.level" class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                <p class="text-gray-500 mb-1.5 font-medium">Level</p>
                <p class="font-semibold text-gray-900 capitalize">{{ course.level }}</p>
              </div>
              <div v-if="course.duration" class="bg-gray-50 p-4 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                <p class="text-gray-500 mb-1.5 font-medium">Duration</p>
                <p class="font-semibold text-gray-900">{{ course.duration }} hours</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Enrollment Card -->
        <div class="enrollment-card bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="p-6 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-semibold mb-6 text-gray-800">Course Enrollment</h2>
            
            <div v-if="!isEnrolled" class="mb-6">
              <p class="text-gray-600 mb-6 leading-relaxed">Enroll in this course to track your progress and get access to course materials.</p>
              
              <button 
                @click="enroll" 
                class="button-primary w-full flex items-center justify-center gap-2"
                :disabled="enrollForm.processing"
              >
                <span v-if="enrollForm.processing">Enrolling...</span>
                <span v-else>Enroll Now</span>
                <svg v-if="!enrollForm.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
              </button>
            </div>
            
            <div v-else class="space-y-6">
              <div class="space-y-4">
                <div class="flex items-center p-4 bg-gray-50 rounded-lg transition-colors duration-200">
                  <div class="w-3 h-3 rounded-full mr-3" :class="{
                    'bg-green-500': userStatus === 'completed',
                    'bg-blue-500': userStatus === 'enrolled'
                  }"></div>
                  <span class="font-medium text-gray-800">Status: {{ userStatus === 'completed' ? 'Completed' : 'Enrolled' }}</span>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                  <p v-if="userStatus === 'enrolled'" class="text-gray-700 leading-relaxed">
                    You're currently enrolled in this course. Mark as completed when you've finished all the materials.
                  </p>
                  
                  <p v-else-if="userStatus === 'completed'" class="text-green-700 font-medium">
                    You've completed this course. Congratulations! 🎉
                  </p>
                </div>
              </div>
              
              <button 
                v-if="userStatus === 'enrolled'"
                @click="markCompleted" 
                class="button-success w-full flex items-center justify-center gap-2"
                :disabled="completeForm.processing"
              >
                <span v-if="completeForm.processing">Processing...</span>
                <span v-else>Mark as Completed</span>
                <svg v-if="!completeForm.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
              
              <div v-else-if="userStatus === 'completed'" class="space-y-4">
                <Link 
                  :href="route('courses.completion', course.id)"
                  class="button-warning w-full flex items-center justify-center gap-2"
                >
                  Rate This Course
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>
                </Link>
                
                <Link 
                  href="/courses" 
                  class="button-primary w-full flex items-center justify-center gap-2"
                >
                  Browse More Courses
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
