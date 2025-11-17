<!--
  Monthly KPI Screenshot Page
  Dedicated screenshot capture page with optimized layout for KPI reporting
-->
<template>
    <div class="min-h-screen bg-background">
        <!-- Screenshot Toolbar -->
        <Card class="rounded-none border-b sticky top-0 z-50">
            <CardContent class="p-4">
                <div class="flex justify-between items-center max-w-7xl mx-auto">
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">📸 KPI Report Screenshot</h1>
                        <p class="text-muted-foreground">Generate and download professional KPI reports</p>
                    </div>
                    <div class="flex gap-3">
                        <Button @click="captureScreenshot" :disabled="capturing" size="lg">
                            <Camera class="mr-2 h-4 w-4" />
                            {{ capturing ? 'Capturing...' : 'Take Screenshot' }}
                        </Button>
                        <Button :as="'a'" :href="backUrl" variant="outline" size="lg">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Dashboard
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Clean Report Content -->
        <div id="screenshot-content" class="max-w-7xl mx-auto bg-background p-8">
            <!-- Report Header -->
            <Card class="mb-8">
                <CardContent class="p-8 text-center">
                    <div class="text-6xl mb-4">🏢</div>
                    <h1 class="text-4xl font-bold text-foreground mb-2">Monthly Training KPI Report</h1>
                    <p class="text-xl text-muted-foreground mb-2">{{ kpiData.period?.period_name || 'Current Period' }}</p>
                    <p class="text-sm text-muted-foreground">Generated: {{ formatDate(new Date()) }}</p>
                </CardContent>
            </Card>

            <!-- KPI Overview Cards -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <BarChart3 class="mr-3 h-6 w-6" />
                        Key Performance Indicators
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">📚</div>
                                <div class="text-sm text-muted-foreground mb-2">Courses Delivered</div>
                                <div class="text-3xl font-bold text-foreground">{{ kpiData.delivery_overview?.courses_delivered || 0 }}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">👥</div>
                                <div class="text-sm text-muted-foreground mb-2">Total Enrolled</div>
                                <div class="text-3xl font-bold text-foreground">{{ kpiData.delivery_overview?.total_enrolled || 0 }}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">🎯</div>
                                <div class="text-sm text-muted-foreground mb-2">Active Participants</div>
                                <div class="text-3xl font-bold text-foreground">{{ kpiData.delivery_overview?.active_participants || 0 }}</div>
                            </CardContent>
                        </Card>

                        <Card class="border-primary bg-primary/5">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">✅</div>
                                <div class="text-sm text-muted-foreground mb-2">Completion Rate</div>
                                <div class="text-3xl font-bold text-primary">{{ kpiData.delivery_overview?.completion_rate || 0 }}%</div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ NEW: Online Course Overview -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <Monitor class="mr-3 h-6 w-6" />
                        Online Course Analytics Overview
                    </CardTitle>
                    <CardDescription>Comprehensive metrics for online learning platforms</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">💻</div>
                                <div class="text-sm text-muted-foreground mb-2">Online Courses</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.delivery?.online_courses_delivered || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">📝</div>
                                <div class="text-sm text-muted-foreground mb-2">Enrollments</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.delivery?.online_enrollments || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">✅</div>
                                <div class="text-sm text-muted-foreground mb-2">Completed</div>
                                <div class="text-3xl font-bold text-green-600">
                                    {{ kpiData.online_course_analytics?.delivery?.online_completed || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-primary bg-primary/5">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">📊</div>
                                <div class="text-sm text-muted-foreground mb-2">Completion Rate</div>
                                <div class="text-3xl font-bold text-primary">
                                    {{ kpiData.online_course_analytics?.delivery?.online_completion_rate || 0 }}%
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">👥</div>
                                <div class="text-sm text-muted-foreground mb-2">Active Learners</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.delivery?.active_online_learners || 0 }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>


            <!-- Engagement Metrics -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <Users class="mr-3 h-6 w-6" />
                        Engagement & Attendance Metrics
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-1/2">Metric</TableHead>
                                    <TableHead>Current Value</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell class="font-medium">📋 Attendance Rate</TableCell>
                                    <TableCell>{{ kpiData.attendance_engagement?.average_attendance_rate || 0 }}%</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(kpiData.attendance_engagement?.average_attendance_rate || 0, 80)">
                                            {{ getStatus(kpiData.attendance_engagement?.average_attendance_rate || 0, 80) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="font-medium">⏱️ Average Time Spent</TableCell>
                                    <TableCell>{{ kpiData.attendance_engagement?.average_time_spent || 0 }} hours</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(kpiData.attendance_engagement?.average_time_spent || 0, 20)">
                                            {{ getStatus(kpiData.attendance_engagement?.average_time_spent || 0, 20) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="font-medium">💯 Engagement Score</TableCell>
                                    <TableCell>{{ kpiData.attendance_engagement?.engagement_score || 0 }}%</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(kpiData.attendance_engagement?.engagement_score || 0, 75)">
                                            {{ getStatus(kpiData.attendance_engagement?.engagement_score || 0, 75) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="font-medium">🔄 Login Frequency</TableCell>
                                    <TableCell>{{ kpiData.attendance_engagement?.login_frequency || 0 }}%</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(kpiData.attendance_engagement?.login_frequency || 0, 70)">
                                            {{ getStatus(kpiData.attendance_engagement?.login_frequency || 0, 70) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ NEW: Video Engagement Metrics -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <PlayCircle class="mr-3 h-6 w-6" />
                        Video Engagement Metrics
                    </CardTitle>
                    <CardDescription>Student interaction with video content</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Card class="border-blue-200 bg-blue-50">
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <PlayCircle class="h-6 w-6 text-blue-600" />
                                    <span class="text-sm text-muted-foreground">Videos Watched</span>
                                </div>
                                <div class="text-3xl font-bold text-blue-600">
                                    {{ kpiData.online_course_analytics?.video_engagement?.total_videos_watched || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-green-200 bg-green-50">
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <CheckCircle class="h-6 w-6 text-green-600" />
                                    <span class="text-sm text-muted-foreground">Avg Completion</span>
                                </div>
                                <div class="text-3xl font-bold text-green-600">
                                    {{ kpiData.online_course_analytics?.video_engagement?.avg_video_completion || 0 }}%
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <Clock class="h-6 w-6" />
                                    <span class="text-sm text-muted-foreground">Watch Time</span>
                                </div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.video_engagement?.total_watch_time_hours || 0 }}h
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <RotateCcw class="h-6 w-6" />
                                    <span class="text-sm text-muted-foreground">Replays</span>
                                </div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.video_engagement?.video_replay_count || 0 }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>


            <!-- Learning Outcomes -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <TrendingUp class="mr-3 h-6 w-6" />
                        Learning Outcomes
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Card class="border-green-200 bg-green-50">
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <CheckCircle class="h-6 w-6 text-green-600" />
                                    <span class="text-sm text-muted-foreground">Quiz Pass Rate</span>
                                </div>
                                <div class="text-3xl font-bold text-green-600">{{ kpiData.learning_outcomes?.quiz_pass_rate || 0 }}%</div>
                            </CardContent>
                        </Card>

                        <Card class="border-red-200 bg-red-50">
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <XCircle class="h-6 w-6 text-red-600" />
                                    <span class="text-sm text-muted-foreground">Quiz Fail Rate</span>
                                </div>
                                <div class="text-3xl font-bold text-red-600">{{ kpiData.learning_outcomes?.quiz_fail_rate || 0 }}%</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <BarChart3 class="h-6 w-6" />
                                    <span class="text-sm text-muted-foreground">Average Score</span>
                                </div>
                                <div class="text-3xl font-bold text-foreground">{{ kpiData.learning_outcomes?.average_quiz_score || 0 }}%</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <TrendingUp class="h-6 w-6" />
                                    <span class="text-sm text-muted-foreground">Improvement Rate</span>
                                </div>
                                <div class="text-3xl font-bold text-foreground">{{ kpiData.learning_outcomes?.improvement_rate || 0 }}%</div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ NEW: Online Module Progress -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <BookOpen class="mr-3 h-6 w-6" />
                        Online Module Progress
                    </CardTitle>
                    <CardDescription>Module completion tracking</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">📚</div>
                                <div class="text-sm text-muted-foreground mb-2">Total Modules</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.module_progress?.total_modules || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-green-200 bg-green-50">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">✅</div>
                                <div class="text-sm text-muted-foreground mb-2">Completed</div>
                                <div class="text-3xl font-bold text-green-600">
                                    {{ kpiData.online_course_analytics?.module_progress?.completed_modules || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">👤</div>
                                <div class="text-sm text-muted-foreground mb-2">Avg Per User</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.module_progress?.avg_modules_per_user || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-primary bg-primary/5">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">📈</div>
                                <div class="text-sm text-muted-foreground mb-2">Completion Rate</div>
                                <div class="text-3xl font-bold text-primary">
                                    {{ kpiData.online_course_analytics?.module_progress?.module_completion_rate || 0 }}%
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>


            <!-- Feedback Analysis -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <Star class="mr-3 h-6 w-6" />
                        Course Quality & Feedback
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Feedback Summary -->
                        <div class="space-y-4">
                            <Card>
                                <CardContent class="p-6 text-center">
                                    <Star class="h-8 w-8 text-yellow-500 mx-auto mb-2" />
                                    <div class="text-sm text-muted-foreground mb-2">Average Rating</div>
                                    <div class="text-4xl font-bold text-foreground">
                                        {{ kpiData.feedback_analysis?.average_rating || 0 }}
                                        <span class="text-xl text-muted-foreground">/5</span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent class="p-6 text-center">
                                    <MessageCircle class="h-8 w-8 text-blue-500 mx-auto mb-2" />
                                    <div class="text-sm text-muted-foreground mb-2">Total Feedback</div>
                                    <div class="text-3xl font-bold text-foreground">{{ kpiData.feedback_analysis?.total_feedback_count || 0 }}</div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Sentiment Breakdown -->
                        <div class="lg:col-span-2 space-y-4">
                            <Card class="border-green-200 bg-green-50">
                                <CardContent class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="text-2xl">😊</div>
                                            <span class="font-medium">Positive</span>
                                        </div>
                                        <Badge variant="secondary" class="text-lg px-3 py-1">
                                            {{ kpiData.feedback_analysis?.feedback_sentiment?.positive || 0 }}%
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="border-gray-200 bg-gray-50">
                                <CardContent class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="text-2xl">😐</div>
                                            <span class="font-medium">Neutral</span>
                                        </div>
                                        <Badge variant="secondary" class="text-lg px-3 py-1">
                                            {{ kpiData.feedback_analysis?.feedback_sentiment?.neutral || 0 }}%
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="border-red-200 bg-red-50">
                                <CardContent class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="text-2xl">😞</div>
                                            <span class="font-medium">Negative</span>
                                        </div>
                                        <Badge variant="secondary" class="text-lg px-3 py-1">
                                            {{ kpiData.feedback_analysis?.feedback_sentiment?.negative || 0 }}%
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ NEW: Learning Session Analytics -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <Timer class="mr-3 h-6 w-6" />
                        Learning Session Analytics
                    </CardTitle>
                    <CardDescription>Student engagement and attention tracking</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">🎯</div>
                                <div class="text-sm text-muted-foreground mb-2">Total Sessions</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.session_analytics?.total_sessions || 0 }}
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-blue-200 bg-blue-50">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">⏱️</div>
                                <div class="text-sm text-muted-foreground mb-2">Avg Duration</div>
                                <div class="text-3xl font-bold text-blue-600">
                                    {{ kpiData.online_course_analytics?.session_analytics?.avg_session_duration_minutes || 0 }}m
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-green-200 bg-green-50">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">👁️</div>
                                <div class="text-sm text-muted-foreground mb-2">Attention Score</div>
                                <div class="text-3xl font-bold text-green-600">
                                    {{ kpiData.online_course_analytics?.session_analytics?.avg_attention_score || 0 }}%
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">⏰</div>
                                <div class="text-sm text-muted-foreground mb-2">Learning Hours</div>
                                <div class="text-3xl font-bold text-foreground">
                                    {{ kpiData.online_course_analytics?.session_analytics?.total_learning_hours || 0 }}h
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="border-red-200 bg-red-50">
                            <CardContent class="p-6 text-center">
                                <div class="text-4xl mb-3">⚠️</div>
                                <div class="text-sm text-muted-foreground mb-2">Suspicious Activity</div>
                                <div class="text-3xl font-bold text-red-600">
                                    {{ kpiData.online_course_analytics?.session_analytics?.suspicious_activity_count || 0 }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>


            <!-- Performance Analysis -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <Trophy class="mr-3 h-6 w-6" />
                        Performance Analysis
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Top Courses -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <Trophy class="mr-2 h-5 w-5 text-yellow-500" />
                                    Top Performing Courses
                                </CardTitle>
                                <CardDescription>Based on rating & completion</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Course</TableHead>
                                            <TableHead>Rating</TableHead>
                                            <TableHead>Completion</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-if="!kpiData.performance_analysis?.top_performing_courses?.length">
                                            <TableCell colspan="3" class="text-center text-muted-foreground">No data available</TableCell>
                                        </TableRow>
                                        <TableRow v-else v-for="(course, index) in (kpiData.performance_analysis?.top_performing_courses || []).slice(0, 5)" :key="course.id">
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <Badge variant="outline" class="text-xs">{{ index + 1 }}</Badge>
                                                    <span class="font-medium">{{ course.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{{ course.rating }}/5</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">{{ course.completion_rate }}%</Badge>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <!-- Top Users -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <Users class="mr-2 h-5 w-5 text-blue-500" />
                                    Top Performing Users
                                </CardTitle>
                                <CardDescription>Based on evaluation system scores</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>User</TableHead>
                                            <TableHead>Score</TableHead>
                                            <TableHead>Completed</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-if="!kpiData.performance_analysis?.top_performing_users?.length">
                                            <TableCell colspan="3" class="text-center text-muted-foreground">No data available</TableCell>
                                        </TableRow>
                                        <TableRow v-else v-for="(user, index) in (kpiData.performance_analysis?.top_performing_users || []).slice(0, 5)" :key="user.name" class="bg-green-50">
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <Badge variant="outline" class="text-xs">{{ index + 1 }}</Badge>
                                                    <span class="font-medium">{{ user.name }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="default">{{ user.score }}%</Badge>
                                            </TableCell>
                                            <TableCell>{{ user.courses_completed || 0 }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Monthly Trend -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center text-2xl">
                        <Activity class="mr-3 h-6 w-6" />
                        Monthly Engagement Trend
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="p-6 text-center">
                                <BarChart3 class="h-8 w-8 text-primary mx-auto mb-2" />
                                <div class="text-sm text-muted-foreground mb-2">Current Month</div>
                                <div class="text-4xl font-bold text-primary mb-2">{{ kpiData.engagement_trends?.current_month_engagement || 0 }}%</div>
                                <div class="text-xs text-muted-foreground">{{ kpiData.period?.period_name }}</div>
                            </CardContent>
                        </Card>

                        <div class="text-center">
                            <div class="text-4xl mb-2">{{ getTrendArrow(kpiData.engagement_trends?.trend_direction) }}</div>
                            <div class="text-sm font-medium text-muted-foreground capitalize">{{ kpiData.engagement_trends?.trend_direction || 'stable' }}</div>
                        </div>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <Activity class="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                                <div class="text-sm text-muted-foreground mb-2">Previous Month</div>
                                <div class="text-4xl font-bold text-foreground mb-2">{{ kpiData.engagement_trends?.previous_month_engagement || 0 }}%</div>
                                <div class="text-xs text-muted-foreground">{{ kpiData.engagement_trends?.trend_percentage || 0 }}% change</div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Report Footer -->
            <Card>
                <CardContent class="p-6">
                    <div class="bg-muted rounded-lg p-4 text-center space-y-2">
                        <div class="text-sm">
                            <span class="font-semibold">Report Period:</span> {{ kpiData.period?.period_name || 'Current Period' }}
                        </div>
                        <div class="text-sm">
                            <span class="font-semibold">Generated:</span> {{ formatDateTime(new Date()) }}
                        </div>
                        <div class="text-sm">
                            <span class="font-semibold">System:</span> Training Management Platform
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Screenshot Preview Dialog -->
        <Dialog :open="!!screenshotUrl" @update:open="val => { if (!val) screenshotUrl = null }">

        <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <Camera class="mr-2 h-5 w-5" />
                        Screenshot Preview
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="max-h-[60vh] overflow-y-auto border rounded-lg">
                        <img v-if="screenshotUrl" :src="screenshotUrl" alt="KPI Report Screenshot" class="w-full" />
                    </div>
                    <div class="flex gap-3 justify-center">
                        <Button @click="downloadScreenshot" variant="default">
                            <Download class="mr-2 h-4 w-4" />
                            Download
                        </Button>
                        <Button @click="copyToClipboard" variant="outline">
                            <Copy class="mr-2 h-4 w-4" />
                            Copy to Clipboard
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Camera,
    ArrowLeft,
    BarChart3,
    Users,
    TrendingUp,
    Star,
    Trophy,
    Activity,
    CheckCircle,
    XCircle,
    MessageCircle,
    Download,
    Copy,
    Monitor,      // ✅ NEW
    PlayCircle,   // ✅ NEW
    Clock,        // ✅ NEW
    RotateCcw,    // ✅ NEW
    BookOpen,     // ✅ NEW
    Timer,        // ✅ NEW
} from 'lucide-vue-next'

export default {
    name: 'MonthlyKpiScreenshot',

    components: {
        Button,
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
        Badge,
        Dialog,
        DialogContent,
        DialogHeader,
        DialogTitle,
        Camera,
        ArrowLeft,
        BarChart3,
        Users,
        TrendingUp,
        Star,
        Trophy,
        Activity,
        CheckCircle,
        XCircle,
        MessageCircle,
        Download,
        Copy,
        Monitor,      // ✅ NEW
        PlayCircle,   // ✅ NEW
        Clock,        // ✅ NEW
        RotateCcw,    // ✅ NEW
        BookOpen,     // ✅ NEW
        Timer,        // ✅ NEW
    },

    props: {
        kpiData: { type: Object, required: true },
        currentFilters: { type: Object, required: true },
        departments: { type: Array, default: () => [] },
        courses: { type: Array, default: () => [] },
        lastUpdated: { type: String, required: true }
    },

    setup(props) {
        const capturing = ref(false)
        const screenshotUrl = ref(null)

        // Computed properties
        const backUrl = computed(() => {
            const params = new URLSearchParams(props.currentFilters)
            return `/admin/reports/monthly-kpi?${params.toString()}`
        })

        // Methods
        const captureScreenshot = async () => {
            try {
                capturing.value = true

                // Load html2canvas if not already loaded
                if (!window.html2canvas) {
                    const script = document.createElement('script')
                    script.src = 'https://html2canvas.hertzen.com/dist/html2canvas.min.js'
                    document.head.appendChild(script)
                    await new Promise(resolve => { script.onload = resolve })
                }

                // Get the content to capture
                const element = document.getElementById('screenshot-content')
                if (!element) {
                    throw new Error('Screenshot content not found')
                }

                // Capture with optimized settings
                const canvas = await window.html2canvas(element, {
                    backgroundColor: '#ffffff',
                    scale: 1.5,
                    useCORS: true,
                    allowTaint: true,
                    width: element.scrollWidth,
                    height: element.scrollHeight,
                    scrollX: 0,
                    scrollY: 0
                })

                // Create blob and URL
                canvas.toBlob((blob) => {
                    if (blob) {
                        screenshotUrl.value = URL.createObjectURL(blob)
                        console.log('✅ Screenshot captured successfully')
                    }
                }, 'image/png', 0.9)

            } catch (error) {
                console.error('❌ Screenshot failed:', error)
                alert('Screenshot failed. Please try using your browser\'s built-in screenshot (Ctrl/Cmd + Shift + S)')
            } finally {
                capturing.value = false
            }
        }

        const downloadScreenshot = () => {
            if (!screenshotUrl.value) return

            const link = document.createElement('a')
            link.href = screenshotUrl.value
            link.download = `KPI_Report_${props.currentFilters.month}_${props.currentFilters.year}.png`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
        }

        const copyToClipboard = async () => {
            if (!screenshotUrl.value) return

            try {
                const response = await fetch(screenshotUrl.value)
                const blob = await response.blob()
                await navigator.clipboard.write([
                    new ClipboardItem({ 'image/png': blob })
                ])
                alert('📋 Screenshot copied to clipboard!')
            } catch (error) {
                console.error('Copy failed:', error)
                alert('Copy failed. Please download instead.')
            }
        }

        // Utility methods
        const formatDate = (date) => {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        }

        const formatDateTime = (dateTime) => {
            return new Date(dateTime).toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        const getStatus = (value, threshold) => {
            if (value >= threshold) return '✅ Good'
            if (value >= threshold * 0.7) return '⚠️ Fair'
            return '❌ Poor'
        }

        const getStatusVariant = (value, threshold) => {
            if (value >= threshold) return 'default'
            if (value >= threshold * 0.7) return 'secondary'
            return 'destructive'
        }

        const getTrendArrow = (direction) => {
            switch (direction) {
                case 'up': return '↗️'
                case 'down': return '↘️'
                default: return '➡️'
            }
        }

        // Lifecycle
        onMounted(() => {
            console.log('📸 KPI Screenshot page loaded')
        })

        return {
            // State
            capturing,
            screenshotUrl,
            backUrl,

            // Methods
            captureScreenshot,
            downloadScreenshot,
            copyToClipboard,
            formatDate,
            formatDateTime,
            getStatus,
            getStatusVariant,
            getTrendArrow
        }
    }
}
</script>

<style scoped>
/* Custom styles for print-friendly screenshot */
#screenshot-content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Ensure proper spacing for screenshot */
@media screen {
    #screenshot-content {
        background: theme('colors.background');
    }
}
</style>
