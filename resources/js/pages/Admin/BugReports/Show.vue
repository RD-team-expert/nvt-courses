<script setup lang="ts">
import { defineProps } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    Bug,
    ArrowLeft,
    Edit,
    User,
    Calendar,
    Link2,
    AlertTriangle,
    Clock,
    Eye,
    XCircle,
    CheckCircle2,
    UserCheck,
    Settings
} from 'lucide-vue-next'

const props = defineProps({
    bugReport: Object,
    users: Array,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Bug Reports', href: route('admin.bug-reports.index') },
    { name: 'View Bug Report', href: '#' }
]

// Form for assignment - FIXED
const assignForm = useForm({
    assigned_to: props.bugReport.assigned_to?.id?.toString() || 'unassigned'
})

// Form for quick actions
const statusForm = useForm({
    status: props.bugReport.status
})

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

// Get user initials
const getUserInitials = (name: string) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Assign bug - FIXED
const assignBug = () => {
    const assigneeId = assignForm.assigned_to === 'unassigned' ? null : assignForm.assigned_to

    assignForm.patch(`/admin/bug-reports/${props.bugReport.id}/assign`, {
        assigned_to: assigneeId
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash messages
        }
    })
}

// Resolve bug
const resolveBug = () => {
    statusForm.patch(`/admin/bug-reports/${props.bugReport.id}/resolve`, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash messages
        }
    })
}

// Status options
const statusOptions = [
    { value: 'open', label: 'Open' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'closed', label: 'Closed' }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Bug Report Details</h1>
                    <p class="text-sm text-muted-foreground mt-1">Review and manage bug report information</p>
                </div>
                <div class="flex gap-2">
                    <Button :as="Link" :href="`/admin/bug-reports/${bugReport.id}/edit`" variant="outline">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit Report
                    </Button>
                    <Button :as="Link" href="/admin/bug-reports" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Reports
                    </Button>
                </div>
            </div>

            <!-- Success Message -->
            <Alert v-if="$page.props.flash.success" class="border-green-200 bg-green-50">
                <CheckCircle2 class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-green-800">
                    {{ $page.props.flash.success }}
                </AlertDescription>
            </Alert>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Bug Report Details -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-3">
                                    <component :is="getPriorityIcon(bugReport.priority)" class="h-6 w-6 text-primary" />
                                    <div>
                                        <CardTitle class="text-xl">{{ bugReport.title }}</CardTitle>
                                        <CardDescription class="mt-1 flex items-center space-x-4">
                                            <span>Reported {{ formatDate(bugReport.created_at) }}</span>
                                            <Badge :variant="getPriorityVariant(bugReport.priority)" class="text-xs">
                                                {{ bugReport.priority.toUpperCase() }} Priority
                                            </Badge>
                                        </CardDescription>
                                    </div>
                                </div>
                                <Badge :variant="getStatusVariant(bugReport.status)" class="flex items-center">
                                    <component :is="getStatusIcon(bugReport.status)" class="mr-1 h-3 w-3" />
                                    {{ bugReport.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Description -->
                            <div>
                                <Label class="text-base font-semibold mb-2 block">Bug Description</Label>
                                <div class="bg-muted/30 rounded-lg p-4">
                                    <p class="text-muted-foreground leading-relaxed whitespace-pre-wrap">{{ bugReport.description }}</p>
                                </div>
                            </div>

                            <!-- Steps to Reproduce -->
                            <div v-if="bugReport.steps_to_reproduce">
                                <Label class="text-base font-semibold mb-2 block">Steps to Reproduce</Label>
                                <div class="bg-muted/30 rounded-lg p-4">
                                    <p class="text-muted-foreground leading-relaxed whitespace-pre-wrap">{{ bugReport.steps_to_reproduce }}</p>
                                </div>
                            </div>

                            <!-- Page URL -->
                            <div v-if="bugReport.page_url">
                                <Label class="text-base font-semibold mb-2 block">Page URL</Label>
                                <div class="flex items-center space-x-2">
                                    <Link2 class="h-4 w-4 text-muted-foreground" />
                                    <a :href="bugReport.page_url" target="_blank" rel="noopener"
                                       class="text-primary hover:underline text-sm break-all">
                                        {{ bugReport.page_url }}
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Settings class="mr-2 h-5 w-5" />
                                Quick Actions
                            </CardTitle>
                            <CardDescription>
                                Manage bug assignment and status
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Assignment - FIXED -->
                            <div>
                                <Label class="mb-2 block">Assign to Developer</Label>
                                <div class="flex gap-2">
                                    <Select v-model="assignForm.assigned_to" class="flex-1">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select developer" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="unassigned">Unassigned</SelectItem>
                                            <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        @click="assignBug"
                                        :disabled="assignForm.processing"
                                        variant="outline"
                                    >
                                        <UserCheck class="mr-2 h-4 w-4" />
                                        Assign
                                    </Button>
                                </div>
                            </div>

                            <!-- Quick Resolve -->
                            <div v-if="bugReport.status === 'open' || bugReport.status === 'in_progress'">
                                <Button
                                    @click="resolveBug"
                                    :disabled="statusForm.processing"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white"
                                >
                                    <CheckCircle2 class="mr-2 h-4 w-4" />
                                    <span v-if="statusForm.processing">Resolving...</span>
                                    <span v-else>Mark as Resolved</span>
                                </Button>
                            </div>

                            <!-- Resolution Info -->
                            <div v-if="bugReport.resolved_at" class="bg-green-50 border border-green-200 rounded-lg p-3">
                                <div class="flex items-center space-x-2">
                                    <CheckCircle2 class="h-4 w-4 text-green-600" />
                                    <span class="text-sm font-medium text-green-800">
                                        Resolved on {{ formatDate(bugReport.resolved_at) }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Reporter Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <User class="mr-2 h-5 w-5" />
                                Reported By
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center space-x-3">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="bg-muted text-muted-foreground">
                                        {{ getUserInitials(bugReport.reported_by.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="font-medium text-foreground">{{ bugReport.reported_by.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ bugReport.reported_by.email }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Assignment Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <UserCheck class="mr-2 h-5 w-5" />
                                Assigned Developer
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="bugReport.assigned_to" class="flex items-center space-x-3">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="bg-muted text-muted-foreground">
                                        {{ getUserInitials(bugReport.assigned_to.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="font-medium text-foreground">{{ bugReport.assigned_to.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ bugReport.assigned_to.email }}</div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <UserCheck class="mx-auto h-8 w-8 text-muted-foreground mb-2" />
                                <p class="text-sm text-muted-foreground">No developer assigned</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Bug Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <Calendar class="mr-2 h-5 w-5" />
                                Bug Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Priority Level</Label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <component :is="getPriorityIcon(bugReport.priority)" class="h-4 w-4 text-primary" />
                                    <span class="text-sm font-medium">{{ bugReport.priority.toUpperCase() }}</span>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Current Status</Label>
                                <div class="mt-1">
                                    <Badge :variant="getStatusVariant(bugReport.status)" class="text-xs">
                                        <component :is="getStatusIcon(bugReport.status)" class="mr-1 h-3 w-3" />
                                        {{ bugReport.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Created</Label>
                                <p class="text-sm mt-1">{{ formatDate(bugReport.created_at) }}</p>
                            </div>
                            <div v-if="bugReport.updated_at !== bugReport.created_at">
                                <Label class="text-sm font-medium text-muted-foreground">Last Updated</Label>
                                <p class="text-sm mt-1">{{ formatDate(bugReport.updated_at) }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
