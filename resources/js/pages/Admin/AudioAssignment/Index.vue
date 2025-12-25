<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Plus, Volume2, Filter, X } from 'lucide-vue-next'

interface AudioAssignment {
    id: number
    audio: {
        id: number
        name: string
        formatted_duration: string
    }
    user: {
        id: number
        name: string
        email: string
    }
    assigned_by: {
        id: number
        name: string
    }
    status: 'assigned' | 'in_progress' | 'completed'
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    completed_at: string | null
}

interface PaginatedAssignments {
    data: AudioAssignment[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
}

const props = defineProps<{
    assignments: PaginatedAssignments
    filters: {
        status?: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audio Management', href: '/admin/audio' },
    { title: 'Audio Assignments', href: '#' }
]

// Filters
const selectedStatus = ref(props.filters.status || 'all')

// Apply filters
const applyFilters = () => {
    const params = {
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined
    }

    router.get('/admin/audio-assignments', params, { preserveState: true })
}

// Clear filters
const clearFilters = () => {
    selectedStatus.value = 'all'
    applyFilters()
}

// Format date
const formatDate = (dateString: string | null): string => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

// Get status variant
const getStatusVariant = (status: string) => {
    const variants = {
        'assigned': 'secondary',
        'in_progress': 'default',
        'completed': 'outline'
    }
    return variants[status] || 'secondary'
}

// Get status label
const getStatusLabel = (status: string) => {
    const labels = {
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    }
    return labels[status] || status
}

// Check if has active filters
const hasActiveFilters = computed(() => selectedStatus.value !== 'all')
</script>

<template>
    <Head title="Audio Assignments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <Volume2 class="h-7 w-7 text-blue-600" />
                        Audio Assignments
                    </h1>
                    <p class="text-muted-foreground">Manage audio content assignments to users</p>
                </div>

                <Button asChild>
                    <Link href="/admin/audio-assignments/create">
                        <Plus class="h-4 w-4 mr-2" />
                        Create Assignment
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="flex-1 space-y-2">
                            <label class="text-sm font-medium flex items-center gap-2">
                                <Filter class="h-4 w-4" />
                                Filter by Status
                            </label>
                            <Select v-model="selectedStatus" @update:modelValue="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="assigned">Assigned</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            variant="outline"
                            size="sm"
                        >
                            <X class="h-4 w-4 mr-2" />
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Results Info -->
            <div v-if="hasActiveFilters" class="text-sm text-muted-foreground">
                {{ assignments.total }} assignment{{ assignments.total !== 1 ? 's' : '' }} found
            </div>

            <!-- Assignments Table -->
            <Card class="overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/50">
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Audio
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                User
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Status
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Progress
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Assigned By
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Assigned Date
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Empty State -->
                        <TableRow v-if="assignments.data.length === 0">
                            <TableCell colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center gap-4">
                                    <Volume2 class="h-12 w-12 text-muted-foreground" />
                                    <div>
                                        <p class="font-medium text-foreground mb-1">No assignments found</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ hasActiveFilters
                                                ? 'Try adjusting your filters'
                                                : 'Create your first audio assignment to get started' }}
                                        </p>
                                    </div>
                                    <Button v-if="!hasActiveFilters" asChild>
                                        <Link href="/admin/audio-assignments/create">
                                            <Plus class="h-4 w-4 mr-2" />
                                            Create Assignment
                                        </Link>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Assignment Rows -->
                        <TableRow v-for="assignment in assignments.data" :key="assignment.id" class="hover:bg-muted/50">
                            <TableCell>
                                <div class="font-medium text-foreground">{{ assignment.audio.name }}</div>
                                <div class="text-sm text-muted-foreground">{{ assignment.audio.formatted_duration }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-foreground">{{ assignment.user.name }}</div>
                                <div class="text-muted-foreground text-sm">{{ assignment.user.email }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusVariant(assignment.status)">
                                    {{ getStatusLabel(assignment.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-muted rounded-full h-2 max-w-[100px]">
                                        <div
                                            class="bg-primary h-2 rounded-full transition-all"
                                            :style="{ width: `${assignment.progress_percentage}%` }"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-muted-foreground min-w-[3ch]">
                                        {{ Math.round(assignment.progress_percentage) }}%
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ assignment.assigned_by.name }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatDate(assignment.assigned_at) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="assignments.links && assignments.data.length > 0" class="border-t bg-background p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} assignments
                        </div>
                        <div class="flex space-x-1">
                            <Button
                                v-for="link in assignments.links"
                                :key="link.label"
                                :as="Link"
                                :href="link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                size="sm"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
