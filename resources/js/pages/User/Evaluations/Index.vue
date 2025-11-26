<!-- User Profile Evaluations Page -->
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
    CheckCircle,
    Zap,
    DollarSign,
    Star,
    TrendingUp,
    TrendingDown,
    ArrowLeft,
    Eye,
    FileText,
    Award,
    Calendar,
    User
} from 'lucide-vue-next'

// Define interfaces for better type safety
interface Evaluation {
    id: number;
    total_score: number;
    incentive_amount?: number;
    performance_level?: number;
    course: {
        name: string;
    };
    evaluation_date: string;
    evaluated_by?: {
        name: string;
    };
    notes?: string;
    categories?: any[];
}

interface Stats {
    total_evaluations: number;
    average_score: number;
    total_incentives: number;
    best_score: number;
}

const props = defineProps({
    user: Object,
    evaluations: {
        type: Object as () => {
            data: Evaluation[];
            links: any[];
            from: number;
            to: number;
            total: number;
        },
        default: () => ({ data: [], links: [], from: 0, to: 0, total: 0 })
    },
    stats: {
        type: Object as () => Stats,
        default: () => ({ total_evaluations: 0, average_score: 0, total_incentives: 0, best_score: 0 })
    }
})

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Profile', href: route('user.profile.index') },
    { name: 'My Evaluations', href: '#' }
]

// 🎯 DYNAMIC: Calculate max score from actual data
const maxPossibleScore = computed(() => {
    if (!props.evaluations?.data || props.evaluations.data.length === 0) {
        return 25; // Default fallback
    }

    // ✅ FIXED: Changed 'eval' to 'evaluation'
    const allScores = props.evaluations.data.map((evaluation: Evaluation) => evaluation.total_score || 0);
    const highestScore = Math.max(...allScores);

    // Smart detection based on score patterns
    if (highestScore <= 5) return 5;     // 1-5 scale
    if (highestScore <= 10) return 10;   // 1-10 scale
    if (highestScore <= 20) return 20;   // 1-20 scale
    if (highestScore <= 25) return 25;   // 1-25 scale (current)
    if (highestScore <= 50) return 50;   // 1-50 scale
    if (highestScore <= 100) return 100; // 1-100 scale

    // For edge cases, round up to nearest 10
    return Math.ceil(highestScore / 10) * 10;
});

// Standardized performance levels based on absolute score ranges
const getPerformanceLevel = (score: number) => {
    // Using standardized performance level thresholds
    if (score >= 13) return {
        label: 'Outstanding',
        variant: 'default' as const,
        icon: '🏆',
        color: 'text-green-600'
    }
    if (score >= 10) return {
        label: 'Reliable',
        variant: 'secondary' as const,
        icon: '⭐',
        color: 'text-blue-600'
    }
    if (score >= 7) return {
        label: 'Developing',
        variant: 'outline' as const,
        icon: '👍',
        color: 'text-yellow-600'
    }
    if (score >= 0) return {
        label: 'Underperforming',
        variant: 'destructive' as const,
        icon: '📈',
        color: 'text-red-600'
    }
    return {
        label: 'Not Rated',
        variant: 'outline' as const,
        icon: '❓',
        color: 'text-gray-600'
    }
}

// Get performance level badge class
const getPerformanceLevelBadgeClass = (score: number) => {
    if (score >= 13) return 'bg-green-100 text-green-800 border-green-200'
    if (score >= 10) return 'bg-blue-100 text-blue-800 border-blue-200'
    if (score >= 7) return 'bg-yellow-100 text-yellow-800 border-yellow-200'
    if (score >= 0) return 'bg-red-100 text-red-800 border-red-200'
    return 'bg-gray-100 text-gray-800 border-gray-200'
}

// 🎯 DYNAMIC: Score color based on flexible max score
const getScoreColor = (score: number) => {
    const percentage = (score / maxPossibleScore.value) * 100;
    if (percentage >= 80) return 'text-emerald-600'
    if (percentage >= 60) return 'text-green-600'
    if (percentage >= 40) return 'text-yellow-600'
    return 'text-red-600'
}

// Format currency
const formatCurrency = (amount: number) => {
    return `$${parseFloat(amount?.toString() || '0').toFixed(2)}`
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

// Calculate trend
const calculateTrend = computed(() => {
    if (!props.evaluations?.data || props.evaluations.data.length < 2) return null

    const latest = props.evaluations.data[0].total_score
    const previous = props.evaluations.data[1].total_score
    const diff = latest - previous

    return {
        value: diff,
        isPositive: diff > 0,
        isNeutral: diff === 0,
        percentage: previous > 0 ? Math.abs((diff / previous) * 100) : 0
    }
})
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <Card class="bg-gradient-to-r from-purple-500 to-indigo-600">
                <CardContent class="p-6">
                    <div class="flex justify-between items-center text-white">
                        <div>
                            <h1 class="text-3xl font-bold">My Performance Evaluations</h1>
                            <p class="mt-2 text-purple-100">Track your performance progress and achievements</p>
                        </div>
                        <div class="flex space-x-3">
                            <Button asChild variant="secondary" class="bg-white/20 border border-white/30 text-white hover:bg-white/30">
                                <Link :href="route('user.profile.index')">
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back to Profile
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Performance Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                                    <CheckCircle class="h-6 w-6 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Evaluations</p>
                                <p class="text-2xl font-bold">{{ stats?.total_evaluations || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <Zap class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Average Score</p>
                                <p class="text-2xl font-bold" :class="getScoreColor(stats?.average_score || 0)">
                                    {{ stats?.average_score || '0.0' }}<span class="text-sm text-muted-foreground">/{{ maxPossibleScore }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <Award class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Performance Level</p>
                                <p v-if="stats?.average_score" class="text-2xl font-bold" :class="getPerformanceLevel(stats?.average_score || 0).color">
                                    {{ getPerformanceLevel(stats?.average_score || 0).label }}
                                </p>
                                <p v-else class="text-2xl font-bold text-gray-600">Not Rated</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Star class="h-6 w-6 text-white" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Best Score</p>
                                <div class="flex items-center space-x-2">
                                    <p class="text-2xl font-bold" :class="getScoreColor(stats?.best_score || 0)">
                                        {{ stats?.best_score || '0' }}
                                    </p>
                                    <div v-if="calculateTrend && !calculateTrend.isNeutral" class="flex items-center">
                                        <TrendingUp v-if="calculateTrend.isPositive" class="h-3 w-3 text-green-500" />
                                        <TrendingDown v-else class="h-3 w-3 text-red-500" />
                                        <span class="text-xs ml-1" :class="calculateTrend.isPositive ? 'text-green-500' : 'text-red-500'">
                                            {{ calculateTrend.percentage.toFixed(1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Evaluations List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-3">
                        <FileText class="h-6 w-6" />
                        Evaluation History
                    </CardTitle>
                    <CardDescription>Your performance evaluations and feedback</CardDescription>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="evaluations && evaluations.data && evaluations.data.length > 0" class="divide-y divide-border">
                        <div
                            v-for="(evaluation, index) in evaluations.data"
                            :key="evaluation.id"
                            class="p-6 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-lg font-medium">{{ evaluation.course.name }}</h3>
                                        <Badge
                                            :class="getPerformanceLevelBadgeClass(evaluation.total_score)"
                                            class="px-2 py-1">
                                            {{ getPerformanceLevel(evaluation.total_score).icon }} {{ getPerformanceLevel(evaluation.total_score).label }}
                                        </Badge>
                                        <Badge v-if="index === 0" variant="secondary">
                                            Latest
                                        </Badge>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <div>
                                            <p class="text-2xl font-bold" :class="getScoreColor(evaluation.total_score)">
                                                {{ evaluation.total_score }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">Total Score</p>
                                        </div>
                                        <div>
                                            <p class="text-xl font-semibold" :class="getPerformanceLevel(evaluation.total_score).color">
                                                {{ getPerformanceLevel(evaluation.total_score).label }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">Performance Level</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                {{ formatDate(evaluation.evaluation_date) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">Evaluation Date</p>
                                        </div>
                                        <div v-if="evaluation.evaluated_by">
                                            <p class="text-sm font-medium flex items-center gap-1">
                                                <User class="h-3 w-3" />
                                                {{ evaluation.evaluated_by.name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">Evaluated By</p>
                                        </div>
                                    </div>

                                    <!-- 🎯 DYNAMIC: Score Progress Bar -->
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between text-sm text-muted-foreground mb-2">
                                            <span>Performance Score</span>
                                            <span>{{ evaluation.total_score }}/{{ maxPossibleScore }} ({{ Math.round((evaluation.total_score / maxPossibleScore) * 100) }}%)</span>
                                        </div>
                                        <Progress
                                            :value="Math.round((evaluation.total_score / maxPossibleScore) * 100)"
                                            class="w-full"
                                        />
                                    </div>

                                    <!-- Notes Preview -->
                                    <Alert v-if="evaluation.notes" class="mb-4">
                                        <FileText class="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Manager Notes:</strong>
                                            <span v-if="evaluation.notes.length > 150">
                                                {{ evaluation.notes.substring(0, 150) }}...
                                            </span>
                                            <span v-else>{{ evaluation.notes }}</span>
                                        </AlertDescription>
                                    </Alert>

                                    <!-- Categories Preview -->
                                    <div v-if="evaluation.categories && evaluation.categories.length > 0" class="flex flex-wrap gap-2">
                                        <Badge
                                            v-for="category in evaluation.categories.slice(0, 4)"
                                            :key="category.id"
                                            variant="outline"
                                        >
                                            {{ category.category_name }}: {{ category.score }}
                                        </Badge>
                                        <Badge v-if="evaluation.categories.length > 4" variant="secondary">
                                            +{{ evaluation.categories.length - 4 }} more
                                        </Badge>
                                    </div>
                                </div>

                                <div class="ml-6">
                                    <Button asChild>
                                        <Link :href="route('user.evaluations.show', evaluation.id)">
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
                            <Award class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <CardTitle class="mb-2">No evaluations yet</CardTitle>
                        <CardDescription class="mb-6">
                            Your performance evaluations will appear here once they're completed by your manager.
                        </CardDescription>
                        <Button asChild>
                            <Link :href="route('user.profile.index')">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Profile
                            </Link>
                        </Button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="evaluations && evaluations.links && evaluations.data.length > 0" class="px-6 py-4 border-t border-border">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ evaluations.from }} to {{ evaluations.to }} of {{ evaluations.total }} evaluations
                            </div>
                            <div class="flex space-x-1">
                                <Button
                                    v-for="link in evaluations.links"
                                    :key="link.label"
                                    :asChild="!!link.url"
                                    :variant="link.active ? 'default' : 'ghost'"
                                    :disabled="!link.url"
                                    size="sm"
                                    class="px-3 py-2"
                                >
                                    <Link v-if="link.url" :href="link.url" v-html="link.label" />
                                    <span v-else v-html="link.label" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
