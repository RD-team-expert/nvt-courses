<template>
    <Head title="Video Categories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                        <FolderOpen class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                        Video Categories
                    </h1>
                    <p class="text-muted-foreground">Organize your video courses into categories</p>
                </div>
                <Button as-child size="default" class="bg-primary text-primary-foreground hover:bg-primary/90">
                    <Link href="/admin/video-categories/create">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Category
                    </Link>
                </Button>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <FolderOpen class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ stats.total }}
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Total</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500 dark:bg-green-600 rounded-lg">
                                <CheckCircle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                    {{ stats.active }}
                                </div>
                                <div class="text-sm text-green-700 dark:text-green-300">Active</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/50 border border-purple-200/50 dark:border-purple-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-500 dark:bg-purple-600 rounded-lg">
                                <PlaySquare class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ stats.totalVideos }}
                                </div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Videos</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/50 border border-orange-200/50 dark:border-orange-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-500 dark:bg-orange-600 rounded-lg">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                    {{ stats.avgVideos }}
                                </div>
                                <div class="text-sm text-orange-700 dark:text-orange-300">Avg/Category</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Categories Table -->
            <Card class="bg-card border-border">
                <CardHeader class="bg-card text-card-foreground">
                    <CardTitle class="text-xl">Video Categories</CardTitle>
                    <CardDescription class="text-muted-foreground">
                        Manage your video course categories
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-border">
                                <TableHead class="text-foreground">Category</TableHead>
                                <TableHead class="text-foreground">Videos</TableHead>
                                <TableHead class="text-foreground">Status</TableHead>
                                <TableHead class="text-foreground">Sort Order</TableHead>
                                <TableHead class="text-foreground">Created</TableHead>
                                <TableHead class="text-right text-foreground">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="category in categories"
                                :key="category.id"
                                class="hover:bg-accent/50 border-border text-card-foreground"
                            >
                                <!-- Category Details -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-semibold text-base text-foreground">
                                            {{ category.name }}
                                        </div>
                                        <div class="text-sm text-muted-foreground truncate max-w-xs">
                                            {{ category.description || 'No description' }}
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Video Count -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="text-sm text-foreground">
                                            <span class="font-medium">{{ category.videos_count }}</span> total
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ category.active_videos_count }} active
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge :variant="category.is_active ? 'default' : 'secondary'" class="bg-background border-border">
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </TableCell>

                                <!-- Sort Order -->
                                <TableCell class="text-sm text-foreground">
                                    {{ category.sort_order }}
                                </TableCell>

                                <!-- Created -->
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ new Date(category.created_at).toLocaleDateString() }}
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="sm" class="text-foreground hover:bg-accent hover:text-accent-foreground">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="bg-popover border-border text-popover-foreground">
                                            <DropdownMenuLabel class="text-popover-foreground">Actions</DropdownMenuLabel>
                                            <DropdownMenuItem
                                                @click="editCategory(category)"
                                                class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            >
                                                <Edit class="h-4 w-4 mr-2" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator class="bg-border" />
                                            <DropdownMenuItem
                                                @click="toggleActive(category)"
                                                class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            >
                                                <component :is="category.is_active ? ToggleRight : ToggleLeft" class="h-4 w-4 mr-2" />
                                                {{ category.is_active ? 'Deactivate' : 'Activate' }}
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator class="bg-border" />
                                            <DropdownMenuItem
                                                @click="deleteCategory(category)"
                                                class="text-destructive focus:text-destructive cursor-pointer"
                                            >
                                                <Trash2 class="h-4 w-4 mr-2" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="categories.length === 0" class="border-border">
                                <TableCell colspan="6" class="text-center py-16 text-muted-foreground">
                                    <FolderOpen class="h-16 w-16 mx-auto text-muted-foreground mb-6" />
                                    <div class="text-lg font-medium mb-2 text-foreground">No video categories found</div>
                                    <p class="text-muted-foreground mb-4">
                                        Get started by creating your first video category
                                    </p>
                                    <Button as-child class="bg-primary text-primary-foreground hover:bg-primary/90">
                                        <Link href="/admin/video-categories/create">
                                            <Plus class="h-4 w-4 mr-2" />
                                            Add Your First Category
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// Icons
import {
    Plus,
    FolderOpen,
    PlaySquare,
    TrendingUp,
    CheckCircle,
    MoreHorizontal,
    Edit,
    Trash2,
    ToggleLeft,
    ToggleRight
} from 'lucide-vue-next'

interface VideoCategory {
    id: number
    name: string
    description?: string
    is_active: boolean
    sort_order: number
    videos_count: number
    active_videos_count: number
    created_at: string
}

const props = defineProps<{
    categories: VideoCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Categories', href: '/admin/video-categories' },
]

// Statistics
const stats = computed(() => ({
    total: props.categories.length,
    active: props.categories.filter(c => c.is_active).length,
    totalVideos: props.categories.reduce((sum, c) => sum + c.videos_count, 0),
    avgVideos: props.categories.length > 0
        ? Math.round(props.categories.reduce((sum, c) => sum + c.videos_count, 0) / props.categories.length)
        : 0
}))

// Actions
function editCategory(category: VideoCategory) {
    router.visit(`/admin/video-categories/${category.id}/edit`)
}

function toggleActive(category: VideoCategory) {
    router.post(`/admin/video-categories/${category.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Category ${category.is_active ? 'deactivated' : 'activated'} successfully`)
        }
    })
}

function deleteCategory(category: VideoCategory) {
    if (confirm(`Are you sure you want to delete "${category.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/video-categories/${category.id}`, {
            onSuccess: () => {
                console.log('Category deleted successfully')
            }
        })
    }
}
</script>
