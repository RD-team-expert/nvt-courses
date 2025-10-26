<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Textarea } from '@/components/ui/textarea'
import {
    MessageSquarePlus,
    Lightbulb,
    Wrench,
    Star,
    Send
} from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue';

const form = useForm({
    type: '',
    title: '',
    description: ''
})

const feedbackTypes = [
    { value: 'suggestion', label: 'Suggestion', icon: Lightbulb, description: 'Ideas to improve the system' },
    { value: 'improvement', label: 'Improvement', icon: Wrench, description: 'Ways to make existing features better' },
    { value: 'feature_request', label: 'Feature Request', icon: Star, description: 'New features you\'d like to see' },
    { value: 'general', label: 'General Feedback', icon: MessageSquarePlus, description: 'General thoughts and comments' }
]

function submit() {
    form.post('/feedback', {
        onSuccess: () => {
            form.reset()
        }
    })
}

function getTypeIcon(type: string) {
    const feedbackType = feedbackTypes.find(t => t.value === type)
    return feedbackType?.icon || MessageSquarePlus
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

    <div class="min-h-screen bg-background">
        <div class="container mx-auto px-4 py-8 max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-foreground mb-2">Share Your Feedback</h1>
                <p class="text-muted-foreground">Help us improve by sharing your suggestions and ideas</p>
            </div>

            <!-- Success Message -->
            <Alert v-if="$page.props.flash.success" class="mb-6 border-green-200 bg-green-50">
                <MessageSquarePlus class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-green-800">
                    {{ $page.props.flash.success }}
                </AlertDescription>
            </Alert>

            <!-- Feedback Form -->
            <Card class="shadow-lg">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <MessageSquarePlus class="mr-2 h-5 w-5" />
                        Submit Feedback
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Feedback Type Selection -->
                        <div>
                            <Label class="mb-3 block font-medium">What type of feedback is this?</Label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    v-for="type in feedbackTypes"
                                    :key="type.value"
                                    @click="form.type = type.value"
                                    class="border rounded-lg p-3 cursor-pointer transition-all hover:border-primary"
                                    :class="{
                                        'border-primary bg-primary/10': form.type === type.value,
                                        'border-border': form.type !== type.value
                                    }"
                                >
                                    <div class="flex items-start space-x-3">
                                        <component
                                            :is="type.icon"
                                            class="h-5 w-5 mt-0.5"
                                            :class="form.type === type.value ? 'text-primary' : 'text-muted-foreground'"
                                        />
                                        <div>
                                            <h3 class="font-medium text-sm"
                                                :class="form.type === type.value ? 'text-primary' : 'text-foreground'"
                                            >
                                                {{ type.label }}
                                            </h3>
                                            <p class="text-xs text-muted-foreground mt-1">{{ type.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.type" class="text-destructive text-sm mt-2">
                                {{ form.errors.type }}
                            </div>
                        </div>

                        <!-- Title -->
                        <div>
                            <Label for="title" class="mb-2 block">Title</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Brief description of your feedback"
                                :disabled="form.processing"
                                required
                                class="w-full"
                            />
                            <div v-if="form.errors.title" class="text-destructive text-sm mt-1">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <Label for="description" class="mb-2 block">Details</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Please provide detailed information about your feedback..."
                                :disabled="form.processing"
                                required
                                rows="6"
                                class="w-full resize-none"
                            />
                            <p class="text-xs text-muted-foreground mt-1">
                                Be specific and detailed to help us understand your feedback better
                            </p>
                            <div v-if="form.errors.description" class="text-destructive text-sm mt-1">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-4">
                            <Button
                                type="submit"
                                :disabled="form.processing || !form.type"
                                class="flex-1 sm:flex-none sm:px-8"
                            >
                                <Send class="mr-2 h-4 w-4" />
                                <span v-if="form.processing">Submitting...</span>
                                <span v-else>Submit Feedback</span>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing"
                                class="flex-1 sm:flex-none"
                                @click="$inertia.visit('/dashboard')"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Info Card -->
            <Card class="mt-6 border-blue-200 bg-blue-50">
                <CardContent class="pt-6">
                    <div class="flex items-start space-x-3">
                        <MessageSquarePlus class="h-5 w-5 text-blue-600 mt-0.5" />
                        <div>
                            <h3 class="font-medium text-blue-900 mb-1">Your voice matters!</h3>
                            <p class="text-sm text-blue-700 leading-relaxed">
                                All feedback is reviewed by our team. We'll get back to you if we need more information
                                or when we implement your suggestions. Check your feedback status anytime.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
    </AppLayout>

</template>
