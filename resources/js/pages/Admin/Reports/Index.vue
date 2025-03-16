<script setup lang="ts">
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import BarChart from '@/components/Charts/BarChart.vue'
import DoughnutChart from '@/components/Charts/DoughnutChart.vue'

const props = defineProps({
  analytics: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Reports & Analytics', href: route('admin.reports.index') }
]

// Prepare chart data for monthly attendance
const monthlyAttendanceData = computed(() => {
  const labels = Object.keys(props.analytics.trends.monthly_attendance || {}).map(month => {
    const [year, monthNum] = month.split('-')
    return new Date(parseInt(year), parseInt(monthNum) - 1).toLocaleString('default', { month: 'short', year: 'numeric' })
  })
  
  const data = Object.values(props.analytics.trends.monthly_attendance || {})
  
  return {
    labels,
    datasets: [
      {
        label: 'Attendance Records',
        backgroundColor: '#4F46E5',
        data
      }
    ]
  }
})

// Prepare chart data for popular courses
const popularCoursesData = computed(() => {
  // Check if the analytics object and trends exist
  if (!props.analytics?.trends?.popular_courses || !Array.isArray(props.analytics.trends.popular_courses) || props.analytics.trends.popular_courses.length === 0) {
    console.log('No popular courses data available');
    return {
      labels: [],
      datasets: [{
        backgroundColor: [],
        data: []
      }]
    };
  }
  
  const courses = props.analytics.trends.popular_courses;
  console.log('Popular courses data:', courses);
  
  // Extract names and counts
  const names = courses.map(course => course.name || 'Unknown Course');
  const counts = courses.map(course => parseInt(course.registrations || 0));
  const colors = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#0EA5E9', '#14B8A6'];
  
  console.log('Chart labels:', names);
  console.log('Chart data:', counts);
  
  return {
    labels: names,
    datasets: [{
      backgroundColor: colors.slice(0, names.length),
      data: counts
    }]
  };
});
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 sm:px-0">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold">Reports & Analytics</h1>
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
          <a 
            :href="route('admin.reports.course-registrations')" 
            class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center"
          >
            Course Registrations
          </a>
          <a 
            :href="route('admin.reports.attendance')" 
            class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center"
          >
            Attendance Records
          </a>
          <a 
            :href="route('admin.reports.course-completion')" 
            class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center"
          >
            Course Completion
          </a>
        </div>
      </div>
      
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-6">
        <!-- Users Card -->
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
          <h2 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Users
          </h2>
          <div class="flex justify-between items-end">
            <div>
              <p class="text-3xl font-bold text-indigo-700">{{ analytics.users.total }}</p>
              <p class="text-sm text-gray-600">Total Users</p>
            </div>
            <div class="text-right">
              <p class="text-xl font-semibold">{{ analytics.users.active }}</p>
              <p class="text-sm text-gray-600">Active (30d)</p>
              <p class="text-sm font-medium px-2 py-0.5 rounded-full inline-block mt-1" 
                :class="analytics.users.active_percentage >= 50 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                {{ analytics.users.active_percentage }}% Active
              </p>
            </div>
          </div>
        </div>
        
        <!-- Courses Card -->
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
          <h2 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Courses
          </h2>
          <div class="flex justify-between items-end">
            <div>
              <p class="text-3xl font-bold text-indigo-700">{{ analytics.courses.total }}</p>
              <p class="text-sm text-gray-600">Total Courses</p>
            </div>
            <div class="text-right">
              <div class="flex flex-col space-y-1">
                <div class="flex items-center justify-end">
                  <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                  <p class="text-xl font-semibold">{{ analytics.courses.active }}</p>
                </div>
                <p class="text-sm text-gray-600">Active</p>
                
                <div class="flex items-center justify-end mt-1">
                  <span class="inline-block w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                  <p class="text-xl font-semibold">{{ analytics.courses.completed }}</p>
                </div>
                <p class="text-sm text-gray-600">Completed</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Registrations Card -->
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
          <h2 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Registrations
          </h2>
          <div class="flex justify-between items-end">
            <div>
              <p class="text-3xl font-bold text-indigo-700">{{ analytics.registrations.total }}</p>
              <p class="text-sm text-gray-600">Total Registrations</p>
            </div>
            <div class="text-right">
              <p class="text-xl font-semibold">{{ analytics.registrations.completed }}</p>
              <p class="text-sm text-gray-600">Completed</p>
              <p class="text-sm font-medium px-2 py-0.5 rounded-full inline-block mt-1" 
                :class="analytics.registrations.completion_rate >= 50 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                {{ analytics.registrations.completion_rate }}% Completion
              </p>
            </div>
          </div>
        </div>
        
        <!-- Attendance Card -->
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
          <h2 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Attendance
          </h2>
          <div class="flex justify-between items-end">
            <div>
              <p class="text-3xl font-bold text-indigo-700">{{ analytics.attendance.total_clockings }}</p>
              <p class="text-sm text-gray-600">Total Clockings</p>
            </div>
            <div class="text-right">
              <p class="text-xl font-semibold">{{ analytics.attendance.average_duration }} min</p>
              <p class="text-sm text-gray-600">Avg. Duration</p>
              <div class="flex items-center justify-end mt-2">
                <span class="text-sm font-medium mr-1">Rating:</span>
                <div class="flex">
                  <svg v-for="i in 5" :key="i" class="h-4 w-4" 
                    :class="i <= Math.round(analytics.attendance.average_rating) ? 'text-yellow-400' : 'text-gray-300'" 
                    fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
        <!-- Monthly Attendance Chart -->
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
          <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Monthly Attendance
          </h2>
          <div class="h-64 sm:h-72">
            <BarChart :chart-data="monthlyAttendanceData" />
          </div>
        </div>
        
        <!-- Popular Courses Chart -->
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
          <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
            Popular Courses
          </h2>
          <div class="h-64 sm:h-72">
            <div v-if="!props.analytics?.trends?.popular_courses?.length" class="flex items-center justify-center h-full text-gray-500 flex-col">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              No course data available
            </div>
            <DoughnutChart 
              v-if="popularCoursesData.labels.length > 0"
              :chart-data="popularCoursesData" 
              :chart-options="{
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                  legend: {
                    position: 'right',
                    display: true,
                    labels: {
                      boxWidth: 15,
                      font: {
                        size: 12
                      },
                      color: '#4B5563'
                    }
                  },
                  tooltip: {
                    callbacks: {
                      label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        return `${label}: ${value} registrations`;
                      }
                    }
                  }
                }
              }"
            />
          </div>
        </div>
      </div>
      
      <!-- Report Links -->
      <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Detailed Reports</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <a 
            :href="route('admin.reports.course-registrations')" 
            class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center text-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="font-medium">Course Registrations</h3>
            <p class="text-sm text-gray-500">View and export course registration data</p>
          </a>
          
          <a 
            :href="route('admin.reports.attendance')" 
            class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center text-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="font-medium">Attendance Records</h3>
            <p class="text-sm text-gray-500">View and export attendance data</p>
          </a>
          
          <a 
            :href="route('admin.reports.course-completion')" 
            class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center text-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="font-medium">Course Completion</h3>
            <p class="text-sm text-gray-500">View and export course completion data</p>
          </a>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>