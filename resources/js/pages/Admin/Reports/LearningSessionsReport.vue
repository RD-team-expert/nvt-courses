<!--
  Learning Sessions Report Page
  Comprehensive reporting interface for tracking learning sessions and engagement metrics
-->
<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
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
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Download,
    Filter,
    RotateCcw,
    User,
    Activity,
    Calendar,
    Clock,
    Target,
    AlertTriangle,
    CheckCircle,
    Play,
    Pause,
    SkipForward,
    Eye,
    Brain,
    Shield,
    Zap
} from 'lucide-vue-next'

const props = defineProps({
    sessions: Object,
    courses: Array,
    users: Array,
    filters: Object,
    stats: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Learning Sessions', href: route('admin.reports.course-online.learning-sessions') }
]

// Filter state
const filters = ref({
    course_id: props.filters?.course_id || '',
    user_id: props.filters?.user_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    suspicious_only: props.filters?.suspicious_only || false,
})

// Modal state for session details
const showModal = ref(false)
const modalTitle = ref('')
const selectedSession = ref(null)

// ✅ ENGAGEMENT LEVEL HELPER FUNCTIONS
const getEngagementBadgeVariant = (level) => {
    const normalizedLevel = level ? level.toLowerCase() : 'unknown'

    switch (normalizedLevel) {
        case 'high':
            return 'default'
        case 'medium':
            return 'secondary'
        case 'low':
            return 'outline'
        case 'very low':
            return 'destructive'
        default:
            return 'secondary'
    }
}

const getEngagementIcon = (level) => {
    const normalizedLevel = level ? level.toLowerCase() : 'unknown'

    switch (normalizedLevel) {
        case 'high':
            return Target
        case 'medium':
            return Activity
        case 'low':
            return AlertTriangle
        case 'very low':
            return AlertTriangle
        default:
            return Activity
    }
}

const getCheatingRiskBadgeVariant = (risk) => {
    const normalizedRisk = risk ? risk.toLowerCase() : 'none'

    switch (normalizedRisk) {
        case 'critical':
        case 'very high':
            return 'destructive'
        case 'high':
            return 'destructive'
        case 'medium':
            return 'outline'
        case 'low':
            return 'secondary'
        default:
            return 'secondary'
    }
}

const getCheatingRiskIcon = (risk) => {
    const normalizedRisk = risk ? risk.toLowerCase() : 'none'

    switch (normalizedRisk) {
        case 'critical':
        case 'very high':
            return Shield
        case 'high':
            return AlertTriangle
        case 'medium':
            return Eye
        case 'low':
            return CheckCircle
        default:
            return CheckCircle
    }
}

// Handle select changes
const handleCourseChange = (value: string) => {
    filters.value.course_id = value === 'all' ? '' : value
}

const handleUserChange = (value: string) => {
    filters.value.user_id = value === 'all' ? '' : value
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    const filterParams = { ...filters.value }

    if (filterParams.date_from) {
        filterParams.date_from = filterParams.date_from.trim()
    }
    if (filterParams.date_to) {
        filterParams.date_to = filterParams.date_to.trim()
    }

    router.get(route('admin.reports.course-online.learning-sessions'), filterParams, {
        preserveState: true,
        replace: true
    })
}, 500)

// Watch for filter changes
watch(filters, () => {
    applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
    filters.value = {
        course_id: '',
        user_id: '',
        date_from: '',
        date_to: '',
        suspicious_only: false,
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.course-online.export.learning-sessions') + '?' + queryParams
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Format duration
const formatDuration = (minutes) => {
    if (!minutes || minutes <= 0) return '0 min'

    if (minutes < 60) return `${Math.round(minutes)} min`

    const hours = Math.floor(minutes / 60)
    const remainingMinutes = Math.round(minutes % 60)

    return remainingMinutes > 0
        ? `${hours}h ${remainingMinutes}m`
        : `${hours}h`
}

// Modal functions
const showSessionDetails = (session) => {
    selectedSession.value = session
    modalTitle.value = `Session Details - ${session.user_name}`
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedSession.value = null
    modalTitle.value = ''
}

// Close modal on escape key
const handleKeydown = (event) => {
    if (event.key === 'Escape' && showModal.value) {
        closeModal()
    }
}

// Add event listener for escape key
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown)
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Learning Sessions Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Comprehensive reporting interface for tracking learning sessions and engagement metrics</p>
                </div>
                <Button @click="exportToCsv" class="w-full sm:w-auto">
                    <Download class="mr-2 h-4 w-4" />
                    Export to CSV
                </Button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Activity class="h-8 w-8 text-blue-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Sessions</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.total_sessions || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <AlertTriangle class="h-8 w-8 text-red-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Suspicious Sessions</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.suspicious_sessions || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Target class="h-8 w-8 text-yellow-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Avg Attention</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.average_attention_score || 0 }}%</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Clock class="h-8 w-8 text-green-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Avg Duration</p>
                                <p class="text-2xl font-bold text-foreground">{{ formatDuration(stats?.average_session_duration) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Zap class="h-8 w-8 text-purple-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">High Engagement</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.high_engagement_sessions || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <div class="flex items-center">
                        <Filter class="mr-2 h-5 w-5 text-primary" />
                        <div>
                            <CardTitle>Filter Sessions</CardTitle>
                            <CardDescription>Use the filters below to narrow down the session records</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                        <div class="space-y-2">
                            <Label for="course_filter">Course</Label>
                            <Select
                                :model-value="filters.course_id || 'all'"
                                @update:model-value="handleCourseChange"
                            >
                                <SelectTrigger id="course_filter">
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
                            <Label for="user_filter">User</Label>
                            <Select
                                :model-value="filters.user_id || 'all'"
                                @update:model-value="handleUserChange"
                            >
                                <SelectTrigger id="user_filter">
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
                            <Label for="date_from">Session From</Label>
                            <Input
                                id="date_from"
                                type="date"
                                v-model="filters.date_from"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="date_to">Session To</Label>
                            <Input
                                id="date_to"
                                type="date"
                                v-model="filters.date_to"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Options</Label>
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="suspicious_only"
                                    :checked="filters.suspicious_only"
                                    @update:checked="(value) => filters.suspicious_only = value"
                                />
                                <Label for="suspicious_only" class="text-sm font-normal">
                                    Suspicious Only
                                </Label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <Button @click="resetFilters" variant="outline">
                            <RotateCcw class="mr-2 h-4 w-4" />
                            Reset Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Sessions Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>
                                    <div class="flex items-center">
                                        <User class="mr-2 h-4 w-4" />
                                        User
                                    </div>
                                </TableHead>
                                <TableHead class="hidden sm:table-cell">
                                    <div class="flex items-center">
                                        <Activity class="mr-2 h-4 w-4" />
                                        Course & Content
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Calendar class="mr-2 h-4 w-4" />
                                        Session Time
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Clock class="mr-2 h-4 w-4" />
                                        Duration
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Brain class="mr-2 h-4 w-4" />
                                        Engagement
                                    </div>
                                </TableHead>
                                <TableHead class="hidden md:table-cell">
                                    <div class="flex items-center">
                                        <Play class="mr-2 h-4 w-4" />
                                        Activities
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Shield class="mr-2 h-4 w-4" />
                                        Status
                                    </div>
                                </TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="sessions.data.length === 0">
                                <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                                    <div class="flex flex-col items-center">
                                        <Activity class="h-12 w-12 text-muted-foreground mb-2" />
                                        No learning sessions found
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else v-for="(session, i) in sessions.data" :key="i" class="hover:bg-muted/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ session.user_name }}</div>
                                        <div class="text-sm text-muted-foreground hidden sm:block">{{ session.user_email }}</div>
                                        <div class="text-sm text-muted-foreground sm:hidden">{{ session.course_name }}</div>
                                    </div>
                                </TableCell>

                                <TableCell class="hidden sm:table-cell">
                                    <div class="space-y-1">
                                        <Badge variant="outline">{{ session.course_name }}</Badge>
                                        <div class="text-sm text-muted-foreground">{{ session.content_title }}</div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="text-sm text-foreground">{{ session.session_start }}</div>
                                        <div v-if="session.session_end" class="text-sm text-muted-foreground">
                                            Ended: {{ session.session_end }}
                                        </div>
                                        <Badge v-else variant="destructive" class="text-xs">
                                            Active
                                        </Badge>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-foreground">{{ session.duration }}</div>
                                        <div v-if="session.video_completion > 0" class="text-xs text-muted-foreground">
                                            {{ session.video_completion }}% watched
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="space-y-2">
                                        <Badge :variant="getEngagementBadgeVariant(session.engagement_level)" class="flex items-center w-fit">
                                            <component :is="getEngagementIcon(session.engagement_level)" class="mr-1 h-3 w-3" />
                                            {{ session.engagement_level }}
                                        </Badge>
                                        <div class="text-xs text-muted-foreground">{{ session.attention_score }}% attention</div>
                                    </div>
                                </TableCell>

                                <TableCell class="hidden md:table-cell">
                                    <div class="space-y-1 text-xs text-muted-foreground">
                                        <div v-if="session.skip_count > 0" class="flex items-center">
                                            <SkipForward class="mr-1 h-3 w-3 text-orange-600" />
                                            {{ session.skip_count }} skips
                                        </div>
                                        <div v-if="session.seek_count > 0" class="flex items-center">
                                            <Eye class="mr-1 h-3 w-3 text-blue-600" />
                                            {{ session.seek_count }} seeks
                                        </div>
                                        <div v-if="session.skip_count === 0 && session.seek_count === 0" class="text-muted-foreground">
                                            No activity
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div class="flex flex-col space-y-1">
                                        <Badge v-if="session.cheating_score > 20"
                                               :variant="getCheatingRiskBadgeVariant(session.cheating_risk)"
                                               class="flex items-center w-fit">
                                            <component :is="getCheatingRiskIcon(session.cheating_risk)" class="mr-1 h-3 w-3" />
                                            {{ session.cheating_risk }} Risk
                                        </Badge>

                                        <Badge v-if="session.is_suspicious" variant="destructive" class="flex items-center w-fit">
                                            <AlertTriangle class="mr-1 h-3 w-3" />
                                            Suspicious
                                        </Badge>

                                        <Badge v-if="!session.is_suspicious && session.cheating_score <= 20"
                                               variant="secondary"
                                               class="flex items-center w-fit">
                                            <CheckCircle class="mr-1 h-3 w-3" />
                                            Normal
                                        </Badge>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Button
                                        @click="showSessionDetails(session)"
                                        variant="link"
                                        size="sm"
                                        class="h-auto p-0"
                                    >
                                        View Details
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>

        <!-- Enhanced Modal for Session Details -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-4xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <Brain class="mr-2 h-5 w-5 text-primary" />
                        {{ modalTitle }}
                    </DialogTitle>
                    <DialogDescription>
                        Detailed session analytics and engagement metrics
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedSession" class="space-y-6 mt-4">
                    <!-- Session Overview -->
                    <div class="bg-muted/50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-foreground mb-3">Session Overview</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <span class="text-sm text-muted-foreground">User:</span>
                                <div class="font-medium">{{ selectedSession.user_name }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Course:</span>
                                <div class="font-medium">{{ selectedSession.course_name }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Duration:</span>
                                <div class="font-medium">{{ selectedSession.duration }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Engagement:</span>
                                <Badge :variant="getEngagementBadgeVariant(selectedSession.engagement_level)" class="flex items-center w-fit">
                                    <component :is="getEngagementIcon(selectedSession.engagement_level)" class="mr-1 h-3 w-3" />
                                    {{ selectedSession.engagement_level }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Engagement Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-primary mb-2">{{ selectedSession.attention_score }}%</div>
                                <div class="text-sm text-muted-foreground">Attention Score</div>
                                <Badge :variant="getEngagementBadgeVariant(selectedSession.engagement_level)" class="mt-2">
                                    {{ selectedSession.engagement_level }}
                                </Badge>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">{{ selectedSession.video_completion || 0 }}%</div>
                                <div class="text-sm text-muted-foreground">Video Completion</div>
                                <div class="w-full bg-muted rounded-full h-2 mt-2">
                                    <div
                                        class="bg-green-500 h-2 rounded-full transition-all duration-300"
                                        :style="{width: (selectedSession.video_completion || 0) + '%'}"
                                    ></div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-red-600 mb-2">{{ selectedSession.cheating_score || 0 }}%</div>
                                <div class="text-sm text-muted-foreground">Cheating Score</div>
                                <Badge v-if="selectedSession.cheating_score > 20"
                                       :variant="getCheatingRiskBadgeVariant(selectedSession.cheating_risk)"
                                       class="mt-2">
                                    {{ selectedSession.cheating_risk }} Risk
                                </Badge>
                                <Badge v-else variant="secondary" class="mt-2">
                                    Low Risk
                                </Badge>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Activity Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Activity Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                                <div class="p-3 bg-muted rounded-lg">
                                    <div class="text-2xl font-bold text-orange-600">{{ selectedSession.skip_count || 0 }}</div>
                                    <div class="text-sm text-muted-foreground">Video Skips</div>
                                </div>
                                <div class="p-3 bg-muted rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ selectedSession.seek_count || 0 }}</div>
                                    <div class="text-sm text-muted-foreground">Seeks</div>
                                </div>
                                <div class="p-3 bg-muted rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">0</div>
                                    <div class="text-sm text-muted-foreground">Pauses</div>
                                </div>
                                <div class="p-3 bg-muted rounded-lg">
                                    <div class="text-2xl font-bold text-purple-600">0</div>
                                    <div class="text-sm text-muted-foreground">Replays</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Alert for Suspicious Activity -->
                    <Card v-if="selectedSession.is_suspicious" class="border-orange-200 bg-orange-50 dark:bg-orange-950">
                        <CardContent class="p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <AlertTriangle class="h-5 w-5 text-orange-500" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-orange-800 dark:text-orange-200">Attention Required</h3>
                                    <div class="mt-2 text-sm text-orange-700 dark:text-orange-300">
                                        <p>This session shows signs of unusual activity that may require review.</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
