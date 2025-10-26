<script setup lang="ts">
import { defineProps } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    MessageSquarePlus,
    Lightbulb,
    Wrench,
    Star,
    Clock,
    CheckCircle,
    XCircle,
    Eye,
    Plus
} from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    feedback: Object,
})

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
        case 'approved': return 'success'
        case 'rejected': return 'destructive'
        default: return 'secondary'
    }
}

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
        'general': 'General'
    }
    return labels[type] || type
}

function formatDate(dateString: string) {
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
    <AppLayout :breadcrumbs="breadcrumbs">

    <div class="min-h-screen bg-background">
        <div class="container mx-auto px-4 py-8 max-w-4xl">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-foreground mb-2">My Feedback</h1>
                    <p class="text-muted-foreground">Track the status of your submitted feedback</p>
                </div>
                <Button :as="Link" href="/feedback" class="w-full sm:w-auto">
                    <Plus class="mr-2 h-4 w-4" />
                    Submit New Feedback
                </Button>
            </div>

            <!-- Feedback List -->
            <div v-if="feedback?.data?.length > 0" class="space-y-4">
                <Card v-for="item in feedback.data" :key="item.id" class="shadow-sm hover:shadow-md transition-shadow">
                    <CardContent class="pt-6">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <!-- Main Content -->
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex items-center space-x-2">
                                        <component
                                            :is="getTypeIcon(item.type)"
                                            class="h-5 w-5 text-primary"
                                        />
                                        <h3 class="font-semibold text-lg text-foreground">{{ item.title }}</h3>
                                    </div>
                                    <div class="flex items-center space-x-2 flex-shrink-0">
                                        <Badge variant="outline" class="text-xs">
                                            {{ getTypeLabel(item.type) }}
                                        </Badge>
                                        <Badge :variant="getStatusVariant(item.status)" class="text-xs">
                                            <component :is="getStatusIcon(item.status)" class="mr-1 h-3 w-3" />
                                            {{ item.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                        </Badge>
                                    </div>
                                </div>

                                <p class="text-muted-foreground mb-3 leading-relaxed">{{ item.description }}</p>

                                <div class="text-sm text-muted-foreground">
                                    Submitted {{ formatDate(item.created_at) }}
                                </div>
                            </div>
                        </div>

                        <!-- Admin Response -->
                        <div v-if="item.admin_response" class="mt-4 pt-4 border-t border-border">
                            <div class="bg-muted/30 rounded-lg p-4">
                                <h4 class="font-medium text-foreground mb-2 flex items-center">
                                    <MessageSquarePlus class="mr-2 h-4 w-4 text-primary" />
                                    Admin Response
                                </h4>
                                <p class="text-muted-foreground text-sm leading-relaxed">{{ item.admin_response }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="feedback.links && feedback.links.length > 3" class="flex justify-center mt-8">
                    <div class="flex space-x-2">
                        <Button
                            v-for="link in feedback.links"
                            :key="link.label"
                            :as="Link"
                            :href="link.url"
                            :disabled="!link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <Card class="max-w-md mx-auto">
                    <CardContent class="pt-8 pb-8">
                        <MessageSquarePlus class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">No feedback yet</h3>
                        <p class="text-sm text-muted-foreground mb-6">
                            You haven't submitted any feedback. Share your thoughts to help us improve!
                        </p>
                        <Button :as="Link" href="/feedback" class="w-full">
                            <Plus class="mr-2 h-4 w-4" />
                            Submit Your First Feedback
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
    </AppLayout>

</template>
