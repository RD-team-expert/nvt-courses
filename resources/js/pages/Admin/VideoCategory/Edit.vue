<template>
    <Head :title="`Edit ${category.name}`" />
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
                    <h1 class="text-2xl font-bold">Edit Video Category</h1>
                    <p class="text-muted-foreground">Update category details and settings</p>
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
                            Update the details for your video category
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

                        <!-- Slug (Read-only display) -->
                        <div class="space-y-2">
                            <Label>URL Slug</Label>
                            <div class="flex items-center gap-2 px-3 py-2 bg-muted rounded-md">
                                <Link class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm text-muted-foreground">{{ category.slug }}</span>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Auto-generated from category name
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
                            Category Settings
                        </CardTitle>
                        <CardDescription>
                            Configure the category status and visibility
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
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

                <!-- Category Statistics -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Category Statistics
                        </CardTitle>
                        <CardDescription>
                            Current usage statistics for this category
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-blue-50 dark:bg-blue-950/50 rounded-lg border border-blue-200 dark:border-blue-800">
                                <PlaySquare class="h-8 w-8 mx-auto mb-2 text-blue-600 dark:text-blue-400" />
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ category.videos_count }}
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Total Videos</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 dark:bg-green-950/50 rounded-lg border border-green-200 dark:border-green-800">
                                <CheckCircle class="h-8 w-8 mx-auto mb-2 text-green-600 dark:text-green-400" />
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                    {{ category.active_videos_count }}
                                </div>
                                <div class="text-sm text-green-700 dark:text-green-300">Active Videos</div>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-muted-foreground text-center">
                            Created {{ new Date(category.created_at).toLocaleDateString() }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center">
                    <div class="flex gap-2">
                        <Button as-child variant="outline">
                            <Link href="/admin/video-categories">Cancel</Link>
                        </Button>

                        <!-- Danger Zone - Delete -->
                        <Button
                            type="button"
                            variant="destructive"
                            @click="deleteCategory"
                            :disabled="category.videos_count > 0"
                        >
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete Category
                        </Button>
                    </div>

                    <Button type="submit" :disabled="isSubmitting || form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSubmitting ? 'Updating...' : 'Update Category' }}
                    </Button>
                </div>

                <!-- Delete Warning -->
                <div v-if="category.videos_count > 0" class="p-4 bg-yellow-50 dark:bg-yellow-950/50 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-start gap-2">
                        <AlertTriangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                        <div class="text-sm">
                            <div class="font-medium text-yellow-800 dark:text-yellow-200">Cannot delete this category</div>
                            <div class="text-yellow-700 dark:text-yellow-300">
                                This category contains {{ category.videos_count }} video(s). Move or delete all videos before deleting this category.
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
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
import {
    ArrowLeft,
    Save,
    FolderOpen,
    Settings,
    BarChart3,
    PlaySquare,
    CheckCircle,
    Trash2,
    AlertTriangle,
    Link as LinkIcon
} from 'lucide-vue-next'

interface VideoCategory {
    id: number
    name: string
    description?: string
    slug: string
    is_active: boolean
    sort_order: number
    videos_count: number
    active_videos_count: number
    created_at: string
}

const props = defineProps<{
    category: VideoCategory
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Categories', href: '/admin/video-categories' },
    { title: 'Edit Category', href: '' },
]

const form = useForm({
    name: props.category.name,
    description: props.category.description || '',
    is_active: props.category.is_active,
    sort_order: props.category.sort_order,
})

const isSubmitting = ref(false)

const submit = async () => {
    isSubmitting.value = true

    form.put(`/admin/video-categories/${props.category.id}`, {
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}

const deleteCategory = () => {
    if (props.category.videos_count > 0) {
        alert('Cannot delete category with videos. Please remove all videos first.')
        return
    }

    if (confirm(`Are you sure you want to delete "${props.category.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/video-categories/${props.category.id}`)
    }
}
</script>
