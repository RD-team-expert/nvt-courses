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
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    MessageSquarePlus,
    Lightbulb,
    Wrench,
    Star,
    Clock,
    Eye,
    CheckCircle,
    XCircle,
    ArrowLeft,
    Send,
    User,
    Calendar
} from 'lucide-vue-next'

const props = defineProps({
    feedback: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Employee Feedback', href: route('admin.feedback.index') },
    { name: 'View Feedback', href: '#' }
]

// Form for admin response
const form = useForm({
    admin_response: props.feedback.admin_response || '',
    status: props.feedback.status || 'pending'
})

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

function getTypeLabel(type: string) {
    const labels = {
        'suggestion': 'Suggestion',
        'improvement': 'Improvement',
        'feature_request': 'Feature Request',
        'general': 'General Feedback'
    }
    return labels[type] || type
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

// Submit response
const submitResponse = () => {
    form.patch(`/admin/feedback/${props.feedback.id}/respond`, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash messages
        }
    })
}

// Status options
const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'under_review', label: 'Under Review' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Feedback Details</h1>
                    <p class="text-sm text-muted-foreground mt-1">Review and respond to employee feedback</p>
                </div>
                <Button :as="Link" href="/admin/feedback" variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Feedback
                </Button>
            </div>

            <!-- Success Message -->
            <Alert v-if="$page.props.flash.success" class="border-green-200 bg-green-50">
                <CheckCircle class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-green-800">
                    {{ $page.props.flash.success }}
                </AlertDescription>
            </Alert>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Feedback Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Feedback Details -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-3">
                                    <component :is="getTypeIcon(feedback.type)" class="h-6 w-6 text-primary" />
                                    <div>
                                        <CardTitle class="text-xl">{{ feedback.title }}</CardTitle>
                                        <CardDescription class="mt-1">
                                            {{ getTypeLabel(feedback.type) }} • Submitted {{ formatDate(feedback.created_at) }}
                                        </CardDescription>
                                    </div>
                                </div>
                                <Badge :variant="getStatusVariant(feedback.status)" class="flex items-center">
                                    <component :is="getStatusIcon(feedback.status)" class="mr-1 h-3 w-3" />
                                    {{ feedback.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="prose max-w-none">
                                <p class="text-muted-foreground leading-relaxed whitespace-pre-wrap">{{ feedback.description }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admin Response Form -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Send class="mr-2 h-5 w-5" />
                                Admin Response
                            </CardTitle>
                            <CardDescription>
                                Provide feedback to the employee about their suggestion
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submitResponse" class="space-y-4">
                                <!-- Status Selection -->
                                <div>
                                    <Label class="mb-2 block">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="form.errors.status" class="text-destructive text-sm mt-1">
                                        {{ form.errors.status }}
                                    </div>
                                </div>

                                <!-- Response Text -->
                                <div>
                                    <Label for="admin_response" class="mb-2 block">Response Message</Label>
                                    <Textarea
                                        id="admin_response"
                                        v-model="form.admin_response"
                                        placeholder="Write your response to the employee..."
                                        :disabled="form.processing"
                                        rows="6"
                                        class="w-full resize-none"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        This message will be visible to the employee when they view their feedback
                                    </p>
                                    <div v-if="form.errors.admin_response" class="text-destructive text-sm mt-1">
                                        {{ form.errors.admin_response }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex gap-4">
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1 sm:flex-none"
                                    >
                                        <Send class="mr-2 h-4 w-4" />
                                        <span v-if="form.processing">Saving Response...</span>
                                        <span v-else>Save Response</span>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Employee Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <User class="mr-2 h-5 w-5" />
                                Employee Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center space-x-3 mb-4">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="bg-muted text-muted-foreground">
                                        {{ getUserInitials(feedback.user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="font-medium text-foreground">{{ feedback.user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ feedback.user.email }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Feedback Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <Calendar class="mr-2 h-5 w-5" />
                                Feedback Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Type</Label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <component :is="getTypeIcon(feedback.type)" class="h-4 w-4 text-primary" />
                                    <span class="text-sm font-medium">{{ getTypeLabel(feedback.type) }}</span>
                                </div>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Submitted</Label>
                                <p class="text-sm mt-1">{{ formatDate(feedback.created_at) }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-muted-foreground">Current Status</Label>
                                <div class="mt-1">
                                    <Badge :variant="getStatusVariant(feedback.status)" class="text-xs">
                                        <component :is="getStatusIcon(feedback.status)" class="mr-1 h-3 w-3" />
                                        {{ feedback.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Previous Response -->
                    <Card v-if="feedback.admin_response">
                        <CardHeader>
                            <CardTitle class="text-lg">Current Response</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="bg-muted/30 rounded-lg p-4">
                                <p class="text-sm text-muted-foreground leading-relaxed whitespace-pre-wrap">{{ feedback.admin_response }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
