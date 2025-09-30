<!-- Activities Page - Fixed Pagination -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import Pagination from '@/components/Pagination.vue'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'

// Icons
import {
    Clock,
    Filter,
    Search,
    X,
    Calendar,
    User,
    Tag,
    ChevronDown,
    Activity
} from 'lucide-vue-next'
import Pagination2 from '@/components/Pagination2.vue';

const props = defineProps({
    activities: Object,
    isAdmin: {
        type: Boolean,
        default: false
    }
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'All Activities',
        href: '/activities',
    },
]

// Search and filter functionality - Updated with non-empty default values
const searchQuery = ref('')
const showFilters = ref(false)
const selectedAction = ref('all') // Changed from '' to 'all'
const dateRange = ref('all_time') // Changed from '' to 'all_time'

const actionOptions = [
    { value: 'all', label: 'All Actions' }, // Changed from empty string to 'all'
    { value: 'create', label: 'Create' },
    { value: 'update', label: 'Update' },
    { value: 'delete', label: 'Delete' },
    { value: 'enroll', label: 'Enroll' },
    { value: 'login', label: 'Login' },
    { value: 'complete', label: 'Complete' },
    { value: 'attendance', label: 'Attendance' },
]

const dateRangeOptions = [
    { value: 'all_time', label: 'All Time' }, // Changed from empty string to 'all_time'
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
]

const filteredActivities = computed(() => {
    if (!props.activities?.data) return []

    return props.activities.data.filter(activity => {
        // Search filter
        const matchesSearch = !searchQuery.value ||
            activity.description.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (activity.user?.name && activity.user.name.toLowerCase().includes(searchQuery.value.toLowerCase()))

        // Action filter - Updated to check for 'all' instead of empty string
        const matchesAction = selectedAction.value === 'all' || activity.action === selectedAction.value

        // Date range filter - Updated to check for 'all_time' instead of empty string
        let matchesDate = true
        if (dateRange.value !== 'all_time') {
            const activityDate = new Date(activity.created_at)
            const today = new Date()
            today.setHours(0, 0, 0, 0)

            if (dateRange.value === 'today') {
                const tomorrow = new Date(today)
                tomorrow.setDate(tomorrow.getDate() + 1)
                matchesDate = activityDate >= today && activityDate < tomorrow
            } else if (dateRange.value === 'yesterday') {
                const yesterday = new Date(today)
                yesterday.setDate(yesterday.getDate() - 1)
                matchesDate = activityDate >= yesterday && activityDate < today
            } else if (dateRange.value === 'week') {
                const weekStart = new Date(today)
                weekStart.setDate(weekStart.getDate() - 1)
                matchesDate = activityDate >= weekStart
            } else if (dateRange.value === 'month') {
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
                matchesDate = activityDate >= monthStart
            }
        }

        return matchesSearch && matchesAction && matchesDate
    })
})

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}

// Format time ago for activity feed
const timeAgo = (dateString) => {
    if (!dateString) return ''

    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now - date) / 1000)

    if (diffInSeconds < 60) {
        return 'just now'
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60)
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600)
        return `${hours} hour${hours > 1 ? 's' : ''} ago`
    } else {
        const days = Math.floor(diffInSeconds / 86400)
        return `${days} day${days > 1 ? 's' : ''} ago`
    }
}

// Get action badge variant
const getActionVariant = (action) => {
    const variantMap = {
        'create': 'secondary',
        'update': 'default',
        'delete': 'destructive',
        'enroll': 'default',
        'login': 'secondary',
        'complete': 'default',
        'attendance': 'secondary',
    }

    return variantMap[action] || 'outline'
}

// Clear all filters - Updated to use non-empty default values
const clearFilters = () => {
    searchQuery.value = ''
    selectedAction.value = 'all'
    dateRange.value = 'all_time'
    showFilters.value = false
}
</script>

<template>
    <Head title="All Activities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
            <Card>
                <CardHeader>
                    <!-- Header with title and search -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <CardTitle class="text-xl sm:text-2xl">All Activities</CardTitle>
                            <CardDescription>Monitor system activities and user actions</CardDescription>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search activities..."
                                    class="pl-10 pr-10"
                                />
                                <Button
                                    v-if="searchQuery"
                                    @click="searchQuery = ''"
                                    variant="ghost"
                                    size="sm"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 h-6 w-6 p-0"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>

                            <Collapsible v-model:open="showFilters">
                                <CollapsibleTrigger asChild>
                                    <Button variant="outline" class="gap-2">
                                        <Filter class="h-4 w-4" />
                                        Filters
                                        <ChevronDown
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="{ 'rotate-180': showFilters }"
                                        />
                                    </Button>
                                </CollapsibleTrigger>
                            </Collapsible>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <!-- Filters panel -->
                    <Collapsible v-model:open="showFilters">
                        <CollapsibleContent class="space-y-4">
                            <Card class="mb-6">
                                <CardContent class="p-4">
                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <div class="flex-1 space-y-2">
                                            <Label>Action Type</Label>
                                            <Select v-model="selectedAction">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="All Actions" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="option in actionOptions" :key="option.value" :value="option.value">
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="flex-1 space-y-2">
                                            <Label>Date Range</Label>
                                            <Select v-model="dateRange">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="All Time" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="option in dateRangeOptions" :key="option.value" :value="option.value">
                                                        {{ option.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="flex items-end">
                                            <Button
                                                @click="clearFilters"
                                                variant="outline"
                                                class="w-full sm:w-auto"
                                            >
                                                Clear Filters
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </CollapsibleContent>
                    </Collapsible>

                    <!-- Activity list -->
                    <div class="space-y-4">
                        <!-- Empty State -->
                        <div v-if="filteredActivities.length === 0" class="text-center py-12">
                            <Clock class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                            <CardTitle class="mb-2">No activities found</CardTitle>
                            <CardDescription>
                                {{ searchQuery || selectedAction !== 'all' || dateRange !== 'all_time' ? 'Try adjusting your filters' : 'Activity history will appear here' }}
                            </CardDescription>
                        </div>

                        <!-- Activity Cards -->
                        <Card
                            v-for="activity in filteredActivities"
                            :key="activity.id"
                            class="hover:shadow-md transition-shadow duration-200"
                        >
                            <CardContent class="p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    <div class="flex items-start flex-1 gap-3">
                                        <Avatar class="w-10 h-10 shrink-0">
                                            <AvatarImage
                                                v-if="activity.user?.avatar"
                                                :src="activity.user.avatar"
                                                :alt="activity.user?.name"
                                            />
                                            <AvatarFallback>
                                                {{ activity.user?.name ? activity.user.name.charAt(0).toUpperCase() : 'S' }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <div class="flex-1 min-w-0 space-y-2">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                <p class="text-sm font-medium break-words">{{ activity.description }}</p>
                                                <div class="text-xs text-muted-foreground whitespace-nowrap">
                                                    {{ timeAgo(activity.created_at) }}
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <Badge
                                                    v-if="activity.action"
                                                    :variant="getActionVariant(activity.action)"
                                                    class="text-xs"
                                                >
                                                    {{ activity.action }}
                                                </Badge>

                                                <div class="flex items-center text-xs text-muted-foreground gap-1">
                                                    <Calendar class="h-3 w-3" />
                                                    {{ formatDate(activity.created_at) }}
                                                </div>

                                                <div v-if="activity.user?.name" class="flex items-center text-xs text-muted-foreground gap-1">
                                                    <User class="h-3 w-3" />
                                                    {{ activity.user.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Pagination - FIXED: Changed from 'courses' to 'activities' -->
                    <div v-if="activities?.links && activities.links.length > 0" class="flex justify-center items-center mt-8">
                        <Pagination2 :links="activities.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
