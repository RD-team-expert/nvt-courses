<!-- Attendance Records Page -->
<script setup>
import { ref, computed } from 'vue'
import ClockInModal from '@/components/attendance/ClockInModal.vue'
import ClockOutModal from '@/components/attendance/ClockOutModal.vue'
import { useForm } from '@inertiajs/vue3'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

// Icons
import {
    Clock,
    Play,
    Square,
    Eye,
    Star,
    Calendar,
    Timer,
    Activity
} from 'lucide-vue-next'

const props = defineProps({
    attendanceRecords: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: null
    }
})

// Modal state
const showClockInModal = ref(false)
const showClockOutModal = ref(false)
const selectedRecord = ref(null)
const clockOutForm = ref({
    rating: 3,
    comment: ''
})

// Form submission state
const isSubmittingClockIn = ref(false)
const isSubmittingClockOut = ref(false)

// Format functions
function formatDate(dateString) {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleDateString()
}

function formatTime(timeString) {
    if (!timeString) return '—'
    const date = new Date(timeString)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function formatDuration(minutes) {
    if (!minutes) return '—'
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return `${hours}h ${mins}m`
}

function formatStatus(status) {
    const statusMap = {
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'missed': 'Missed'
    }
    return statusMap[status] || status
}

// Get status badge variant
function getStatusVariant(status) {
    if (status === 'completed') return 'default'
    if (status === 'in_progress') return 'secondary'
    if (status === 'missed') return 'destructive'
    return 'outline'
}

// Modal handlers
function openClockInModal() {
    showClockInModal.value = true
}

function openClockOutModal(record) {
    selectedRecord.value = record
    clockOutForm.value = {
        rating: 3,
        comment: ''
    }
    showClockOutModal.value = true
}

function viewDetails(record) {
    console.log('View details for record:', record)
}

// Form submission handlers
function handleClockIn(data) {
    isSubmittingClockIn.value = true

    setTimeout(() => {
        console.log('Clock in data:', data)
        isSubmittingClockIn.value = false
        showClockInModal.value = false
    }, 1000)
}

function handleClockOut(data) {
    if (!selectedRecord.value) return

    isSubmittingClockOut.value = true

    setTimeout(() => {
        console.log('Clock out data for record:', selectedRecord.value.id, data)
        isSubmittingClockOut.value = false
        showClockOutModal.value = false
        selectedRecord.value = null
    }, 1000)
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Attendance Records</h1>
                <p class="text-muted-foreground mt-1">Track and manage your attendance history</p>
            </div>
            <Button @click="openClockInModal">
                <Play class="h-4 w-4 mr-2" />
                Clock In
            </Button>
        </div>

        <!-- Records Table -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Activity class="h-5 w-5" />
                    Attendance History
                </CardTitle>
                <CardDescription>
                    View your complete attendance records and manage active sessions
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[120px]">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-4 w-4" />
                                        Date
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center gap-1">
                                        <Clock class="h-4 w-4" />
                                        Clock In
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center gap-1">
                                        <Clock class="h-4 w-4" />
                                        Clock Out
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center gap-1">
                                        <Timer class="h-4 w-4" />
                                        Duration
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center gap-1">
                                        <Star class="h-4 w-4" />
                                        Rating
                                    </div>
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="record in attendanceRecords" :key="record.id" class="hover:bg-accent/50">
                                <TableCell class="font-medium">
                                    {{ formatDate(record.date) }}
                                </TableCell>
                                <TableCell>
                                    {{ formatTime(record.clock_in_time) }}
                                </TableCell>
                                <TableCell>
                                    {{ record.clock_out_time ? formatTime(record.clock_out_time) : '—' }}
                                </TableCell>
                                <TableCell>
                                    {{ record.duration ? formatDuration(record.duration) : '—' }}
                                </TableCell>
                                <TableCell>
                                    <div v-if="record.rating" class="flex items-center gap-1">
                                        <Star
                                            v-for="i in 5"
                                            :key="i"
                                            :class="[
                        'h-4 w-4',
                        i <= record.rating ? 'text-yellow-400 fill-current' : 'text-muted-foreground'
                      ]"
                                        />
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusVariant(record.status)">
                                        {{ formatStatus(record.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            v-if="record.status === 'in_progress'"
                                            @click="openClockOutModal(record)"
                                            variant="destructive"
                                            size="sm"
                                        >
                                            <Square class="h-4 w-4 mr-1" />
                                            Clock Out
                                        </Button>
                                        <Button
                                            v-if="record.status === 'completed'"
                                            @click="viewDetails(record)"
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Eye class="h-4 w-4 mr-1" />
                                            View
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="attendanceRecords.length === 0">
                                <TableCell colspan="7" class="text-center py-12">
                                    <div class="flex flex-col items-center gap-2">
                                        <Activity class="h-12 w-12 text-muted-foreground" />
                                        <div>
                                            <h3 class="font-medium">No attendance records found</h3>
                                            <p class="text-sm text-muted-foreground">Start by clocking in to create your first record</p>
                                        </div>
                                        <Button @click="openClockInModal" class="mt-2">
                                            <Play class="h-4 w-4 mr-2" />
                                            Clock In Now
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Pagination controls if needed -->
        <div v-if="pagination && pagination.total > pagination.per_page" class="flex justify-center">
            <!-- Pagination component here -->
            <Card class="p-4">
                <div class="text-sm text-muted-foreground text-center">
                    Showing {{ pagination.from }} - {{ pagination.to }} of {{ pagination.total }} records
                </div>
            </Card>
        </div>

        <!-- Clock In Modal -->
        <ClockInModal
            :show="showClockInModal"
            :is-submitting="isSubmittingClockIn"
            @close="showClockInModal = false"
            @submit="handleClockIn"
        />

        <!-- Clock Out Modal -->
        <ClockOutModal
            :show="showClockOutModal"
            :initial-form="clockOutForm"
            :is-submitting="isSubmittingClockOut"
            @close="showClockOutModal = false"
            @submit="handleClockOut"
        />
    </div>
</template>
