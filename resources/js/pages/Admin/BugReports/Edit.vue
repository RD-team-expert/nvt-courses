<script setup lang="ts">
import { defineProps } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    Bug,
    Save,
    ArrowLeft,
    AlertTriangle,
    Clock,
    Eye,
    XCircle,
    CheckCircle2,
    User
} from 'lucide-vue-next'

const props = defineProps({
    bugReport: Object,
    users: Array,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Bug Reports', href: route('admin.bug-reports.index') },
    { name: 'Edit Bug Report', href: '#' }
]

// Form - FIXED
const form = useForm({
    title: props.bugReport.title || '',
    description: props.bugReport.description || '',
    priority: props.bugReport.priority || 'medium',
    status: props.bugReport.status || 'open',
    steps_to_reproduce: props.bugReport.steps_to_reproduce || '',
    page_url: props.bugReport.page_url || '',
    assigned_to: props.bugReport.assigned_to?.id?.toString() || 'unassigned'
})

// Priority options
const priorityOptions = [
    { value: 'low', label: 'Low', icon: Clock, color: 'text-blue-600' },
    { value: 'medium', label: 'Medium', icon: Eye, color: 'text-yellow-600' },
    { value: 'high', label: 'High', icon: AlertTriangle, color: 'text-orange-600' },
    { value: 'critical', label: 'Critical', icon: XCircle, color: 'text-red-600' }
]

// Status options
const statusOptions = [
    { value: 'open', label: 'Open', icon: Clock, color: 'text-gray-600' },
    { value: 'in_progress', label: 'In Progress', icon: Eye, color: 'text-blue-600' },
    { value: 'resolved', label: 'Resolved', icon: CheckCircle2, color: 'text-green-600' },
    { value: 'closed', label: 'Closed', icon: XCircle, color: 'text-gray-600' }
]

// Submit form - FIXED
const submit = () => {
    // Handle assignment conversion
    const formData = { ...form.data() }
    if (formData.assigned_to === 'unassigned') {
        formData.assigned_to = null
    }

    form.put(`/admin/bug-reports/${props.bugReport.id}`, {
        data: formData,
        onSuccess: () => {
            // Success handled by redirect in controller
        }
    })
}

// Get option details
const getPriorityDetails = (priority: string) => {
    return priorityOptions.find(p => p.value === priority) || priorityOptions[1]
}

const getStatusDetails = (status: string) => {
    return statusOptions.find(s => s.value === status) || statusOptions[0]
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
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Edit Bug Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Update bug report information and status</p>
                </div>
                <div class="flex gap-2">
                    <Button :as="Link" :href="`/admin/bug-reports/${bugReport.id}`" variant="outline">
                        <Eye class="mr-2 h-4 w-4" />
                        View Details
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
                <!-- Main Form -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Bug class="mr-2 h-5 w-5" />
                                Bug Report Information
                            </CardTitle>
                            <CardDescription>
                                Update the bug report details and management information
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Title -->
                                <div>
                                    <Label for="title" class="mb-2 block">Bug Title <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        placeholder="Brief description of the bug"
                                        :disabled="form.processing"
                                        required
                                        class="w-full"
                                    />
                                    <div v-if="form.errors.title" class="text-destructive text-sm mt-1">
                                        {{ form.errors.title }}
                                    </div>
                                </div>

                                <!-- Priority & Status -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Priority -->
                                    <div>
                                        <Label class="mb-3 block font-medium">Priority Level <span class="text-red-500">*</span></Label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div
                                                v-for="priority in priorityOptions"
                                                :key="priority.value"
                                                @click="form.priority = priority.value"
                                                class="border rounded-lg p-3 cursor-pointer transition-all hover:border-primary"
                                                :class="{
                                                    'border-primary bg-primary/10': form.priority === priority.value,
                                                    'border-border': form.priority !== priority.value
                                                }"
                                            >
                                                <div class="flex flex-col items-center text-center space-y-2">
                                                    <component
                                                        :is="priority.icon"
                                                        class="h-4 w-4"
                                                        :class="form.priority === priority.value ? 'text-primary' : priority.color"
                                                    />
                                                    <span class="text-xs font-medium"
                                                          :class="form.priority === priority.value ? 'text-primary' : 'text-foreground'"
                                                    >
                                                        {{ priority.label }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="form.errors.priority" class="text-destructive text-sm mt-1">
                                            {{ form.errors.priority }}
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <Label class="mb-3 block font-medium">Status <span class="text-red-500">*</span></Label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div
                                                v-for="status in statusOptions"
                                                :key="status.value"
                                                @click="form.status = status.value"
                                                class="border rounded-lg p-3 cursor-pointer transition-all hover:border-primary"
                                                :class="{
                                                    'border-primary bg-primary/10': form.status === status.value,
                                                    'border-border': form.status !== status.value
                                                }"
                                            >
                                                <div class="flex flex-col items-center text-center space-y-2">
                                                    <component
                                                        :is="status.icon"
                                                        class="h-4 w-4"
                                                        :class="form.status === status.value ? 'text-primary' : status.color"
                                                    />
                                                    <span class="text-xs font-medium"
                                                          :class="form.status === status.value ? 'text-primary' : 'text-foreground'"
                                                    >
                                                        {{ status.label }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="form.errors.status" class="text-destructive text-sm mt-1">
                                            {{ form.errors.status }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <Label for="description" class="mb-2 block">Bug Description <span class="text-red-500">*</span></Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Detailed description of the bug, what happened, and the impact..."
                                        :disabled="form.processing"
                                        required
                                        rows="5"
                                        class="w-full resize-none"
                                    />
                                    <div v-if="form.errors.description" class="text-destructive text-sm mt-1">
                                        {{ form.errors.description }}
                                    </div>
                                </div>

                                <!-- Steps to Reproduce -->
                                <div>
                                    <Label for="steps_to_reproduce" class="mb-2 block">Steps to Reproduce</Label>
                                    <Textarea
                                        id="steps_to_reproduce"
                                        v-model="form.steps_to_reproduce"
                                        placeholder="1. Go to... &#10;2. Click on... &#10;3. Notice that..."
                                        :disabled="form.processing"
                                        rows="4"
                                        class="w-full resize-none"
                                    />
                                    <div v-if="form.errors.steps_to_reproduce" class="text-destructive text-sm mt-1">
                                        {{ form.errors.steps_to_reproduce }}
                                    </div>
                                </div>

                                <!-- Page URL -->
                                <div>
                                    <Label for="page_url" class="mb-2 block">Page URL (where bug occurred)</Label>
                                    <Input
                                        id="page_url"
                                        v-model="form.page_url"
                                        type="url"
                                        placeholder="https://example.com/page-where-bug-occurred"
                                        :disabled="form.processing"
                                        class="w-full"
                                    />
                                    <div v-if="form.errors.page_url" class="text-destructive text-sm mt-1">
                                        {{ form.errors.page_url }}
                                    </div>
                                </div>

                                <!-- Assignment - FIXED -->
                                <div>
                                    <Label class="mb-2 block">Assign to Developer</Label>
                                    <Select v-model="form.assigned_to">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select developer to assign" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="unassigned">Leave Unassigned</SelectItem>
                                            <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.assigned_to" class="text-destructive text-sm mt-1">
                                        {{ form.errors.assigned_to }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex gap-4 pt-4 border-t">
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1 sm:flex-none sm:px-8"
                                    >
                                        <Save class="mr-2 h-4 w-4" />
                                        <span v-if="form.processing">Updating Bug Report...</span>
                                        <span v-else>Update Bug Report</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :disabled="form.processing"
                                        :as="Link"
                                        :href="`/admin/bug-reports/${bugReport.id}`"
                                        class="flex-1 sm:flex-none"
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Original Report Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Original Report</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Reported By</Label>
                                <p class="text-sm mt-1 font-medium">{{ bugReport.reported_by.name }}</p>
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

                    <!-- Priority Guide -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Priority Guidelines</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="priority in priorityOptions" :key="priority.value" class="flex items-start space-x-3">
                                <component :is="priority.icon" :class="`h-4 w-4 mt-0.5 ${priority.color}`" />
                                <div>
                                    <div class="font-medium text-sm">{{ priority.label }}</div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        <span v-if="priority.value === 'low'">Minor issues, cosmetic problems</span>
                                        <span v-else-if="priority.value === 'medium'">Moderate impact, workarounds exist</span>
                                        <span v-else-if="priority.value === 'high'">Major functionality affected</span>
                                        <span v-else>System down, critical features broken</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status Guide -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Status Guidelines</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="status in statusOptions" :key="status.value" class="flex items-start space-x-3">
                                <component :is="status.icon" :class="`h-4 w-4 mt-0.5 ${status.color}`" />
                                <div>
                                    <div class="font-medium text-sm">{{ status.label }}</div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        <span v-if="status.value === 'open'">Bug reported and awaiting investigation</span>
                                        <span v-else-if="status.value === 'in_progress'">Bug is being actively worked on</span>
                                        <span v-else-if="status.value === 'resolved'">Bug has been fixed and tested</span>
                                        <span v-else>Bug report closed and archived</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Guidelines Card -->
                    <Card class="border-blue-200 bg-blue-50">
                        <CardContent class="pt-6">
                            <div class="flex items-start space-x-3">
                                <Bug class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <h3 class="font-medium text-blue-900 mb-2">Update Guidelines</h3>
                                    <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• Update status as work progresses</li>
                                        <li>• Assign to appropriate developer</li>
                                        <li>• Adjust priority if needed</li>
                                        <li>• Add missing reproduction steps</li>
                                        <li>• Update description with new findings</li>
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
