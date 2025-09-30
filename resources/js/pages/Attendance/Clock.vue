<!-- Attendance Clock Page -->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AttendanceLayout from '@/layouts/AttendanceLayout.vue'
import ClockOutModal from '@/components/attendance/ClockOutModal.vue'
import ClockInModal from '@/components/attendance/ClockInModal.vue'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    Clock,
    Play,
    Square,
    Activity,
    BookOpen,
    Star,
    ChevronLeft,
    ChevronRight,
    Loader2,
    Calendar,
    Timer
} from 'lucide-vue-next'

const props = defineProps({
    clockings: {
        type: Object,
        required: true,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            prev_page_url: null,
            next_page_url: null
        })
    },
    activeSession: {
        type: Object,
        default: null
    },
    userCourses: {
        type: Array,
        default: () => []
    }
})

// State variables
const isLoading = ref(false)
const isSubmitting = ref(false)
const showClockOutModal = ref(false)
const showClockInModal = ref(false)
const clockOutForm = ref({
    rating: 3,
    comment: ''
})
const currentTime = ref('')
const currentDate = ref('')
let clockTimer = null

// Computed properties
const activeSession = computed(() => {
    return props.activeSession !== null
})

const activeSessionData = computed(() => {
    return props.activeSession || {}
})

// Methods
function updateClock() {
    const now = new Date()

    currentTime.value = now.toLocaleTimeString('en-US', {
        hour12: true,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })

    currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

function formatDate(dateString) {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleString()
}

function formatDateShort(dateString) {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleString('en-US', {
        hour12: false,
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

function formatDuration(minutes) {
    if (!minutes || minutes < 0) return '0 minutes'

    const hours = Math.floor(minutes / 60)
    const mins = Math.floor(minutes % 60)

    if (hours > 0) {
        return `${hours}h ${mins}m`
    }
    return `${mins}m`
}

function openClockInDialog() {
    showClockInModal.value = true
}

async function submitClockIn(formData) {
    try {
        isSubmitting.value = true
        router.post('/clock-in', {
            course_id: formData.course_id
        }, {
            onSuccess: () => {
                console.log('Clock in successful')
                showClockInModal.value = false
            },
            onError: (errors) => {
                console.error('Clock in failed:', errors)
                alert('Failed to clock in. Please try again.')
            },
            onFinish: () => {
                isSubmitting.value = false
            }
        })
    } catch (error) {
        console.error('Clock in exception:', error)
        isSubmitting.value = false
    }
}

function openClockOutDialog() {
    showClockOutModal.value = true
    clockOutForm.value = {
        rating: null,
        comment: ''
    }
}

async function submitClockOut(formData) {
    if (!formData.rating) {
        alert('Please provide a rating')
        return
    }

    try {
        isSubmitting.value = true
        router.post('/clock-out', {
            rating: formData.rating,
            comment: formData.comment
        }, {
            onSuccess: () => {
                console.log('Clock out successful')
                showClockOutModal.value = false
            },
            onError: (errors) => {
                console.error('Clock out failed:', errors)
                alert('Failed to clock out. Please try again.')
            },
            onFinish: () => {
                isSubmitting.value = false
            }
        })
    } catch (error) {
        console.error('Clock out exception:', error)
        isSubmitting.value = false
    }
}

function formatTime(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

// Get status badge variant
const getStatusVariant = (hasClockOut) => {
    return hasClockOut ? 'default' : 'secondary'
}

// Lifecycle hooks
onMounted(() => {
    updateClock()
    clockTimer = setInterval(updateClock, 1000)
})

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer)
    }
})
</script>

<template>
    <AttendanceLayout title="Attendance Clock">
        <template #actions>
            <Button asChild variant="outline">
                <Link href="/attendance">
                    View Records
                </Link>
            </Button>
        </template>

        <div class="flex h-full flex-1 flex-col gap-6 p-0 md:p-4">
            <!-- Top Row - Clock and Status -->
            <div class="grid auto-rows-min gap-6 md:grid-cols-2 max-w-5xl mx-auto w-full">
                <!-- Digital Clock Card -->
                <Card class="transition-all duration-300 hover:shadow-md">
                    <CardContent class="flex flex-col items-center justify-center p-6">
                        <div class="text-5xl md:text-6xl font-mono font-bold transition-all duration-300 transform hover:scale-105 mb-4">
                            {{ currentTime }}
                        </div>
                        <CardDescription class="text-center text-base">
                            {{ currentDate }}
                        </CardDescription>
                    </CardContent>
                </Card>

                <!-- Status Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-center flex items-center justify-center gap-2">
                            <Activity class="h-5 w-5" />
                            Current Status
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-center">
                            <div :class="[
                'h-3 w-3 rounded-full mr-2',
                activeSession ? 'bg-green-500 animate-pulse' : 'bg-muted-foreground'
              ]"></div>
                            <Badge :variant="activeSession ? 'default' : 'secondary'" class="font-medium">
                                {{ activeSession ? 'Clocked In' : 'Clocked Out' }}
                            </Badge>
                        </div>

                        <div v-if="activeSession" class="space-y-2 text-center">
                            <div class="text-sm text-muted-foreground">
                                <Clock class="h-4 w-4 inline mr-1" />
                                Clocked in at: {{ formatDate(activeSessionData.clock_in) }}
                            </div>

                            <div v-if="activeSession && activeSessionData.course" class="text-sm text-muted-foreground">
                                <BookOpen class="h-4 w-4 inline mr-1" />
                                Course: {{ activeSessionData.course.name }}
                            </div>

                            <div class="text-sm text-muted-foreground">
                                <Timer class="h-4 w-4 inline mr-1" />
                                Duration: {{ formatDuration(activeSessionData.current_duration || 0) }}
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-3">
                            <Button
                                v-if="!activeSession"
                                @click="openClockInDialog"
                                class="w-full"
                                size="lg"
                                :disabled="isLoading"
                            >
                                <Loader2 v-if="isLoading" class="mr-2 h-5 w-5 animate-spin" />
                                <Play v-else class="mr-2 h-5 w-5" />
                                Clock In
                            </Button>
                            <Button
                                v-else
                                @click="openClockOutDialog"
                                variant="destructive"
                                class="w-full"
                                size="lg"
                                :disabled="isLoading"
                            >
                                <Square class="mr-2 h-5 w-5" />
                                Clock Out
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity Section -->
            <Card v-if="clockings.data && clockings.data.length > 0" class="max-w-5xl mx-auto w-full">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5" />
                        Recent Activity
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        <Card
                            v-for="(record, i) in clockings.data"
                            :key="i"
                            class="hover:shadow-md transition-shadow"
                        >
                            <CardContent class="p-4 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div class="text-sm font-medium">
                                        {{ formatDateShort(record.clock_in) }}
                                    </div>
                                    <Badge :variant="getStatusVariant(record.clock_out)" class="text-xs">
                                        {{ record.clock_out ? 'Completed' : 'In Progress' }}
                                    </Badge>
                                </div>

                                <!-- Course name if available -->
                                <div v-if="record.course_name" class="text-sm text-muted-foreground">
                                    <div class="flex items-center">
                                        <BookOpen class="h-4 w-4 mr-1 shrink-0" />
                                        <span class="truncate">{{ record.course_name }}</span>
                                    </div>
                                </div>

                                <!-- Duration information -->
                                <div class="text-sm text-muted-foreground">
                                    <div class="flex items-center">
                                        <Clock class="h-4 w-4 mr-1 shrink-0" />
                                        <span>
                      <span v-if="record.clock_out">
                        {{ formatDuration(record.duration_in_minutes) }}
                      </span>
                      <span v-else>
                        {{ formatDuration(record.current_duration || 0) }} (ongoing)
                      </span>
                    </span>
                                    </div>
                                </div>

                                <!-- Rating if available -->
                                <div v-if="record.rating" class="flex items-center gap-1">
                                    <Star
                                        v-for="star in 5"
                                        :key="star"
                                        :class="[
                      'h-4 w-4',
                      star <= record.rating ? 'text-yellow-400 fill-current' : 'text-muted-foreground'
                    ]"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Pagination -->
                    <Separator class="my-6" />
                    <div class="flex justify-center items-center gap-4">
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                            :disabled="!clockings.prev_page_url"
                        >
                            <Link
                                v-if="clockings.prev_page_url"
                                :href="clockings.prev_page_url"
                            >
                                <ChevronLeft class="h-4 w-4 mr-1" />
                                Previous
                            </Link>
                            <span v-else>
                <ChevronLeft class="h-4 w-4 mr-1" />
                Previous
              </span>
                        </Button>

                        <span class="text-sm text-muted-foreground">
              Page {{ clockings.current_page }} of {{ clockings.last_page }}
            </span>

                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                            :disabled="!clockings.next_page_url"
                        >
                            <Link
                                v-if="clockings.next_page_url"
                                :href="clockings.next_page_url"
                            >
                                Next
                                <ChevronRight class="h-4 w-4 ml-1" />
                            </Link>
                            <span v-else>
                Next
                <ChevronRight class="h-4 w-4 ml-1" />
              </span>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Clock In Modal -->
        <ClockInModal
            :show="showClockInModal"
            :courses="userCourses"
            :is-submitting="isSubmitting"
            @close="showClockInModal = false"
            @submit="submitClockIn"
        />

        <!-- Clock Out Modal -->
        <ClockOutModal
            :show="showClockOutModal"
            :initial-form="clockOutForm"
            :is-submitting="isSubmitting"
            @close="showClockOutModal = false"
            @submit="submitClockOut"
        />
    </AttendanceLayout>
</template>
