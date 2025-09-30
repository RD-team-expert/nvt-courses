<!-- Admin Audio Category Edit -->
<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'

// Icons
import { ArrowLeft, Save, BookOpen, Hash, Settings } from 'lucide-vue-next'

interface Category {
    id: number
    name: string
    description: string
    slug: string
    is_active: boolean
    sort_order: number
}

const props = defineProps<{
    category: Category
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Audio Categories', href: '/admin/audio-categories' },
    { title: props.category.name, href: '#' },
    { title: 'Edit', href: '#' }
]

const form = useForm({
    name: props.category.name,
    description: props.category.description || '',
    is_active: props.category.is_active,
    sort_order: props.category.sort_order
})

const submit = () => {
    form.put(`/admin/audio-categories/${props.category.id}`)
}
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button asChild variant="ghost">
                    <Link href="/admin/audio-categories">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Categories
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Edit Audio Category</h1>
                    <p class="text-muted-foreground">Update "{{ category.name }}" category details</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BookOpen class="h-5 w-5" />
                            Category Information
                        </CardTitle>
                        <CardDescription>
                            Update the basic details for your audio category
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Category Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter category name"
                                :class="{ 'border-destructive': form.errors.name }"
                                required
                            />
                            <div v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Current slug: <code class="bg-muted px-1 rounded text-xs">{{ category.slug }}</code>
                                (auto-updated when name changes)
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe what types of audio courses belong to this category..."
                                :rows="3"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
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
                            Configure the category visibility and ordering
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Sort Order -->
                        <div class="space-y-2">
                            <Label for="sort_order">Sort Order</Label>
                            <div class="flex items-center gap-2">
                                <Hash class="h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="sort_order"
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                    max="999"
                                    :class="{ 'border-destructive': form.errors.sort_order }"
                                />
                            </div>
                            <div v-if="form.errors.sort_order" class="text-sm text-destructive">
                                {{ form.errors.sort_order }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Lower numbers appear first. Use 0 for default ordering.
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <Label>Active Status</Label>
                                <div class="text-sm text-muted-foreground">
                                    Make this category available for use
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
                    <Button asChild variant="outline">
                        <Link href="/admin/audio-categories">
                            Cancel
                        </Link>
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ form.processing ? 'Updating...' : 'Update Category' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
