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
    Plus,
    GripVertical,
    Eye,
    Edit,
    Trash2,
    Video,
    FileText,
    Clock,
    AlertTriangle,
    FolderOpen,
    BookOpen,
    Play
} from 'lucide-vue-next'

// Types
interface Module {
    id: number
    name: string
    description?: string
    order_number: number
    is_active: boolean
    is_required: boolean
    video_count?: number
    pdf_count?: number
    estimated_duration?: number
}

interface Course {
    id: number
    name: string
    description?: string
}

// Props
const props = defineProps<{
    course: Course
    modules: Module[]
}>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Courses', href: '/admin/course-online' },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: 'Modules', href: '#' }
]

// State
const showDeleteModal = ref(false)
const moduleToDelete = ref<Module | null>(null)
const draggedModule = ref<Module | null>(null)
const draggedIndex = ref<number | null>(null)
const isDragging = ref(false)

// Computed
const totalContent = computed(() => {
    return props.modules.reduce((total, module) => {
        return total + (module.video_count || 0) + (module.pdf_count || 0)
    }, 0)
})

const totalDuration = computed(() => {
    return props.modules.reduce((total, module) => {
        return total + (module.estimated_duration || 0)
    }, 0)
})

const activeModulesCount = computed(() => {
    return props.modules.filter(module => module.is_active).length
})

// Drag and Drop functionality
const handleDragStart = (event: DragEvent, module: Module, index: number) => {
    draggedModule.value = module
    draggedIndex.value = index
    isDragging.value = true

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/html', (event.target as HTMLElement).outerHTML)
    }

    // Add visual feedback
    setTimeout(() => {
        if (event.target) {
            (event.target as HTMLElement).style.opacity = '0.5'
        }
    }, 0)
}

const handleDragOver = (event: DragEvent) => {
    event.preventDefault()
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move'
    }
}

const handleDrop = (event: DragEvent, targetIndex: number) => {
    event.preventDefault()

    if (draggedIndex.value !== null && draggedIndex.value !== targetIndex) {
        const reorderedModules = [...props.modules]
        const draggedItem = reorderedModules.splice(draggedIndex.value, 1)[0]
        reorderedModules.splice(targetIndex, 0, draggedItem)

        // Update order numbers
        const moduleUpdates = reorderedModules.map((module, index) => ({
            id: module.id,
            order_number: index + 1
        }))

        // Send update to server
        router.patch(route('admin.course-modules.update-order', props.course.id), {
            modules: moduleUpdates
        }, {
            onSuccess: () => {
                router.reload()
            },
            onError: () => {
                console.error('Failed to update module order')
            }
        })
    }
}

const handleDragEnd = (event: DragEvent) => {
    if (event.target) {
        (event.target as HTMLElement).style.opacity = '1'
    }
    draggedModule.value = null
    draggedIndex.value = null
    isDragging.value = false
}

// Delete functionality
const confirmDeleteModule = (module: Module) => {
    moduleToDelete.value = module
    showDeleteModal.value = true
}

const deleteModule = () => {
    if (moduleToDelete.value) {
        router.delete(route('admin.course-modules.destroy', [props.course.id, moduleToDelete.value.id]), {
            onSuccess: () => {
                showDeleteModal.value = false
                moduleToDelete.value = null
            },
            onError: () => {
                console.error('Failed to delete module')
            }
        })
    }
}

const cancelDelete = () => {
    showDeleteModal.value = false
    moduleToDelete.value = null
}
</script>

<template>
    <Head :title="`Modules - ${course.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <div class="space-y-1">
                <div class="flex items-center gap-3 mb-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link :href="route('admin.course-online.index')">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Courses
                        </Link>
                    </Button>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">Course Modules</h1>
                <p class="text-muted-foreground">{{ course.name }}</p>

                <!-- Quick Stats -->
                <div class="flex items-center gap-6 mt-4 text-sm text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <FolderOpen class="h-4 w-4" />
                        <span>{{ modules.length }} modules</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <BookOpen class="h-4 w-4" />
                        <span>{{ totalContent }} content items</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Clock class="h-4 w-4" />
                        <span>{{ Math.ceil(totalDuration / 60) }}h total duration</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Play class="h-4 w-4" />
                        <span>{{ activeModulesCount }} active</span>
                    </div>
                </div>
            </div>

            <Button asChild>
                <Link :href="route('admin.course-modules.create', course.id)">
                    <Plus class="h-4 w-4 mr-2" />
                    Add Module
                </Link>
            </Button>
        </div>

        <!-- Modules List -->
        <div v-if="modules.length > 0" class="space-y-4">
            <!-- Drag and Drop Help -->
            <Alert>
                <GripVertical class="h-4 w-4" />
                <AlertDescription>
                    Drag and drop modules to reorder them. The order will be automatically saved.
                </AlertDescription>
            </Alert>

            <!-- Modules Container -->
            <div class="space-y-3">
                <Card
                    v-for="(module, index) in modules"
                    :key="module.id"
                    :data-module-id="module.id"
                    class="group transition-all duration-200 hover:shadow-md cursor-move"
                    :class="{ 'opacity-50': isDragging && draggedIndex === index }"
                    :draggable="true"
                    @dragstart="handleDragStart($event, module, index)"
                    @dragover="handleDragOver"
                    @drop="handleDrop($event, index)"
                    @dragend="handleDragEnd"
                >
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <!-- Drag Handle -->
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <div class="flex-shrink-0 p-1 hover:bg-muted rounded cursor-grab active:cursor-grabbing">
                                                <GripVertical class="h-5 w-5 text-muted-foreground" />
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Drag to reorder</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <!-- Module Order Badge -->
                                <Badge variant="outline" class="w-8 h-8 rounded-full flex items-center justify-center font-medium">
                                    {{ module.order_number }}
                                </Badge>

                                <!-- Module Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold truncate">
                                            {{ module.name }}
                                        </h3>
                                        <div class="flex items-center gap-2">
                                            <Badge v-if="module.is_required" variant="destructive" class="text-xs">
                                                Required
                                            </Badge>
                                            <Badge
                                                :variant="module.is_active ? 'default' : 'secondary'"
                                                class="text-xs"
                                            >
                                                {{ module.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <p v-if="module.description" class="text-sm text-muted-foreground mb-3 line-clamp-2">
                                        {{ module.description }}
                                    </p>

                                    <!-- Module Stats -->
                                    <div class="flex items-center gap-6 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Video class="h-4 w-4 text-blue-500" />
                                            <span>{{ module.video_count || 0 }} videos</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <FileText class="h-4 w-4 text-green-500" />
                                            <span>{{ module.pdf_count || 0 }} PDFs</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-orange-500" />
                                            <span>{{ module.estimated_duration || 0 }}min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link :href="route('admin.course-modules.show', [course.id, module.id])">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>View Module</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link :href="route('admin.course-modules.edit', [course.id, module.id])">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Edit Module</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button variant="outline" size="sm" @click="confirmDeleteModule(module)">
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Delete Module</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Empty State -->
        <Card v-else class="border-dashed">
            <CardContent class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mb-4">
                    <FolderOpen class="h-8 w-8 text-muted-foreground" />
                </div>
                <CardTitle class="mb-2">No modules yet</CardTitle>
                <CardDescription class="mb-6 max-w-sm">
                    Get started by creating your first course module. Modules help organize your course content into logical sections.
                </CardDescription>
                <Button asChild>
                    <Link :href="route('admin.course-modules.create', course.id)">
                        <Plus class="h-4 w-4 mr-2" />
                        Create First Module
                    </Link>
                </Button>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-destructive" />
                        Delete Module
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "<strong>{{ moduleToDelete?.name }}</strong>"?
                        This action cannot be undone and will remove all content within this module.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDelete">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteModule">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Delete Module
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
