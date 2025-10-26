<script setup lang="ts">
import { defineProps, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import {
    Bug,
    Plus,
    Eye,
    Edit,
    Trash2,
    Search,
    X,
    UserCheck,
    Clock,
    AlertTriangle,
    CheckCircle2,
    XCircle
} from 'lucide-vue-next'

const props = defineProps({
    bugReports: Object,
    search: String,
    status: String,
    priority: String,
    statusOptions: Object,
    priorityOptions: Object,
})

// Search and filter state
const page = usePage()
const searchTerm = ref(props.search || '')
const statusFilter = ref(props.status || 'all')
const priorityFilter = ref(props.priority || 'all')

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Bug Reports', href: route('admin.bug-reports.index') }
]

// Debounced search function
const performSearch = debounce(() => {
    router.get(route('admin.bug-reports.index'), {
        search: searchTerm.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        priority: priorityFilter.value !== 'all' ? priorityFilter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}, 500)

// Watch for changes
watch([searchTerm, statusFilter, priorityFilter], () => {
    performSearch()
})

// Clear filters
const clearFilters = () => {
    searchTerm.value = ''
    statusFilter.value = 'all'
    priorityFilter.value = 'all'
}

// Priority helpers
function getPriorityIcon(priority: string) {
    switch (priority) {
        case 'low': return Clock
        case 'medium': return Eye
        case 'high': return AlertTriangle
        case 'critical': return XCircle
        default: return Clock
    }
}

function getPriorityVariant(priority: string) {
    switch (priority) {
        case 'low': return 'secondary'
        case 'medium': return 'default'
        case 'high': return 'destructive'
        case 'critical': return 'destructive'
        default: return 'secondary'
    }
}

// Status helpers
function getStatusIcon(status: string) {
    switch (status) {
        case 'open': return Clock
        case 'in_progress': return Eye
        case 'resolved': return CheckCircle2
        case 'closed': return XCircle
        default: return Clock
    }
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'open': return 'secondary'
        case 'in_progress': return 'default'
        case 'resolved': return 'default'
        case 'closed': return 'outline'
        default: return 'secondary'
    }
}

// Delete bug report
const deleteBugReport = (bugReportId: number) => {
    router.delete(`/admin/bug-reports/${bugReportId}`, {
        preserveState: true,
        onSuccess: () => {
            // Success handling
        },
        onError: (errors) => {
            console.error('Delete failed:', errors)
        }
    })
}

// Quick assign
const quickAssign = (bugReportId: number, userId: number) => {
    router.patch(`/admin/bug-reports/${bugReportId}/assign`, {
        assigned_to: userId
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Quick resolve
const quickResolve = (bugReportId: number) => {
    router.patch(`/admin/bug-reports/${bugReportId}/resolve`, {}, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Get user initials
const getUserInitials = (name: string) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Bug Reports</h1>
                    <p class="text-sm text-muted-foreground mt-1">Track and manage technical issues and bugs</p>
                </div>
                <Button :as="Link" href="/admin/bug-reports/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Report New Bug
                </Button>
            </div>

            <!-- Search and Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="relative md:col-span-2">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchTerm"
                                type="text"
                                placeholder="Search bug reports by title or description..."
                                class="pl-10 pr-10"
                            />
                            <Button
                                v-if="searchTerm"
                                @click="searchTerm = ''"
                                variant="ghost"
                                size="sm"
                                class="absolute right-1 top-1/2 transform -translate-y-1/2 h-8 w-8 p-0"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem v-for="(label, value) in statusOptions" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Priority Filter -->
                        <div>
                            <Select v-model="priorityFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Priority</SelectItem>
                                    <SelectItem v-for="(label, value) in priorityOptions" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <div v-if="searchTerm || (statusFilter !== 'all') || (priorityFilter !== 'all')" class="flex items-center gap-2 mt-4">
                        <span class="text-sm text-muted-foreground">Active filters:</span>
                        <Badge v-if="searchTerm" variant="secondary" class="text-xs">
                            Search: "{{ searchTerm }}"
                        </Badge>
                        <Badge v-if="statusFilter !== 'all'" variant="secondary" class="text-xs">
                            Status: {{ statusOptions[statusFilter] }}
                        </Badge>
                        <Badge v-if="priorityFilter !== 'all'" variant="secondary" class="text-xs">
                            Priority: {{ priorityOptions[priorityFilter] }}
                        </Badge>
                        <Button @click="clearFilters" variant="ghost" size="sm" class="text-xs">
                            <X class="h-3 w-3 mr-1" />
                            Clear all
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bug Reports Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Bug class="mr-2 h-5 w-5" />
                        Bug Reports
                    </CardTitle>
                    <CardDescription>
                        Showing {{ bugReports?.data?.length || 0 }} bug reports
                        {{ bugReports?.total ? `of ${bugReports.total} total` : '' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Bug Report</TableHead>
                                    <TableHead>Reporter</TableHead>
                                    <TableHead>Priority</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Assigned To</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="bug in bugReports.data" :key="bug.id" class="hover:bg-muted/50">
                                    <!-- Bug Report -->
                                    <TableCell class="max-w-xs">
                                        <div class="font-medium text-foreground mb-1">{{ bug.title }}</div>
                                        <div class="text-xs text-muted-foreground line-clamp-2">
                                            {{ bug.description.substring(0, 100) }}{{ bug.description.length > 100 ? '...' : '' }}
                                        </div>
                                    </TableCell>

                                    <!-- Reporter -->
                                    <TableCell>
                                        <div class="flex items-center space-x-2">
                                            <Avatar class="h-6 w-6">
                                                <AvatarFallback class="bg-muted text-muted-foreground text-xs">
                                                    {{ getUserInitials(bug.reported_by.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="text-sm font-medium">{{ bug.reported_by.name }}</div>
                                        </div>
                                    </TableCell>

                                    <!-- Priority -->
                                    <TableCell>
                                        <Badge :variant="getPriorityVariant(bug.priority)" class="flex items-center w-fit">
                                            <component :is="getPriorityIcon(bug.priority)" class="mr-1 h-3 w-3" />
                                            {{ priorityOptions[bug.priority] }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(bug.status)" class="flex items-center w-fit">
                                            <component :is="getStatusIcon(bug.status)" class="mr-1 h-3 w-3" />
                                            {{ statusOptions[bug.status] }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Assigned To -->
                                    <TableCell>
                                        <div v-if="bug.assigned_to" class="flex items-center space-x-2">
                                            <Avatar class="h-6 w-6">
                                                <AvatarFallback class="bg-muted text-muted-foreground text-xs">
                                                    {{ getUserInitials(bug.assigned_to.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="text-sm">{{ bug.assigned_to.name }}</div>
                                        </div>
                                        <div v-else class="text-xs text-muted-foreground">Unassigned</div>
                                    </TableCell>

                                    <!-- Date -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(bug.created_at) }}
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <div class="flex justify-end space-x-1">
                                            <Button
                                                :as="Link"
                                                :href="`/admin/bug-reports/${bug.id}`"
                                                variant="ghost"
                                                size="sm"
                                                title="View Details"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                :as="Link"
                                                :href="`/admin/bug-reports/${bug.id}/edit`"
                                                variant="ghost"
                                                size="sm"
                                                title="Edit"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="bug.status === 'open' || bug.status === 'in_progress'"
                                                @click="quickResolve(bug.id)"
                                                variant="ghost"
                                                size="sm"
                                                class="text-green-600 hover:text-green-700"
                                                title="Mark as Resolved"
                                            >
                                                <CheckCircle2 class="h-4 w-4" />
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        title="Delete"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete Bug Report</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to delete this bug report: <strong>{{ bug.title }}</strong>?
                                                            This action cannot be undone.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            @click="deleteBugReport(bug.id)"
                                                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                        >
                                                            Delete Report
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Empty state -->
                        <div v-if="!bugReports.data || bugReports.data.length === 0" class="text-center py-12">
                            <Bug class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">
                                {{ searchTerm || (statusFilter !== 'all') || (priorityFilter !== 'all') ? 'No bug reports match your filters' : 'No bug reports yet' }}
                            </h3>
                            <p class="text-sm text-muted-foreground mb-6">
                                {{ searchTerm || (statusFilter !== 'all') || (priorityFilter !== 'all') ? 'Try adjusting your search criteria' : 'Bug reports will appear here once submitted' }}
                            </p>
                            <div class="space-x-2">
                                <Button v-if="searchTerm || (statusFilter !== 'all') || (priorityFilter !== 'all')" @click="clearFilters" variant="outline">
                                    Clear Filters
                                </Button>
                                <Button :as="Link" href="/admin/bug-reports/create">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Report New Bug
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="bugReports.data && bugReports.data.length > 0" class="flex justify-between items-center pt-4 border-t">
                        <Button
                            v-if="bugReports.prev_page_url"
                            :as="Link"
                            :href="bugReports.prev_page_url"
                            variant="outline"
                            size="sm"
                            preserve-scroll
                        >
                            Previous
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled>
                            Previous
                        </Button>

                        <!-- Pagination info -->
                        <div class="text-sm text-muted-foreground hidden sm:block">
                            <span v-if="bugReports.from && bugReports.to && bugReports.total">
                                Showing {{ bugReports.from }} to {{ bugReports.to }} of {{ bugReports.total }} bug reports
                                <span v-if="searchTerm || (statusFilter !== 'all') || (priorityFilter !== 'all')">(filtered)</span>
                            </span>
                        </div>

                        <Button
                            v-if="bugReports.next_page_url"
                            :as="Link"
                            :href="bugReports.next_page_url"
                            variant="outline"
                            size="sm"
                            preserve-scroll
                        >
                            Next
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled>
                            Next
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
