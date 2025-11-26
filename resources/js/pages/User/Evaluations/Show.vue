<!-- User Evaluation Details Page -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { BreadcrumbItemType } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    ArrowLeft,
    Trophy,
    Star,
    ThumbsUp,
    BarChart3,
    TrendingUp,
    Zap,
    DollarSign,
    Calendar,
    User,
    BookOpen,
    Target,
    Award,
    FileText,
    MessageSquare
} from 'lucide-vue-next'

const props = defineProps({
    evaluation: Object, // Single evaluation object
    user: Object,
})

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Profile', href: route('user.profile.index') },
    { name: 'My Evaluations', href: route('user.evaluations.index') },
    { name: 'Evaluation Details', href: '#' }
]

// 🎯 DYNAMIC: Calculate max score from the current evaluation
const maxPossibleScore = computed(() => {
    const currentScore = props.evaluation?.total_score || 0;

    // Smart detection based on current score
    if (currentScore <= 5) return 5;     // 1-5 scale
    if (currentScore <= 10) return 10;   // 1-10 scale
    if (currentScore <= 20) return 20;   // 1-20 scale
    if (currentScore <= 25) return 25;   // 1-25 scale (current)
    if (currentScore <= 50) return 50;   // 1-50 scale
    if (currentScore <= 100) return 100; // 1-100 scale

    // For edge cases, round up to nearest 10
    return Math.ceil(currentScore / 10) * 10;
});

// Standardized performance level based on absolute score ranges
const performance = computed(() => {
    const score = props.evaluation?.total_score || 0;
    
    // Using our standardized performance level thresholds
    if (score >= 13) return {
        label: 'Outstanding',
        variant: 'default' as const,
        icon: Trophy,
        color: 'text-green-600',
        bgColor: 'bg-green-50 border-green-200',
        description: 'Exceptional performance that consistently exceeds expectations'
    }
    if (score >= 10) return {
        label: 'Reliable',
        variant: 'secondary' as const,
        icon: Star,
        color: 'text-blue-600',
        bgColor: 'bg-blue-50 border-blue-200',
        description: 'Consistently dependable performance that meets all expectations'
    }
    if (score >= 7) return {
        label: 'Developing',
        variant: 'outline' as const,
        icon: ThumbsUp,
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-50 border-yellow-200',
        description: 'Progressing performance that meets most expectations'
    }
    if (score >= 0) return {
        label: 'Underperforming',
        variant: 'destructive' as const,
        icon: TrendingUp,
        color: 'text-red-600',
        bgColor: 'bg-red-50 border-red-200',
        description: 'Performance that requires significant improvement'
    }
    return {
        label: 'Not Rated',
        variant: 'outline' as const,
        icon: BarChart3,
        color: 'text-gray-600',
        bgColor: 'bg-gray-50 border-gray-200',
        description: 'No evaluation score provided'
    }
});

const formatCurrency = (amount: number) => {
    return `$${parseFloat(amount?.toString() || '0').toFixed(2)}`
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold">{{ evaluation?.course.name }}</h1>
                            <CardDescription class="mt-2 text-lg">Performance Evaluation Details</CardDescription>
                            <div class="mt-4 flex items-center space-x-4">
                                <Badge :variant="performance.variant" class="text-sm">
                                    <component :is="performance.icon" class="h-4 w-4 mr-1" />
                                    {{ performance.label }}
                                </Badge>
                                <div class="flex items-center text-sm text-muted-foreground">
                                    <Calendar class="h-4 w-4 mr-1" />
                                    Evaluated on {{ formatDate(evaluation?.evaluation_date || evaluation?.created_at) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <Button asChild variant="outline">
                                <Link :href="route('user.evaluations.index')">
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back to Evaluations
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Overall Performance -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Award class="h-6 w-6" />
                                Overall Performance
                            </CardTitle>
                            <div class="mt-2">
                                <Badge :class="performance.color">
                                    <component :is="performance.icon" class="h-4 w-4 mr-1" />
                                    {{ performance.label }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <Card class="bg-accent/50">
                                    <CardContent class="text-center p-6">
                                        <div class="text-4xl font-bold text-primary">{{ evaluation?.total_score || 0 }}</div>
                                        <div class="text-sm text-muted-foreground mt-1">Total Score (out of 25)</div>
                                        <div class="mt-3">
                                            <Progress
                                                :value="Math.round(((evaluation?.total_score || 0) / 25) * 100)"
                                                class="w-full"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card :class="performance.bgColor">
                                    <CardContent class="text-center p-6">
                                        <div class="text-4xl font-bold" :class="performance.color">
                                            {{ performance.label }}
                                        </div>
                                        <div class="text-sm text-muted-foreground mt-1">Performance Level</div>
                                        <div class="mt-2 text-xs" :class="performance.color">
                                            Based on standardized evaluation criteria
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <Separator class="my-4" />

                            <div>
                                <h3 class="font-medium mb-2">Performance Level: {{ performance.label }}</h3>
                                <p class="text-sm text-muted-foreground">{{ performance.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Category Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Target class="h-6 w-6" />
                                Category Breakdown
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="evaluation?.categories && evaluation.categories.length > 0" class="space-y-4">
                                <Card
                                    v-for="category in evaluation.categories"
                                    :key="category.id"
                                    class="hover:shadow-sm transition-shadow"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="font-medium">{{ category.category_name }}</h3>
                                            <div class="flex items-center space-x-2">
                                                <Badge variant="outline">{{ category.score }} points</Badge>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="flex items-center justify-between text-sm text-muted-foreground mb-2">
                                                <span>{{ category.evaluation_type }}</span>
                                                <span>{{ category.score }} points</span>
                                            </div>
                                            <Progress
                                                :value="Math.min((category.score / 5) * 100, 100)"
                                                class="w-full"
                                            />
                                        </div>

                                        <Alert v-if="category.comments">
                                            <MessageSquare class="h-4 w-4" />
                                            <AlertDescription>
                                                <strong>Feedback:</strong> {{ category.comments }}
                                            </AlertDescription>
                                        </Alert>
                                    </CardContent>
                                </Card>
                            </div>

                            <div v-else class="text-center py-8">
                                <FileText class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <p class="text-muted-foreground">No category details available for this evaluation.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Manager Notes -->
                    <Card v-if="evaluation?.notes">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-6 w-6" />
                                Manager's Overall Notes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Alert>
                                <FileText class="h-4 w-4" />
                                <AlertDescription class="whitespace-pre-line">
                                    {{ evaluation.notes }}
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Evaluation Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Evaluation Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">Course</dt>
                                    <dd class="text-sm font-medium flex items-center gap-1 mt-1">
                                        <BookOpen class="h-4 w-4" />
                                        {{ evaluation?.course.name }}
                                    </dd>
                                </div>

                                <Separator />

                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">Evaluation Date</dt>
                                    <dd class="text-sm font-medium flex items-center gap-1 mt-1">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(evaluation?.evaluation_date || evaluation?.created_at) }}
                                    </dd>
                                </div>

                                <div v-if="evaluation?.evaluated_by">
                                    <dt class="text-sm font-medium text-muted-foreground">Evaluated By</dt>
                                    <dd class="text-sm font-medium flex items-center gap-1 mt-1">
                                        <User class="h-4 w-4" />
                                        {{ evaluation.evaluated_by.name }}
                                    </dd>
                                </div>

                                <Separator />

                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">Performance Level</dt>
                                    <dd class="mt-1">
                                        <Badge :variant="performance.variant">
                                            <component :is="performance.icon" class="h-3 w-3 mr-1" />
                                            {{ performance.label }}
                                        </Badge>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">Score</dt>
                                    <dd class="text-lg font-bold text-primary">{{ evaluation?.total_score || 0 }}/25</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">Performance Level</dt>
                                    <dd class="text-lg font-bold" :class="performance.color">{{ performance.label }}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <!-- Performance Insights -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5" />
                                Performance Insights
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <Card class="bg-blue-50 border-blue-200">
                                    <CardContent class="flex items-center p-3">
                                        <div class="shrink-0">
                                            <Zap class="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-blue-900">Score Percentage</p>
                                            <p class="text-xs text-blue-700">{{ Math.round(((evaluation?.total_score || 0) / 25) * 100) }}% of maximum possible score</p>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card :class="performance.bgColor">
                                    <CardContent class="flex items-center p-3">
                                        <div class="shrink-0">
                                            <component :is="performance.icon" :class="performance.color" class="h-5 w-5" />
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium" :class="performance.color">Performance Level</p>
                                            <p class="text-xs" :class="performance.color">{{ performance.description }}</p>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card class="bg-purple-50 border-purple-200">
                                    <CardContent class="flex items-center p-3">
                                        <div class="shrink-0">
                                            <Target class="h-5 w-5 text-purple-600" />
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-purple-900">Categories Evaluated</p>
                                            <p class="text-xs text-purple-700">{{ evaluation?.categories?.length || 0 }} performance areas</p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <Button asChild class="w-full">
                                    <Link :href="route('user.evaluations.index')">
                                        <FileText class="h-4 w-4 mr-2" />
                                        View All Evaluations
                                    </Link>
                                </Button>

                                <Button asChild variant="outline" class="w-full">
                                    <Link :href="route('user.profile.index')">
                                        <User class="h-4 w-4 mr-2" />
                                        Back to Profile
                                    </Link>
                                </Button>

                                <Button asChild variant="secondary" class="w-full">
                                    <Link :href="route('courses.index')">
                                        <BookOpen class="h-4 w-4 mr-2" />
                                        Browse Courses
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
