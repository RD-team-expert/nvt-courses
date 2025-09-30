<!-- Team Management Page -->
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
import { Separator } from '@/components/ui/separator'

// Icons
import {
    Users,
    CheckCircle,
    Building2,
    Zap,
    ArrowLeft,
    Eye,
    BookOpen,
    Award,
    DollarSign,
    User,
    Mail,
    GraduationCap
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    managerRoles: Array,
    directReports: Array,
    stats: Object,
})

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Team', href: '#' }
]

const getPerformanceLevel = (score: number) => {
    if (score >= 20) return { label: 'Exceptional', variant: 'default' as const }
    if (score >= 15) return { label: 'Excellent', variant: 'default' as const }
    if (score >= 10) return { label: 'Good', variant: 'secondary' as const }
    if (score >= 5) return { label: 'Average', variant: 'secondary' as const }
    return { label: 'Needs Improvement', variant: 'destructive' as const }
}

const getStatusVariant = (status: string) => {
    return status === 'active' ? 'default' : 'destructive'
}

const formatCurrency = (amount: number) => {
    return `$${parseFloat(amount?.toString() || '0').toFixed(2)}`
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold">My Team</h1>
                            <CardDescription class="mt-2 text-lg">
                                Manage and track your direct reports' performance
                            </CardDescription>
                        </div>
                        <Button asChild>
                            <Link :href="route('user.profile.index')">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Profile
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                                    <Users class="h-6 w-6 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Team Members</p>
                                <p class="text-2xl font-bold">{{ stats?.total_reports || 0 }}</p>
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
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Active Members</p>
                                <p class="text-2xl font-bold">{{ stats?.active_reports || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Building2 class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Departments</p>
                                <p class="text-2xl font-bold">{{ stats?.departments || 0 }}</p>
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
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Avg Completion</p>
                                <p class="text-2xl font-bold">{{ Math.round(stats?.avg_course_completion || 0) }}%</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Team Members List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-6 w-6" />
                        Team Members
                    </CardTitle>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="directReports && directReports.length > 0" class="divide-y divide-border">
                        <div
                            v-for="report in directReports"
                            :key="report.id"
                            class="p-6 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <Avatar class="h-12 w-12">
                                        <AvatarFallback class="bg-primary/10 text-primary text-lg font-medium">
                                            {{ report.name.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <h3 class="text-lg font-medium flex items-center gap-2">
                                            {{ report.name }}
                                        </h3>
                                        <p class="text-sm text-muted-foreground flex items-center gap-1">
                                            <Mail class="h-3 w-3" />
                                            {{ report.email }}
                                        </p>
                                        <div class="flex items-center space-x-4 mt-2">
                                            <Badge v-if="report.level" variant="outline" class="text-xs">
                                                <GraduationCap class="h-3 w-3 mr-1" />
                                                {{ report.level.code }} - {{ report.level.name }}
                                            </Badge>
                                            <Badge v-if="report.department" variant="secondary" class="text-xs">
                                                <Building2 class="h-3 w-3 mr-1" />
                                                {{ report.department.name }}
                                            </Badge>
                                            <Badge :variant="getStatusVariant(report.status)" class="text-xs">
                                                {{ report.status }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-6">
                                    <!-- Course Stats -->
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-primary">{{ report.active_courses }}</div>
                                        <div class="text-xs text-muted-foreground">Active</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-green-600">{{ report.completed_courses }}</div>
                                        <div class="text-xs text-muted-foreground">Completed</div>
                                    </div>

                                    <Separator orientation="vertical" class="h-12" />

                                    <!-- Latest Evaluation -->
                                    <div v-if="report.latest_evaluation" class="text-center">
                                        <div class="text-lg font-bold text-purple-600">{{ report.latest_evaluation.total_score }}</div>
                                        <div class="text-xs text-muted-foreground">Latest Score</div>
                                        <div class="text-xs font-medium text-green-600 flex items-center gap-1">
                                            <DollarSign class="h-3 w-3" />
                                            {{ formatCurrency(report.latest_evaluation.incentive_amount) }}
                                        </div>
                                    </div>
                                    <div v-else class="text-center">
                                        <Award class="h-8 w-8 text-muted-foreground mx-auto mb-1" />
                                        <div class="text-xs text-muted-foreground">No evaluations</div>
                                    </div>

                                    <!-- Actions -->
                                    <Button asChild>
                                        <Link :href="route('user.team.show', report.id)">
                                            <Eye class="h-4 w-4 mr-2" />
                                            View Details
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <div class="mx-auto w-16 h-16 bg-accent rounded-full flex items-center justify-center mb-4">
                            <Users class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <CardTitle class="mb-2">No team members</CardTitle>
                        <CardDescription>
                            You don't have any direct reports assigned yet.
                        </CardDescription>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
