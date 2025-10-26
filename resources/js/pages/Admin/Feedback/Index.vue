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
    MessageSquarePlus,
    Lightbulb,
    Wrench,
    Star,
    Clock,
    Eye,
    CheckCircle,
    XCircle,
    Search,
    X,
    Filter
} from 'lucide-vue-next'

const props = defineProps({
    feedback: Object,
    search: String,
    status: String,
    type: String,
    statusOptions: Object,
    typeOptions: Object,
})

// Search and filter state - FIXED
const page = usePage()
const searchTerm = ref(props.search || '')
const statusFilter = ref(props.status || 'all')
const typeFilter = ref(props.type || 'all')

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Employee Feedback', href: route('admin.feedback.index') }
]

// Debounced search function - FIXED
const performSearch = debounce(() => {
    router.get(route('admin.feedback.index'), {
        search: searchTerm.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}, 500)

// Watch for changes
watch([searchTerm, statusFilter, typeFilter], () => {
    performSearch()
})

// Clear filters - FIXED
const clearFilters = () => {
    searchTerm.value = ''
    statusFilter.value = 'all'
    typeFilter.value = 'all'
}

// Status helpers
function getStatusIcon(status: string) {
    switch (status) {
        case 'pending': return Clock
        case 'under_review': return Eye
        case 'approved': return CheckCircle
        case 'rejected': return XCircle
        default: return Clock
    }
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'pending': return 'secondary'
        case 'under_review': return 'default'
        case 'approved': return 'default'
        case 'rejected': return 'destructive'
        default: return 'secondary'
    }
}

// Type helpers
function getTypeIcon(type: string) {
    switch (type) {
        case 'suggestion': return Lightbulb
        case 'improvement': return Wrench
        case 'feature_request': return Star
        case 'general': return MessageSquarePlus
        default: return MessageSquarePlus
    }
}

// Update status
const updateStatus = (feedbackId: number, newStatus: string) => {
    router.patch(`/admin/feedback/${feedbackId}/status`, {
        status: newStatus
    }, {
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
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Employee Feedback</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage feedback and suggestions from employees</p>
                </div>
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
                                placeholder="Search feedback by title, description, or user..."
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

                        <!-- Status Filter - FIXED -->
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

                        <!-- Type Filter - FIXED -->
                        <div>
                            <Select v-model="typeFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem v-for="(label, value) in typeOptions" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Active Filters - FIXED -->
                    <div v-if="searchTerm || (statusFilter !== 'all') || (typeFilter !== 'all')" class="flex items-center gap-2 mt-4">
                        <span class="text-sm text-muted-foreground">Active filters:</span>
                        <Badge v-if="searchTerm" variant="secondary" class="text-xs">
                            Search: "{{ searchTerm }}"
                        </Badge>
                        <Badge v-if="statusFilter !== 'all'" variant="secondary" class="text-xs">
                            Status: {{ statusOptions[statusFilter] }}
                        </Badge>
                        <Badge v-if="typeFilter !== 'all'" variant="secondary" class="text-xs">
                            Type: {{ typeOptions[typeFilter] }}
                        </Badge>
                        <Button @click="clearFilters" variant="ghost" size="sm" class="text-xs">
                            <X class="h-3 w-3 mr-1" />
                            Clear all
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Feedback Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <MessageSquarePlus class="mr-2 h-5 w-5" />
                        Feedback Submissions
                    </CardTitle>
                    <CardDescription>
                        Showing {{ feedback?.data?.length || 0 }} feedback items
                        {{ feedback?.total ? `of ${feedback.total} total` : '' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Employee</TableHead>
                                    <TableHead>Feedback</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in feedback.data" :key="item.id" class="hover:bg-muted/50">
                                    <!-- Employee -->
                                    <TableCell>
                                        <div class="flex items-center space-x-3">
                                            <Avatar class="h-8 w-8">
                                                <AvatarFallback class="bg-muted text-muted-foreground text-sm">
                                                    {{ getUserInitials(item.user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">{{ item.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ item.user.email }}</div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Feedback -->
                                    <TableCell class="max-w-xs">
                                        <div class="font-medium text-foreground mb-1">{{ item.title }}</div>
                                        <div class="text-xs text-muted-foreground line-clamp-2">
                                            {{ item.description.substring(0, 100) }}{{ item.description.length > 100 ? '...' : '' }}
                                        </div>
                                    </TableCell>

                                    <!-- Type -->
                                    <TableCell>
                                        <Badge variant="outline" class="flex items-center w-fit">
                                            <component :is="getTypeIcon(item.type)" class="mr-1 h-3 w-3" />
                                            {{ typeOptions[item.type] }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(item.status)" class="flex items-center w-fit">
                                            <component :is="getStatusIcon(item.status)" class="mr-1 h-3 w-3" />
                                            {{ statusOptions[item.status] }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Date -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(item.created_at) }}
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <div class="flex justify-end space-x-2">
                                            <Button
                                                :as="Link"
                                                :href="`/admin/feedback/${item.id}`"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Empty state - FIXED -->
                        <div v-if="!feedback.data || feedback.data.length === 0" class="text-center py-12">
                            <MessageSquarePlus class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">
                                {{ searchTerm || (statusFilter !== 'all') || (typeFilter !== 'all') ? 'No feedback matches your filters' : 'No feedback yet' }}
                            </h3>
                            <p class="text-sm text-muted-foreground mb-6">
                                {{ searchTerm || (statusFilter !== 'all') || (typeFilter !== 'all') ? 'Try adjusting your search criteria' : 'Employee feedback will appear here once submitted' }}
                            </p>
                            <Button v-if="searchTerm || (statusFilter !== 'all') || (typeFilter !== 'all')" @click="clearFilters" variant="outline">
                                Clear Filters
                            </Button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="feedback.data && feedback.data.length > 0" class="flex justify-between items-center pt-4 border-t">
                        <Button
                            v-if="feedback.prev_page_url"
                            :as="Link"
                            :href="feedback.prev_page_url"
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
                            <span v-if="feedback.from && feedback.to && feedback.total">
                                Showing {{ feedback.from }} to {{ feedback.to }} of {{ feedback.total }} items
                                <span v-if="searchTerm || (statusFilter !== 'all') || (typeFilter !== 'all')">(filtered)</span>
                            </span>
                        </div>

                        <Button
                            v-if="feedback.next_page_url"
                            :as="Link"
                            :href="feedback.next_page_url"
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
