<template>
    <Head title="Create Video Category" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost">
                    <Link href="/admin/video-categories">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Categories
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Video Category</h1>
                    <p class="text-muted-foreground">Add a new category to organize your video courses</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FolderOpen class="h-5 w-5" />
                            Category Information
                        </CardTitle>
                        <CardDescription>
                            Enter the details for your video category
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Category Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter category name..."
                                :class="{ 'border-destructive': form.errors.name }"
                                required
                            />
                            <div v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe this video category..."
                                rows="4"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Sort Order -->
                        <div class="space-y-2">
                            <Label for="sort_order">Sort Order</Label>
                            <Input
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                max="999"
                                placeholder="0"
                                :class="{ 'border-destructive': form.errors.sort_order }"
                            />
                            <div v-if="form.errors.sort_order" class="text-sm text-destructive">
                                {{ form.errors.sort_order }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Lower numbers appear first (0-999)
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Settings
                        </CardTitle>
                        <CardDescription>
                            Configure the category settings
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <Label>Active Status</Label>
                                <div class="text-sm text-muted-foreground">
                                    Make this category available for video organization
                                </div>
                            </div>
                            <Switch
                                :checked="form.is_active"
                                @update:checked="form.is_active = $event"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <Button as-child variant="outline">
                        <Link href="/admin/video-categories">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="isSubmitting || form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Creating...' : 'Create Category' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'

// Icons
import { ArrowLeft, Save, FolderOpen, Settings } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Categories', href: '/admin/video-categories' },
    { title: 'Create Category', href: '' },
]

const form = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
})

const isSubmitting = ref(false)

const submit = async () => {
    isSubmitting.value = true

    form.post('/admin/video-categories', {
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}
</script>
