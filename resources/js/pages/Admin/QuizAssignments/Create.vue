<template>
    <AdminLayout>
        <Head title="Assign Quiz to Users" />

        <div class="py-12 dark">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-card overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h2 class="text-2xl font-semibold mb-6 text-foreground">Assign Quiz to Users</h2>

                        <!-- Success/Error Messages -->
                        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ $page.props.flash.error }}
                        </div>

                        <form @submit.prevent="submitAssignment">
                            <!-- Quiz Selection -->
                            <div class="mb-6">
                                <label for="quiz" class="block text-sm font-medium text-foreground mb-2">
                                    Select Quiz <span class="text-red-500">*</span>
                                </label>
                                <select
  id="quiz"
  v-model="form.quiz_id"
  class="w-full border-border rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-card text-foreground"
>

                                    <option value="">-- Select a Quiz --</option>
                                    <option
                                        v-for="quiz in quizzes"
                                        :key="quiz.id"
                                        :value="quiz.id"
                                    >
                                        {{ quiz.title }}
                                    </option>
                                </select>
                                <div v-if="form.errors.quiz_id" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.quiz_id }}
                                </div>
                            </div>

                            <!-- Department Filter -->
                            <div class="mb-6">
                                <label for="department" class="block text-sm font-medium text-foreground mb-2">
                                    Filter by Department
                                </label>
                                <select
                                    id="department"
                                    v-model="selectedDepartmentId"
                                    @change="filterByDepartment"
  class="w-full border-border rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-card text-foreground"
                                >
                                    <option value="">-- All Departments --</option>
                                    <option
                                        v-for="department in departments"
                                        :key="department.id"
                                        :value="department.id"
                                    >
                                        {{ department.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- User Selection -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-foreground">
                                        Select Users <span class="text-red-500">*</span>
                                    </label>
                                    <div class="text-sm">
                                        <button
                                            type="button"
                                            @click="selectAll"
                                            class="text-indigo-600 hover:text-indigo-800 mr-3"
                                        >
                                            Select All
                                        </button>
                                        <button
                                            type="button"
                                            @click="deselectAll"
                                            class="text-indigo-600 hover:text-indigo-800"
                                        >
                                            Deselect All
                                        </button>
                                    </div>
                                </div>

                                <div class="border border-border rounded-md p-4 max-h-96 overflow-y-auto bg-accent">
                                    <div v-if="users.length === 0" class="text-muted-foreground text-center py-4">
                                        No users found. Please select a department or check your filters.
                                    </div>
                                    <div v-else class="space-y-2">
                                        <div
                                            v-for="user in users"
                                            :key="user.id"
                                            class="flex items-center p-2 hover:bg-muted rounded cursor-pointer"
                                        >
                                            <input
                                                :id="`user-${user.id}`"
                                                type="checkbox"
                                                :value="user.id"
                                                v-model="form.user_ids"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-border rounded"
                                            />
                                            <label
                                                :for="`user-${user.id}`"
                                                class="ml-3 flex-grow cursor-pointer text-foreground"
                                            >
                                                <div class="text-sm font-medium">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ user.email }} 
                                                    <span v-if="user.department" class="ml-2">
                                                        ({{ user.department.name }})
                                                    </span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="form.errors.user_ids" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.user_ids }}
                                </div>
                                <div class="text-sm text-muted-foreground mt-2">
                                    {{ form.user_ids.length }} user(s) selected
                                </div>
                            </div>

                            <!-- Notification Options -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-foreground mb-2">
                                    Notification <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input
                                            id="notify-none"
                                            type="radio"
                                            value="none"
                                            v-model="form.send_notification"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-border"
                                        />
                                        <label for="notify-none" class="ml-3 text-sm text-foreground cursor-pointer">
                                            Don't send notification (assign only)
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            id="notify-email"
                                            type="radio"
                                            value="email_now"
                                            v-model="form.send_notification"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-border"
                                        />
                                        <label for="notify-email" class="ml-3 text-sm text-foreground cursor-pointer">
                                            Send email notification now
                                        </label>
                                    </div>
                                </div>
                                <div v-if="form.errors.send_notification" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.send_notification }}
                                </div>
                            </div>

                            <!-- Progress Indicator -->
                            <div v-if="form.processing" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Processing assignment...</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-3">
                                <Link
                                    href="/admin/quiz-assignments"
                                    class="px-4 py-2 border border-border rounded-md text-foreground hover:bg-muted"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Assigning...' : 'Assign Quiz' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
// No changes here, keep as is
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

// Props
const props = defineProps({
    departments: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    },
    quizzes: {
        type: Array,
        default: () => []
    },
    selectedDepartmentId: {
        type: [Number, String, null],
        default: null
    }
});

// Form data
const form = useForm({
    quiz_id: '',
    user_ids: [],
    send_notification: 'none'
});

// Department filter
const selectedDepartmentId = ref(props.selectedDepartmentId || '');

// Methods
const filterByDepartment = () => {
    router.get(
        route('admin.quiz-assignments.create'),
        { department_id: selectedDepartmentId.value },
        { preserveState: true, preserveScroll: true }
    );
};

const selectAll = () => {
    form.user_ids = props.users.map(user => user.id);
};

const deselectAll = () => {
    form.user_ids = [];
};

const submitAssignment = () => {
    form.post(route('admin.quiz-assignments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('user_ids', 'send_notification');
        }
    });
};
</script>

<style scoped>
/* No changes required here */
</style>
