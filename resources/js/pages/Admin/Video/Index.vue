<template>
    <Head title="Video Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                        <PlaySquare class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                        Video Management
                    </h1>
                    <p class="text-muted-foreground">Manage your video courses and track performance</p>
                </div>
                <Button as-child size="default" class="bg-primary text-primary-foreground hover:bg-primary/90">
                    <Link href="/admin/videos/create">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Video Course
                    </Link>
                </Button>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <PlaySquare class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ stats.total }}
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Total Videos</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500 dark:bg-green-600 rounded-lg">
                                <Play class="h-6 w-6 text-white" />
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
                                <Users class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ stats.totalViewers }}
                                </div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Total Viewers</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/50 border border-orange-200/50 dark:border-orange-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-500 dark:bg-orange-600 rounded-lg">
                                <CheckCircle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                    {{ stats.completed }}
                                </div>
                                <div class="text-sm text-orange-700 dark:text-orange-300">Completed</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-950/50 dark:to-teal-900/50 border border-teal-200/50 dark:border-teal-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-teal-500 dark:bg-teal-600 rounded-lg">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-teal-900 dark:text-teal-100">
                                    {{ stats.avgCompletion }}%
                                </div>
                                <div class="text-sm text-teal-700 dark:text-teal-300">Avg Progress</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Videos Table -->
            <Card class="bg-card border-border">
                <CardHeader class="bg-card text-card-foreground">
                    <CardTitle class="text-xl">Video Courses</CardTitle>
                    <CardDescription class="text-muted-foreground">
                        Manage your video courses and track their performance
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-border">
                                <TableHead class="text-foreground">Video</TableHead>
                                <TableHead class="text-foreground">Category</TableHead>
                                <TableHead class="text-foreground">Duration</TableHead>
                                <TableHead class="text-foreground">Status</TableHead>
                                <TableHead class="text-foreground">Analytics</TableHead>
                                <TableHead class="text-foreground">Created</TableHead>
                                <TableHead class="text-right text-foreground">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="video in videos"
                                :key="video.id"
                                class="hover:bg-accent/50 border-border text-card-foreground"
                            >
                                <!-- Video Details -->
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="relative w-16 h-16 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 flex items-center justify-center shrink-0 border border-muted">
                                            <img
                                                v-if="video.thumbnail_url"
                                                :src="video.thumbnail_url"
                                                :alt="video.name"
                                                class="w-full h-full object-cover"
                                                @error="handleImageError"
                                            />
                                            <PlaySquare
                                                class="fallback-icon h-8 w-8 text-blue-500 dark:text-blue-400"
                                                :style="{ display: video.thumbnail_url ? 'none' : 'flex' }"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold truncate text-base text-foreground">
                                                {{ video.name }}
                                            </div>
                                            <div class="text-sm text-muted-foreground truncate max-w-xs">
                                                {{ video.description || 'No description' }}
                                            </div>
                                            <div class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                                                <span>by {{ video.creator.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Category -->
                                <TableCell>
                                    <Badge v-if="video.category" variant="outline" class="gap-1 bg-background border-border text-foreground">
                                        <FolderOpen class="h-3 w-3" />
                                        {{ video.category.name }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground text-sm">No category</span>
                                </TableCell>

                                <!-- Duration -->
                                <TableCell>
                                    <div class="flex items-center gap-1 text-sm text-foreground">
                                        <Clock class="h-4 w-4 text-muted-foreground" />
                                        {{ video.formatted_duration }}
                                    </div>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge :variant="getStatusVariant(video.is_active)" class="bg-background border-border">
                                        {{ video.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </TableCell>

                                <!-- Analytics -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-sm text-foreground">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ video.total_viewers }} viewers</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm">
                                            <TrendingUp class="h-4 w-4 text-muted-foreground" />
                                            <span :class="getCompletionColor(video.avg_completion)">
                                                {{ Math.round(video.avg_completion) }}% completion
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Created -->
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ new Date(video.created_at).toLocaleDateString() }}
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
                                                @click="viewAnalytics(video)"
                                                class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            >
                                                <BarChart3 class="h-4 w-4 mr-2" />
                                                Analytics
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="editVideo(video)"
                                                class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            >
                                                <Edit class="h-4 w-4 mr-2" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator class="bg-border" />
                                            <DropdownMenuItem
                                                @click="toggleActive(video)"
                                                class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer"
                                            >
                                                <component :is="video.is_active ? ToggleRight : ToggleLeft" class="h-4 w-4 mr-2" />
                                                {{ video.is_active ? 'Deactivate' : 'Activate' }}
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator class="bg-border" />
                                            <DropdownMenuItem
                                                @click="deleteVideo(video)"
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
                            <TableRow v-if="videos.length === 0" class="border-border">
                                <TableCell colspan="7" class="text-center py-16 text-muted-foreground">
                                    <PlaySquare class="h-16 w-16 mx-auto text-muted-foreground mb-6" />
                                    <div class="text-lg font-medium mb-2 text-foreground">No video courses found</div>
                                    <p class="text-muted-foreground mb-4">
                                        Get started by creating your first video course
                                    </p>
                                    <Button as-child class="bg-primary text-primary-foreground hover:bg-primary/90">
                                        <Link href="/admin/videos/create">
                                            <Plus class="h-4 w-4 mr-2" />
                                            Add Your First Video Course
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
import { computed, onMounted } from 'vue'
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
    PlaySquare,
    Clock,
    Users,
    TrendingUp,
    CheckCircle,
    MoreHorizontal,
    Edit,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Play,
    FolderOpen,
    BarChart3
} from 'lucide-vue-next'

interface Video {
    id: number
    name: string
    description?: string
    duration: number
    formatted_duration: string
    thumbnail_url?: string
    is_active: boolean
    category?: {
        id: number
        name: string
    }
    creator: {
        id: number
        name: string
    }
    total_viewers: number
    completed_viewers: number
    avg_completion: number
    created_at: string
}

interface VideoCategory {
    id: number
    name: string
}

const props = defineProps<{
    videos: Video[]
    categories: VideoCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
]

// ✅ DEBUG LOGGING
onMounted(() => {
    console.log('====== VIDEO INDEX DEBUG ======')
    console.log('Total videos:', props.videos.length)
    console.log('Total categories:', props.categories.length)
    console.log('Available categories:', props.categories)
    console.log('----------------------------')
    
    props.videos.forEach((video, index) => {
        console.log(`Video ${index + 1}: "${video.name}"`)
        console.log('  - ID:', video.id)
        console.log('  - Category object:', video.category)
        console.log('  - Has category?:', !!video.category)
        console.log('  - Category ID:', video.category?.id || 'NULL')
        console.log('  - Category Name:', video.category?.name || 'NULL')
        console.log('----------------------------')
    })
})

// Statistics
const stats = computed(() => ({
    total: props.videos.length,
    active: props.videos.filter(v => v.is_active).length,
    totalViewers: props.videos.reduce((sum, v) => sum + v.total_viewers, 0),
    completed: props.videos.reduce((sum, v) => sum + v.completed_viewers, 0),
    avgCompletion: props.videos.length > 0
        ? Math.round(props.videos.reduce((sum, v) => sum + v.avg_completion, 0) / props.videos.length)
        : 0
}))

// Actions
function viewAnalytics(video: Video) {
    router.visit(`/admin/videos/${video.id}`)
}

function editVideo(video: Video) {
    router.visit(`/admin/videos/${video.id}/edit`)
}

function toggleActive(video: Video) {
    router.post(`/admin/videos/${video.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Video ${video.is_active ? 'deactivated' : 'activated'} successfully`)
        }
    })
}

function deleteVideo(video: Video) {
    if (confirm(`Are you sure you want to delete "${video.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/videos/${video.id}`, {
            onSuccess: () => {
                console.log('Video deleted successfully')
            }
        })
    }
}

// Helper functions
function getStatusVariant(isActive: boolean) {
    return isActive ? 'default' : 'secondary'
}

function getCompletionColor(rate: number) {
    if (rate >= 80) return 'text-green-600'
    if (rate >= 60) return 'text-yellow-600'
    return 'text-red-600'
}

function handleImageError(event: Event) {
    const img = event.target as HTMLImageElement
    img.style.display = 'none'

    const parent = img.parentElement
    if (parent) {
        const fallback = parent.querySelector('.fallback-icon') as HTMLElement
        if (fallback) {
            fallback.style.display = 'flex'
        }
    }
}
</script>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
