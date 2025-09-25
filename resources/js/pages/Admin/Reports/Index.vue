<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <!-- Header -->
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
                    <a
                        :href="route('admin.reports.quiz-attempts')"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition text-center"
                    >
                        Quiz Attempts
                    </a>
                </div>
            </div>

            <!-- Enhanced Filters Section -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md mb-4 sm:mb-6 border border-gray-100">
                <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
                    </svg>
                    Global Filters
                    <span v-if="hasActiveFilters" class="ml-2 px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                        {{ activeFilterCount }} active
                    </span>
                </h2>
                <form @submit.prevent="applyFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            id="date_from"
                            v-model="filters.date_from"
                            type="date"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            id="date_to"
                            v-model="filters.date_to"
                            type="date"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select
                            id="user_id"
                            v-model="filters.user_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Users</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select
                            id="course_id"
                            v-model="filters.course_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Courses</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="quiz_id" class="block text-sm font-medium text-gray-700 mb-1">Quiz</label>
                        <select
                            id="quiz_id"
                            v-model="filters.quiz_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Quizzes</option>
                            <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">
                                {{ quiz.title }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2 sm:col-span-2 lg:col-span-5">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 text-sm font-medium transition-colors duration-200"
                        >
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            @click="clearFilters"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-hidden focus:ring-2 focus:ring-gray-300 text-sm font-medium transition-colors duration-200"
                        >
                            Clear Filters
                        </button>
                        <div v-if="hasActiveFilters" class="text-sm text-gray-600 ml-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Filters applied to all sections
                        </div>
                    </div>
                </form>
            </div>


            <!-- Summary Cards -->
            <div v-if="analytics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-6">
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
                            <p class="text-3xl font-bold text-indigo-700">{{ analytics.users && analytics.users.total ? analytics.users.total : 0 }}</p>
                            <p class="text-sm text-gray-600">Total Users</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-semibold">{{ analytics.users && analytics.users.active ? analytics.users.active : 0 }}</p>
                            <p class="text-sm text-gray-600">Active (30d)</p>
                            <p class="text-sm font-medium px-2 py-0.5 rounded-full inline-block mt-1"
                               :class="(analytics.users && analytics.users.active_percentage ? analytics.users.active_percentage : 0) >= 50 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                {{ analytics.users && analytics.users.active_percentage ? analytics.users.active_percentage : 0 }}% Active
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
                            <p class="text-3xl font-bold text-indigo-700">{{ analytics.courses && analytics.courses.total ? analytics.courses.total : 0 }}</p>
                            <p class="text-sm text-gray-600">Total Courses</p>
                        </div>
                        <div class="text-right">
                            <div class="flex flex-col space-y-1">
                                <div class="flex items-center justify-end">
                                    <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                                    <p class="text-xl font-semibold">{{ analytics.courses && analytics.courses.active ? analytics.courses.active : 0 }}</p>
                                </div>
                                <p class="text-sm text-gray-600">Active</p>

                                <div class="flex items-center justify-end mt-1">
                                    <span class="inline-block w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                                    <p class="text-xl font-semibold">{{ analytics.courses && analytics.courses.completed ? analytics.courses.completed : 0 }}</p>
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
                            <p class="text-3xl font-bold text-indigo-700">{{ analytics.registrations && analytics.registrations.total ? analytics.registrations.total : 0 }}</p>
                            <p class="text-sm text-gray-600">Total Registrations</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-semibold">{{ analytics.registrations && analytics.registrations.completed ? analytics.registrations.completed : 0 }}</p>
                            <p class="text-sm text-gray-600">Completed</p>
                            <p class="text-sm font-medium px-2 py-0.5 rounded-full inline-block mt-1"
                               :class="(analytics.registrations && analytics.registrations.completion_rate ? analytics.registrations.completion_rate : 0) >= 50 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                {{ analytics.registrations && analytics.registrations.completion_rate ? analytics.registrations.completion_rate : 0 }}% Completion
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
                            <p class="text-3xl font-bold text-indigo-700">{{ analytics.attendance && analytics.attendance.total_clockings ? analytics.attendance.total_clockings : 0 }}</p>
                            <p class="text-sm text-gray-600">Total Clockings</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-semibold">{{ analytics.attendance && analytics.attendance.average_duration ? analytics.attendance.average_duration : 0 }} min</p>
                            <p class="text-sm text-gray-600">Avg. Duration</p>
                            <div class="flex items-center justify-end mt-2">
                                <span class="text-sm font-medium mr-1">Rating:</span>
                                <div class="flex">
                                    <svg v-for="i in 5" :key="i" class="h-4 w-4"
                                         :class="i <= Math.round(analytics.attendance && analytics.attendance.average_rating ? analytics.attendance.average_rating : 0) ? 'text-yellow-400' : 'text-gray-300'"
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
            <div v-if="analytics && analytics.trends" class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                <!-- Monthly Attendance Chart -->
                <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Monthly Attendance
                    </h2>
                    <div class="h-64 sm:h-72">
                        <BarChart v-if="monthlyAttendanceData.labels.length > 0" :chart-data="monthlyAttendanceData" />
                        <div v-else class="flex items-center justify-center h-full text-gray-500">
                            No attendance data available
                        </div>
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
                        <div v-if="!analytics.trends.popular_courses || !analytics.trends.popular_courses.length" class="flex items-center justify-center h-full text-gray-500 flex-col">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            No course data available
                        </div>
                        <DoughnutChart
                            v-else-if="popularCoursesData.labels.length > 0"
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
            <!-- Enhanced Quiz Analytics Card -->
            <div v-if="analytics && analytics.quiz" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Quiz Analytics
                    </h2>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full font-medium">
                            {{ analytics.quiz.pass_rate || 0 }}% Pass Rate
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="bg-indigo-50 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-indigo-700 mb-1">{{ analytics.quiz.total_attempts || 0 }}</p>
                        <p class="text-sm text-gray-600">Total Attempts</p>
                    </div>

                    <div class="text-center">
                        <div class="bg-green-50 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-green-600 mb-1">{{ analytics.quiz.passed_attempts || 0 }}</p>
                        <p class="text-sm text-gray-600">Passed</p>
                    </div>

                    <div class="text-center">
                        <div class="bg-red-50 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-red-600 mb-1">{{ analytics.quiz.failed_attempts || 0 }}</p>
                        <p class="text-sm text-gray-600">Failed</p>
                    </div>

                    <div class="text-center">
                        <div class="bg-blue-50 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-blue-600 mb-1">{{ analytics.quiz.average_score || 0 }}%</p>
                        <p class="text-sm text-gray-600">Average Score</p>
                    </div>
                </div>


                <!-- Top Quizzes -->
                <div v-if="analytics.quiz.top_quizzes && analytics.quiz.top_quizzes.length > 0" class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Top Performing Quizzes</h3>
                    <div class="space-y-3">
                        <div v-for="quiz in analytics.quiz.top_quizzes.slice(0, 3)" :key="quiz.quiz_title" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ quiz.quiz_title }}</p>
                                <p class="text-sm text-gray-600">{{ quiz.attempt_count }} attempts</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-indigo-600">{{ quiz.avg_score }}% avg</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Links -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Detailed Reports</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
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

                    <a
                        :href="route('admin.reports.quiz-attempts')"
                        class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center text-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="font-medium">Quiz Attempts</h3>
                        <p class="text-sm text-gray-500">View and export quiz attempt data</p>
                    </a>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!analytics" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-500">Loading analytics...</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import BarChart from '@/components/Charts/BarChart.vue'
import DoughnutChart from '@/components/Charts/DoughnutChart.vue'

const props = defineProps({
    analytics: Object,
    courses: Array,
    quizzes: Array,
    users: Array,
    filters: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') }
]

// Filter state
const filters = ref({
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    user_id: props.filters?.user_id || '',
    course_id: props.filters?.course_id || '',
    quiz_id: props.filters?.quiz_id || '',
})

// Computed properties for filter status
const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '' && value !== null)
})

const activeFilterCount = computed(() => {
    return Object.values(filters.value).filter(value => value !== '' && value !== null).length
})

// Apply filters function
const applyFilters = () => {
    router.get(route('admin.reports.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Clear filters function
const clearFilters = () => {
    filters.value = {
        date_from: '',
        date_to: '',
        user_id: '',
        course_id: '',
        quiz_id: '',
    }
    applyFilters()
}

// Quiz trends chart data
const quizTrendsData = computed(() => {
    if (!props.analytics?.quiz?.monthly_trends) {
        return {
            labels: [],
            datasets: [{
                label: 'Quiz Attempts',
                backgroundColor: '#8B5CF6',
                data: []
            }]
        }
    }

    const trends = props.analytics.quiz.monthly_trends
    const labels = Object.keys(trends).map(month => {
        const [year, monthNum] = month.split('-')
        return new Date(parseInt(year), parseInt(monthNum) - 1).toLocaleString('default', { month: 'short', year: 'numeric' })
    })

    const data = Object.values(trends)

    return {
        labels,
        datasets: [
            {
                label: 'Quiz Attempts',
                backgroundColor: '#8B5CF6',
                data
            }
        ]
    }
})

// Prepare chart data for monthly attendance
const monthlyAttendanceData = computed(() => {
    if (!props.analytics?.trends?.monthly_attendance) {
        return {
            labels: [],
            datasets: [{
                label: 'Attendance Records',
                backgroundColor: '#4F46E5',
                data: []
            }]
        }
    }

    const labels = Object.keys(props.analytics.trends.monthly_attendance).map(month => {
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
    if (!props.analytics?.trends?.popular_courses || !Array.isArray(props.analytics.trends.popular_courses) || props.analytics.trends.popular_courses.length === 0) {
        return {
            labels: [],
            datasets: [{
                backgroundColor: [],
                data: []
            }]
        }
    }

    const courses = props.analytics.trends.popular_courses
    const names = courses.map(course => course.name || 'Unknown Course')
    const counts = courses.map(course => parseInt(course.registrations || 0))
    const colors = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#0EA5E9', '#14B8A6']

    return {
        labels: names,
        datasets: [{
            backgroundColor: colors.slice(0, names.length),
            data: counts
        }]
    }
})
</script>
