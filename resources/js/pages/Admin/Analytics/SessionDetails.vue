<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'

import {
    ArrowLeft,
    AlertTriangle,
    Eye,
    Clock,
    User,
    BookOpen,
    Activity,
    Shield,
    TrendingUp,
    Zap,
    Target,
    Calendar,
    Play,
    Pause,
    SkipForward,
    Mouse
} from 'lucide-vue-next'

const props = defineProps<{
    session: any
    analytics: any
    userPatterns: any
    courseProgress: any[]
    sessionTimeline: any[]
    fraudAnalysis: any
    recentSessions: any[]
}>()

const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
    { title: 'Analytics', href: '/admin/analytics' },
    { title: 'Cheating Detection', href: '/admin/analytics/cheating-detection' },
    { title: 'Session Investigation', href: '#' }
]

const getRiskColor = (risk: string) => {
    switch (risk.toLowerCase()) {
        case 'critical': return 'text-red-700 dark:text-red-400'
        case 'high': return 'text-orange-700 dark:text-orange-400'
        case 'medium': return 'text-yellow-700 dark:text-yellow-400'
        default: return 'text-blue-700 dark:text-blue-400'
    }
}

const getRiskBadgeColor = (risk: string) => {
    switch (risk.toLowerCase()) {
        case 'critical': return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400'
        case 'high': return 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400'
        case 'medium': return 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400'
        default: return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400'
    }
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <Head title="Session Investigation Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-4 mb-2">
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/admin/analytics/cheating-detection">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Detection
                            </Link>
                        </Button>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                        <Shield class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                        Session Investigation
                    </h1>
                    <p class="text-muted-foreground mt-1">Detailed analysis of learning session #{{ session.id }}</p>
                </div>
                <Badge :class="getRiskBadgeColor(analytics.cheating_risk)" class="text-lg px-3 py-1">
                    {{ analytics.cheating_risk }} Risk
                </Badge>
            </div>

            <!-- Critical Alert -->
            <Alert v-if="fraudAnalysis.action_required" class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/20">
                <AlertTriangle class="h-4 w-4 text-red-600 dark:text-red-400" />
                <AlertDescription class="text-red-800 dark:text-red-200">
                    <strong>Immediate Action Required:</strong> This session shows critical fraud indicators.
                    Investigation and potential course invalidation recommended.
                </AlertDescription>
            </Alert>

            <!-- Session Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Risk Score</CardTitle>
                        <Target class="h-4 w-4 text-red-600 dark:text-red-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="getRiskColor(analytics.cheating_risk)">
                            {{ analytics.cheating_score }}
                        </div>
                        <p class="text-xs text-muted-foreground">Out of 100</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Attention Level</CardTitle>
                        <Eye class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ analytics.attention_score }}%</div>
                        <p class="text-xs text-muted-foreground">Focus score</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Session Duration</CardTitle>
                        <Clock class="h-4 w-4 text-green-600 dark:text-green-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ session.formatted_duration }}</div>
                        <p class="text-xs text-muted-foreground">Learning time</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Video Completion</CardTitle>
                        <Play class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ analytics.video_completion }}%</div>
                        <p class="text-xs text-muted-foreground">Content watched</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Session Details -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Session Information
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <h4 class="font-medium mb-2">User Details</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-muted-foreground">Name:</span> {{ session.user.name }}</div>
                                <div><span class="text-muted-foreground">Email:</span> {{ session.user.email }}</div>
                                <div><span class="text-muted-foreground">Employee ID:</span> {{ session.user.employee_code || 'N/A' }}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium mb-2">Session Details</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-muted-foreground">Course:</span> {{ session.course.name }}</div>
                                <div><span class="text-muted-foreground">Started:</span> {{ formatDate(session.session_start) }}</div>
                                <div><span class="text-muted-foreground">Ended:</span> {{ session.session_end ? formatDate(session.session_end) : 'Active' }}</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Analysis Tabs -->
            <Tabs default-value="fraud" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="fraud">Fraud Analysis</TabsTrigger>
                    <TabsTrigger value="timeline">Session Timeline</TabsTrigger>
                    <TabsTrigger value="behavior">Video Behavior</TabsTrigger>
                    <TabsTrigger value="patterns">User Patterns</TabsTrigger>
                    <TabsTrigger value="history">Recent Sessions</TabsTrigger>
                </TabsList>

                <!-- Fraud Analysis Tab -->
                <TabsContent value="fraud" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 text-red-700 dark:text-red-400">
                                    <AlertTriangle class="h-5 w-5" />
                                    Fraud Indicators
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="fraudAnalysis.fraud_indicators.length === 0" class="text-center py-6">
                                    <Shield class="h-12 w-12 mx-auto mb-2 text-green-600 dark:text-green-400" />
                                    <p class="text-muted-foreground">No major fraud indicators detected</p>
                                </div>
                                <ul v-else class="space-y-2">
                                    <li v-for="indicator in fraudAnalysis.fraud_indicators" :key="indicator"
                                        class="flex items-start gap-2">
                                        <AlertTriangle class="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" />
                                        <span class="text-sm">{{ indicator }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2 text-blue-700 dark:text-blue-400">
                                    <TrendingUp class="h-5 w-5" />
                                    Recommendations
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-2">
                                    <li v-for="recommendation in fraudAnalysis.recommendations" :key="recommendation"
                                        class="flex items-start gap-2">
                                        <Zap class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                                        <span class="text-sm">{{ recommendation }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Investigation Priority -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Investigation Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="text-center p-4 border rounded">
                                    <div class="text-lg font-bold" :class="getRiskColor(fraudAnalysis.overall_risk)">
                                        {{ fraudAnalysis.overall_risk }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">Overall Risk</div>
                                </div>
                                <div class="text-center p-4 border rounded">
                                    <div class="text-lg font-bold">{{ fraudAnalysis.investigation_priority }}</div>
                                    <div class="text-sm text-muted-foreground">Investigation Priority</div>
                                </div>
                                <div class="text-center p-4 border rounded">
                                    <div class="text-lg font-bold">
                                        {{ fraudAnalysis.action_required ? 'Yes' : 'No' }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">Action Required</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Session Timeline Tab -->
                <TabsContent value="timeline" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" />
                                Session Activity Timeline
                            </CardTitle>
                            <CardDescription>Minute-by-minute session activity</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <div v-for="timepoint in sessionTimeline" :key="timepoint.time"
                                     class="flex items-center gap-4 p-3 border rounded"
                                     :class="timepoint.is_suspicious ? 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/20' : ''">
                                    <div class="text-sm font-mono">{{ timepoint.time }}</div>
                                    <div class="flex-1">
                                        <div class="font-medium">{{ timepoint.activity }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            Attention: {{ timepoint.attention_score }}%
                                        </div>
                                    </div>
                                    <div v-if="timepoint.events.length > 0" class="text-sm">
                                        <Badge v-for="event in timepoint.events" :key="event"
                                               variant="outline" class="mr-1">
                                            {{ event }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Video Behavior Tab -->
                <TabsContent value="behavior" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm">Skip Count</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ analytics.skip_count }}</div>
                                <p class="text-xs text-muted-foreground">Content skips</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm">Seek Count</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ analytics.seek_count }}</div>
                                <p class="text-xs text-muted-foreground">Position changes</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm">Pause Count</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ analytics.pause_count }}</div>
                                <p class="text-xs text-muted-foreground">Video pauses</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm">Total Clicks</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ analytics.clicks_count }}</div>
                                <p class="text-xs text-muted-foreground">User interactions</p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Behavior Analysis -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Video Interaction Analysis</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <h4 class="font-medium mb-2">Viewing Pattern</h4>
                                    <div class="space-y-2 text-sm">
                                        <div>Watch Time: {{ Math.round(analytics.video_watch_time) }} minutes</div>
                                        <div>Total Duration: {{ Math.round(analytics.video_total_duration) }} minutes</div>
                                        <div>Completion Rate: {{ analytics.video_completion }}%</div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-medium mb-2">Interaction Frequency</h4>
                                    <div class="space-y-2 text-sm">
                                        <div>Skips per minute: {{ (analytics.skip_count / session.duration_minutes).toFixed(1) }}</div>
                                        <div>Seeks per minute: {{ (analytics.seek_count / session.duration_minutes).toFixed(1) }}</div>
                                        <div>Pauses per minute: {{ (analytics.pause_count / session.duration_minutes).toFixed(1) }}</div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- User Patterns Tab -->
                <TabsContent value="patterns" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Learning Patterns</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex justify-between">
                                    <span>Total Sessions</span>
                                    <span class="font-medium">{{ userPatterns.total_sessions }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Average Duration</span>
                                    <span class="font-medium">{{ userPatterns.average_duration }} min</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Short Sessions (&lt;10min)</span>
                                    <span class="font-medium">{{ userPatterns.short_sessions_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Late Night Learning</span>
                                    <span class="font-medium">{{ userPatterns.late_night_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Weekend Sessions</span>
                                    <span class="font-medium">{{ userPatterns.weekend_percentage }}%</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-red-700 dark:text-red-400">Risk Indicators</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span>Frequent Short Sessions</span>
                                    <Badge :variant="userPatterns.risk_indicators.frequent_short_sessions ? 'destructive' : 'outline'">
                                        {{ userPatterns.risk_indicators.frequent_short_sessions ? 'Yes' : 'No' }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Night Owl Pattern</span>
                                    <Badge :variant="userPatterns.risk_indicators.night_owl_pattern ? 'destructive' : 'outline'">
                                        {{ userPatterns.risk_indicators.night_owl_pattern ? 'Yes' : 'No' }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Weekend Cramming</span>
                                    <Badge :variant="userPatterns.risk_indicators.weekend_cramming ? 'destructive' : 'outline'">
                                        {{ userPatterns.risk_indicators.weekend_cramming ? 'Yes' : 'No' }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Recent Sessions Tab -->
                <TabsContent value="history" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="h-5 w-5" />
                                Recent Learning Sessions
                            </CardTitle>
                            <CardDescription>User's last 10 learning sessions</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Course</TableHead>
                                        <TableHead>Duration</TableHead>
                                        <TableHead>Attention</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="recentSession in recentSessions" :key="recentSession.id">
                                        <TableCell>{{ new Date(recentSession.date).toLocaleDateString() }}</TableCell>
                                        <TableCell>{{ recentSession.course_name }}</TableCell>
                                        <TableCell>{{ recentSession.duration }}</TableCell>
                                        <TableCell>
                                            <span :class="recentSession.attention_score < 50 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                                {{ recentSession.attention_score }}%
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link :href="route('admin.analytics.session-details', recentSession.id)">
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    View
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
