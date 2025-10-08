<!-- Attendance Records Page -->
<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AttendanceLayout from '@/layouts/AttendanceLayout.vue'
import { useAttendance } from '@/composables/useAttendance'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    Clock,
    Calendar,
    Timer,
    Star,
    MessageSquare,
    BookOpen,
    ChevronLeft,
    ChevronRight,
    Activity,
    FileText
} from 'lucide-vue-next'

interface ClockingData {
    clock_in: string;
    clock_out: string | null;
    duration_in_minutes: number | null;
    current_duration?: number;
    rating: number | null;
    comment: string | null;
    course_name?: string | null;
}

interface ClockingsPagination {
    data: ClockingData[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps({
    clockings: {
        type: Object as () => ClockingsPagination,
        required: true,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            prev_page_url: null,
            next_page_url: null
        })
    }
})

const { isLoading, formatDate, formatDuration } = useAttendance(props.clockings.data)

// Custom function to format duration in a more human-readable way
function formatHumanDuration(minutes: number | null | undefined): string {
    if (!minutes || isNaN(minutes) || minutes < 0) return '0 minutes';

    minutes = Math.round(minutes);

    const hours: number = Math.floor(minutes / 60);
    const mins: number = minutes % 60;

    if (hours > 0) {
        return `${hours} ${hours === 1 ? 'hour' : 'hours'}${mins > 0 ? ` ${mins} ${mins === 1 ? 'minute' : 'minutes'}` : ''}`;
    }
    return `${mins} ${mins === 1 ? 'minute' : 'minutes'}`;
}
</script>

<template>
    <AttendanceLayout title="My Attendance Records" >
        <template #actions>
            <Button asChild>
                <Link href="/attendance/clock">
                    <Clock class="h-4 w-4 mr-2" />
                    Clock In/Out
                </Link>
            </Button>
        </template>

        <div class="space-y-6">
            <!-- Mobile View (Card Layout) -->
            <div class="md:hidden space-y-4">
                <!-- Loading State -->
                <div v-if="isLoading" class="space-y-4">
                    <Card v-for="i in 3" :key="i">
                        <CardContent class="p-4">
                            <Skeleton class="h-4 w-full mb-2" />
                            <Skeleton class="h-4 w-3/4 mb-2" />
                            <Skeleton class="h-4 w-1/2" />
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <Card v-else-if="clockings.data.length === 0">
                    <CardContent class="text-center py-12">
                        <Activity class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <CardTitle class="mb-2">No attendance records found</CardTitle>
                        <CardDescription class="mb-4">Start tracking your attendance by clocking in</CardDescription>
                        <Button asChild>
                            <Link href="/attendance/clock">
                                <Clock class="h-4 w-4 mr-2" />
                                Clock In Now
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Record Cards -->
                <Card
                    v-else
                    v-for="(record, i) in clockings.data"
                    :key="i"
                    class="hover:shadow-md transition-shadow"
                >
                    <CardContent class="p-4">
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Course name at the top -->
                            <div class="col-span-2 mb-2">
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mb-1">
                                    <BookOpen class="h-3 w-3" />
                                    Course
                                </div>
                                <div class="text-sm font-medium">{{ record.course_name || 'General Attendance' }}</div>
                            </div>

                            <div>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mb-1">
                                    <Clock class="h-3 w-3" />
                                    Clock In
                                </div>
                                <div class="text-sm font-medium">{{ formatDate(record.clock_in) }}</div>
                            </div>
                            <div>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mb-1">
                                    <Clock class="h-3 w-3" />
                                    Clock Out
                                </div>
                                <div class="text-sm font-medium">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</div>
                            </div>

                            <!-- Duration in mobile view -->
                            <div>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mb-1">
                                    <Timer class="h-3 w-3" />
                                    Duration
                                </div>
                                <div class="text-sm font-medium">
                  <span v-if="record.clock_out">
                    {{ formatHumanDuration(record.duration_in_minutes) }}
                  </span>
                                    <span v-else class="flex items-center gap-1">
                    {{ formatHumanDuration(record.current_duration || 0) }}
                    <Badge variant="secondary" class="text-xs">ongoing</Badge>
                  </span>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center gap-1 text-xs text-muted-foreground mb-1">
                                    <Star class="h-3 w-3" />
                                    Rating
                                </div>
                                <div class="flex items-center text-sm font-medium">
                                    <div v-if="record.rating" class="flex items-center gap-1">
                                        <span>{{ record.rating }}</span>
                                        <div class="flex">
                                            <Star
                                                v-for="star in 5"
                                                :key="star"
                                                :class="[
                          'h-4 w-4',
                          star <= record.rating ? 'text-yellow-400 fill-current' : 'text-muted-foreground'
                        ]"
                                            />
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </div>
                            </div>
                        </div>

                        <Separator class="my-3" />

                        <div>
                            <div class="flex items-center gap-1 text-xs text-muted-foreground mb-1">
                                <MessageSquare class="h-3 w-3" />
                                Comment
                            </div>
                            <div class="text-sm">{{ record.comment || '—' }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Desktop View (Table Layout) -->
            <Card class="hidden md:block">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Attendance History
                    </CardTitle>
                    <CardDescription>
                        View and track your complete attendance records
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>
                                    <div class="flex items-center gap-1">
                                        <BookOpen class="h-4 w-4" />
                                        Course
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
                                <TableHead>
                                    <div class="flex items-center gap-1">
                                        <MessageSquare class="h-4 w-4" />
                                        Comment
                                    </div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <!-- Loading State -->
                            <TableRow v-if="isLoading">
                                <TableCell colspan="6">
                                    <div class="space-y-2">
                                        <Skeleton class="h-4 w-full" />
                                        <Skeleton class="h-4 w-3/4" />
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-else-if="clockings.data.length === 0">
                                <TableCell colspan="6" class="text-center py-12">
                                    <div class="flex flex-col items-center gap-2">
                                        <Activity class="h-12 w-12 text-muted-foreground" />
                                        <div>
                                            <h3 class="font-medium">No attendance records found</h3>
                                            <p class="text-sm text-muted-foreground">Start tracking your attendance by clocking in</p>
                                        </div>
                                        <Button asChild class="mt-2">
                                            <Link href="/attendance/clock">
                                                <Clock class="h-4 w-4 mr-2" />
                                                Clock In Now
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Record Rows -->
                            <TableRow v-else v-for="(record, i) in clockings.data" :key="i" class="hover:bg-accent/50">
                                <TableCell class="font-medium">{{ record.course_name || 'General Attendance' }}</TableCell>
                                <TableCell>{{ formatDate(record.clock_in) }}</TableCell>
                                <TableCell>{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</TableCell>

                                <!-- Duration in desktop view -->
                                <TableCell>
                                    <div v-if="record.clock_out">
                                        {{ formatHumanDuration(record.duration_in_minutes) }}
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        {{ formatHumanDuration(record.current_duration || 0) }}
                                        <Badge variant="secondary" class="text-xs">ongoing</Badge>
                                    </div>
                                </TableCell>

                                <!-- Rating column -->
                                <TableCell>
                                    <div v-if="record.rating" class="flex items-center gap-1">
                                        <span class="text-sm font-medium">{{ record.rating }}</span>
                                        <div class="flex">
                                            <Star
                                                v-for="star in 5"
                                                :key="star"
                                                :class="[
                          'h-4 w-4',
                          star <= record.rating ? 'text-yellow-400 fill-current' : 'text-muted-foreground'
                        ]"
                                            />
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>

                                <TableCell class="max-w-xs">
                                    <div class="truncate" :title="record.comment">
                                        {{ record.comment || '—' }}
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <Card>
                <CardContent class="p-4">
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
    </AttendanceLayout>
</template>
