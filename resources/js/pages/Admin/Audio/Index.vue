<!-- Admin Audio Management - Updated with Better Image Handling -->
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
import { Alert, AlertDescription } from '@/components/ui/alert'
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
    Volume2,
    Clock,
    Users,
    TrendingUp,
    MoreHorizontal,
    Eye,
    Edit,
    Trash2,
    ToggleLeft,
    ToggleRight,
    Play,
    Pause,
    BookOpen,
    BarChart3,
    AlertTriangle,
    Image as ImageIcon
} from 'lucide-vue-next'

interface Audio {
    id: number
    name: string
    description: string
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
    total_listeners: number
    completed_listeners: number
    avg_completion: number
    created_at: string
}

interface AudioCategory {
    id: number
    name: string
}

const props = defineProps<{
    audios: Audio[]
    categories: AudioCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Audio Management', href: '/admin/audio' }
]

// State
const selectedCategory = ref('all')
const selectedStatus = ref('all')

// Filter audios
const filteredAudios = computed(() => {
    let filtered = [...props.audios]

    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(audio =>
            audio.category?.id.toString() === selectedCategory.value
        )
    }

    if (selectedStatus.value !== 'all') {
        const isActive = selectedStatus.value === 'active'
        filtered = filtered.filter(audio => audio.is_active === isActive)
    }

    return filtered
})

// Statistics
const stats = computed(() => ({
    total: props.audios.length,
    active: props.audios.filter(a => a.is_active).length,
    inactive: props.audios.filter(a => !a.is_active).length,
    totalListeners: props.audios.reduce((sum, a) => sum + a.total_listeners, 0),
    avgCompletion: props.audios.length > 0
        ? Math.round(props.audios.reduce((sum, a) => sum + a.avg_completion, 0) / props.audios.length)
        : 0
}))

// Actions
function toggleActive(audio: Audio) {
    router.post(`/admin/audio/${audio.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Audio ${audio.is_active ? 'deactivated' : 'activated'} successfully`)
        }
    })
}

function deleteAudio(audio: Audio) {
    if (confirm(`Are you sure you want to delete "${audio.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/audio/${audio.id}`, {
            onSuccess: () => {
                console.log('Audio deleted successfully')
            }
        })
    }
}

// Get status badge variant
const getStatusVariant = (isActive: boolean) => {
    return isActive ? 'default' : 'secondary'
}

// Get completion rate color
const getCompletionColor = (rate: number) => {
    if (rate >= 80) return 'text-green-600'
    if (rate >= 60) return 'text-yellow-600'
    return 'text-red-600'
}

// ✅ Handle image loading errors
function handleImageError(event: Event) {
    const img = event.target as HTMLImageElement
    img.style.display = 'none'

    // Show fallback icon
    const parent = img.parentElement
    if (parent) {
        const fallback = parent.querySelector('.fallback-icon') as HTMLElement
        if (fallback) {
            fallback.style.display = 'flex'
        }
    }
}

// Clear filters
function clearFilters() {
    selectedCategory.value = 'all'
    selectedStatus.value = 'all'
}
</script>

<template>
    <Head title="Audio Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                        <Volume2 class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                        Audio Management
                    </h1>
                    <p class="text-muted-foreground">Manage your audio courses and track performance</p>
                </div>
                <Button asChild size="default" class="bg-primary text-primary-foreground hover:bg-primary/90">
                    <Link href="/admin/audio/create">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Audio Course
                    </Link>
                </Button>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <Volume2 class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ stats.total }}</div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Total Audio</div>
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
                                <Pause class="h-6 w-6 text-white" />
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
                                <Users class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ stats.totalListeners }}</div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Total Listeners</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/50 border border-orange-200/50 dark:border-orange-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-500 dark:bg-orange-600 rounded-lg">
                                <BarChart3 class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ stats.avgCompletion }}%</div>
                                <div class="text-sm text-orange-700 dark:text-orange-300">Avg Completion</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="bg-card border-border">
                <CardContent class="p-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1 space-y-2">
                            <label class="text-sm font-medium text-foreground">Category</label>
                            <select
                                v-model="selectedCategory"
                                class="flex h-10 w-full rounded-md border border-input bg-background text-foreground px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="all">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex-1 space-y-2">
                            <label class="text-sm font-medium text-foreground">Status</label>
                            <select
                                v-model="selectedStatus"
                                class="flex h-10 w-full rounded-md border border-input bg-background text-foreground px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <Button @click="clearFilters" variant="outline" class="bg-background border-border text-foreground hover:bg-accent hover:text-accent-foreground">
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Audio Table -->
            <Card class="bg-card border-border">
                <CardHeader class="bg-card">
                    <CardTitle class="flex items-center gap-2 text-card-foreground">
                        <Volume2 class="h-5 w-5" />
                        Audio Courses ({{ filteredAudios.length }})
                    </CardTitle>
                    <CardDescription class="text-muted-foreground">
                        Manage your audio courses and monitor their performance
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0 bg-card">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-muted/50">
                                <TableRow class="border-border hover:bg-muted/50">
                                    <TableHead class="text-muted-foreground">Audio Details</TableHead>
                                    <TableHead class="text-muted-foreground">Category</TableHead>
                                    <TableHead class="text-muted-foreground">Duration</TableHead>
                                    <TableHead class="text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-muted-foreground">Analytics</TableHead>
                                    <TableHead class="text-muted-foreground">Created</TableHead>
                                    <TableHead class="text-right text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="filteredAudios.length === 0" class="border-border">
                                    <TableCell colspan="7" class="text-center py-16 text-muted-foreground">
                                        <Volume2 class="h-16 w-16 mx-auto text-muted-foreground mb-6" />
                                        <div class="text-lg font-medium mb-2 text-foreground">No audio courses found</div>
                                        <p class="text-muted-foreground mb-4">
                                            {{ selectedCategory !== 'all' || selectedStatus !== 'all'
                                            ? 'Try adjusting your filters to find more courses'
                                            : 'Get started by creating your first audio course'
                                            }}
                                        </p>
                                        <Button asChild v-if="selectedCategory === 'all' && selectedStatus === 'all'" class="bg-primary text-primary-foreground hover:bg-primary/90">
                                            <Link href="/admin/audio/create">
                                                <Plus class="h-4 w-4 mr-2" />
                                                Add Your First Audio Course
                                            </Link>
                                        </Button>
                                        <Button @click="clearFilters" v-else variant="outline" class="bg-background border-border text-foreground hover:bg-accent">
                                            Clear Filters
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-for="audio in filteredAudios" :key="audio.id" class="hover:bg-accent/50 border-border text-card-foreground">
                                    <!-- Audio Details -->
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <!-- ✅ IMPROVED THUMBNAIL WITH ERROR HANDLING -->
                                            <div class="relative w-16 h-16 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 flex items-center justify-center shrink-0 border border-muted">
                                                <img
                                                    v-if="audio.thumbnail_url"
                                                    :src="audio.thumbnail_url"
                                                    :alt="audio.name"
                                                    class="w-full h-full object-cover"
                                                    @error="handleImageError"
                                                />
                                                <Volume2 class="fallback-icon h-8 w-8 text-blue-500 dark:text-blue-400" :style="{ display: audio.thumbnail_url ? 'none' : 'flex' }" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold truncate text-base text-foreground">{{ audio.name }}</div>
                                                <div class="text-sm text-muted-foreground truncate max-w-xs">{{ audio.description || 'No description' }}</div>
                                                <div class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                                                    <span>by {{ audio.creator.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Category -->
                                    <TableCell>
                                        <Badge v-if="audio.category" variant="outline" class="gap-1 bg-background border-border text-foreground">
                                            <BookOpen class="h-3 w-3" />
                                            {{ audio.category.name }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground text-sm">No category</span>
                                    </TableCell>

                                    <!-- Duration -->
                                    <TableCell>
                                        <div class="flex items-center gap-1 text-sm text-foreground">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            {{ audio.formatted_duration }}
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(audio.is_active)" class="bg-background border-border">
                                            {{ audio.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Analytics -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 text-sm text-foreground">
                                                <Users class="h-4 w-4 text-muted-foreground" />
                                                <span>{{ audio.total_listeners }} listeners</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm">
                                                <TrendingUp class="h-4 w-4 text-muted-foreground" />
                                                <span :class="getCompletionColor(audio.avg_completion)">
                                                    {{ Math.round(audio.avg_completion) }}% completion
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Created -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ new Date(audio.created_at).toLocaleDateString() }}
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
                                                    <Link :href="`/admin/audio/${audio.id}`">
                                                        <Eye class="h-4 w-4 mr-2" />
                                                        View Analytics
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem asChild class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                                    <Link :href="`/audio/${audio.id}`" target="_blank">
                                                        <Play class="h-4 w-4 mr-2" />
                                                        Preview
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem asChild class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                                    <Link :href="`/admin/audio/${audio.id}/edit`">
                                                        <Edit class="h-4 w-4 mr-2" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator class="bg-border" />

                                                <DropdownMenuItem @click="toggleActive(audio)" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                                    <component :is="audio.is_active ? ToggleRight : ToggleLeft" class="h-4 w-4 mr-2" />
                                                    {{ audio.is_active ? 'Deactivate' : 'Activate' }}
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator class="bg-border" />

                                                <DropdownMenuItem @click="deleteAudio(audio)" class="text-destructive focus:text-destructive cursor-pointer">
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
