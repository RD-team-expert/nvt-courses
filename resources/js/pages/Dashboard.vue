<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { BarChart, BookOpenCheck, Clock, Users } from 'lucide-vue-next';

// Get current user from page props
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.is_admin === true || user.value?.role === 'admin');

// Get dashboard data from props
const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalCourses: 0,
      totalUsers: 0,
      activeSessions: 0,
      completionRate: 0,
      pendingCourses: 0,
      completedCourses: 0,
      userCourses: 0,
      userAttendanceRate: 0,
      userCompletedCourses: 0,
      userTotalCourses: 0,
      upcomingSessions: 0
    })
  },
  recentActivity: {
    type: Array,
    default: () => []
  },
  userCourses: {
    type: Array,
    default: () => []
  },
  userAttendance: {
    type: Array,
    default: () => []
  }
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        name: 'Dashboard',
        href: '/dashboard',
    },
];

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString();
};

// Format time ago for activity feed
const timeAgo = (dateString) => {
  if (!dateString) return '';

  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);

  if (diffInSeconds < 60) {
    return 'just now';
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours} hour${hours > 1 ? 's' : ''} ago`;
  } else {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} day${days > 1 ? 's' : ''} ago`;
  }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
            <!-- Welcome Section -->
            <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 sm:p-6 shadow-sm">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">
                    Welcome back, {{ user?.name }}!
                </h1>
                <p class="text-gray-600">
                    {{ isAdmin ? 'Manage your courses and users from your admin dashboard.' : 'Track your courses and attendance from your dashboard.' }}
                </p>
            </div>

            <!-- Admin Dashboard -->
            <div v-if="isAdmin" class="space-y-6">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <BookOpenCheck class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Courses</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.totalCourses || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <Users class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Users</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.totalUsers || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <Clock class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Active Sessions</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.activeSessions || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                <BarChart class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Completion Rate</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.completionRate || 0 }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions section remains unchanged -->

                <!-- Update the Recent Activity section in Dashboard.vue -->
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 sm:p-6 shadow-sm">
                  <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Recent Activity</h2>
                    <Link href="/activities" class="text-sm text-blue-600 hover:underline">View all</Link>
                  </div>
                  <div class="space-y-3">
                    <div v-if="recentActivity.length === 0" class="text-center py-8">
                      <Clock class="h-12 w-12 mx-auto text-gray-400 mb-2" />
                      <h3 class="text-base font-medium text-gray-900">No recent activity</h3>
                      <p class="text-sm text-gray-500">Activity will appear here as users interact with the system</p>
                    </div>
                    <div
                      v-for="(activity, index) in recentActivity"
                      :key="activity.id || index"
                      class="flex items-start p-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                      <div class="w-10 h-10 rounded-full bg-gray-200 shrink-0 mr-3 overflow-hidden">
                        <img v-if="activity.user?.avatar" :src="activity.user.avatar" class="w-full h-full object-cover" alt="User avatar" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-500 font-medium text-sm">
                          {{ activity.user?.name ? activity.user.name.charAt(0).toUpperCase() : 'S' }}
                        </div>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 break-words">{{ activity.description }}</p>
                        <div class="flex items-center mt-1">
                          <span
                            v-if="activity.action"
                            class="px-2 py-0.5 text-xs rounded-full mr-2"
                            :class="{
                              'bg-blue-100 text-blue-800': activity.action === 'create',
                              'bg-green-100 text-green-800': activity.action === 'update',
                              'bg-red-100 text-red-800': activity.action === 'delete',
                              'bg-purple-100 text-purple-800': activity.action === 'enroll',
                              'bg-yellow-100 text-yellow-800': activity.action === 'login',
                            }"
                          >
                            {{ activity.action }}
                          </span>
                          <span class="text-xs text-gray-500">{{ timeAgo(activity.created_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>

            <!-- User Dashboard -->
            <div v-else class="space-y-6">
                <!-- User Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <BookOpenCheck class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">My Courses</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.userCourses || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <Clock class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Attendance Rate</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.userAttendanceRate || 0 }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                <BarChart class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Completed</p>
                                <p class="text-2xl font-bold text-gray-800">
                                    {{ stats.userCompletedCourses || 0 }}/{{ stats.userTotalCourses || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Courses section in Dashboard.vue -->
                <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 sm:p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">My Courses</h2>
                        <Link href="/courses" class="text-sm text-blue-600 hover:underline">View all</Link>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-if="userCourses.length === 0" class="col-span-full text-center py-4 text-gray-500">
                            You are not enrolled in any courses yet
                        </div>
                        <div
                            v-for="(course, index) in userCourses"
                            :key="course.id || index"
                            class="border border-gray-200 rounded-lg overflow-hidden"
                        >
                            <div class="h-32 bg-gray-200">
                                <img
                                    v-if="course.image_path"
                                    :src="`/storage/${course.image_path}`"
                                    :alt="course.name"
                                    class="w-full h-full object-contain"
                                    @error="$event.target.src = '/images/course-placeholder.jpg'"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <BookOpenCheck class="h-10 w-10 text-gray-400" />
                                </div>
                            </div>
                            <div class="p-3">
                                <h3 class="font-medium">{{ course.name || 'Course Name' }}</h3>
                                <!-- Description removed as requested -->
                                <div class="flex justify-between items-center mt-2">
                                    <span
                                        class="text-xs px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-blue-100 text-blue-800': course.status === 'in_progress' || course.status === 'enrolled',
                                            'bg-green-100 text-green-800': course.status === 'completed',
                                            'bg-yellow-100 text-yellow-800': course.status === 'pending'
                                        }"
                                    >
                                        {{ course.status ? (course.status.charAt(0).toUpperCase() + course.status.slice(1)).replace('_', ' ') : 'Enrolled' }}
                                    </span>
                                    <Link :href="`/courses/${course.id}`" class="text-xs text-blue-600 hover:underline">
                                        Continue
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance -->
                <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 sm:p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Recent Attendance</h2>
                        <Link href="/attendance" class="text-sm text-blue-600 hover:underline">View all</Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="userAttendance.length === 0" class="text-center">
                                    <td colspan="3" class="px-4 py-8">
                                        <Clock class="h-12 w-12 mx-auto text-gray-400 mb-2" />
                                        <h3 class="text-base font-medium text-gray-900">No attendance records</h3>
                                        <p class="text-sm text-gray-500">Your attendance history will appear here</p>
                                    </td>
                                </tr>
                                <tr v-for="(record, index) in userAttendance" :key="record.id || index" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ formatDate(record.date) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ record.course_name || 'General Attendance' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800': record.status === 'completed',
                                                'bg-yellow-100 text-yellow-800': record.status === 'in_progress',
                                                'bg-blue-100 text-blue-800': record.status === 'present'
                                            }"
                                        >
                                            {{ record.status ? (record.status.charAt(0).toUpperCase() + record.status.slice(1)).replace('_', ' ') : 'Present' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Sessions -->
                <div v-if="stats.upcomingSessions > 0" class="bg-white rounded-xl border border-sidebar-border/70 p-4 sm:p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Upcoming Sessions</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ stats.upcomingSessions }} upcoming</span>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-sm text-blue-800">
                            You have {{ stats.upcomingSessions }} upcoming session{{ stats.upcomingSessions > 1 ? 's' : '' }}.
                            Check your course schedule for details.
                        </p>
                        <Link href="/courses" class="mt-2 inline-flex items-center text-sm font-medium text-blue-600 hover:underline">
                            View my courses
                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
