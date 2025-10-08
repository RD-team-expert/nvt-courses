<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'

// Icons
import {
    ArrowLeft,
    AlertTriangle,
    Shield,
    Eye,
    Download,
    Filter,
    Search,
    TrendingUp,
    Users,
    BookOpen,
    Clock,
    Zap,
    Target,
    ChevronDown,
    ChevronRight,
    Mail,
    Ban,
    Activity,
    BarChart3
} from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
}

interface SuspiciousSession {
    id: number
    user: User
    course: string
    content_title: string
    session_start: string
    duration: string
    cheating_score: number
    cheating_risk: string
    video_completion: number
    video_watch_time: number
    video_total_duration: number
    skip_count: number
    seek_count: number
    attention_score: number
    cheating_reasons?: string[]
}

interface HighRiskUser {
    id: number
    name: string
    email: string
    suspicious_sessions_count: number
    risk_level?: string
}

interface CheatingPattern {
    avg_skips: number
    avg_seeks: number
    avg_cheating_score: number
    total_incidents: number
}

interface CourseCheatinRate {
    name: string
    total_sessions: number
    suspicious_sessions: number
    cheating_rate: number
}

interface CheatingStats {
    total_incidents: number
    high_risk_sessions?: number
    critical_risk_sessions?: number
    average_cheating_score?: number
    cheating_rate_percentage?: number
    high_risk_users: HighRiskUser[]
    cheating_patterns: CheatingPattern
    course_cheating_rates: CourseCheatinRate[]
}

interface Course {
    id: number
    name: string
}

const props = defineProps<{
    suspiciousSessions: {
        data: SuspiciousSession[]
        current_page?: number
        last_page?: number
        per_page?: number
        total?: number
        from?: number
        to?: number
        links?: any[]
        meta?: {
            total: number
            per_page: number
            current_page: number
            last_page: number
        }
    }
    cheatingStats: CheatingStats
    filters: {
        course_id?: string
        user_id?: string
        min_cheating_score?: string
    }
    users: User[]
    courses: Course[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Report', href: '/admin/reports' },
    { title: 'Cheating Detection', href: '#' }
]

// State
const selectedCourse = ref(props.filters.course_id || 'all')
const selectedUser = ref(props.filters.user_id || 'all')
const minCheatingScore = ref(props.filters.min_cheating_score || '50')
const expandedSessions = ref<Set<number>>(new Set())

// ✅ FIXED: Export state and menu
const showExportMenu = ref(false)
const isExporting = ref(false)

// Computed
const totalIncidents = computed(() => props.cheatingStats.total_incidents || 0)
const highRiskUsers = computed(() => props.cheatingStats.high_risk_users || [])
const severityCounts = computed(() => {
    const sessions = props.suspiciousSessions.data || []
    return {
        critical: sessions.filter(s => s.cheating_score >= 90).length,
        high: sessions.filter(s => s.cheating_score >= 70 && s.cheating_score < 90).length,
        medium: sessions.filter(s => s.cheating_score >= 50 && s.cheating_score < 70).length,
        low: sessions.filter(s => s.cheating_score < 50).length,
    }
})

const topRiskyCourses = computed(() =>
    [...(props.cheatingStats.course_cheating_rates || [])]
        .sort((a, b) => b.cheating_rate - a.cheating_rate)
        .slice(0, 5)
)

// Methods
const applyFilters = () => {
    const params = new URLSearchParams()

    if (selectedCourse.value && selectedCourse.value !== 'all') {
        params.append('course_id', selectedCourse.value)
    }
    if (selectedUser.value && selectedUser.value !== 'all') {
        params.append('user_id', selectedUser.value)
    }
    if (minCheatingScore.value) {
        params.append('min_cheating_score', minCheatingScore.value)
    }

    router.get(route('admin.analytics.cheating-detection'), Object.fromEntries(params))
}

const clearFilters = () => {
    selectedCourse.value = 'all'
    selectedUser.value = 'all'
    minCheatingScore.value = '50'
    router.get(route('admin.analytics.cheating-detection'))
}

const toggleSessionDetails = (sessionId: number) => {
    if (expandedSessions.value.has(sessionId)) {
        expandedSessions.value.delete(sessionId)
    } else {
        expandedSessions.value.add(sessionId)
    }
}

// ✅ FIXED: Export methods
const exportSuspiciousActivity = async () => {
    if (isExporting.value) return

    try {
        isExporting.value = true
        const params = new URLSearchParams()

        // Add current filters
        if (selectedCourse.value && selectedCourse.value !== 'all') {
            params.append('course_id', selectedCourse.value)
        }
        if (selectedUser.value && selectedUser.value !== 'all') {
            params.append('user_id', selectedUser.value)
        }
        if (minCheatingScore.value) {
            params.append('min_cheating_score', minCheatingScore.value)
        }

        params.append('type', 'suspicious_activity')

        // Trigger download
        window.open(route('admin.analytics.export') + '?' + params.toString(), '_blank')

        showExportMenu.value = false
    } catch (error) {
        console.error('Export failed:', error)
        alert('Export failed. Please try again.')
    } finally {
        isExporting.value = false
    }
}

const exportHighRiskUsers = async () => {
    if (isExporting.value) return

    try {
        isExporting.value = true
        window.open(route('admin.analytics.export', { type: 'high_risk_users' }), '_blank')
        showExportMenu.value = false
    } catch (error) {
        console.error('Export failed:', error)
        alert('Export failed. Please try again.')
    } finally {
        isExporting.value = false
    }
}

const exportCourseSecurityReport = async () => {
    if (isExporting.value) return

    try {
        isExporting.value = true
        window.open(route('admin.analytics.export', { type: 'course_security' }), '_blank')
        showExportMenu.value = false
    } catch (error) {
        console.error('Export failed:', error)
        alert('Export failed. Please try again.')
    } finally {
        isExporting.value = false
    }
}

const getRiskBadgeColor = (risk: string): string => {
    switch (risk.toLowerCase()) {
        case 'critical': return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'
        case 'high': return 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800'
        case 'medium': return 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800'
        case 'low': return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800'
        default: return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800'
    }
}

const getScoreColor = (score: number): string => {
    if (score >= 90) return 'text-red-700 dark:text-red-400'
    if (score >= 70) return 'text-orange-700 dark:text-orange-400'
    if (score >= 50) return 'text-yellow-700 dark:text-yellow-400'
    return 'text-blue-700 dark:text-blue-400'
}

const getSessionBgColor = (score: number): string => {
    if (score >= 90) return 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/20'
    if (score >= 70) return 'border-orange-300 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/20'
    if (score >= 50) return 'border-yellow-300 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950/20'
    return 'border-blue-300 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/20'
}

const getSeverityText = (score: number): string => {
    if (score >= 90) return 'Critical'
    if (score >= 70) return 'High'
    if (score >= 50) return 'Medium'
    return 'Low'
}

const formatDuration = (duration: string): string => {
    return duration
}

const formatPercentage = (value: number): string => {
    return `${Math.round(value)}%`
}

const formatTime = (minutes: number): string => {
    if (minutes < 60) return `${Math.round(minutes)}m`
    const hours = Math.floor(minutes / 60)
    const mins = Math.round(minutes % 60)
    return `${hours}h ${mins}m`
}

const sendWarningEmail = async (userId: number) => {
    if (isExporting.value) return

    try {
        isExporting.value = true

        // Use Inertia router for CSRF protection
        router.post(route('admin.analytics.send-warning', userId), {
            session_data: {
                course_name: 'Current Course',
                risk_score: 75,
                risk_level: 'Medium',
                session_date: new Date().toLocaleDateString(),
                duration: '30 minutes',
                reasons: ['Unusual learning pattern detected']
            }
        }, {
            onSuccess: (page) => {
                alert('✅ Warning email sent successfully!')
            },
            onError: (errors) => {
                console.error('Warning email errors:', errors)
                alert('❌ Failed to send warning email: ' + (errors.message || 'Unknown error'))
            },
            onFinish: () => {
                isExporting.value = false
            }
        })

    } catch (error) {
        console.error('Warning email failed:', error)
        alert('❌ Failed to send warning email. Please try again.')
        isExporting.value = false
    }
}
const suspendUser = (userId: number) => {
    if (confirm('Are you sure you want to suspend this user?')) {
        alert(`User ${userId} suspended`)
    }
}

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// ✅ FIXED: Click outside handler
const handleClickOutside = () => {
    showExportMenu.value = false
}
</script>

<template>
    <Head title="Cheating Detection" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>

                    <h1 class="text-3xl font-bold tracking-tight flex items-center gap-3">
                        <Shield class="h-8 w-8 text-red-600 dark:text-red-400" />
                        Cheating Detection & Prevention
                    </h1>
                    <p class="text-muted-foreground mt-1">Monitor and investigate suspicious learning activities</p>
                </div>

                <!-- ✅ FIXED: Export dropdown -->
                <div class="relative">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="showExportMenu = !showExportMenu"
                        :disabled="isExporting"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        {{ isExporting ? 'Exporting...' : 'Export Reports' }}
                        <ChevronDown class="ml-2 h-4 w-4" />
                    </Button>

                    <!-- Export Menu -->
                    <div v-if="showExportMenu" class="absolute right-0 mt-2 w-64 bg-popover border rounded-md shadow-lg z-50">
                        <div class="p-2">
                            <div class="text-sm font-medium text-muted-foreground mb-2">Choose Report Type:</div>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="w-full justify-start mb-1"
                                @click="exportHighRiskUsers"
                                :disabled="isExporting"
                            >
                                <Users class="mr-2 h-4 w-4 text-orange-500" />
                                High Risk Users Report
                            </Button>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="w-full justify-start"
                                @click="exportCourseSecurityReport"
                                :disabled="isExporting"
                            >
                                <BookOpen class="mr-2 h-4 w-4 text-blue-500" />
                                Course Security Report
                            </Button>
                        </div>
                    </div>

                    <!-- Click outside to close -->
                    <div v-if="showExportMenu"
                         class="fixed inset-0 z-40"
                         @click="handleClickOutside">
                    </div>
                </div>
            </div>

            <!-- Security Overview Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Incidents</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-red-600 dark:text-red-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ totalIncidents }}</div>
                        <p class="text-xs text-muted-foreground">Suspicious activities detected</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Critical Risk</CardTitle>
                        <Target class="h-4 w-4 text-red-700 dark:text-red-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-700 dark:text-red-400">{{ severityCounts.critical }}</div>
                        <p class="text-xs text-muted-foreground">Score ≥ 90</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">High Risk Users</CardTitle>
                        <Users class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ highRiskUsers.length }}</div>
                        <p class="text-xs text-muted-foreground">Repeat offenders</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg Risk Score</CardTitle>
                        <BarChart3 class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ Math.round(cheatingStats.average_cheating_score || cheatingStats.cheating_patterns?.avg_cheating_score || 0) }}
                        </div>
                        <p class="text-xs text-muted-foreground">Across all incidents</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Critical Alert -->
            <Alert v-if="severityCounts.critical > 0" class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/20">
                <AlertTriangle class="h-4 w-4 text-red-600 dark:text-red-400" />
                <AlertDescription class="text-red-800 dark:text-red-200">
                    <strong>{{ severityCounts.critical }}</strong> critical risk sessions require immediate attention.
                    These sessions show extremely suspicious patterns and should be investigated immediately.
                </AlertDescription>
            </Alert>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Investigation Filters
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-4">
                            <!-- Course Filter -->
                            <div>
                                <Label for="course">Course</Label>
                                <Select v-model="selectedCourse">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Courses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Courses</SelectItem>
                                        <SelectItem
                                            v-for="course in courses"
                                            :key="course.id"
                                            :value="course.id.toString()"
                                        >
                                            {{ course.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- User Filter -->
                            <div>
                                <Label for="user">User</Label>
                                <Select v-model="selectedUser">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Users" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Users</SelectItem>
                                        <SelectItem
                                            v-for="user in users"
                                            :key="user.id"
                                            :value="user.id.toString()"
                                        >
                                            {{ user.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Risk Score Filter -->
                            <div>
                                <Label for="score">Min Risk Score</Label>
                                <Select v-model="minCheatingScore">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="90">Critical (90+)</SelectItem>
                                        <SelectItem value="70">High (70+)</SelectItem>
                                        <SelectItem value="50">Medium (50+)</SelectItem>
                                        <SelectItem value="30">Low (30+)</SelectItem>
                                        <SelectItem value="0">All Incidents</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-end gap-2">
                                <Button type="submit" class="flex-1">Apply</Button>
                                <Button type="button" variant="outline" @click="clearFilters">Clear</Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Main Content Tabs -->
            <Tabs default-value="incidents" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="incidents">Suspicious Incidents</TabsTrigger>
                    <TabsTrigger value="users">High Risk Users</TabsTrigger>
                    <TabsTrigger value="patterns">Cheating Patterns</TabsTrigger>
                    <TabsTrigger value="courses">Course Risk Analysis</TabsTrigger>
                </TabsList>

                <!-- Suspicious Incidents Tab -->
                <TabsContent value="incidents" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                                Suspicious Learning Sessions
                            </CardTitle>
                            <CardDescription>
                                Showing {{ suspiciousSessions.data?.length || 0 }} suspicious sessions
                                {{ suspiciousSessions.total ? `of ${suspiciousSessions.total} total` : '' }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!suspiciousSessions.data || suspiciousSessions.data.length === 0" class="text-center py-12">
                                <Shield class="h-16 w-16 mx-auto mb-4 text-green-600 dark:text-green-400" />
                                <h3 class="text-lg font-medium mb-2">No Suspicious Activities</h3>
                                <p class="text-muted-foreground">All learning sessions appear normal</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="session in suspiciousSessions.data"
                                    :key="session.id"
                                    class="border rounded-lg p-4"
                                    :class="getSessionBgColor(session.cheating_score)"
                                >
                                    <Collapsible>
                                        <CollapsibleTrigger
                                            @click="toggleSessionDetails(session.id)"
                                            class="w-full text-left"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4">
                                                    <!-- Risk Badge -->
                                                    <Badge :class="getRiskBadgeColor(getSeverityText(session.cheating_score))">
                                                        {{ getSeverityText(session.cheating_score) }}
                                                    </Badge>

                                                    <!-- User & Course Info -->
                                                    <div>
                                                        <div class="font-medium">{{ session.user.name }}</div>
                                                        <div class="text-sm text-muted-foreground">
                                                            {{ session.course }} • {{ session.content_title }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-4">
                                                    <!-- Risk Score -->
                                                    <div class="text-right">
                                                        <div class="text-lg font-bold" :class="getScoreColor(session.cheating_score)">
                                                            {{ session.cheating_score }}
                                                        </div>
                                                        <div class="text-xs text-muted-foreground">Risk Score</div>
                                                    </div>

                                                    <!-- Session Time -->
                                                    <div class="text-right">
                                                        <div class="text-sm font-medium">{{ formatDate(session.session_start) }}</div>
                                                        <div class="text-xs text-muted-foreground">{{ session.duration }}</div>
                                                    </div>

                                                    <!-- Expand Icon -->
                                                    <ChevronDown
                                                        class="h-4 w-4 transition-transform"
                                                        :class="{ 'rotate-180': expandedSessions.has(session.id) }"
                                                    />
                                                </div>
                                            </div>
                                        </CollapsibleTrigger>

                                        <CollapsibleContent v-if="expandedSessions.has(session.id)">
                                            <div class="mt-4 pt-4 border-t space-y-4">
                                                <!-- Suspicious Reasons -->
                                                <div v-if="session.cheating_reasons && session.cheating_reasons.length > 0"
                                                     class="bg-card border rounded-lg p-3">
                                                    <h4 class="font-medium mb-2 text-red-700 dark:text-red-400">🚨 Suspicious Indicators:</h4>
                                                    <ul class="text-sm space-y-1">
                                                        <li v-for="reason in session.cheating_reasons" :key="reason"
                                                            class="flex items-center gap-2">
                                                            <AlertTriangle class="h-3 w-3 text-red-500" />
                                                            {{ reason }}
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- Detailed Metrics -->
                                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                                    <div class="text-center p-3 bg-card border rounded">
                                                        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ session.attention_score }}%</div>
                                                        <div class="text-xs text-muted-foreground">Attention Score</div>
                                                    </div>
                                                    <div class="text-center p-3 bg-card border rounded">
                                                        <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ session.video_completion }}%</div>
                                                        <div class="text-xs text-muted-foreground">Video Completion</div>
                                                    </div>
                                                    <div class="text-center p-3 bg-card border rounded">
                                                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ session.skip_count }}</div>
                                                        <div class="text-xs text-muted-foreground">Skip Count</div>
                                                    </div>
                                                    <div class="text-center p-3 bg-card border rounded">
                                                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ session.seek_count }}</div>
                                                        <div class="text-xs text-muted-foreground">Seek Count</div>
                                                    </div>
                                                </div>

                                                <!-- Video Analytics -->
                                                <div class="bg-card border rounded-lg p-3">
                                                    <h4 class="font-medium mb-2">Video Behavior Analysis</h4>
                                                    <div class="grid gap-2 md:grid-cols-3 text-sm">
                                                        <div>
                                                            <span class="text-muted-foreground">Watch Time:</span>
                                                            <span class="ml-2 font-medium">{{ formatTime(session.video_watch_time) }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-muted-foreground">Total Duration:</span>
                                                            <span class="ml-2 font-medium">{{ formatTime(session.video_total_duration) }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-muted-foreground">Completion:</span>
                                                            <span class="ml-2 font-medium">{{ session.video_completion }}%</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="flex items-center gap-2 pt-2">
                                                    <Button size="sm" variant="outline" asChild>
                                                        <Link :href="route('admin.analytics.session-details', session.id)">
                                                            <Eye class="mr-2 h-4 w-4" />
                                                            View Details
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        @click="sendWarningEmail(session.user.id)"
                                                    >
                                                        <Mail class="mr-2 h-4 w-4" />
                                                        Send Warning
                                                    </Button>
                                                    <Button
                                                        v-if="session.cheating_score >= 90"
                                                        size="sm"
                                                        variant="destructive"
                                                        @click="suspendUser(session.user.id)"
                                                    >
                                                        <Ban class="mr-2 h-4 w-4" />
                                                        Suspend User
                                                    </Button>
                                                </div>
                                            </div>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="suspiciousSessions.links && suspiciousSessions.links.length > 0"
                                 class="flex items-center justify-center space-x-2 mt-6">
                                <Button
                                    v-for="link in suspiciousSessions.links"
                                    :key="link.label"
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url"
                                    size="sm"
                                    @click="link.url && router.get(link.url)"
                                    v-html="link.label"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- High Risk Users Tab -->
                <TabsContent value="users" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5 text-red-600 dark:text-red-400" />
                                High Risk Users
                            </CardTitle>
                            <CardDescription>Users with multiple suspicious activities</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="highRiskUsers.length === 0" class="text-center py-12">
                                <Shield class="h-16 w-16 mx-auto mb-4 text-green-600 dark:text-green-400" />
                                <h3 class="text-lg font-medium mb-2">No High Risk Users</h3>
                                <p class="text-muted-foreground">All users are showing normal learning behavior</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="user in highRiskUsers"
                                    :key="user.id"
                                    class="flex items-center justify-between p-4 border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/20 rounded-lg"
                                >
                                    <div>
                                        <div class="font-medium">{{ user.name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                                {{ user.suspicious_sessions_count }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">Suspicious Sessions</div>
                                        </div>
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline">
                                                <Eye class="mr-2 h-4 w-4" />
                                                View Details
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="sendWarningEmail(user.id)"
                                            >
                                                <Mail class="mr-2 h-4 w-4" />
                                                Send Warning
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Cheating Patterns Tab -->
                <TabsContent value="patterns" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Behavior Patterns -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Activity class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                    Common Cheating Patterns
                                </CardTitle>
                                <CardDescription>Average behavior in suspicious sessions</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span>Average Skips per Session</span>
                                    <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                        {{ Math.round(cheatingStats.cheating_patterns?.avg_skips || 0) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Average Seeks per Session</span>
                                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                        {{ Math.round(cheatingStats.cheating_patterns?.avg_seeks || 0) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Average Cheating Score</span>
                                    <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                        {{ Math.round(cheatingStats.cheating_patterns?.avg_cheating_score || 0) }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Total Incidents</span>
                                    <div class="text-lg font-bold">
                                        {{ cheatingStats.cheating_patterns?.total_incidents || 0 }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Risk Distribution -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <BarChart3 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    Risk Level Distribution
                                </CardTitle>
                                <CardDescription>Breakdown by severity</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-red-700 dark:bg-red-400 rounded"></div>
                                        <span>Critical (90+)</span>
                                    </div>
                                    <div class="text-lg font-bold text-red-700 dark:text-red-400">{{ severityCounts.critical }}</div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-orange-600 dark:bg-orange-400 rounded"></div>
                                        <span>High (70-89)</span>
                                    </div>
                                    <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ severityCounts.high }}</div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-yellow-600 dark:bg-yellow-400 rounded"></div>
                                        <span>Medium (50-69)</span>
                                    </div>
                                    <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ severityCounts.medium }}</div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 bg-blue-600 dark:bg-blue-400 rounded"></div>
                                        <span>Low (30-49)</span>
                                    </div>
                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ severityCounts.low }}</div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Course Risk Analysis Tab -->
                <TabsContent value="courses" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BookOpen class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                Course Risk Analysis
                            </CardTitle>
                            <CardDescription>Cheating rates by course</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Course Name</TableHead>
                                        <TableHead>Total Sessions</TableHead>
                                        <TableHead>Suspicious Sessions</TableHead>
                                        <TableHead>Cheating Rate</TableHead>
                                        <TableHead>Risk Level</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="course in cheatingStats.course_cheating_rates" :key="course.name">
                                        <TableCell class="font-medium">{{ course.name }}</TableCell>
                                        <TableCell>{{ course.total_sessions }}</TableCell>
                                        <TableCell>
                                            <span :class="course.suspicious_sessions > 0 ? 'text-red-600 dark:text-red-400 font-medium' : ''">
                                                {{ course.suspicious_sessions }}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span :class="getScoreColor(course.cheating_rate)" class="font-medium">
                                                {{ course.cheating_rate }}%
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                :class="course.cheating_rate >= 20 ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' :
                                                       course.cheating_rate >= 10 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400' :
                                                       course.cheating_rate > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' :
                                                       'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'"
                                            >
                                                {{
                                                    course.cheating_rate >= 20 ? 'High Risk' :
                                                        course.cheating_rate >= 10 ? 'Medium Risk' :
                                                            course.cheating_rate > 0 ? 'Low Risk' :
                                                                'Safe'
                                                }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="outline" size="sm">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Investigate
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
