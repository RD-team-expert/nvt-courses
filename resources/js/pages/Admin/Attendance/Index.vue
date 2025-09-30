<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { onUnmounted, onErrorCaptured } from 'vue'

const props = defineProps({
    clockings: Object,
    users: Array,
    courses: Array,
    filters: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Attendance Management', href: route('admin.attendance.index') }
]

// Filter state - properly initialized
const filters = ref({
    user_id: props.filters?.user_id || '',
    date: props.filters?.date || '',
    course_id: props.filters?.course_id || ''
})

// Modal state - properly initialized
const showEditModal = ref(false)
const selectedRecord = ref(null)
const form = ref({
    user_id: '',
    course_id: null,
    clock_in: '',
    clock_out: '',
    rating: null,
    comment: ''
})
const errors = ref({})
const processing = ref(false)

// Open edit modal with safety checks
const openEditModal = (record) => {
    if (!record) return

    selectedRecord.value = record
    form.value = {
        user_id: record.user_id || '',
        course_id: record.course_id || null,
        clock_in: formatDateForInput(record.clock_in),
        clock_out: formatDateForInput(record.clock_out),
        rating: record.rating || null,
        comment: record.comment || ''
    }
    showEditModal.value = true
}

// Close modal with proper cleanup
const closeModal = () => {
    showEditModal.value = false
    selectedRecord.value = null
    errors.value = {}
    // Reset form to initial state
    form.value = {
        user_id: '',
        course_id: null,
        clock_in: '',
        clock_out: '',
        rating: null,
        comment: ''
    }
}

// Format date for input fields (YYYY-MM-DDTHH:MM)
const formatDateForInput = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)

    // Format to local timezone for datetime-local input
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')

    return `${year}-${month}-${day}T${hours}:${minutes}`
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Format duration in minutes to human-readable format
const formatHumanDuration = (minutes) => {
    if (!minutes || isNaN(minutes) || minutes < 0) return '—'

    // Round to nearest integer to avoid decimal values
    minutes = Math.round(minutes)

    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60

    if (hours > 0) {
        return `${hours} ${hours === 1 ? 'hour' : 'hours'}${remainingMinutes > 0 ? ` ${remainingMinutes} ${remainingMinutes === 1 ? 'minute' : 'minutes'}` : ''}`
    }
    return `${remainingMinutes} ${remainingMinutes === 1 ? 'minute' : 'minutes'}`
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    router.get(route('admin.attendance.index'), filters.value, {
        preserveState: true,
        replace: true
    })
}, 500)

// Watch for filter changes
watch(filters, () => {
    applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
    filters.value = {
        user_id: '',
        date: '',
        course_id: ''
    }
    applyFilters()
}

// Submit form with proper error handling
const submitForm = () => {
    processing.value = true

    router.put(route('admin.attendance.update', selectedRecord.value.id), form.value, {
        onSuccess: () => {
            closeModal()
            processing.value = false
        },
        onError: (err) => {
            errors.value = err || {}
            processing.value = false
        }
    })
}

// Clean up on unmount
onUnmounted(() => {
    if (selectedRecord.value) {
        selectedRecord.value = null
    }
    if (form.value) {
        form.value = {
            user_id: '',
            course_id: null,
            clock_in: '',
            clock_out: '',
            rating: null,
            comment: ''
        }
    }
})

// Catch errors during component lifecycle
onErrorCaptured((error) => {
    console.error('Component error:', error)
    return false
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <h1 class="text-2xl font-bold text-foreground mb-2 sm:mb-0">Attendance Records</h1>
        </div>

        <!-- Filters -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle class="text-lg font-medium">Filter Attendance Records</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6">
                    <div class="space-y-2">
                        <Label for="user_filter">User</Label>
                        <Select v-model="filters.user_id">
                            <SelectTrigger id="user_filter">
                                <SelectValue placeholder="All Users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Users</SelectItem>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="date_filter">Date</Label>
                        <Input
                            id="date_filter"
                            type="date"
                            v-model="filters.date"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="course_filter">Course</Label>
                        <Select v-model="filters.course_id">
                            <SelectTrigger id="course_filter">
                                <SelectValue placeholder="All Courses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Courses</SelectItem>
                                <SelectItem value="general">General Attendance</SelectItem>
                                <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                    {{ course.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end">
                        <Button @click="resetFilters" variant="secondary">
                            Reset Filters
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Attendance Records Table -->
        <Card>
            <!-- Mobile view for small screens -->
            <div class="block sm:hidden">
                <div v-if="clockings.data.length === 0" class="px-4 py-4 text-center text-muted-foreground">
                    No attendance records found
                </div>
                <div v-else v-for="(record, i) in clockings.data" :key="i" class="border-b p-4">
                    <div class="flex justify-between items-center mb-2">
                        <div class="font-medium text-foreground">{{ record.user?.name || 'Unknown User' }}</div>
                        <div class="text-sm text-muted-foreground">{{ record.user?.email }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-muted-foreground">Course:</span>
                            <div class="font-medium">{{ record.course_name || 'General Attendance' }}</div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Clock In:</span>
                            <div class="font-medium">{{ formatDate(record.clock_in) }}</div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Clock Out:</span>
                            <div class="font-medium">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Duration:</span>
                            <div class="font-medium">
                <span v-if="record.clock_out">
                  {{ formatHumanDuration(record.duration_in_minutes) }}
                </span>
                                <span v-else class="flex items-center">
                  {{ formatHumanDuration(record.current_duration || 0) }}
                  <Badge variant="secondary" class="ml-1">ongoing</Badge>
                </span>
                            </div>
                        </div>
                        <div v-if="record.rating">
                            <span class="text-muted-foreground">Rating:</span>
                            <div class="font-medium flex items-center">
                                {{ record.rating }}/5
                                <div class="ml-1 flex">
                                    <svg v-for="i in 5" :key="i" class="h-4 w-4" :class="i <= record.rating ? 'text-yellow-400' : 'text-muted'" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="record.comment" class="mt-2">
                        <span class="text-muted-foreground">Comment:</span>
                        <div class="font-medium">{{ record.comment }}</div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <Button @click="openEditModal(record)" variant="outline" size="sm">
                            Edit
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Desktop view for larger screens -->
            <div class="hidden sm:block overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>User</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Clock In</TableHead>
                            <TableHead>Clock Out</TableHead>
                            <TableHead>Duration</TableHead>
                            <TableHead class="hidden md:table-cell">Rating</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="clockings.data.length === 0">
                            <TableCell colspan="7" class="text-center text-muted-foreground">
                                No attendance records found
                            </TableCell>
                        </TableRow>
                        <TableRow v-else v-for="(record, i) in clockings.data" :key="i">
                            <TableCell>
                                <div class="flex flex-col">
                                    <div class="font-medium text-foreground">{{ record.user?.name || 'Unknown User' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ record.user?.email }}</div>
                                </div>
                            </TableCell>
                            <TableCell>{{ record.course_name || 'General Attendance' }}</TableCell>
                            <TableCell>{{ formatDate(record.clock_in) }}</TableCell>
                            <TableCell>{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</TableCell>
                            <TableCell>
                <span v-if="record.clock_out">
                  {{ formatHumanDuration(record.duration_in_minutes) }}
                </span>
                                <span v-else class="flex items-center">
                  {{ formatHumanDuration(record.current_duration || 0) }}
                  <Badge variant="secondary" class="ml-1">ongoing</Badge>
                </span>
                            </TableCell>
                            <TableCell class="hidden md:table-cell">
                                <div v-if="record.rating" class="flex items-center">
                                    <span>{{ record.rating }}/5</span>
                                    <div class="ml-1 flex">
                                        <svg v-for="i in 5" :key="i" class="h-4 w-4" :class="i <= record.rating ? 'text-yellow-400' : 'text-muted'" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </TableCell>
                            <TableCell>
                                <Button @click="openEditModal(record)" variant="ghost" size="sm">
                                    Edit
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="clockings.data && clockings.data.length > 0" class="border-t p-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Button
                            :as="Link"
                            :href="clockings.prev_page_url"
                            variant="outline"
                            size="sm"
                            :disabled="!clockings.prev_page_url"
                        >
                            Previous
                        </Button>
                        <Button
                            :as="Link"
                            :href="clockings.next_page_url"
                            variant="outline"
                            size="sm"
                            :disabled="!clockings.next_page_url"
                        >
                            Next
                        </Button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Showing <span class="font-medium">{{ clockings.from }}</span> to
                                <span class="font-medium">{{ clockings.to }}</span> of
                                <span class="font-medium">{{ clockings.total }}</span> results
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button
                                :as="Link"
                                :href="clockings.prev_page_url"
                                variant="outline"
                                size="sm"
                                :disabled="!clockings.prev_page_url"
                            >
                                <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Previous
                            </Button>
                            <span class="text-sm text-muted-foreground">
                Page {{ clockings.current_page }} of {{ clockings.last_page }}
              </span>
                            <Button
                                :as="Link"
                                :href="clockings.next_page_url"
                                variant="outline"
                                size="sm"
                                :disabled="!clockings.next_page_url"
                            >
                                Next
                                <svg class="h-4 w-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Edit Modal - Fixed with v-if for conditional rendering -->
        <Dialog v-if="showEditModal" v-model:open="showEditModal">
            <DialogContent class="sm:max-w-lg" @escape-key-down="closeModal">
                <DialogHeader>
                    <DialogTitle>Edit Attendance Record</DialogTitle>
                    <DialogDescription>
                        Update the attendance record details below.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <!-- User -->
                    <div class="space-y-2">
                        <Label for="user_id">User</Label>
                        <Select v-model="form.user_id" :disabled="processing">
                            <SelectTrigger id="user_id">
                                <SelectValue placeholder="Select user" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="errors.user_id" class="text-destructive text-sm">{{ errors.user_id }}</div>
                    </div>

                    <!-- Course -->
                    <div class="space-y-2">
                        <Label for="course_id">Course</Label>
                        <Select v-model="form.course_id" :disabled="processing">
                            <SelectTrigger id="course_id">
                                <SelectValue placeholder="Select course" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">General Attendance</SelectItem>
                                <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                    {{ course.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="errors.course_id" class="text-destructive text-sm">{{ errors.course_id }}</div>
                    </div>

                    <!-- Clock In -->
                    <div class="space-y-2">
                        <Label for="clock_in">Clock In</Label>
                        <Input
                            type="datetime-local"
                            id="clock_in"
                            v-model="form.clock_in"
                            :disabled="processing"
                        />
                        <div v-if="errors.clock_in" class="text-destructive text-sm">{{ errors.clock_in }}</div>
                    </div>

                    <!-- Clock Out -->
                    <div class="space-y-2">
                        <Label for="clock_out">Clock Out</Label>
                        <Input
                            type="datetime-local"
                            id="clock_out"
                            v-model="form.clock_out"
                            :disabled="processing"
                        />
                        <div v-if="errors.clock_out" class="text-destructive text-sm">{{ errors.clock_out }}</div>
                    </div>

                    <!-- Rating -->
                    <div class="space-y-2">
                        <Label for="rating">Rating</Label>
                        <Select v-model="form.rating" :disabled="processing">
                            <SelectTrigger id="rating">
                                <SelectValue placeholder="No Rating" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">No Rating</SelectItem>
                                <SelectItem v-for="i in 5" :key="i" :value="i.toString()">{{ i }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="errors.rating" class="text-destructive text-sm">{{ errors.rating }}</div>
                    </div>

                    <!-- Comment -->
                    <div class="space-y-2">
                        <Label for="comment">Comment</Label>
                        <Textarea
                            id="comment"
                            v-model="form.comment"
                            :disabled="processing"
                            rows="3"
                        />
                        <div v-if="errors.comment" class="text-destructive text-sm">{{ errors.comment }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" @click="closeModal" variant="secondary" :disabled="processing">
                        Cancel
                    </Button>
                    <Button type="button" @click="submitForm" :disabled="processing">
                        <span v-if="processing">Saving...</span>
                        <span v-else>Save Changes</span>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
