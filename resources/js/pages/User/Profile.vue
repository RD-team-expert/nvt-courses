<!-- User Profile Dashboard Page - Fixed Layout -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { BreadcrumbItemType } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    BookOpen,
    CheckCircle,
    Users,
    Zap,
    User,
    Award,
    ExternalLink,
    Building2,
    GraduationCap,
    Calendar,
    Shield,
    Crown,
    UserCheck,
    TrendingUp
} from 'lucide-vue-next'

// Define interfaces for better type safety
interface Course {
    id: number;
    name: string;
    status: string;
    completion?: number;
}

interface Evaluation {
    id: number;
    course_name: string;
    total_score: number;
    incentive_amount?: number;
    performance_level?: number;
    created_at: string;
}

interface ManagerRole {
    id: number;
    role_type: 'direct_manager' | 'project_manager' | 'department_head' | 'senior_manager' | 'team_lead';
    role_display: string;
    is_primary: boolean;
    department: string;
    authority_level: number;
}

interface DirectReport {
    id: number;
    name: string;
    level?: string;
    department?: string;
}

const props = defineProps({
    user: Object,
    managerRoles: {
        type: Array as () => ManagerRole[],
        default: () => []
    },
    directReports: {
        type: Array as () => DirectReport[],
        default: () => []
    },
    managers: {
        type: Array as () => any[],
        default: () => []
    },
    evaluations: {
        type: Array as () => Evaluation[],
        default: () => []
    },
    courses: {
        type: Array as () => Course[],
        default: () => []
    },
    recentActivity: {
        type: Array as () => any[],
        default: () => []
    }
})

// Get level badge variant
const getLevelVariant = (levelCode: string): 'destructive' | 'outline' | 'default' | 'secondary' => {
    const variants: Record<string, 'destructive' | 'outline' | 'default' | 'secondary'> = {
        'L1': 'secondary',
        'L2': 'default',
        'L3': 'secondary',
        'L4': 'destructive',
    }
    return variants[levelCode] || 'outline'
}

// Get role badge variant
const getRoleVariant = (roleType: string): 'destructive' | 'outline' | 'default' | 'secondary' => {
    const variants: Record<string, 'destructive' | 'outline' | 'default' | 'secondary'> = {
        'direct_manager': 'secondary',
        'project_manager': 'default',
        'department_head': 'default',
        'senior_manager': 'destructive',
        'team_lead': 'secondary',
    }
    return variants[roleType] || 'outline'
}

// Get evaluation performance level
const getPerformanceLevel = (score: number) => {
    if (score >= 13) return { label: 'Outstanding', variant: 'default' as const, color: 'text-green-600' }
    if (score >= 10) return { label: 'Reliable', variant: 'secondary' as const, color: 'text-blue-600' }
    if (score >= 7) return { label: 'Developing', variant: 'outline' as const, color: 'text-yellow-600' }
    if (score >= 0) return { label: 'Underperforming', variant: 'destructive' as const, color: 'text-red-600' }
    return { label: 'Not Rated', variant: 'outline' as const, color: 'text-gray-600' }
}

// Get performance level badge class
const getPerformanceLevelBadgeClass = (score: number) => {
    if (score >= 13) return 'bg-green-100 text-green-800 border-green-200'
    if (score >= 10) return 'bg-blue-100 text-blue-800 border-blue-200'
    if (score >= 7) return 'bg-yellow-100 text-yellow-800 border-yellow-200'
    if (score >= 0) return 'bg-red-100 text-red-800 border-red-200'
    return 'bg-gray-100 text-gray-800 border-gray-200'
}

// Get status variant
const getStatusVariant = (status: string) => {
    if (status === 'active') return 'default'
    if (status === 'inactive') return 'destructive'
    if (status === 'on_leave') return 'secondary'
    return 'outline'
}

// Get course status variant
const getCourseStatusVariant = (status: string) => {
    if (status === 'completed') return 'default'
    if (status === 'in_progress') return 'secondary'
    return 'outline'
}

// Stats
const stats = computed(() => ({
    managerRoles: props.managerRoles?.length || 0,
    directReports: props.directReports?.length || 0,
    managers: props.managers?.length || 0,
    evaluations: props.evaluations?.length || 0,
    courses: props.courses?.length || 0,
    isManager: (props.managerRoles?.length || 0) > 0
}))

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Profile', href: '#' }
]

// Latest evaluation
const latestEvaluation = computed<Evaluation | null>(() => {
    if (!props.evaluations || props.evaluations.length === 0) return null
    return props.evaluations[0] // Assuming they're ordered by date
})

// Course completion rate
const courseCompletionRate = computed(() => {
    if (!props.courses || props.courses.length === 0) return 0
    const completed = props.courses.filter((course: Course) => course.status === 'completed').length
    return Math.round((completed / props.courses.length) * 100)
})

// Truncate long text
const truncateText = (text: string, maxLength: number = 20) => {
    if (!text) return ''
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto space-y-6">
            <!-- Profile Header -->
            <Card class="bg-gradient-to-r from-blue-500 to-purple-600">
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-white">
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-20 w-20 border-2 border-white/30">
                                <AvatarFallback class="bg-white/20 text-white text-2xl font-bold border border-white/30">
                                    {{ user?.name?.charAt(0).toUpperCase() || '?' }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 class="text-3xl font-bold">{{ user?.name || 'Your Name' }}</h1>
                                <p class="text-blue-100 mt-1">{{ user?.email || 'your.email@company.com' }}</p>
                                <div class="mt-3 flex items-center space-x-4">

                                    <Badge v-if="user?.level" variant="secondary" class="bg-white/20 text-white border border-white/30">
                                        <GraduationCap class="h-3 w-3 mr-1" />
                                        {{ user.level.code }} - {{ user.level.name }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 w-full sm:w-auto">
                            <Button asChild variant="secondary" class="bg-white/20 text-white border border-white/30 hover:bg-white/30">
                                <Link :href="route('user.evaluations.index')">
                                    <Award class="h-4 w-4 mr-2" />
                                    My Evaluations
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center">
                                    <BookOpen class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground">Completed Courses</p>
                                <p class="text-2xl font-bold">{{ stats.courses }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <CheckCircle class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground">Evaluations</p>
                                <p class="text-2xl font-bold">{{ stats.evaluations }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Users class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground">Team Members</p>
                                <p class="text-2xl font-bold">{{ stats.directReports }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <Zap class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground">Completion Rate</p>
                                <p class="text-2xl font-bold">{{ courseCompletionRate }}%</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Personal Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Personal Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Department</dt>
                            <dd>
                                <div v-if="user?.department" class="text-sm font-medium flex items-center gap-1">
                                    <Building2 class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ user.department.name }}</span>
                                    <span v-if="user.department.code" class="text-muted-foreground ml-1">({{ user.department.code }})</span>
                                </div>
                                <div v-else class="text-sm text-muted-foreground italic">No department assigned</div>
                            </dd>
                        </div>

                        <Separator />

                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Position Level</dt>
                            <dd>
                                <Badge v-if="user?.level" :variant="getLevelVariant(user.level.code)">
                                    <GraduationCap class="h-3 w-3 mr-1" />
                                    {{ user.level.code }} - Employee
                                </Badge>
                                <span v-else class="text-sm text-muted-foreground italic">No level assigned</span>
                            </dd>
                        </div>



                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Direct Manager</dt>
                            <dd>
                                <div v-if="user && user.direct_manager" class="text-sm font-medium">
                                    {{ user.direct_manager.name }}
                                </div>
                                <div v-else class="text-sm text-muted-foreground italic">No direct manager assigned</div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Join Date</dt>
                            <dd class="text-sm font-medium flex items-center gap-1">
                                <Calendar class="h-4 w-4 shrink-0" />
                                <span>{{ user?.created_at ? new Date(user.created_at).toLocaleDateString() : 'Unknown' }}</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Status</dt>
                            <dd>
                                <Badge :variant="getStatusVariant(user?.status)" class="bg-gray-900 text-white">
                                    {{ user?.status || 'active' }}
                                </Badge>
                            </dd>
                        </div>
                    </CardContent>
                </Card>

                <!-- Latest Evaluation -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <Award class="h-5 w-5" />
                                Latest Evaluation
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm">
                                <Link :href="route('user.evaluations.index')" class="text-sm">
                                    View All
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="latestEvaluation" class="space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-medium truncate pr-2">{{ truncateText(latestEvaluation.course_name || 'GHFHG', 15) }}</h3>
                                    <Badge
                                        :class="getPerformanceLevelBadgeClass(latestEvaluation.total_score || 0)"
                                        class="shrink-0 text-xs px-2 py-1">
                                        {{ getPerformanceLevel(latestEvaluation.total_score || 0).label }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-2xl font-bold">15<span class="text-sm text-muted-foreground">/25</span></p>
                                        <p class="text-sm text-muted-foreground">Total Score</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold" :class="getPerformanceLevel(latestEvaluation.total_score || 0).color">
                                            {{ getPerformanceLevel(latestEvaluation.total_score || 0).label }}
                                        </p>
                                        <p class="text-sm text-muted-foreground">Performance Level</p>
                                    </div>
                                </div>
                                <div class="text-xs text-muted-foreground flex items-center gap-1">
                                    <Calendar class="h-3 w-3 shrink-0" />
                                    <span>Evaluated on 9/22/2025</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <Award class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <CardTitle class="text-sm mb-2">No evaluations yet</CardTitle>
                            <CardDescription>Your performance evaluations will appear here.</CardDescription>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Courses -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <BookOpen class="h-5 w-5" />
                                Recent Courses
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm">
                                <Link :href="route('courses.index')" class="text-sm">
                                    View All
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="courses && courses.length > 0" class="space-y-3">
                            <!-- First Course -->
                            <Card class="hover:bg-accent/50 transition-colors">
                                <CardContent class="p-3 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="font-medium text-sm truncate flex-1" :title="'sesesesesese'">
                                            sesesesesese
                                        </h3>
                                        <Badge variant="default" class="shrink-0 bg-gray-900 text-white text-xs">
                                            Completed
                                        </Badge>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        0% Complete
                                    </div>
                                    <Progress :value="0" class="w-full h-2" />
                                </CardContent>
                            </Card>

                            <!-- Second Course -->
                            <Card class="hover:bg-accent/50 transition-colors">
                                <CardContent class="p-3 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="font-medium text-sm truncate flex-1" :title="'mmmmmmmmmmmmmmmmmmm'">
                                            mmmmmmmmmmmmm...
                                        </h3>
                                        <Badge variant="default" class="shrink-0 bg-gray-900 text-white text-xs">
                                            Completed
                                        </Badge>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        0% Complete
                                    </div>
                                    <Progress :value="0" class="w-full h-2" />
                                </CardContent>
                            </Card>

                            <!-- Third Course -->
                            <Card class="hover:bg-accent/50 transition-colors">
                                <CardContent class="p-3 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="font-medium text-sm truncate flex-1">
                                            sleman
                                        </h3>
                                        <Badge variant="secondary" class="shrink-0 text-xs">
                                            In Progress
                                        </Badge>
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        0% Complete
                                    </div>
                                    <Progress :value="0" class="w-full h-2" />
                                </CardContent>
                            </Card>
                        </div>

                        <div v-else class="text-center py-8">
                            <BookOpen class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <CardTitle class="text-sm mb-2">No courses yet</CardTitle>
                            <CardDescription>Your enrolled courses will appear here.</CardDescription>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Manager Roles & Team (if applicable) -->
            <div v-if="stats.isManager" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Manager Roles -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Shield class="h-5 w-5" />
                            My Management Roles
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="managerRoles && managerRoles.length > 0" class="space-y-3">
                            <Card
                                v-for="role in managerRoles"
                                :key="role.id"
                                class="transition-colors"
                                :class="{ 'border-primary bg-primary/5': role.is_primary }"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <Badge :variant="getRoleVariant(role.role_type)">
                                            {{ role.role_display }}
                                        </Badge>
                                        <Badge v-if="role.is_primary" variant="default">
                                            <Crown class="h-3 w-3 mr-1" />
                                            Primary Role
                                        </Badge>
                                    </div>
                                    <div class="text-sm font-medium mb-1 flex items-center gap-1">
                                        <Building2 class="h-4 w-4 shrink-0" />
                                        <span class="truncate">{{ role.department }}</span>
                                    </div>
                                    <div class="text-xs text-muted-foreground flex items-center gap-1">
                                        <TrendingUp class="h-3 w-3 shrink-0" />
                                        Authority Level {{ role.authority_level }}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <!-- Direct Reports -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                My Team ({{ stats.directReports }})
                            </CardTitle>
                            <Button
                                v-if="stats.directReports > 0"
                                asChild
                                variant="ghost"
                                size="sm"
                            >
                                <Link :href="route('user.team.index')" class="text-sm">
                                    Manage Team
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="directReports && directReports.length > 0" class="space-y-3">
                            <Card
                                v-for="report in directReports.slice(0, 4)"
                                :key="report.id"
                                class="hover:bg-accent/50 transition-colors"
                            >
                                <CardContent class="flex items-center space-x-3 p-3">
                                    <Avatar class="h-8 w-8 shrink-0">
                                        <AvatarFallback class="text-xs">
                                            {{ report.name.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium truncate">{{ report.name }}</div>
                                        <div class="text-xs text-muted-foreground truncate">{{ report.level || 'Staff' }} • {{ report.department || 'No Dept' }}</div>
                                    </div>
                                </CardContent>
                            </Card>
                            <div v-if="directReports.length > 4" class="text-center pt-2">
                                <Button asChild variant="ghost" size="sm">
                                    <Link :href="route('user.team.index')" class="text-sm">
                                        <UserCheck class="h-4 w-4 mr-1" />
                                        View {{ directReports.length - 4 }} more team members
                                    </Link>
                                </Button>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <CardTitle class="text-sm mb-2">No direct reports</CardTitle>
                            <CardDescription>Your team members will appear here.</CardDescription>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
