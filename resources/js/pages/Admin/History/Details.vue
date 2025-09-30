<!--
  Evaluation Details History Page
  Detailed view of individual evaluation with category breakdown
-->
<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'
import {
    User,
    ArrowLeft,
    FileText,
    BarChart3,
    DollarSign,
    Target,
    Calendar,
    Building,
    GraduationCap,
    Mail
} from 'lucide-vue-next'

const props = defineProps<{
    history?: Array<{
        id: number
        evaluation_id: number
        category_name: string
        type_name: string
        score: number
        comments?: string
        created_at: string
        evaluation: {
            id: number
            total_score: number
            incentive_amount: number
            created_at: string
            user: {
                id: number
                name: string
                email: string
            }
            course?: {
                id: number
                name: string
            }
            department?: {
                id: number
                name: string
            }
        }
    }>
}>()

const safeHistory = computed(() => props.history || [])
const evaluation = computed(() => safeHistory.value[0]?.evaluation || null)

// Get performance level styling
function getPerformanceLevel(score: number) {
    if (score >= 20) return { label: 'Exceptional', variant: 'default' }
    if (score >= 15) return { label: 'Excellent', variant: 'secondary' }
    if (score >= 10) return { label: 'Good', variant: 'outline' }
    if (score >= 5) return { label: 'Average', variant: 'secondary' }
    return { label: 'Below Average', variant: 'destructive' }
}

// Get category variant
function getCategoryVariant(score: number) {
    if (score >= 5) return 'default'
    if (score >= 4) return 'secondary'
    if (score >= 3) return 'outline'
    if (score >= 2) return 'secondary'
    return 'destructive'
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'History', href: route('admin.evaluations.history') },
    { name: 'Details', href: null }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">Evaluation Details</h1>
                    <p v-if="evaluation" class="mt-2 text-sm text-muted-foreground">
                        Detailed view of evaluation for {{ evaluation.user.name }}
                    </p>
                </div>
                <Button :as="Link" :href="route('admin.evaluations.history')" variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to History
                </Button>
            </div>

            <!-- No Evaluation Found State -->
            <Card v-if="!evaluation">
                <CardContent class="p-8 text-center">
                    <FileText class="mx-auto h-12 w-12 text-muted-foreground" />
                    <CardTitle class="mt-4 text-lg">No Evaluation Found</CardTitle>
                    <p class="mt-2 text-sm text-muted-foreground">
                        The requested evaluation details could not be found.
                    </p>
                </CardContent>
            </Card>

            <!-- Evaluation Content -->
            <div v-else class="space-y-8">
                <!-- Employee Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center">
                            <div class="shrink-0 mr-4">
                                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-primary/10">
                                    <User class="h-6 w-6 text-primary" />
                                </div>
                            </div>
                            <div>
                                <CardTitle>Employee Information</CardTitle>
                                <CardDescription>Basic details about the evaluated employee</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-muted-foreground">Employee Name</label>
                                    <p class="mt-1 text-lg font-semibold text-foreground">{{ evaluation.user.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-muted-foreground">Email Address</label>
                                    <div class="mt-1 flex items-center text-sm text-foreground">
                                        <Mail class="mr-2 h-4 w-4 text-muted-foreground" />
                                        {{ evaluation.user.email }}
                                    </div>
                                </div>
                                <div v-if="evaluation.department">
                                    <label class="block text-sm font-medium text-muted-foreground">Department</label>
                                    <div class="mt-1 flex items-center text-sm text-foreground">
                                        <Building class="mr-2 h-4 w-4 text-muted-foreground" />
                                        {{ evaluation.department.name }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-if="evaluation.course">
                                    <label class="block text-sm font-medium text-muted-foreground">Course</label>
                                    <div class="mt-1 flex items-center text-sm text-foreground">
                                        <GraduationCap class="mr-2 h-4 w-4 text-muted-foreground" />
                                        {{ evaluation.course.name }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-muted-foreground">Evaluation Date</label>
                                    <div class="mt-1 flex items-center text-sm text-foreground">
                                        <Calendar class="mr-2 h-4 w-4 text-muted-foreground" />
                                        {{ new Date(evaluation.created_at).toLocaleDateString() }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-muted-foreground">Performance Level</label>
                                    <div class="mt-1">
                                        <Badge :variant="getPerformanceLevel(evaluation.total_score).variant" class="text-sm">
                                            {{ getPerformanceLevel(evaluation.total_score).label }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Overall Results -->
                <Card>
                    <CardHeader>
                        <CardTitle>Overall Results</CardTitle>
                        <CardDescription>Summary of evaluation scores and incentive information</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Score Summary -->
                            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
                                <CardContent class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-blue-900">Total Score</p>
                                            <p class="text-3xl font-bold text-blue-600">{{ evaluation.total_score }}</p>
                                        </div>
                                        <BarChart3 class="h-10 w-10 text-blue-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Incentive Summary -->
                            <Card class="bg-gradient-to-r from-green-50 to-emerald-50 border-green-200">
                                <CardContent class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Incentive Amount</p>
                                            <p class="text-3xl font-bold text-green-600">
                                                ${{ parseFloat(evaluation.incentive_amount.toString()).toFixed(2) }}
                                            </p>
                                        </div>
                                        <DollarSign class="h-10 w-10 text-green-600" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <!-- Category Details -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center">
                            <div class="shrink-0 mr-4">
                                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-purple-100">
                                    <Target class="h-6 w-6 text-purple-600" />
                                </div>
                            </div>
                            <div>
                                <CardTitle>Category Breakdown</CardTitle>
                                <CardDescription>Detailed scores for each evaluation category</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6">
                            <Card
                                v-for="(item, index) in safeHistory"
                                :key="item.id"
                                class="hover:shadow-md transition-shadow duration-200"
                            >
                                <CardContent class="p-6">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-foreground mb-2">{{ item.category_name }}</h3>
                                            <p class="text-sm text-muted-foreground mb-3">
                                                Performance Level: <span class="font-medium text-foreground">{{ item.type_name }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <Badge :variant="getCategoryVariant(item.score)" class="text-lg font-bold px-3 py-1">
                                                {{ item.score }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <Separator v-if="item.comments" class="my-4" />

                                    <Alert v-if="item.comments" class="bg-muted/50">
                                        <FileText class="h-4 w-4" />
                                        <AlertDescription>
                                            <h4 class="font-medium text-foreground mb-2">Comments & Feedback</h4>
                                            <p class="text-sm text-muted-foreground">{{ item.comments }}</p>
                                        </AlertDescription>
                                    </Alert>

                                    <div class="mt-4 flex items-center text-xs text-muted-foreground">
                                        <Calendar class="mr-1 h-3 w-3" />
                                        Evaluated on {{ new Date(item.created_at).toLocaleString() }}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
