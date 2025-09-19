<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import Editor from '@tinymce/tinymce-vue'
import { ref, computed } from 'vue'

const props = defineProps({
    course: Object,
})

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
            notes: availability.notes || '',
            status: availability.status || 'active'
        })) :
        [{
            start_date: '',
            end_date: '',
            capacity: 25,
            notes: '',
            status: 'active'
        }]
})

// ADD THESE FUNCTIONS
function addAvailability() {
    if (form.availabilities.length < 5) {
        form.availabilities.push({
            start_date: '',
            end_date: '',
            capacity: 25,
            notes: '',
            status: 'active'
        })
    }
}

function removeAvailability(index) {
    if (form.availabilities.length > 1) {
        form.availabilities.splice(index, 1)
    }
}

function submit() {
    // Ensure dates are properly formatted before submission
    if (form.start_date) {
        form.start_date = formatDate(form.start_date);
    }
    if (form.end_date) {
        form.end_date = formatDate(form.end_date);
    }

    form.post(`/admin/courses/${props.course.id}?_method=PUT`, {
        forceFormData: true,
    })
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-4">Edit Course</h1>

            <form @submit.prevent="submit" class="max-w-4xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                    <!-- Course Name -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Course Name</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Course Status -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
                    </div>

                    <!-- Privacy Field - Fixed to be inside the grid -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Course Privacy</label>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <input
                                    id="privacy-public"
                                    name="privacy"
                                    type="radio"
                                    value="public"
                                    v-model="form.privacy"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                />
                                <label for="privacy-public" class="ml-2 block text-sm text-gray-900">
                                    🌍 Public
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input
                                    id="privacy-private"
                                    name="privacy"
                                    type="radio"
                                    value="private"
                                    v-model="form.privacy"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                />
                                <label for="privacy-private" class="ml-2 block text-sm text-gray-900">
                                    🔒 Private
                                </label>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Public courses are visible to everyone. Private courses are only visible to enrolled users.
                        </p>
                        <div v-if="form.errors.privacy" class="mt-1 text-sm text-red-600">
                            {{ form.errors.privacy }}
                        </div>
                    </div>

                    <!-- Course Level -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Level (Optional)</label>
                        <select
                            v-model="form.level"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Level</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                        <div v-if="form.errors.level" class="text-red-600 text-sm mt-1">{{ form.errors.level }}</div>
                    </div>

                    <!-- Course Duration -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Duration (hours)</label>
                        <input
                            type="number"
                            step="0.1"
                            v-model="form.duration"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.duration" class="text-red-600 text-sm mt-1">{{ form.errors.duration }}</div>
                    </div>

                    <!-- Start Date -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Start Date</label>
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.start_date" class="text-red-600 text-sm mt-1">{{ form.errors.start_date }}</div>
                    </div>

                    <!-- End Date -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">End Date</label>
                        <input
                            type="date"
                            v-model="form.end_date"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.end_date" class="text-red-600 text-sm mt-1">{{ form.errors.end_date }}</div>
                    </div>
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold">Course Session Schedules</h2>
                            <button
                                type="button"
                                @click="addAvailability"
                                :disabled="form.availabilities.length >= 5 || form.processing"
                                class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600 disabled:bg-gray-400 disabled:cursor-not-allowed"
                            >
                                Add Session Schedule ({{ form.availabilities.length }}/5)
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div
                                v-for="(availability, index) in form.availabilities"
                                :key="index"
                                class="border border-gray-300 rounded-lg p-4 bg-gray-50"
                            >
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="font-medium">Session Schedule {{ index + 1 }}</h3>
                                    <button
                                        v-if="form.availabilities.length > 1"
                                        type="button"
                                        @click="removeAvailability(index)"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                        :disabled="form.processing"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Start Date -->
                                    <div>
                                        <label class="block font-medium mb-1">Start Date</label>
                                        <input
                                            type="datetime-local"
                                            v-model="availability.start_date"
                                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="form.processing"
                                            required
                                        />
                                        <div v-if="form.errors[`availabilities.${index}.start_date`]" class="text-red-600 text-sm mt-1">
                                            {{ form.errors[`availabilities.${index}.start_date`] }}
                                        </div>
                                    </div>

                                    <!-- End Date -->
                                    <div>
                                        <label class="block font-medium mb-1">End Date</label>
                                        <input
                                            type="datetime-local"
                                            v-model="availability.end_date"
                                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="form.processing"
                                            required
                                        />
                                        <div v-if="form.errors[`availabilities.${index}.end_date`]" class="text-red-600 text-sm mt-1">
                                            {{ form.errors[`availabilities.${index}.end_date`] }}
                                        </div>
                                    </div>

                                    <!-- ✅ Seats Available (renamed from Number of Sessions) -->
                                    <div>
                                        <label class="block font-medium mb-1">Seats Available</label>
                                        <input
                                            type="number"
                                            v-model.number="availability.capacity"
                                            min="1"
                                            max="1000"
                                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="form.processing"
                                            required
                                        />
                                        <p class="text-xs text-gray-500 mt-1">Maximum number of seats available for this schedule</p>
                                        <div v-if="form.errors[`availabilities.${index}.capacity`]" class="text-red-600 text-sm mt-1">
                                            {{ form.errors[`availabilities.${index}.capacity`] }}
                                        </div>
                                    </div>

                                    <!-- ✅ Sessions (new separate field) -->
                                    <div>
                                        <label class="block font-medium mb-1">Sessions</label>
                                        <input
                                            type="number"
                                            v-model.number="availability.sessions"
                                            min="1"
                                            max="100"
                                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="form.processing"
                                            required
                                        />
                                        <p class="text-xs text-gray-500 mt-1">Number of sessions in this schedule</p>
                                        <div v-if="form.errors[`availabilities.${index}.sessions`]" class="text-red-600 text-sm mt-1">
                                            {{ form.errors[`availabilities.${index}.sessions`] }}
                                        </div>
                                    </div>

                                    <!-- Notes (now spans full width) -->
                                    <div class="md:col-span-2">
                                        <label class="block font-medium mb-1">Notes (Optional)</label>
                                        <input
                                            type="text"
                                            v-model="availability.notes"
                                            placeholder="Special instructions or requirements"
                                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="form.processing"
                                        />
                                        <div v-if="form.errors[`availabilities.${index}.notes`]" class="text-red-600 text-sm mt-1">
                                            {{ form.errors[`availabilities.${index}.notes`] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.availabilities" class="text-red-600 text-sm mt-2">
                            {{ form.errors.availabilities }}
                        </div>
                    </div>

                    <!-- Course Image -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Course Image (Optional)</label>
                        <input
                            type="file"
                            @input="form.image = $event.target.files[0]"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.image" class="text-red-600 text-sm mt-1">{{ form.errors.image }}</div>

                        <div v-if="props.course.image_path" class="mt-2">
                            <p class="text-sm text-gray-600">Current image:</p>
                            <img
                                :src="`/storage/${props.course.image_path}`"
                                alt="Current course image"
                                class="mt-1 h-32 w-full sm:w-48 object-cover rounded"
                            />
                        </div>
                    </div>

                    <!-- Course Description -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Description</label>
                        <Editor
                            v-model="form.description"
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
                        <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 mb-4">
                    <button
                        type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto"
                    >
                        Update Course
                    </button>
                    <Link
                        href="/admin/courses"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition w-full sm:w-auto text-center mt-2 sm:mt-0"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
