<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import Editor from '@tinymce/tinymce-vue'
import { ref, computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'

const props = defineProps({
    course: Object,
})

// Available days for selection
const availableDays = [
    { value: 'monday', label: 'Monday' },
    { value: 'tuesday', label: 'Tuesday' },
    { value: 'wednesday', label: 'Wednesday' },
    { value: 'thursday', label: 'Thursday' },
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' }
]

// Format dates for the form inputs (YYYY-MM-DD format required by date inputs)
const formatDate = (dateString) => {
    if (!dateString) return '';

    // Create a date object without timezone conversion
    // By using YYYY-MM-DD format directly, we avoid timezone issues
    const parts = dateString.split('T')[0].split('-');
    if (parts.length !== 3) {
        // If the date isn't in the expected format, try to parse it
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return ''; // Invalid date
        return date.toISOString().split('T')[0]; // Returns YYYY-MM-DD
    }

    // Return the date in YYYY-MM-DD format
    return parts.join('-');
}

// Convert days_of_week string to array for UI
const stringToArray = (daysString) => {
    if (!daysString) return [];
    return daysString.split(',').filter(day => day.trim() !== '');
}

// Initialize the form with existing course data
const form = useForm({
    name: props.course.name,
    description: props.course.description,
    start_date: formatDate(props.course.start_date),
    end_date: formatDate(props.course.end_date),
    status: props.course.status || 'pending',
    level: props.course.level || '',
    privacy: props.course.privacy || 'public',
    duration: props.course.duration || '',
    image: null,

    availabilities: props.course.availabilities && props.course.availabilities.length ?
        props.course.availabilities.map(availability => ({
            id: availability.id,
            start_date: availability.start_date ? new Date(availability.start_date).toISOString().slice(0, 16) : '',
            end_date: availability.end_date ? new Date(availability.end_date).toISOString().slice(0, 16) : '',
            capacity: availability.capacity || 25,
            sessions: availability.sessions || 1,
            notes: availability.notes || '',
            status: availability.status || 'active',

            // NEW SCHEDULING FIELDS - UPDATED
            days_of_week: stringToArray(availability.days_of_week || ''), // Convert string to array for UI
            duration_weeks: availability.duration_weeks || 1,
            // REMOVED: session_time: availability.session_time || '',
            // ADDED: Multiple shift times
            session_time_shift_2: availability.session_time_shift_2 || '',
            session_time_shift_3: availability.session_time_shift_3 || '',
            session_duration_minutes: availability.session_duration_minutes || 60
        })) :
        [{
            start_date: '',
            end_date: '',
            capacity: 25,
            sessions: 1,
            notes: '',
            status: 'active',

            // NEW SCHEDULING FIELDS - UPDATED
            days_of_week: [],
            duration_weeks: 1,
            // REMOVED: session_time: '',
            // ADDED: Multiple shift times
            session_time_shift_2: '',
            session_time_shift_3: '',
            session_duration_minutes: 60
        }]
})

// Handle day selection for SET field
function toggleDay(availabilityIndex, day) {
    const availability = form.availabilities[availabilityIndex]
    const dayIndex = availability.days_of_week.indexOf(day)

    if (dayIndex > -1) {
        // Remove day if already selected
        availability.days_of_week.splice(dayIndex, 1)
    } else {
        // Add day if not selected
        availability.days_of_week.push(day)
    }
}

// Check if day is selected
function isDaySelected(availabilityIndex, day) {
    return form.availabilities[availabilityIndex].days_of_week.includes(day)
}

// Get formatted selected days for display
function getSelectedDaysText(availability) {
    if (availability.days_of_week.length === 0) return 'No days selected'

    const dayNames = {
        'monday': 'Mon',
        'tuesday': 'Tue',
        'wednesday': 'Wed',
        'thursday': 'Thu',
        'friday': 'Fri',
        'saturday': 'Sat',
        'sunday': 'Sun'
    }

    return availability.days_of_week
        .map(day => dayNames[day])
        .join(', ')
}

function addAvailability() {
    if (form.availabilities.length < 5) {
        form.availabilities.push({
            start_date: '',
            end_date: '',
            capacity: 25,
            sessions: 1,
            notes: '',
            status: 'active',

            // NEW SCHEDULING FIELDS - UPDATED
            days_of_week: [],
            duration_weeks: 1,
            // REMOVED: session_time: '',
            // ADDED: Multiple shift times
            session_time_shift_2: '',
            session_time_shift_3: '',
            session_duration_minutes: 60
        })
    }
}

function removeAvailability(index) {
    if (form.availabilities.length > 1) {
        form.availabilities.splice(index, 1)
    }
}

function submit() {
    // ✅ FIXED: Convert days_of_week arrays to comma-separated strings BEFORE validation
    const processedData = {
        ...form.data(),
        availabilities: form.availabilities.map(availability => ({
            ...availability,
            days_of_week: availability.days_of_week.length > 0
                ? availability.days_of_week.join(',')  // Convert array to string
                : '' // Empty string if no days selected
        }))
    }

    // Ensure dates are properly formatted before submission
    if (processedData.start_date) {
        processedData.start_date = formatDate(processedData.start_date);
    }
    if (processedData.end_date) {
        processedData.end_date = formatDate(processedData.end_date);
    }
// ✅ CHANGED: Remove ?_method=PUT and use the new POST route
    form.post(`/admin/courses/${props.course.id}/update`, {
        data: processedData,
        forceFormData: true,
    })
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <Card class="max-w-4xl">
                <CardHeader>
                    <CardTitle>Edit Course</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                            <!-- Course Name -->
                            <div class="col-span-full">
                                <Label>Course Name</Label>
                                <Input
                                    type="text"
                                    v-model="form.name"
                                    :disabled="form.processing"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-destructive text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <!-- Course Status -->
                            <div class="col-span-1">
                                <Label>Status</Label>
                                <Select v-model="form.status" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.status" class="text-destructive text-sm mt-1">{{ form.errors.status }}</div>
                            </div>

                            <!-- Privacy Field -->
                            <div class="col-span-1">
                                <Label class="text-base font-semibold">Course Privacy</Label>
                                <div class="flex items-center space-x-4 mt-2">
                                    <div class="flex items-center">
                                        <input
                                            id="privacy-public"
                                            name="privacy"
                                            type="radio"
                                            value="public"
                                            v-model="form.privacy"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                            :disabled="form.processing"
                                        />
                                        <Label for="privacy-public" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                                            🌍 Public
                                        </Label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            id="privacy-private"
                                            name="privacy"
                                            type="radio"
                                            value="private"
                                            v-model="form.privacy"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                            :disabled="form.processing"
                                        />
                                        <Label for="privacy-private" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                                            🔒 Private
                                        </Label>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Public courses are visible to everyone. Private courses are only visible to enrolled users.
                                </p>
                                <div v-if="form.errors.privacy" class="mt-1 text-sm text-destructive">
                                    {{ form.errors.privacy }}
                                </div>
                            </div>

                            <!-- Course Level -->
                            <div class="col-span-1">
                                <Label>Level (Optional)</Label>
                                <Select v-model="form.level" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="beginner">Beginner</SelectItem>
                                        <SelectItem value="intermediate">Intermediate</SelectItem>
                                        <SelectItem value="advanced">Advanced</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.level" class="text-destructive text-sm mt-1">{{ form.errors.level }}</div>
                            </div>

                            <!-- Course Duration -->
                            <div class="col-span-1">
                                <Label>Duration (hours)</Label>
                                <Input
                                    type="number"
                                    step="0.1"
                                    v-model="form.duration"
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.duration" class="text-destructive text-sm mt-1">{{ form.errors.duration }}</div>
                            </div>

                            <!-- Start Date -->
                            <div class="col-span-1">
                                <Label>Start Date</Label>
                                <Input
                                    type="date"
                                    v-model="form.start_date"
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.start_date" class="text-destructive text-sm mt-1">{{ form.errors.start_date }}</div>
                            </div>

                            <!-- End Date -->
                            <div class="col-span-1">
                                <Label>End Date</Label>
                                <Input
                                    type="date"
                                    v-model="form.end_date"
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.end_date" class="text-destructive text-sm mt-1">{{ form.errors.end_date }}</div>
                            </div>

                            <!-- Course Image -->
                            <div class="col-span-full">
                                <Label>Course Image (Optional)</Label>
                                <Input
                                    type="file"
                                    @input="form.image = $event.target.files[0]"
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.image" class="text-destructive text-sm mt-1">{{ form.errors.image }}</div>

                                <div v-if="props.course.image_path" class="mt-2">
                                    <p class="text-sm text-muted-foreground">Current image:</p>
                                    <img
                                        :src="`/storage/${props.course.image_path}`"
                                        alt="Current course image"
                                        class="mt-1 h-32 w-full sm:w-48 object-cover rounded"
                                    />
                                </div>
                            </div>

                            <!-- Course Description -->
                            <div class="col-span-full">
                                <Label>Description</Label>
                                <Editor
                                    v-model="form.description"
                                    :disabled="form.processing"
                                    :api-key="'r1racrxd2joy9wp9xp9sj91ka9j4m3humenifqvwtx9s6i3y'"
                                    :init="{
                                    toolbar_mode: 'sliding',
                                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                                    mobile: {
                                      menubar: false,
                                      toolbar: 'undo redo | bold italic | link | bullist numlist',
                                      toolbar_mode: 'scrolling'
                                    }
                                  }"
                                />
                                <div v-if="form.errors.description" class="text-destructive text-sm mt-1">{{ form.errors.description }}</div>
                            </div>
                        </div>

                        <!-- Course Availabilities Section - UPDATED WITH MULTIPLE SHIFT TIMES -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold">Course Session Schedules</h2>
                                <Button
                                    type="button"
                                    @click="addAvailability"
                                    :disabled="form.availabilities.length >= 5 || form.processing"
                                    variant="default"
                                >
                                    Add Session Schedule ({{ form.availabilities.length }}/5)
                                </Button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(availability, index) in form.availabilities"
                                    :key="index"
                                    class="border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800"
                                >
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="font-medium">Session Schedule {{ index + 1 }}</h3>
                                        <Button
                                            v-if="form.availabilities.length > 1"
                                            type="button"
                                            @click="removeAvailability(index)"
                                            variant="destructive"
                                            size="sm"
                                            :disabled="form.processing"
                                        >
                                            Remove
                                        </Button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Days of Week Selection -->
                                        <div class="md:col-span-2">
                                            <label class="block font-medium mb-2">Days of the Week</label>
                                            <div class="grid grid-cols-4 md:grid-cols-7 gap-2">
                                                <div
                                                    v-for="day in availableDays"
                                                    :key="day.value"
                                                    class="flex items-center"
                                                >
                                                    <input
                                                        :id="`day-${index}-${day.value}`"
                                                        type="checkbox"
                                                        :checked="isDaySelected(index, day.value)"
                                                        @change="toggleDay(index, day.value)"
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                        :disabled="form.processing"
                                                    />
                                                    <Label :for="`day-${index}-${day.value}`" class="ml-2 text-sm">
                                                        {{ day.label }}
                                                    </Label>
                                                </div>
                                            </div>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                Selected: {{ getSelectedDaysText(availability) }}
                                            </p>
                                            <div v-if="form.errors[`availabilities.${index}.days_of_week`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.days_of_week`] }}
                                            </div>
                                        </div>

                                        <!-- Duration in Weeks -->
                                        <div>
                                            <label class="block font-medium mb-1">Duration (Weeks)</label>
                                            <Input
                                                type="number"
                                                v-model="availability.duration_weeks"
                                                min="1"
                                                max="52"
                                                :disabled="form.processing"
                                                required
                                            />
                                            <p class="text-xs text-muted-foreground mt-1">Number of weeks this course runs</p>
                                            <div v-if="form.errors[`availabilities.${index}.duration_weeks`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.duration_weeks`] }}
                                            </div>
                                        </div>

                                        <!-- NEW: Session Time Shift 2 -->
                                        <div>
                                            <label class="block font-medium mb-1">Session Time - Shift 2</label>
                                            <Input
                                                type="time"
                                                v-model="availability.session_time_shift_2"
                                                :disabled="form.processing"
                                            />
                                            <p class="text-xs text-muted-foreground mt-1">Start time for Shift 2 sessions (optional)</p>
                                            <div v-if="form.errors[`availabilities.${index}.session_time_shift_2`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.session_time_shift_2`] }}
                                            </div>
                                        </div>

                                        <!-- NEW: Session Time Shift 3 -->
                                        <div>
                                            <label class="block font-medium mb-1">Session Time - Shift 3</label>
                                            <Input
                                                type="time"
                                                v-model="availability.session_time_shift_3"
                                                :disabled="form.processing"
                                            />
                                            <p class="text-xs text-muted-foreground mt-1">Start time for Shift 3 sessions (optional)</p>
                                            <div v-if="form.errors[`availabilities.${index}.session_time_shift_3`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.session_time_shift_3`] }}
                                            </div>
                                        </div>

                                        <!-- Session Duration -->
                                        <div>
                                            <label class="block font-medium mb-1">Session Duration (Minutes)</label>
                                            <Input
                                                type="number"
                                                v-model="availability.session_duration_minutes"
                                                min="15"
                                                max="480"
                                                step="15"
                                                :disabled="form.processing"
                                            />
                                            <p class="text-xs text-muted-foreground mt-1">Duration of each session in minutes</p>
                                            <div v-if="form.errors[`availabilities.${index}.session_duration_minutes`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.session_duration_minutes`] }}
                                            </div>
                                        </div>

                                        <!-- Start Date -->
                                        <div>
                                            <Label>Start Date</Label>
                                            <Input
                                                type="datetime-local"
                                                v-model="availability.start_date"
                                                :disabled="form.processing"
                                                required
                                            />
                                            <div v-if="form.errors[`availabilities.${index}.start_date`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.start_date`] }}
                                            </div>
                                        </div>

                                        <!-- End Date -->
                                        <div>
                                            <Label>End Date</Label>
                                            <Input
                                                type="datetime-local"
                                                v-model="availability.end_date"
                                                :disabled="form.processing"
                                                required
                                            />
                                            <div v-if="form.errors[`availabilities.${index}.end_date`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.end_date`] }}
                                            </div>
                                        </div>

                                        <!-- Seats Available -->
                                        <div>
                                            <Label>Seats Available</Label>
                                            <Input
                                                type="number"
                                                v-model.number="availability.capacity"
                                                min="1"
                                                max="1000"
                                                :disabled="form.processing"
                                                required
                                            />
                                            <p class="text-xs text-muted-foreground mt-1">Maximum number of seats available for this schedule</p>
                                            <div v-if="form.errors[`availabilities.${index}.capacity`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.capacity`] }}
                                            </div>
                                        </div>

                                        <!-- Sessions -->
                                        <div>
                                            <Label>Sessions</Label>
                                            <Input
                                                type="number"
                                                v-model.number="availability.sessions"
                                                min="1"
                                                max="100"
                                                :disabled="form.processing"
                                                required
                                            />
                                            <p class="text-xs text-muted-foreground mt-1">Number of sessions in this schedule</p>
                                            <div v-if="form.errors[`availabilities.${index}.sessions`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.sessions`] }}
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div class="md:col-span-2">
                                            <Label>Notes (Optional)</Label>
                                            <Input
                                                type="text"
                                                v-model="availability.notes"
                                                placeholder="Special instructions or requirements"
                                                :disabled="form.processing"
                                            />
                                            <div v-if="form.errors[`availabilities.${index}.notes`]" class="text-destructive text-sm mt-1">
                                                {{ form.errors[`availabilities.${index}.notes`] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.errors.availabilities" class="text-destructive text-sm mt-2">
                                {{ form.errors.availabilities }}
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6">
                            <Button variant="outline" as-child :disabled="form.processing">
                                <Link :href="route('admin.courses.index')">
                                    Cancel
                                </Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Course</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
