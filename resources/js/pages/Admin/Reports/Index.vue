<!--
  Reports & Analytics Dashboard Page
  Comprehensive analytics and reporting interface for tracking system performance
-->
<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Reports & Analytics</h1>
                    <p class="text-sm text-wrap max-w-11/12 text-muted-foreground mt-1">Comprehensive analytics and reporting interface for tracking system performance</p>
                </div>
            </div>

            <!-- ✅ UPDATED: Report Buttons with Course Online Dropdown -->
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                <Button :as="'a'" :href="route('admin.reports.course-registrations')" variant="outline" class="w-full sm:w-auto">
                    <BookOpen class="mr-2 h-4 w-4" />
                    Course Registrations
                </Button>
                <Button :as="'a'" :href="route('admin.reports.attendance')" variant="outline" class="w-full sm:w-auto">
                    <Clock class="mr-2 h-4 w-4" />
                    Attendance Records
                </Button>
                <Button :as="'a'" :href="route('admin.reports.course-completion')" variant="outline" class="w-full sm:w-auto">
                    <CheckCircle class="mr-2 h-4 w-4" />
                    Course Completion
                </Button>

                <!-- ✅ NEW: Course Online Dropdown -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="w-full sm:w-auto">
                            <Monitor class="mr-2 h-4 w-4" />
                           Online Courses
                            <ChevronDown class="ml-2 h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem as-child>
                            <Link :href="route('admin.reports.course-online.progress')" class="flex items-center">
                                <BarChart3 class="mr-2 h-4 w-4" />
                                Progress Report
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="route('admin.reports.course-online.learning-sessions')" class="flex items-center">
                                <Activity class="mr-2 h-4 w-4" />
                                Learning Sessions
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="route('admin.reports.course-online.user-performance')" class="flex items-center">
                                <Users class="mr-2 h-4 w-4" />
                                User Performance
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="route('admin.reports.course-online.department-performance')" class="flex items-center">
                                <Trophy class="mr-2 h-4 w-4" />
                                Department Performance
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link href="/admin/analytics/cheating-detection" class="flex items-center">
                                <Shield class="mr-2 h-4 w-4" />
                                Cheating Detection
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button :as="'a'" :href="route('admin.reports.quiz-attempts')" variant="outline" class="w-full sm:w-auto">
                    <FileText class="mr-2 h-4 w-4" />
                    Quiz Attempts
                </Button>
                <Button :as="'a'" :href="route('admin.reports.monthly-kpi')" variant="outline" class="w-full sm:w-auto">
                    <FileText class="mr-2 h-4 w-4" />
                    Monthly-kpi
                </Button>
            </div>

            <!-- Enhanced Filters Section -->
            <Card>
                <CardHeader>
                    <div class="flex items-center">
                        <Filter class="mr-2 h-5 w-5 text-primary" />
                        <div>
                            <CardTitle>Global Filters</CardTitle>
                            <CardDescription>
                                Filter data across all sections
                                <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2">
                                    {{ activeFilterCount }} active
                                </Badge>
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="space-y-2">
                            <Label for="date_from">From Date</Label>
                            <Input
                                id="date_from"
                                v-model="filters.date_from"
                                type="date"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="date_to">To Date</Label>
                            <Input
                                id="date_to"
                                v-model="filters.date_to"
                                type="date"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="user_id">User</Label>
                            <Select :model-value="filters.user_id || 'all'" @update:model-value="(value) => filters.user_id = value === 'all' ? '' : value">
                                <SelectTrigger id="user_id">
                                    <SelectValue placeholder="All Users" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Users</SelectItem>
                                    <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="course_id">Course</Label>
                            <Select :model-value="filters.course_id || 'all'" @update:model-value="(value) => filters.course_id = value === 'all' ? '' : value">
                                <SelectTrigger id="course_id">
                                    <SelectValue placeholder="All Courses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Courses</SelectItem>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="quiz_id">Quiz</Label>
                            <Select :model-value="filters.quiz_id || 'all'" @update:model-value="(value) => filters.quiz_id = value === 'all' ? '' : value">
                                <SelectTrigger id="quiz_id">
                                    <SelectValue placeholder="All Quizzes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Quizzes</SelectItem>
                                    <SelectItem v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id.toString()">
                                        {{ quiz.title }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex items-end space-x-2 sm:col-span-2 lg:col-span-5">
                            <Button type="submit">
                                Apply Filters
                            </Button>
                            <Button type="button" @click="clearFilters" variant="outline">
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Clear Filters
                            </Button>
                            <div v-if="hasActiveFilters" class="text-sm text-wrap max-w-11/12 text-muted-foreground ml-4 flex items-center">
                                <CheckCircle class="h-4 w-4 mr-1 text-green-600" />
                                Filters applied to all sections
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- ✅ UPDATED: Summary Cards (Changed to 5 columns grid) -->
            <div v-if="analytics" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- Users Card -->
                <Card class="hover:shadow-lg transition-shadow duration-300">
                    <CardHeader class="pb-3">
                        <div class="flex items-center">
                            <div class="p-2 bg-primary/10 rounded-lg mr-3">
                                <Users class="h-5 w-5 text-primary" />
                            </div>
                            <CardTitle class="text-lg">Users</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-3xl font-bold text-primary">{{ analytics.users && analytics.users.total ? analytics.users.total : 0 }}</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Total Users</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-semibold text-foreground">{{ analytics.users && analytics.users.active ? analytics.users.active : 0 }}</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Active (30d)</p>
                                <Badge :variant="(analytics.users && analytics.users.active_percentage ? analytics.users.active_percentage : 0) >= 50 ? 'default' : 'secondary'" class="mt-1">
                                    {{ analytics.users && analytics.users.active_percentage ? analytics.users.active_percentage : 0 }}% Active
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Courses Card -->
                <Card class="hover:shadow-lg transition-shadow duration-300">
                    <CardHeader class="pb-3">
                        <div class="flex items-center">
                            <div class="p-2 bg-primary/10 rounded-lg mr-3">
                                <BookOpen class="h-5 w-5 text-primary" />
                            </div>
                            <CardTitle class="text-lg">Courses</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-3xl font-bold text-primary">{{ analytics.courses && analytics.courses.total ? analytics.courses.total : 0 }}</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Total Courses</p>
                            </div>
                            <div class="text-right space-y-1">
                                <div class="flex items-center justify-end">
                                    <div class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                    <p class="text-xl font-semibold text-foreground">{{ analytics.courses && analytics.courses.active ? analytics.courses.active : 0 }}</p>
                                </div>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Active</p>

                                <div class="flex items-center justify-end mt-1">
                                    <div class="inline-block w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                    <p class="text-xl font-semibold text-foreground">{{ analytics.courses && analytics.courses.completed ? analytics.courses.completed : 0 }}</p>
                                </div>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Completed</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Registrations Card -->
<!--                <Card class="hover:shadow-lg transition-shadow duration-300">-->
<!--                    <CardHeader class="pb-3">-->
<!--                        <div class="flex items-center">-->
<!--                            <div class="p-2 bg-primary/10 rounded-lg mr-3">-->
<!--                                <ClipboardList class="h-5 w-5 text-primary" />-->
<!--                            </div>-->
<!--                            <CardTitle class="text-lg">Registrations</CardTitle>-->
<!--                        </div>-->
<!--                    </CardHeader>-->
<!--                    <CardContent>-->
<!--                        <div class="flex justify-between items-end">-->
<!--                            <div>-->
<!--                                <p class="text-3xl font-bold text-primary">{{ analytics.registrations && analytics.registrations.total ? analytics.registrations.total : 0 }}</p>-->
<!--                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Total Registrations</p>-->
<!--                            </div>-->
<!--                            <div class="text-right">-->
<!--                                <p class="text-xl font-semibold text-foreground">{{ analytics.registrations && analytics.registrations.completed ? analytics.registrations.completed : 0 }}</p>-->
<!--                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Completed</p>-->
<!--                                <Badge :variant="(analytics.registrations && analytics.registrations.completion_rate ? analytics.registrations.completion_rate : 0) >= 50 ? 'default' : 'secondary'" class="mt-1">-->
<!--                                    {{ analytics.registrations && analytics.registrations.completion_rate ? analytics.registrations.completion_rate : 0 }}% Completion-->
<!--                                </Badge>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </CardContent>-->
<!--                </Card>-->

                <!-- Attendance Card -->
                <Card class="hover:shadow-lg transition-shadow duration-300">
                    <CardHeader class="pb-3">
                        <div class="flex items-center">
                            <div class="p-2 bg-primary/10 rounded-lg mr-3">
                                <Clock class="h-5 w-5 text-primary" />
                            </div>
                            <CardTitle class="text-lg">Attendance</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-3xl font-bold text-primary">{{ analytics.attendance && analytics.attendance.total_clockings ? analytics.attendance.total_clockings : 0 }}</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Total Clockings</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-semibold text-foreground">{{ analytics.attendance && analytics.attendance.average_duration ? analytics.attendance.average_duration : 0 }} min</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Avg. Duration</p>
                                <!-- ✅ FIXED: Rating alignment -->
                                <div class="flex items-center justify-end mt-2 gap-1">
                                    <span class="text-sm font-medium text-muted-foreground">Rating:</span>
                                    <div class="flex items-center">
                                        <Star v-for="i in 5" :key="i" class="h-4 w-4"
                                              :class="i <= Math.round(analytics.attendance && analytics.attendance.average_rating ? analytics.attendance.average_rating : 0) ? 'text-yellow-400 fill-yellow-400' : 'text-muted-foreground'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ✅ NEW: Online Courses Card -->
                <Card class="hover:shadow-lg transition-shadow duration-300">
                    <CardHeader class="pb-3">
                        <div class="flex items-center">
                            <div class="p-2 bg-primary/10 rounded-lg mr-3">
                                <Monitor class="h-5 w-5 text-primary" />
                            </div>
                            <CardTitle class="text-lg">Online Courses</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-3xl font-bold text-primary">{{ analytics.online_courses && analytics.online_courses.total_enrollments ? analytics.online_courses.total_enrollments : 0 }}</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Total Enrollments</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-semibold text-foreground">{{ analytics.online_courses && analytics.online_courses.completed ? analytics.online_courses.completed : 0 }}</p>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Completed</p>
                                <Badge :variant="(analytics.online_courses && analytics.online_courses.completion_rate ? analytics.online_courses.completion_rate : 0) >= 50 ? 'default' : 'secondary'" class="mt-1">
                                    {{ analytics.online_courses && analytics.online_courses.completion_rate ? analytics.online_courses.completion_rate : 0 }}% Completion
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts -->
            <div v-if="analytics && analytics.trends" class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <!-- Monthly Attendance Chart -->
                <Card class="hover:shadow-lg transition-shadow duration-300">
                    <CardHeader>
                        <div class="flex items-center">
                            <BarChart3 class="mr-2 h-5 w-5 text-primary" />
                            <CardTitle>Monthly Attendance</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 sm:h-72">
                            <BarChart v-if="monthlyAttendanceData.labels.length > 0" :chart-data="monthlyAttendanceData" />
                            <div v-else class="flex items-center justify-center h-full text-muted-foreground">
                                <div class="text-center">
                                    <BarChart3 class="h-12 w-12 mx-auto mb-2 text-muted-foreground" />
                                    No attendance data available
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Popular Courses Chart -->
                <Card class="hover:shadow-lg transition-shadow duration-300">
                    <CardHeader>
                        <div class="flex items-center">
                            <PieChart class="mr-2 h-5 w-5 text-primary" />
                            <CardTitle>Popular Courses</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64 sm:h-72">
                            <div v-if="!analytics.trends.popular_courses || !analytics.trends.popular_courses.length" class="flex items-center justify-center h-full text-muted-foreground">
                                <div class="text-center">
                                    <PieChart class="h-12 w-12 mx-auto mb-2 text-muted-foreground" />
                                    No course data available
                                </div>
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
                                                font: { size: 12 },
                                                color: '#64748b'
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
                    </CardContent>
                </Card>
            </div>

            <!-- Enhanced Quiz Analytics Card -->
            <Card v-if="analytics && analytics.quiz" class="hover:shadow-lg transition-shadow duration-300">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-primary/10 rounded-lg mr-3">
                                <FileText class="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <CardTitle class="text-xl">Quiz Analytics</CardTitle>
                                <CardDescription>Performance metrics and insights</CardDescription>
                            </div>
                        </div>
                        <Badge variant="secondary">
                            {{ analytics.quiz.pass_rate || 0 }}% Pass Rate
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="text-center">
                            <div class="bg-primary/10 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                                <FileText class="h-8 w-8 text-primary" />
                            </div>
                            <p class="text-3xl font-bold text-primary mb-1">{{ analytics.quiz.total_attempts || 0 }}</p>
                            <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Total Attempts</p>
                        </div>

                        <div class="text-center">
                            <div class="bg-green-100 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                                <CheckCircle class="h-8 w-8 text-green-600" />
                            </div>
                            <p class="text-3xl font-bold text-green-600 mb-1">{{ analytics.quiz.passed_attempts || 0 }}</p>
                            <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Passed</p>
                        </div>

                        <div class="text-center">
                            <div class="bg-red-100 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                                <XCircle class="h-8 w-8 text-red-600" />
                            </div>
                            <p class="text-3xl font-bold text-red-600 mb-1">{{ analytics.quiz.failed_attempts || 0 }}</p>
                            <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Failed</p>
                        </div>

                        <div class="text-center">
                            <div class="bg-blue-100 rounded-full p-3 w-16 h-16 mx-auto mb-3 flex items-center justify-center">
                                <BarChart3 class="h-8 w-8 text-blue-600" />
                            </div>
                            <p class="text-3xl font-bold text-blue-600 mb-1">{{ analytics.quiz.average_score || 0 }}%</p>
                            <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Average Score</p>
                        </div>
                    </div>

                    <!-- Top Quizzes -->
                    <div v-if="analytics.quiz.top_quizzes && analytics.quiz.top_quizzes.length > 0" class="pt-6 border-t">
                        <h3 class="text-lg font-medium text-foreground mb-4">Top Performing Quizzes</h3>
                        <div class="space-y-3">
                            <Card v-for="quiz in analytics.quiz.top_quizzes.slice(0, 3)" :key="quiz.quiz_title" class="bg-muted/50">
                                <CardContent class="p-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-foreground">{{ quiz.quiz_title }}</p>
                                            <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">{{ quiz.attempt_count }} attempts</p>
                                        </div>
                                        <div class="text-right">
                                            <Badge variant="secondary">{{ quiz.avg_score }}% avg</Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ UPDATED: Report Links -->
            <Card>
                <CardHeader>
                    <CardTitle>Detailed Reports</CardTitle>
                    <CardDescription>Access comprehensive reports and export data</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <Button
                            :as="'a'"
                            :href="route('admin.reports.course-registrations')"
                            variant="outline"
                            class="h-auto p-4 flex flex-col items-center text-center space-y-2"
                        >
                            <ClipboardList class="h-8 w-8 text-primary" />
                            <div>
                                <div class="font-medium">Course Registrations</div>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">View and export course registration data</p>
                            </div>
                        </Button>

                        <Button
                            :as="'a'"
                            :href="route('admin.reports.attendance')"
                            variant="outline"
                            class="h-auto p-4 flex flex-col items-center text-center space-y-2"
                        >
                            <Clock class="h-8 w-8 text-primary" />
                            <div>
                                <div class="font-medium">Attendance Records</div>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">View and export attendance data</p>
                            </div>
                        </Button>

                        <Button
                            :as="'a'"
                            :href="route('admin.reports.course-completion')"
                            variant="outline"
                            class="h-auto p-4 flex flex-col items-center text-center space-y-2"
                        >
                            <CheckCircle class="h-8 w-8 text-primary" />
                            <div>
                                <div class="font-medium">Course Completion</div>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">View and export course completion data</p>
                            </div>
                        </Button>

                        <!-- ✅ NEW: Course Online Reports Card -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="h-auto p-4 flex flex-col items-center text-center space-y-2"
                                >
                                    <div class="relative">
                                        <Monitor class="h-8 w-8 text-primary" />
                                        <ChevronDown class="absolute -bottom-1 -right-1 h-4 w-4 text-primary" />
                                    </div>
                                    <div>
                                        <div class="font-medium">Course Online</div>
                                        <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">Online learning analytics</p>
                                    </div>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="center" class="w-52">
                                <DropdownMenuItem as-child>
                                    <Link :href="route('admin.reports.course-online.progress')" class="flex items-center">
                                        <BarChart3 class="mr-2 h-4 w-4" />
                                        <div>
                                            <div class="font-medium">Progress Report</div>
                                            <div class="text-xs text-muted-foreground">Assignment tracking</div>
                                        </div>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('admin.reports.course-online.learning-sessions')" class="flex items-center">
                                        <Activity class="mr-2 h-4 w-4" />
                                        <div>
                                            <div class="font-medium">Learning Sessions</div>
                                            <div class="text-xs text-muted-foreground">User activity logs</div>
                                        </div>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('admin.reports.course-online.user-performance')" class="flex items-center">
                                        <Users class="mr-2 h-4 w-4" />
                                        <div>
                                            <div class="font-medium">User Performance</div>
                                            <div class="text-xs text-muted-foreground">Comprehensive analysis</div>
                                        </div>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('admin.reports.course-online.department-performance')" class="flex items-center">
                                        <Trophy class="mr-2 h-4 w-4" />
                                        <div>
                                            <div class="font-medium">Department Performance</div>
                                            <div class="text-xs text-muted-foreground">Top & bottom performers</div>
                                        </div>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link href="/admin/analytics/cheating-detection" class="flex items-center">
                                        <Shield class="mr-2 h-4 w-4" />
                                        <div>
                                            <div class="font-medium">Cheating Detection</div>
                                            <div class="text-xs text-muted-foreground">Security monitoring</div>
                                        </div>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button
                            :as="'a'"
                            :href="route('admin.reports.quiz-attempts')"
                            variant="outline"
                            class="h-auto p-4 flex flex-col items-center text-center space-y-2"
                        >
                            <FileText class="h-8 w-8 text-primary" />
                            <div>
                                <div class="font-medium">Quiz Attempts</div>
                                <p class="text-sm text-wrap max-w-11/12 text-muted-foreground">View and export quiz attempt data</p>
                            </div>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Loading State -->
            <Card v-if="!analytics">
                <CardContent class="p-12 text-center">
                    <Loader2 class="mx-auto h-12 w-12 text-muted-foreground animate-spin" />
                    <p class="mt-4 text-muted-foreground">Loading analytics...</p>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import BarChart from '@/components/Charts/BarChart.vue'
import DoughnutChart from '@/components/Charts/DoughnutChart.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Filter,
    RotateCcw,
    CheckCircle,
    Users,
    BookOpen,
    ClipboardList,
    Clock,
    Star,
    BarChart3,
    PieChart,
    FileText,
    XCircle,
    Loader2,
    Monitor,
    ChevronDown,
    Activity,
    Shield
} from 'lucide-vue-next'

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
