<!-- Admin Audio Category Index -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

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
    BookOpen,
    MoreHorizontal,
    Eye,
    Edit,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Volume2,
    Hash
} from 'lucide-vue-next'

interface AudioCategory {
    id: number
    name: string
    description: string
    slug: string
    is_active: boolean
    sort_order: number
    audios_count: number
    active_audios_count: number
    created_at: string
}

const props = defineProps<{
    categories: AudioCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audio Categories', href: '/admin/audio-categories' }
]

// Statistics
const stats = computed(() => ({
    total: props.categories.length,
    active: props.categories.filter(c => c.is_active).length,
    inactive: props.categories.filter(c => !c.is_active).length,
    totalAudios: props.categories.reduce((sum, c) => sum + c.audios_count, 0)
}))

// Actions
function toggleActive(category: AudioCategory) {
    router.post(`/admin/audio-categories/${category.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Category ${category.is_active ? 'deactivated' : 'activated'} successfully`)
        }
    })
}

function deleteCategory(category: AudioCategory) {
    if (category.audios_count > 0) {
        alert(`Cannot delete "${category.name}" because it has ${category.audios_count} audio(s) associated with it.`)
        return
    }

    if (confirm(`Are you sure you want to delete "${category.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/audio-categories/${category.id}`, {
            onSuccess: () => {
                console.log('Category deleted successfully')
            }
        })
    }
}

// Get status badge variant
const getStatusVariant = (isActive: boolean) => {
    return isActive ? 'default' : 'secondary'
}
</script>

<template>
    <Head title="Audio Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                        <BookOpen class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                        Audio Categories
                    </h1>
                    <p class="text-muted-foreground">Organize your audio courses with categories</p>
                </div>
                <Button asChild class="bg-primary text-primary-foreground hover:bg-primary/90">
                    <Link href="/admin/audio-categories/create">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Category
                    </Link>
                </Button>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <BookOpen class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ stats.total }}</div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Total Categories</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500 dark:bg-green-600 rounded-lg">
                                <ToggleRight class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ stats.active }}</div>
                                <div class="text-sm text-green-700 dark:text-green-300">Active</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-950/50 dark:to-gray-900/50 border border-gray-200/50 dark:border-gray-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gray-500 dark:bg-gray-600 rounded-lg">
                                <ToggleLeft class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.inactive }}</div>
                                <div class="text-sm text-gray-700 dark:text-gray-300">Inactive</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/50 border border-purple-200/50 dark:border-purple-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-500 dark:bg-purple-600 rounded-lg">
                                <Volume2 class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ stats.totalAudios }}</div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Total Audios</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Categories Table -->
            <Card class="bg-card border-border">
                <CardHeader class="bg-card">
                    <CardTitle class="flex items-center gap-2 text-card-foreground">
                        <BookOpen class="h-5 w-5" />
                        Categories ({{ categories.length }})
                    </CardTitle>
                    <CardDescription class="text-muted-foreground">
                        Manage and organize your audio course categories
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0 bg-card">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-muted/50">
                                <TableRow class="border-border">
                                    <TableHead class="text-muted-foreground">Category</TableHead>
                                    <TableHead class="text-muted-foreground">Slug</TableHead>
                                    <TableHead class="text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-muted-foreground">Sort Order</TableHead>
                                    <TableHead class="text-muted-foreground">Audio Count</TableHead>
                                    <TableHead class="text-muted-foreground">Created</TableHead>
                                    <TableHead class="text-right text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="categories.length === 0" class="border-border">
                                    <TableCell colspan="7" class="text-center py-16 text-muted-foreground">
                                        <BookOpen class="h-16 w-16 mx-auto text-muted-foreground mb-6" />
                                        <div class="text-lg font-medium mb-2 text-foreground">No categories yet</div>
                                        <p class="text-muted-foreground mb-4">Create your first audio category to get started</p>
                                        <Button asChild class="bg-primary text-primary-foreground hover:bg-primary/90">
                                            <Link href="/admin/audio-categories/create">
                                                <Plus class="h-4 w-4 mr-2" />
                                                Add First Category
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-for="category in categories" :key="category.id" class="hover:bg-accent/50 border-border text-card-foreground">
                                    <!-- Category Info -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="font-semibold text-foreground">{{ category.name }}</div>
                                            <div v-if="category.description" class="text-sm text-muted-foreground line-clamp-2">
                                                {{ category.description }}
                                            </div>
                                            <div v-else class="text-sm text-muted-foreground italic">
                                                No description
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Slug -->
                                    <TableCell>
                                        <code class="text-xs bg-muted text-muted-foreground px-2 py-1 rounded border border-border">{{ category.slug }}</code>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(category.is_active)" class="bg-background border-border">
                                            {{ category.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Sort Order -->
                                    <TableCell>
                                        <div class="flex items-center gap-1 text-sm text-foreground">
                                            <Hash class="h-3 w-3 text-muted-foreground" />
                                            {{ category.sort_order }}
                                        </div>
                                    </TableCell>

                                    <!-- Audio Count -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="text-sm text-foreground">
                                                <span class="font-medium">{{ category.audios_count }}</span> total
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ category.active_audios_count }} active
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Created -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ new Date(category.created_at).toLocaleDateString() }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" class="text-foreground hover:bg-accent hover:text-accent-foreground">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="bg-popover border-border text-popover-foreground">
                                                <DropdownMenuLabel class="text-popover-foreground">Actions</DropdownMenuLabel>

                                                <DropdownMenuItem asChild class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                                    <Link :href="`/admin/audio-categories/${category.id}/edit`">
                                                        <Edit class="h-4 w-4 mr-2" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator class="bg-border" />

                                                <DropdownMenuItem @click="toggleActive(category)" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                                    <component :is="category.is_active ? ToggleRight : ToggleLeft" class="h-4 w-4 mr-2" />
                                                    {{ category.is_active ? 'Deactivate' : 'Activate' }}
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator class="bg-border" />

                                                <DropdownMenuItem
                                                    @click="deleteCategory(category)"
                                                    class="text-destructive focus:text-destructive cursor-pointer"
                                                    :disabled="category.audios_count > 0"
                                                >
                                                    <Trash2 class="h-4 w-4 mr-2" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
