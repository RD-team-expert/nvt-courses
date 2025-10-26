<!--
  Course Details Page
  Comprehensive course information with enrollment, rating, and assignment functionality
  Updated to show new scheduling data (days, weeks, session times)
-->
<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed, ref, watch } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert'
import {
    BookOpen,
    Calendar,
    Clock,
    Star,
    Users,
    CheckCircle,
    AlertTriangle,
    ArrowRight,
    Check,
    X,
    ClipboardList,
    Trophy,
    MapPin,
    Loader2,
    CalendarDays,
    Timer
} from 'lucide-vue-next'

const props = defineProps({
    course: Object,
    isEnrolled: Boolean,
    userStatus: String,
    selectedAvailability: Object,
    availabilities: Array,
    userAssignment: Object,
    completion: Object,
})

// ✅ ERROR ALERT SYSTEM
const showErrorAlert = ref(false)
const errorMessage = ref('')
const errorTitle = ref('')

// Function to show user-friendly error alerts
const showAlert = (title: string, message: string) => {
    errorTitle.value = title
    errorMessage.value = message
    showErrorAlert.value = true

    // Auto-hide after 6 seconds
    setTimeout(() => {
        showErrorAlert.value = false
    }, 6000)
}

const closeAlert = () => {
    showErrorAlert.value = false
}

// Reactive key for forcing re-render
const componentKey = ref(0)

// Watch for prop changes to trigger re-render
watch(() => props.completion, (newVal, oldVal) => {
    console.log('Completion changed:', oldVal, '->', newVal);
    componentKey.value += 1;
}, { deep: true })

watch(() => props.userStatus, (newVal, oldVal) => {
    console.log('UserStatus changed:', oldVal, '->', newVal);
    componentKey.value += 1;
})

const ratingForm = useForm({
    rating: 0,
    feedback: ''
})

// Check if user has already rated
const hasRated = computed(() => {
    const result = props.completion && props.completion.rating !== null;
    console.log('hasRated computed:', result, 'completion:', props.completion);
    return result;
})

// Check if user can mark complete (must be enrolled AND rated)
const canMarkComplete = computed(() => {
    return props.isEnrolled &&
        (props.userStatus === 'pending' || props.userStatus === 'in_progress') &&
        hasRated.value
})

// Submit rating function
function submitRating() {
    console.log('=== VUE SUBMIT RATING START ===');
    console.log('Rating form data:', ratingForm.data());

    ratingForm.post(route('courses.submitRating', props.course.id), {
        onSuccess: (page) => {
            console.log('✅ Rating submitted successfully');
            router.reload({
                only: ['completion', 'userStatus', 'isEnrolled']
            });
        },
        onError: (errors) => {
            console.error('❌ Rating submission failed:', errors);

            // Show user-friendly error alert
            if (errors.message) {
                showAlert('Rating Submission Failed', errors.message)
            } else if (errors.feedback) {
                showAlert('Feedback Required', errors.feedback)
            } else {
                showAlert('Rating Error', 'There was an error submitting your rating. Please try again.')
            }
        }
    });
}

// Create a form for enrollment
const enrollForm = useForm({
    course_availability_id: null
})

// ✅ ENHANCED ENROLLMENT FUNCTION WITH USER-FRIENDLY ERROR HANDLING
function enroll() {
    console.log('=== ENROLLMENT DEBUG START ===');

    if (props.availabilities && props.availabilities.length > 0 && !enrollForm.course_availability_id) {
        showAlert('Session Selection Required', 'Please select a session schedule before enrolling in this course.')
        return;
    }

    enrollForm.post(route('courses.enroll', props.course.id), {
        preserveState: true,
        onSuccess: (page) => {
            console.log('✅ Enrollment SUCCESS!');
            router.reload({ only: ['isEnrolled', 'userStatus', 'selectedAvailability'] });
        },
        onError: (errors) => {
            console.error('❌ Enrollment FAILED!', errors);

            // ✅ USER-FRIENDLY ERROR HANDLING
            if (errors.message) {
                // Handle specific server messages
                if (errors.message.includes('no longer available')) {
                    showAlert(
                        '📅 Session No Longer Available',
                        'Sorry, the selected session schedule is no longer available. Please choose a different session or refresh the page to see updated availability.'
                    )
                } else if (errors.message.includes('fully booked')) {
                    showAlert(
                        '📋 Session Fully Booked',
                        'This session is now fully booked. Please select a different session schedule with available spots.'
                    )
                } else if (errors.message.includes('already enrolled')) {
                    showAlert(
                        '✅ Already Enrolled',
                        'You are already enrolled in this course. Please refresh the page to see your current enrollment status.'
                    )
                } else {
                    showAlert('Enrollment Error', errors.message)
                }
            } else if (errors.course_availability_id) {
                showAlert('Session Selection Error', errors.course_availability_id)
            } else {
                showAlert(
                    '⚠️ Enrollment Failed',
                    'We encountered an issue while processing your enrollment. Please refresh the page and try again, or contact support if the problem persists.'
                )
            }

            // Refresh the page data to show updated availability
            setTimeout(() => {
                router.reload({ only: ['availabilities', 'isEnrolled', 'userStatus'] });
            }, 3000);
        }
    });
}

// Function to mark course as completed
const completeForm = useForm({})
function markCompleted() {
    console.log('=== VUE MARK COMPLETED START ===');

    completeForm.post(route('courses.markCompleted', props.course.id), {
        onSuccess: (page) => {
            console.log('✅ Course marked as completed');
            router.reload({ only: ['userStatus'] });
        },
        onError: (errors) => {
            console.error('❌ Failed to mark course as completed:', errors);

            if (errors.message) {
                showAlert('Completion Error', errors.message)
            } else {
                showAlert('Unable to Complete', 'There was an error marking this course as completed. Please try again.')
            }
        }
    });
}

// Get status variant
const getStatusVariant = (status) => {
    switch (status) {
        case 'in_progress': return 'default'
        case 'pending': return 'secondary'
        case 'completed': return 'outline'
        default: return 'outline'
    }
}

// Get assignment status variant
const getAssignmentStatusVariant = (status) => {
    switch (status) {
        case 'pending': return 'secondary'
        case 'accepted': return 'default'
        case 'declined': return 'destructive'
        case 'completed': return 'outline'
        default: return 'outline'
    }
}

// ✅ Format capacity (total sessions)
const formatCapacity = (capacity) => {
    if (!capacity) return 'Capacity not specified';
    if (capacity === 1) {
        return '1 Total Session';
    }
    return `${capacity} seats available`;
}

// ✅ Format available sessions
const formatSessions = (sessions) => {
    if (sessions === undefined || sessions === null) return 'Sessions not available';
    if (sessions === 0) return 'No sessions available';
    if (sessions === 1) {
        return '1 Session Available';
    }
    return `${sessions} Total Sessions`;
}

// ✅ Availability status methods based on sessions
const getAvailabilityStatusVariant = (availability) => {
    const sessions = availability?.sessions || 0;

    if (sessions <= 0) {
        return 'destructive';
    } else if (sessions <= 2) {
        return 'secondary';
    } else {
        return 'default';
    }
};

const getAvailabilityStatusText = (availability) => {
    const sessions = availability?.sessions || 0;

    if (sessions <= 0) {
        return 'Fully Booked';
    } else if (sessions === 1) {
        return '1 Session Available';
    } else {
        return `${sessions} Sessions Available`;
    }
};

// Format status for display
const formatStatus = (status) => {
    const statusMap = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    }
    return statusMap[status] || status
}

const formatDateTime = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleDateString() + ' at ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleDateString()
}

// ✅ Check if availability is disabled based on sessions
const isAvailabilityDisabled = (availability) => {
    const sessions = availability?.sessions || 0;
    return sessions <= 0 || !availability?.is_available || (props.userAssignment && props.userAssignment.status === 'pending');
}

// NEW: Format multiple session times for display
const formatSessionTimes = (availability) => {
    const times = [];

    if (availability.session_time_shift_2) {
        times.push(`Shift 2: ${availability.session_time_shift_2}`);
    }

    if (availability.session_time_shift_3) {
        times.push(`Shift 3: ${availability.session_time_shift_3}`);
    }

    if (times.length === 0) {
        return 'No session times set';
    }

    return times.join(', ');
}

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Courses', href: route('courses.index') },
    { name: props.course.name, href: route('courses.show', props.course.id) }
]
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- ✅ ERROR ALERT POPUP -->
        <Alert v-if="showErrorAlert" class="fixed top-4 right-4 z-50 max-w-md border-destructive">
            <AlertTriangle class="h-4 w-4" />
            <AlertTitle>{{ errorTitle }}</AlertTitle>
            <AlertDescription>{{ errorMessage }}</AlertDescription>
            <Button @click="closeAlert" variant="ghost" size="sm" class="absolute top-2 right-2">
                <X class="h-4 w-4" />
            </Button>
        </Alert>

        <div class="container px-4 mx-auto py-6 sm:py-8 max-w-7xl space-y-6">
            <!-- Course Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-foreground">{{ course.name }}</h1>
                    <!-- Privacy Badge -->
                    <div class="mt-2">
                        <Badge
                            :variant="course.privacy === 'public' ? 'default' : 'secondary'"
                            class="text-xs"
                        >
                            {{ course.privacy === 'public' ? '🌍 Public Course' : '🔒 Private Course' }}
                        </Badge>
                    </div>
                </div>
                <Badge :variant="getStatusVariant(course.status)">
                    {{ formatStatus(course.status) }}
                </Badge>
            </div>

            <!-- Course Assignment Notification -->
            <Card v-if="userAssignment" class="border-l-4" :class="{
                'border-l-yellow-500': userAssignment.status === 'pending',
                'border-l-green-500': userAssignment.status === 'accepted',
                'border-l-red-500': userAssignment.status === 'declined',
                'border-l-blue-500': userAssignment.status === 'completed'
            }">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3">
                            <ClipboardList class="h-5 w-5 mt-0.5" :class="{
                                'text-yellow-500': userAssignment.status === 'pending',
                                'text-green-500': userAssignment.status === 'accepted',
                                'text-red-500': userAssignment.status === 'declined',
                                'text-blue-500': userAssignment.status === 'completed'
                            }" />
                            <div>
                                <CardTitle class="text-lg">
                                    <span v-if="userAssignment.status === 'pending'">📋 Course Assignment - Action Required</span>
                                    <span v-else-if="userAssignment.status === 'accepted'">✅ Course Assignment - Accepted</span>
                                    <span v-else-if="userAssignment.status === 'declined'">❌ Course Assignment - Declined</span>
                                    <span v-else-if="userAssignment.status === 'completed'">🎉 Course Assignment - Completed</span>
                                </CardTitle>
                                <CardDescription v-if="userAssignment.assigned_by">
                                    Assigned by: {{ userAssignment.assigned_by }}
                                </CardDescription>
                            </div>
                        </div>
                        <Badge :variant="getAssignmentStatusVariant(userAssignment.status)">
                            {{ userAssignment.status.charAt(0).toUpperCase() + userAssignment.status.slice(1) }}
                        </Badge>
                    </div>
                </CardHeader>

                <!-- Assignment Action Buttons -->
                <CardContent v-if="userAssignment.status === 'pending'">
                    <div class="flex gap-3">
                        <Button
                            :as="Link"
                            :href="route('assignments.accept', userAssignment.id)"
                            method="post"
                        >
                            <Check class="mr-2 h-4 w-4" />
                            Accept Assignment
                        </Button>
                        <Button
                            :as="Link"
                            :href="route('assignments.decline', userAssignment.id)"
                            method="post"
                            variant="outline"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Decline Assignment
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Course Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- Course Details -->
                <Card class="lg:col-span-2 group hover:shadow-lg transition-all duration-200">
                    <!-- Course Image -->
                    <div class="relative bg-muted overflow-hidden aspect-video">
                        <img
                            v-if="course.image_path"
                            :src="`/storage/${course.image_path}`"
                            :alt="course.name"
                            class="absolute inset-0 w-full h-full object-fill transition-transform duration-500 group-hover:scale-105"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-muted to-muted/80"
                        >
                            <BookOpen class="w-12 h-12 text-muted-foreground" />
                        </div>
                    </div>

                    <CardContent class="p-6 sm:p-8">
                        <!-- Course description with proper HTML rendering -->
                        <div class="prose max-w-none mb-8 text-foreground" v-html="course.description"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Card class="p-4">
                                <div class="flex items-center space-x-2 text-muted-foreground mb-2">
                                    <Calendar class="h-4 w-4" />
                                    <span class="text-sm font-medium">Start Date</span>
                                </div>
                                <p class="font-semibold text-foreground">{{ formatDate(course.start_date) }}</p>
                            </Card>
                            <Card class="p-4">
                                <div class="flex items-center space-x-2 text-muted-foreground mb-2">
                                    <Calendar class="h-4 w-4" />
                                    <span class="text-sm font-medium">End Date</span>
                                </div>
                                <p class="font-semibold text-foreground">{{ formatDate(course.end_date) }}</p>
                            </Card>
                            <Card v-if="course.level" class="p-4">
                                <div class="flex items-center space-x-2 text-muted-foreground mb-2">
                                    <Badge variant="outline" class="text-xs">Level</Badge>
                                </div>
                                <p class="font-semibold text-foreground capitalize">{{ course.level }}</p>
                            </Card>
                            <Card v-if="course.duration" class="p-4">
                                <div class="flex items-center space-x-2 text-muted-foreground mb-2">
                                    <Clock class="h-4 w-4" />
                                    <span class="text-sm font-medium">Total Duration</span>
                                </div>
                                <p class="font-semibold text-foreground">{{ course.duration }} hours</p>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <!-- Enrollment Card -->
                <Card class="h-fit">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <BookOpen class="mr-2 h-5 w-5" />
                            Course Enrollment
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Not Enrolled State -->
                        <div v-if="!isEnrolled">
                            <!-- Assignment-based enrollment message -->
                            <div v-if="userAssignment && userAssignment.status === 'accepted'">
                                <p class="text-muted-foreground mb-6 leading-relaxed">
                                    This course has been assigned to you. Select a session schedule to complete your enrollment.
                                </p>
                            </div>

                            <!-- Pending assignment message -->
                            <Alert v-else-if="userAssignment && userAssignment.status === 'pending'" class="border-yellow-200">
                                <AlertTriangle class="h-4 w-4" />
                                <AlertTitle>Assignment Pending</AlertTitle>
                                <AlertDescription>
                                    Please accept your course assignment above to proceed with enrollment.
                                </AlertDescription>
                            </Alert>

                            <!-- Declined assignment message -->
                            <Alert v-else-if="userAssignment && userAssignment.status === 'declined'" class="border-destructive">
                                <X class="h-4 w-4" />
                                <AlertTitle>Assignment Declined</AlertTitle>
                                <AlertDescription>
                                    You have declined this course assignment. Contact your administrator if you'd like to reconsider.
                                </AlertDescription>
                            </Alert>

                            <!-- Regular enrollment message -->
                            <div v-else>
                                <p class="text-muted-foreground mb-6 leading-relaxed">
                                    Select a session schedule and enroll in this course to track your progress.
                                </p>
                            </div>

                            <!-- ✅ Available Session Schedules with UPDATED SCHEDULING DATA -->
                            <div v-if="availabilities && availabilities.length > 0" class="space-y-4" :class="{ 'opacity-50': userAssignment && userAssignment.status === 'pending' }">
                                <Label class="text-base font-semibold">Available Session Schedules:</Label>

                                <RadioGroup
                                    v-model="enrollForm.course_availability_id"
                                    :disabled="userAssignment && userAssignment.status === 'pending'"
                                    class="space-y-3"
                                >
                                    <div
                                        v-for="availability in availabilities"
                                        :key="availability.id"
                                        class="flex items-start space-x-3"
                                    >
                                        <!-- ENHANCED: More visible radio button with custom styling -->
                                        <RadioGroupItem
                                            :value="availability.id.toString()"
                                            :disabled="isAvailabilityDisabled(availability)"
                                            class="mt-1 w-6 h-6 rounded-full border-2 border-gray-400 bg-white hover:border-primary focus:border-primary focus:ring-2 focus:ring-primary focus:ring-offset-2 data-[state=checked]:border-primary data-[state=checked]:bg-primary transition-all duration-200"
                                        >
                                            <!-- ENHANCED: Visible inner dot with better contrast -->
                                            <div class="w-full h-full rounded-full flex items-center justify-center">
                                                <div class="w-2.5 h-2.5 rounded-full bg-white opacity-0 data-[state=checked]:opacity-100 transition-opacity duration-200"></div>
                                            </div>
                                        </RadioGroupItem>

                                        <Card class="flex-1 p-4" :class="{
                'border-primary ring-2 ring-primary ring-opacity-20': enrollForm.course_availability_id === availability.id.toString(),
                'opacity-50': isAvailabilityDisabled(availability)
            }">
                                            <!-- Your existing card content remains the same -->
                                            <div class="space-y-3">
                                                <!-- Date Range -->
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <p class="font-medium text-foreground">
                                                            {{ availability.formatted_date_range }}
                                                        </p>
                                                        <p class="text-sm text-muted-foreground mt-1">
                                                            {{ formatDateTime(availability.start_date) }} - {{ formatDateTime(availability.end_date) }}
                                                        </p>
                                                    </div>
                                                    <Badge :variant="getAvailabilityStatusVariant(availability)">
                                                        {{ getAvailabilityStatusText(availability) }}
                                                    </Badge>
                                                </div>

                                                <!-- Rest of your existing content... -->
                                                <div class="border-t pt-3 space-y-2">
                                                    <h4 class="text-xs font-semibold text-foreground uppercase tracking-wide">Schedule Details</h4>

                                                    <div v-if="availability.formatted_days && availability.formatted_days !== 'N/A'" class="flex items-center space-x-2">
                                                        <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                                        <span class="text-sm text-muted-foreground">Days:</span>
                                                        <Badge variant="outline" class="text-xs">
                                                            {{ availability.formatted_days }}
                                                        </Badge>
                                                    </div>

                                                    <div v-if="availability.duration_weeks" class="flex items-center space-x-2">
                                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                                        <span class="text-sm text-muted-foreground">Duration:</span>
                                                        <span class="text-sm font-medium text-foreground">{{ availability.duration_weeks }} weeks</span>
                                                    </div>

                                                    <div v-if="availability.session_time_shift_2 || availability.session_time_shift_3" class="flex items-start space-x-2">
                                                        <Timer class="h-4 w-4 text-muted-foreground mt-0.5" />
                                                        <div class="flex-1">
                                                            <span class="text-sm text-muted-foreground">Session Times:</span>
                                                            <div class="mt-1 space-y-1">
                                                                <div v-if="availability.session_time_shift_2" class="text-sm font-medium text-foreground">
                                                                    Shift 2: {{ availability.session_time_shift_2 }}
                                                                </div>
                                                                <div v-if="availability.session_time_shift_3" class="text-sm font-medium text-foreground">
                                                                    Shift 3: {{ availability.session_time_shift_3 }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-if="availability.formatted_session_duration" class="flex items-center space-x-2">
                                                        <Clock class="h-4 w-4 text-muted-foreground" />
                                                        <span class="text-sm text-muted-foreground">Length:</span>
                                                        <span class="text-sm font-medium text-foreground">{{ availability.formatted_session_duration }}</span>
                                                    </div>
                                                </div>

                                                <div class="border-t pt-3 space-y-1">
                                                    <p class="text-sm text-primary font-medium">
                                                        {{ formatCapacity(availability.capacity) }}
                                                    </p>
                                                    <p class="text-sm text-green-600 font-medium">
                                                        {{ formatSessions(availability.sessions) }}
                                                    </p>
                                                    <p v-if="(availability.capacity - availability.sessions) > 0" class="text-xs text-muted-foreground">
                                                        {{ (availability.capacity - availability.sessions) }} sessions enrolled
                                                    </p>
                                                </div>

                                                <div v-if="availability.notes" class="border-t pt-3">
                                                    <p class="text-sm text-muted-foreground">
                                                        <span class="font-medium">Notes:</span> {{ availability.notes }}
                                                    </p>
                                                </div>
                                            </div>
                                        </Card>
                                    </div>
                                </RadioGroup>
                            </div>

                            <!-- Simple enrollment for courses without availabilities -->
                            <div v-else-if="!availabilities || availabilities.length === 0">
                                <p class="text-muted-foreground mb-6 leading-relaxed">
                                    Enroll in this course to track your progress and get access to course materials.
                                </p>
                            </div>

                            <Button
                                @click="enroll"
                                class=" mt-6  w-full"
                                :disabled="enrollForm.processing || (availabilities && availabilities.length > 0 && !enrollForm.course_availability_id) || (userAssignment && userAssignment.status === 'pending')"
                            >
                                <Loader2 v-if="enrollForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <span v-if="enrollForm.processing">Enrolling...</span>
                                <span v-else-if="userAssignment && userAssignment.status === 'pending'">Accept Assignment First</span>
                                <span v-else-if="userAssignment && userAssignment.status === 'declined'">Assignment Declined</span>
                                <span v-else>Enroll Now</span>
                                <ArrowRight v-if="!enrollForm.processing && (!userAssignment || userAssignment.status === 'accepted')" class="ml-2 h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Enrolled State -->
                        <div v-else class="space-y-6" :key="componentKey">
                            <div class="space-y-4">
                                <div class="flex items-center p-4 bg-muted rounded-lg">
                                    <div class="w-3 h-3 rounded-full mr-3" :class="{
                                        'bg-green-500': userStatus === 'completed',
                                        'bg-primary': userStatus === 'pending' || userStatus === 'in_progress'
                                    }"></div>
                                    <span class="font-medium text-foreground">
                                        Status: {{ userStatus === 'completed' ? 'Completed' : 'Enrolled' }}
                                    </span>
                                </div>

                                <!-- Step 1: Rating Section (Show if enrolled but not rated) -->
                                <Alert v-if="(userStatus === 'pending' || userStatus === 'in_progress') && !hasRated" class="border-yellow-200">
                                    <Star class="h-4 w-4" />
                                    <AlertTitle>📋 Rate This Course First</AlertTitle>
                                    <AlertDescription class="mb-4">
                                        Please rate this course before marking it as complete.
                                    </AlertDescription>

                                    <!-- Star Rating -->
                                    <div class="space-y-4">
                                        <div>
                                            <Label class="text-sm font-medium">Your Rating</Label>
                                            <div class="flex space-x-1 mt-2">
                                                <Button
                                                    v-for="star in 5"
                                                    :key="star"
                                                    type="button"
                                                    @click="ratingForm.rating = star"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="p-1"
                                                >
                                                    <Star
                                                        class="h-6 w-6"
                                                        :class="{
                                                            'text-yellow-400 fill-current': star <= ratingForm.rating,
                                                            'text-muted-foreground': star > ratingForm.rating
                                                        }"
                                                    />
                                                </Button>
                                            </div>
                                        </div>

                                        <!-- Feedback (Required) -->
                                        <div>
                                            <Label class="text-sm font-medium">
                                                Feedback <span class="text-destructive">*</span>
                                            </Label>
                                            <Textarea
                                                v-model="ratingForm.feedback"
                                                placeholder="Please share your thoughts about this course... (required)"
                                                class="mt-2"
                                                :class="{ 'border-destructive': ratingForm.errors.feedback }"
                                                required
                                            />
                                            <div v-if="ratingForm.errors.feedback" class="text-destructive text-sm mt-1">
                                                {{ ratingForm.errors.feedback }}
                                            </div>
                                            <p class="text-xs text-muted-foreground mt-1">Minimum 10 characters required</p>
                                        </div>

                                        <!-- Submit Rating Button -->
                                        <Button
                                            @click="submitRating"
                                            :disabled="ratingForm.processing || ratingForm.rating === 0 || !ratingForm.feedback?.trim() || ratingForm.feedback.trim().length < 10"
                                            class="w-full"
                                        >
                                            <Loader2 v-if="ratingForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                            <span v-if="ratingForm.processing">Submitting Rating...</span>
                                            <span v-else>Submit Rating</span>
                                        </Button>
                                    </div>
                                </Alert>

                                <!-- Step 2: Mark Complete Section (Show if rated but not completed) -->
                                <Alert v-else-if="(userStatus === 'pending' || userStatus === 'in_progress') && hasRated" class="border-green-200">
                                    <CheckCircle class="h-4 w-4" />
                                    <AlertTitle>✅ Ready to Complete</AlertTitle>
                                    <AlertDescription class="mb-4">
                                        Thank you for rating! You can now mark this course as completed.
                                    </AlertDescription>

                                    <div v-if="completion && completion.rating" class="mb-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm">Your rating:</span>
                                            <div class="flex">
                                                <Star
                                                    v-for="i in 5"
                                                    :key="i"
                                                    class="h-4 w-4"
                                                    :class="i <= completion.rating ? 'text-yellow-400 fill-current' : 'text-muted-foreground'"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <Button
                                        @click="markCompleted"
                                        :disabled="completeForm.processing"
                                        class="w-full"
                                    >
                                        <Loader2 v-if="completeForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                        <Check v-else class="mr-2 h-4 w-4" />
                                        <span v-if="completeForm.processing">Processing...</span>
                                        <span v-else>Mark as Completed</span>
                                    </Button>
                                </Alert>

                                <!-- Step 3: Completed Section -->
                                <Alert v-else-if="userStatus === 'completed'" class="border-green-200">
                                    <Trophy class="h-4 w-4" />
                                    <AlertTitle>🎉 Course Completed!</AlertTitle>
                                    <AlertDescription class="mb-4">
                                        Congratulations! You've successfully completed all sessions in this course.
                                    </AlertDescription>

                                    <Button :as="Link" href="/courses" class="w-full">
                                        <BookOpen class="mr-2 h-4 w-4" />
                                        Browse More Courses
                                    </Button>
                                </Alert>

                                <!-- ✅ Show selected session schedule with UPDATED SCHEDULING DATA -->
                                <Card v-if="selectedAvailability" class="bg-primary/5 border-primary/20">
                                    <CardContent class="p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <MapPin class="h-4 w-4 text-primary" />
                                            <span class="text-sm font-medium text-primary">Your Selected Session Schedule</span>
                                        </div>

                                        <div class="space-y-3">
                                            <!-- Date Range -->
                                            <div>
                                                <p class="font-semibold text-foreground">{{ selectedAvailability.formatted_date_range }}</p>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ formatDateTime(selectedAvailability.start_date) }} - {{ formatDateTime(selectedAvailability.end_date) }}
                                                </p>
                                            </div>

                                            <!-- UPDATED: Scheduling Information with Multiple Shift Times -->
                                            <div class="border-t pt-3 space-y-2 text-sm">
                                                <h4 class="text-xs font-semibold text-foreground uppercase tracking-wide">Schedule Details</h4>

                                                <!-- Days of Week -->
                                                <div v-if="selectedAvailability.formatted_days && selectedAvailability.formatted_days !== 'N/A'" class="flex items-center space-x-2">
                                                    <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                                    <span class="text-muted-foreground">Days:</span>
                                                    <Badge variant="outline" class="text-xs">
                                                        {{ selectedAvailability.formatted_days }}
                                                    </Badge>
                                                </div>

                                                <!-- Duration in Weeks -->
                                                <div v-if="selectedAvailability.duration_weeks" class="flex items-center space-x-2">
                                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                                    <span class="text-muted-foreground">Duration:</span>
                                                    <span class="font-medium text-foreground">{{ selectedAvailability.duration_weeks }} weeks</span>
                                                </div>

                                                <!-- UPDATED: Multiple Session Times -->
                                                <div v-if="selectedAvailability.session_time_shift_2 || selectedAvailability.session_time_shift_3" class="flex items-start space-x-2">
                                                    <Timer class="h-4 w-4 text-muted-foreground mt-0.5" />
                                                    <div class="flex-1">
                                                        <span class="text-muted-foreground">Session Times:</span>
                                                        <div class="mt-1 space-y-1">
                                                            <div v-if="selectedAvailability.session_time_shift_2" class="font-medium text-foreground">
                                                                Shift 2: {{ selectedAvailability.session_time_shift_2 }}
                                                            </div>
                                                            <div v-if="selectedAvailability.session_time_shift_3" class="font-medium text-foreground">
                                                                Shift 3: {{ selectedAvailability.session_time_shift_3 }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Session Duration -->
                                                <div v-if="selectedAvailability.formatted_session_duration" class="flex items-center space-x-2">
                                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                                    <span class="text-muted-foreground">Length:</span>
                                                    <span class="font-medium text-foreground">{{ selectedAvailability.formatted_session_duration }}</span>
                                                </div>
                                            </div>

                                            <!-- ✅ Display BOTH capacity and sessions -->
                                            <div class="border-t pt-3 space-y-1 text-xs">
                                                <p class="text-primary font-medium">
                                                    Total Capacity: {{ selectedAvailability.capacity }} sessions
                                                </p>
                                                <p class="text-green-600 font-medium">
                                                    Available: {{ selectedAvailability.sessions }} sessions
                                                </p>
                                                <p class="text-muted-foreground">
                                                    Enrolled: {{ (selectedAvailability.capacity - selectedAvailability.sessions) }} sessions
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.aspect-video {
    aspect-ratio: 16 / 9;
}

.prose {
    max-width: none;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    color: hsl(var(--foreground));
}

.prose p, .prose li {
    color: hsl(var(--foreground));
}

@media (max-width: 768px) {
    .aspect-video {
        aspect-ratio: 4 / 3;
    }
}
</style>
