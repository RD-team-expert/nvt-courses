<!--
  Department Performance Report Page
  Shows top and bottom 3 performers per department based on evaluation scores.
-->
<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import {
    Download,
    Filter,
    Users,
    Trophy,
    AlertTriangle,
    Building,
    BarChart3
} from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Input } from '@/components/ui/input'

const props = defineProps({
    departments: Array, // For filter dropdown
    reportData: Array,  // The main data
    stats: Object,
    filters: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Department Performance', href: route('admin.reports.course-online.department-performance') }
]

// Filter state
const filters = ref({
    department_id: props.filters?.department_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

// Apply filters with debounce
const applyFilters = debounce(() => {
    const filterParams = { ...filters.value }
    router.get(route('admin.reports.course-online.department-performance'), filterParams, {
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
        department_id: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.course-online.export.department-performance') + '?' + queryParams
}

// Helper for score color
const getScoreColor = (score) => {
    if (score >= 85) return 'bg-green-100 text-green-800 border-green-200'
    if (score >= 70) return 'bg-yellow-100 text-yellow-800 border-yellow-200'
    return 'bg-red-100 text-red-800 border-red-200'
}

const getInitials = (name) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Department Performance Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Top and bottom performers by department based on evaluation scores</p>
                </div>
                <Button @click="exportToCsv" class="w-full sm:w-auto">
                    <Download class="mr-2 h-4 w-4" />
                    Export to CSV
                </Button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Building class="h-8 w-8 text-blue-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Departments</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.total_departments || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Users class="h-8 w-8 text-purple-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Users Evaluated</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.total_users_evaluated || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <BarChart3 class="h-8 w-8 text-green-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Overall Avg Score</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.overall_avg_score || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Trophy class="h-8 w-8 text-yellow-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Highest Dept Avg</p>
                                <p class="text-2xl font-bold text-foreground">{{ stats?.highest_department_avg || 0 }}</p>
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
                            <CardTitle>Filters</CardTitle>
                            <CardDescription>Narrow down the results by department or date range</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label for="department_filter">Department</Label>
                            <Select
                                :model-value="filters.department_id || 'all'"
                                @update:model-value="(value) => filters.department_id = value === 'all' ? '' : value"
                            >
                                <SelectTrigger id="department_filter">
                                    <SelectValue placeholder="All Departments" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Departments</SelectItem>
                                    <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="date_from">From Date</Label>
                            <Input
                                id="date_from"
                                type="date"
                                v-model="filters.date_from"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="date_to">To Date</Label>
                            <Input
                                id="date_to"
                                type="date"
                                v-model="filters.date_to"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <Button variant="outline" @click="resetFilters" size="sm">
                            Reset Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Department Cards -->
            <div v-if="reportData && reportData.length > 0" class="space-y-8">
                <div v-for="dept in reportData" :key="dept.id" class="space-y-4">
                    <div class="flex items-center justify-between border-b pb-2">
                        <h2 class="text-xl font-semibold flex items-center">
                            <Building class="mr-2 h-5 w-5 text-muted-foreground" />
                            {{ dept.name }}
                            <Badge variant="secondary" class="ml-3">Avg: {{ dept.department_avg }}</Badge>
                        </h2>
                        <span class="text-sm text-muted-foreground">{{ dept.total_users_evaluated }} Users Evaluated</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Top Performers -->
                        <Card class="border-l-4 border-l-green-500">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-lg flex items-center text-green-700">
                                    <Trophy class="mr-2 h-5 w-5" />
                                    Top Performers
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-for="(user, index) in dept.top_performers" :key="user.id" 
                                        class="flex items-center justify-between p-3 bg-green-50/50 rounded-lg border border-green-100">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-6 h-6 rounded-full bg-green-200 text-green-800 font-bold text-xs">
                                                {{ index + 1 }}
                                            </div>
                                            <Avatar class="h-10 w-10 border-2 border-white shadow-sm">
                                                <AvatarImage :src="user.avatar" :alt="user.name" />
                                                <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p class="font-medium text-sm">{{ user.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ user.employee_code }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <Badge :class="getScoreColor(user.avg_score)">{{ user.avg_score }}</Badge>
                                            <p class="text-xs text-muted-foreground mt-1">{{ user.total_evaluations }} evals</p>
                                        </div>
                                    </div>
                                    <div v-if="dept.top_performers.length === 0" class="text-center py-4 text-muted-foreground text-sm">
                                        No data available
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Bottom Performers -->
                        <Card class="border-l-4 border-l-red-500">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-lg flex items-center text-red-700">
                                    <AlertTriangle class="mr-2 h-5 w-5" />
                                    Needs Support
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-for="(user, index) in dept.bottom_performers" :key="user.id" 
                                        class="flex items-center justify-between p-3 bg-red-50/50 rounded-lg border border-red-100">
                                        <div class="flex items-center gap-3">
                                            <!-- Rank for bottom performers is tricky, usually we just list them. 
                                                 If we want to show rank from bottom, we can use index + 1 
                                            -->
                                            <Avatar class="h-10 w-10 border-2 border-white shadow-sm">
                                                <AvatarImage :src="user.avatar" :alt="user.name" />
                                                <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p class="font-medium text-sm">{{ user.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ user.employee_code }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <Badge :class="getScoreColor(user.avg_score)">{{ user.avg_score }}</Badge>
                                            <p class="text-xs text-muted-foreground mt-1">{{ user.total_evaluations }} evals</p>
                                        </div>
                                    </div>
                                    <div v-if="dept.bottom_performers.length === 0" class="text-center py-4 text-muted-foreground text-sm">
                                        No data available
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-muted mb-4">
                    <BarChart3 class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-medium text-foreground">No Data Available</h3>
                <p class="text-muted-foreground mt-1">No evaluation data found for the selected filters.</p>
            </div>
        </div>
    </AdminLayout>
</template>
