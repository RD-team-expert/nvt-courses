<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const currentUrl = computed(() => page.url);

// Define breadcrumbs for admin section
const breadcrumbs = [
  { name: 'Admin', href: '/admin' }
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Admin navigation -->
        <div class="mb-6 border-b border-gray-200">
          <div class="flex space-x-8">
            <Link
              href="/admin/courses"
              class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
              :class="{ 'border-blue-500 text-gray-900': currentUrl.includes('/admin/courses') }"
            >
              Courses
            </Link>
              <Link
              href="/admin/quizzes"
              class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
              :class="{ 'border-blue-500 text-gray-900': currentUrl.includes('/admin/quizzes') }"
            >
                  Quizzes
            </Link>
            <Link
              href="/admin/users"
              class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
              :class="{ 'border-blue-500 text-gray-900': currentUrl.includes('/admin/users') }"
            >
              Users
            </Link>


            <Link
              href="/admin/attendance"
              class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
              :class="{ 'border-blue-500 text-gray-900': currentUrl.includes('/admin/attendance') }"
            >
              Attendance
            </Link>

            <Link
              href="/admin/reports"
              class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
              :class="{ 'border-blue-500 text-gray-900': currentUrl.includes('/admin/reports') }"
            >
              Reports
            </Link>

              <!-- ✅ NEW: Organizational Management Tabs -->
              <Link
                  href="/admin/departments"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                  :class="{ 'border-blue-500 text-blue-600': currentUrl.includes('/admin/departments') }"
              >
                  Departments
              </Link>

              <Link
                  href="/admin/user-levels"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                  :class="{ 'border-blue-500 text-blue-600': currentUrl.includes('/admin/user-levels') }"
              >
                  User Levels
              </Link>

              <Link
                  href="/admin/manager-roles"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                  :class="{ 'border-blue-500 text-blue-600': currentUrl.includes('/admin/manager-roles') }"
              >
                  Manager Roles
              </Link>

              <Link
                  href="/admin/users/assignment"
                  class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                  :class="{ 'border-blue-500 text-blue-600': currentUrl.includes('/admin/users/assignment') }"
              >
                  User Assignment
              </Link>

          </div>
        </div>

        <!-- Main content -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
