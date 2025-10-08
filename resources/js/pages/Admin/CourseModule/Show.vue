<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'

// Icons
import {
    ArrowLeft,
    Edit,
    Plus,
    Video,
    FileText,
    Clock,
    FolderOpen,
    Hash,
    Settings,
    Trash2,
    Play,
    File,
    CheckCircle,
    XCircle,
    AlertTriangle,
    BookOpen,
    BarChart3,
    Users,
    Target
} from 'lucide-vue-next'

// Types
interface Content {
    id: number
    title: string
    content_type: 'video' | 'pdf'
    order_number: number
    is_active: boolean
    is_required: boolean
    duration?: number
    formatted_duration?: string
    video?: {
        name: string
    }
    tasks_count: number
}

interface Module {
    id: number
    name: string
    description?: string
    order_number: number
    is_active: boolean
    is_required: boolean
    estimated_duration?: number
    content_count: number
    video_count: number
    pdf_count: number
    content: Content[]
}

interface Course {
    id: number
    name: string
    description?: string
}

// Props
const props = defineProps<{
    course: Course
    module: Module
}>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Courses', href: '/admin/course-online' },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: 'Modules', href: `/admin/course-modules/${props.course.id}` },
    { title: props.module.name, href: '#' }
]

// State
const showAddContentModal = ref(false)
const showDeleteModal = ref(false)
const contentToDelete = ref<Content | null>(null)

// Computed
const totalDurationHours = computed(() => {
    if (!props.module.estimated_duration) return '0h'
    const hours = Math.floor(props.module.estimated_duration / 60)
    const minutes = props.module.estimated_duration % 60
    return hours > 0
        ? (minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`)
        : `${minutes}m`
})

const activeContentCount = computed(() => {
    return props.module.content.filter(content => content.is_active).length
})

const requiredContentCount = computed(() => {
    return props.module.content.filter(content => content.is_required).length
})

// Methods
const confirmDeleteContent = (content: Content) => {
    contentToDelete.value = content
    showDeleteModal.value = true
}

const deleteContent = () => {
    if (contentToDelete.value) {
        router.delete(`/admin/course-online/${props.course.id}/modules/${props.module.id}/content/${contentToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                contentToDelete.value = null
            },
            onError: () => {
                console.error('Failed to delete content')
            }
        })
    }
}

const cancelDelete = () => {
    showDeleteModal.value = false
    contentToDelete.value = null
}

const getContentTypeIcon = (type: string) => {
    return type === 'video' ? Video : FileText
}

const getContentTypeColor = (type: string) => {
    return type === 'video'
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400'
}
</script>

<template>
    <Head :title="`${module.name} - Module Details`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <div class="space-y-1">
                <div class="flex items-center gap-3 mb-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link :href="route('admin.course-modules.index', course.id)">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Modules
                        </Link>
                    </Button>
                </div>

                <div class="flex items-center gap-4 mb-3">
                    <Badge variant="outline" class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold">
                        {{ module.order_number }}
                    </Badge>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ module.name }}</h1>
                        <p class="text-muted-foreground">{{ course.name }}</p>
                    </div>
                </div>

                <p v-if="module.description" class="text-muted-foreground max-w-3xl">
                    {{ module.description }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button variant="outline" asChild>
                    <Link :href="route('admin.course-modules.edit', [course.id, module.id])">
                        <Edit class="h-4 w-4 mr-2" />
                        Edit Module
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Content</p>
                            <p class="text-3xl font-bold">{{ module.content_count }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                            <FolderOpen class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Videos</p>
                            <p class="text-3xl font-bold">{{ module.video_count }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                            <Video class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">PDFs</p>
                            <p class="text-3xl font-bold">{{ module.pdf_count }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center">
                            <FileText class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Duration</p>
                            <p class="text-3xl font-bold">{{ totalDurationHours }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                            <Clock class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Content List -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <BookOpen class="h-5 w-5" />
                                    Module Content
                                </CardTitle>
                                <CardDescription>
                                    {{ module.content.length }} content items in this module
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent v-if="module.content.length > 0" class="p-0">
                        <div class="divide-y">
                            <div
                                v-for="(content, index) in module.content"
                                :key="content.id"
                                class="p-6 hover:bg-muted/50 transition-colors group"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <!-- Order Number -->
                                        <Badge variant="outline" class="w-8 h-8 rounded-full flex items-center justify-center font-medium">
                                            {{ content.order_number }}
                                        </Badge>

                                        <!-- Content Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                                 :class="content.content_type === 'video'
                                                     ? 'bg-green-100 dark:bg-green-900/20'
                                                     : 'bg-red-100 dark:bg-red-900/20'">
                                                <component
                                                    :is="getContentTypeIcon(content.content_type)"
                                                    class="h-5 w-5"
                                                    :class="getContentTypeColor(content.content_type)"
                                                />
                                            </div>
                                        </div>

                                        <!-- Content Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-foreground truncate">
                                                {{ content.title }}
                                            </h4>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
                                                <span class="uppercase font-medium">
                                                    {{ content.content_type }}
                                                </span>
                                                <span v-if="content.formatted_duration">
                                                    {{ content.formatted_duration }}
                                                </span>
                                                <span v-if="content.video?.name" class="truncate">
                                                    {{ content.video.name }}
                                                </span>
                                                <span v-if="content.tasks_count > 0" class="text-blue-600 dark:text-blue-400">
                                                    {{ content.tasks_count }} tasks
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status & Actions -->
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <Badge v-if="content.is_required" variant="destructive" class="text-xs">
                                                Required
                                            </Badge>
                                            <Badge
                                                :variant="content.is_active ? 'default' : 'secondary'"
                                                class="text-xs"
                                            >
                                                {{ content.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>

                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                                                        @click="confirmDeleteContent(content)"
                                                    >
                                                        <Trash2 class="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Remove Content</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>

                    <!-- Empty State -->
                    <CardContent v-else class="py-16">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                                <FolderOpen class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <CardTitle class="mb-2">No content yet</CardTitle>
                            <CardDescription class="mb-6 max-w-sm mx-auto">
                                Add videos or PDFs to make this module interactive and engaging for students.
                            </CardDescription>
                            <Button @click="showAddContentModal = true">
                                <Plus class="h-4 w-4 mr-2" />
                                Add First Content
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Module Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Module Settings
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Order Number</span>
                                <Badge variant="outline">{{ module.order_number }}</Badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Duration</span>
                                <span class="text-sm font-medium">
                                    {{ module.estimated_duration || 'Not set' }}
                                    <span v-if="module.estimated_duration" class="text-muted-foreground">minutes</span>
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Required</span>
                                <Badge :variant="module.is_required ? 'destructive' : 'secondary'">
                                    {{ module.is_required ? 'Yes' : 'No' }}
                                </Badge>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Status</span>
                                <Badge :variant="module.is_active ? 'default' : 'secondary'">
                                    {{ module.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Content Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Content Summary
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Active Content</span>
                                <span class="text-sm font-medium">{{ activeContentCount }} / {{ module.content.length }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Required Content</span>
                                <span class="text-sm font-medium">{{ requiredContentCount }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Video Content</span>
                                <span class="text-sm font-medium">{{ module.video_count }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">PDF Content</span>
                                <span class="text-sm font-medium">{{ module.pdf_count }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>

                        <Button variant="ghost" class="w-full justify-start" asChild>
                            <Link :href="route('admin.course-modules.edit', [course.id, module.id])">
                                <Edit class="h-4 w-4 mr-3" />
                                Edit Module
                            </Link>
                        </Button>
                </Card>
            </div>
        </div>

        <!-- Add Content Modal -->
        <Dialog :open="showAddContentModal" @update:open="showAddContentModal = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Content to Module</DialogTitle>
                    <DialogDescription>
                        Choose the type of content you want to add to "{{ module.name }}"
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-2 gap-4 py-4">
                    <Button asChild class="h-20 flex-col gap-2 bg-green-600 hover:bg-green-700">
                        <Link :href="`/admin/course-online/${course.id}/modules/${module.id}/content/create?type=video`">
                            <Video class="h-6 w-6" />
                            <span>Add Video</span>
                        </Link>
                    </Button>

                    <Button asChild class="h-20 flex-col gap-2 bg-red-600 hover:bg-red-700" variant="destructive">
                        <Link :href="`/admin/course-online/${course.id}/modules/${module.id}/content/create?type=pdf`">
                            <FileText class="h-6 w-6" />
                            <span>Upload PDF</span>
                        </Link>
                    </Button>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showAddContentModal = false">
                        Cancel
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Content Modal -->
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-destructive" />
                        Remove Content
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to remove "<strong>{{ contentToDelete?.title }}</strong>"?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDelete">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteContent">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Remove Content
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
