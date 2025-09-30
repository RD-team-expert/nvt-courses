<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Progress } from '@/components/ui/progress'

const props = defineProps({
    courses: Array,
    users: Array
})

const form = useForm({
    course_id: new URLSearchParams(window.location.search).get('course_id') || '',
    user_ids: [],
    course_availability_id: null
})

// Email progress tracking
const emailProgress = ref({
    isVisible: false,
    current: 0,
    total: 0,
    status: 'sending', // 'sending', 'success', 'error'
    message: ''
})

// Get availabilities for selected course
const selectedCourse = computed(() => {
    return props.courses.find(course => course.id == form.course_id)
})

// Calculate progress percentage
const progressPercentage = computed(() => {
    if (emailProgress.value.total === 0) return 0
    return Math.round((emailProgress.value.current / emailProgress.value.total) * 100)
})

function submit() {
    // Show progress indicator
    emailProgress.value = {
        isVisible: true,
        current: 0,
        total: form.user_ids.length,
        status: 'sending',
        message: 'Preparing to send emails...'
    }

    // Start simulating progress
    const progressInterval = setInterval(() => {
        if (emailProgress.value.current < emailProgress.value.total && emailProgress.value.status === 'sending') {
            emailProgress.value.current++
            emailProgress.value.message = `Sending email ${emailProgress.value.current} of ${emailProgress.value.total}...`
        }
    }, 3000) // 3 seconds per email (matching your sleep(3))

    form.post('/admin/assignments', {
        onSuccess: (response) => {
            clearInterval(progressInterval)
            emailProgress.value.status = 'success'
            emailProgress.value.message = 'All course assignments sent successfully!'
            emailProgress.value.current = emailProgress.value.total

            // Auto-hide after 4 seconds
            setTimeout(() => {
                emailProgress.value.isVisible = false
            }, 4000)
        },
        onError: (errors) => {
            clearInterval(progressInterval)
            emailProgress.value.status = 'error'
            emailProgress.value.message = 'Some assignments failed to send. Please check the logs.'

            // Don't auto-hide on error, let user close manually
        },
        onFinish: () => {
            clearInterval(progressInterval)
        }
    })
}

// Close progress modal
function closeProgressModal() {
    emailProgress.value.isVisible = false
}

// Select all users
function selectAllUsers() {
    form.user_ids = props.users.map(u => u.id)
}

// Clear all users
function clearAllUsers() {
    form.user_ids = []
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-xl sm:text-3xl font-bold text-foreground mb-2">Assign Course to Users</h1>
                <p class="text-muted-foreground">Select a course and users to send course assignment notifications with login credentials.</p>
            </div>

            <!-- Email Progress Modal/Overlay -->
            <Dialog v-model:open="emailProgress.isVisible">
                <DialogContent class="sm:max-w-md" @interact-outside="emailProgress.status !== 'sending' ? closeProgressModal() : null">
                    <DialogHeader>
                        <DialogTitle>
                            <span v-if="emailProgress.status === 'sending'">Sending Course Assignments</span>
                            <span v-else-if="emailProgress.status === 'success'">Assignment Complete!</span>
                            <span v-else-if="emailProgress.status === 'error'">Assignment Failed</span>
                        </DialogTitle>
                        <DialogDescription>{{ emailProgress.message }}</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-6 py-4">
                        <!-- Progress Circle -->
                        <div class="relative w-32 h-32 mx-auto">
                            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background circle -->
                                <path
                                    class="text-muted"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <!-- Progress circle -->
                                <path
                                    :class="{
                                        'text-primary': emailProgress.status === 'sending',
                                        'text-green-500': emailProgress.status === 'success',
                                        'text-destructive': emailProgress.status === 'error'
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${progressPercentage}, 100`"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="transition: stroke-dasharray 0.3s ease-in-out;"
                                />
                            </svg>
                            <!-- Percentage text -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-2xl font-bold text-foreground">{{ progressPercentage }}%</span>
                            </div>
                        </div>

                        <!-- Status Icon -->
                        <div class="flex justify-center">
                            <!-- Sending spinner -->
                            <div v-if="emailProgress.status === 'sending'" class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                <svg class="animate-spin h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <!-- Success checkmark -->
                            <div v-else-if="emailProgress.status === 'success'" class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center animate-pulse">
                                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <!-- Error X -->
                            <div v-else-if="emailProgress.status === 'error'" class="w-12 h-12 bg-destructive/10 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Progress Details -->
                        <div class="bg-muted rounded-lg p-4">
                            <div class="flex justify-between text-sm text-muted-foreground mb-2">
                                <span>Progress</span>
                                <span>{{ emailProgress.current }} / {{ emailProgress.total }}</span>
                            </div>
                            <Progress :value="progressPercentage" class="h-2" />
                        </div>

                        <!-- Action buttons -->
                        <div class="flex gap-3 justify-center">
                            <!-- Close button (only show when complete or error) -->
                            <Button
                                v-if="emailProgress.status !== 'sending'"
                                @click="closeProgressModal"
                                variant="secondary"
                            >
                                Close
                            </Button>

                            <!-- View assignments button on success -->
                            <Button
                                v-if="emailProgress.status === 'success'"
                                :as="Link"
                                href="/admin/assignments"
                                @click="closeProgressModal"
                            >
                                View Assignments
                            </Button>
                        </div>

                        <!-- Cancel note for sending state -->
                        <p v-if="emailProgress.status === 'sending'" class="text-xs text-muted-foreground text-center">
                            Please wait while we send the assignment emails...
                        </p>
                    </div>
                </DialogContent>
            </Dialog>

            <!-- Assignment Form -->
            <Card>
                <CardContent class="p-6 sm:p-8">
                    <form @submit.prevent="submit" class="max-w-4xl space-y-8">
                        <!-- Course Selection -->
                        <div class="space-y-3">
                            <Label for="course-select" class="text-sm font-semibold">Select Course *</Label>
                            <Select v-model="form.course_id" :disabled="form.processing">
                                <SelectTrigger id="course-select">
                                    <SelectValue placeholder="Choose a course to assign..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.course_id" class="text-destructive text-sm">{{ form.errors.course_id }}</div>
                        </div>

                        <!-- User Selection -->
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold">Select Users *</Label>
                            <Card class="bg-muted/50">
                                <CardContent class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
                                        <div
                                            v-for="user in users"
                                            :key="user.id"
                                            class="flex items-center space-x-3 p-3 bg-background rounded-lg border hover:border-primary/50 hover:bg-accent/50 transition-colors cursor-pointer"
                                            :class="{ 'border-primary bg-accent': form.user_ids.includes(user.id) }"
                                        >
                                            <Checkbox
                                                :id="`user-${user.id}`"
                                                :checked="form.user_ids.includes(user.id)"
                                                @update:checked="(checked) => {
                                                    if (checked) {
                                                        form.user_ids.push(user.id)
                                                    } else {
                                                        form.user_ids = form.user_ids.filter(id => id !== user.id)
                                                    }
                                                }"
                                                :disabled="form.processing"
                                            />
                                            <Label
                                                :for="`user-${user.id}`"
                                                class="min-w-0 flex-1 cursor-pointer"
                                            >
                                                <span class="text-sm font-medium text-foreground block truncate">{{ user.name }}</span>
                                                <span class="text-xs text-muted-foreground block truncate">{{ user.email }}</span>
                                            </Label>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <div v-if="form.errors.user_ids" class="text-destructive text-sm">{{ form.errors.user_ids }}</div>
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-muted-foreground">
                                    <span class="font-medium">{{ form.user_ids.length }}</span> user{{ form.user_ids.length !== 1 ? 's' : '' }} selected
                                </p>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        @click="selectAllUsers"
                                        variant="link"
                                        size="sm"
                                        :disabled="form.processing"
                                        class="h-auto p-0 text-xs"
                                    >
                                        Select All
                                    </Button>
                                    <span class="text-muted-foreground">|</span>
                                    <Button
                                        type="button"
                                        @click="clearAllUsers"
                                        variant="link"
                                        size="sm"
                                        :disabled="form.processing"
                                        class="h-auto p-0 text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        Clear All
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Info Notice -->
                        <Alert>
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <AlertDescription>
                                <strong>Assignment Information:</strong> Selected users will receive an email with course details and login credentials. New passwords will be automatically generated and sent to each user. Users can accept or decline the course assignment from their email. Email sending may take a few moments due to rate limiting.
                            </AlertDescription>
                        </Alert>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                            <Button
                                type="submit"
                                class="flex-1 sm:flex-none"
                                :disabled="form.processing || form.user_ids.length === 0"
                            >
                                <!-- Loading spinner -->
                                <svg v-if="form.processing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <!-- Button text -->
                                <span v-if="form.processing">Sending Assignments...</span>
                                <span v-else-if="form.user_ids.length === 0">Select Users to Continue</span>
                                <span v-else>
                                    Assign Course to {{ form.user_ids.length }} User{{ form.user_ids.length !== 1 ? 's' : '' }}
                                </span>

                                <!-- Send icon -->
                                <svg v-if="!form.processing && form.user_ids.length > 0" class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </Button>

                            <Button
                                :as="Link"
                                href="/admin/assignments"
                                variant="secondary"
                                class="flex-1 sm:flex-none"
                                :class="{ 'pointer-events-none opacity-50': form.processing }"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to Assignments
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom scrollbar for user selection */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: hsl(var(--muted));
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.5);
}
</style>
