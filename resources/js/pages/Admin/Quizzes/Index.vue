<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Quiz Attempts</h1>
                <div class="flex space-x-3">
                    <Link
                        :href="route('admin.quizzes.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Quiz
                    </Link>
                    <Link
                        :href="route('admin.quiz-attempts.index')"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors duration-200"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Quiz-attempts
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-8 bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Quizzes</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select
                            id="course_id"
                            v-model="filters.course_id"
                            class="block w-full border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3 transition-colors duration-200"
                            :disabled="isLoading"
                            @change="applyFilters"
                        >
                            <option value="">All Courses</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            id="status"
                            v-model="filters.status"
                            class="block w-full border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2 px-3 transition-colors duration-200"
                            :disabled="isLoading"
                            @change="applyFilters"
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <!-- ✅ Clear Filters Button -->
                <div class="mt-4 flex justify-end">
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors duration-200"
                        :disabled="isLoading"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- ✅ Quizzes Table or Empty State -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Show table when quizzes exist -->
                <div v-if="quizzes.data && quizzes.data.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Course
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Questions
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Attempts
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Total Points
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="quiz in quizzes.data"
                            :key="quiz.id"
                            class="hover:bg-gray-50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ quiz.title }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ quiz.course ? quiz.course.name : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': quiz.status === 'published',
                                        'bg-yellow-100 text-yellow-800': quiz.status === 'draft',
                                        'bg-red-100 text-red-800': quiz.status === 'archived',
                                    }"
                                >
                                    {{ quiz.status.charAt(0).toUpperCase() + quiz.status.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ quiz.questions_count }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ quiz.attempts_count }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ quiz.total_points }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap space-x-3">
                                <Link
                                    :href="route('admin.quizzes.show', quiz.id)"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold transition-colors duration-200"
                                >
                                    View
                                </Link>
                                <Link
                                    :href="route('admin.quizzes.edit', quiz.id)"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold transition-colors duration-200"
                                >
                                    Edit
                                </Link>
                                <button
                                    v-if="!quiz.attempts_count"
                                    @click="confirmDelete(quiz.id)"
                                    class="text-red-600 hover:text-red-800 font-semibold transition-colors duration-200"
                                    :disabled="isLoading"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ✅ Empty State - Show when no quizzes found -->
                <div v-else class="text-center py-12">
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-gray-100 mb-6">
                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <!-- Dynamic message based on filter state -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ hasActiveFilters ? 'No Quizzes Match Your Filters' : 'No Quizzes Available' }}
                    </h3>

                    <p class="text-gray-600 mb-6 max-w-sm mx-auto">
                        <span v-if="hasActiveFilters">
                            {{ getFilteredEmptyMessage() }}
                        </span>
                        <span v-else>
                            Get started by creating your first quiz to assess student knowledge and engagement.
                        </span>
                    </p>

                    <!-- Action buttons -->
                    <div class="flex justify-center space-x-4">
                        <Link
                            :href="route('admin.quizzes.create')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors duration-200"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create First Quiz
                        </Link>

                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors duration-200"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination - Only show when there are quizzes -->
            <div v-if="quizzes.data && quizzes.data.length > 0 && quizzes.meta" class="mt-8">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium">{{ quizzes.meta.from || 0 }}</span> to
                        <span class="font-medium">{{ quizzes.meta.to || 0 }}</span> of
                        <span class="font-medium">{{ quizzes.meta.total || 0 }}</span> quizzes
                    </div>
                    <div class="flex space-x-2">
                        <button
                            v-for="link in quizzes.meta.links"
                            :key="link.label"
                            :disabled="!link.url || isLoading"
                            @click="goToPage(link.url)"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium transition-colors duration-200"
                            :class="{
                                'bg-indigo-600 text-white border-indigo-600': link.active,
                                'bg-white text-gray-700 hover:bg-gray-100': !link.active && link.url,
                                'cursor-not-allowed opacity-50': !link.url || isLoading,
                            }"
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <Modal :show="showDeleteModal" @close="showDeleteModal = false">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Confirm Deletion</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Are you sure you want to delete this quiz? This action cannot be undone.
                    </p>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 text-sm font-medium transition-colors duration-200"
                            :disabled="isLoading"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deleteQuiz"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm font-medium transition-colors duration-200"
                            :disabled="isLoading"
                        >
                            <span v-if="isLoading" class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                </svg>
                                Deleting...
                            </span>
                            <span v-else>Delete</span>
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import AdminLayout from "@/layouts/AdminLayout.vue";

export default {
    components: {
        AdminLayout,
        Link,
        Modal,
    },
    props: {
        quizzes: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        courses: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Dashboard', route: 'admin.dashboard' },
            { name: 'Quizzes', route: null },
        ];

        // Reactive state for filters
        const filters = ref({
            course_id: props.filters.course_id || '',
            status: props.filters.status || '',
        });

        // State for delete modal and loading
        const showDeleteModal = ref(false);
        const quizToDelete = ref(null);
        const isLoading = ref(false);

        // ✅ Check if any filters are active
        const hasActiveFilters = computed(() => {
            return filters.value.course_id !== '' || filters.value.status !== '';
        });

        // ✅ Get selected course name for empty message
        const getSelectedCourseName = () => {
            const selectedCourse = props.courses.find(course => course.id == filters.value.course_id);
            return selectedCourse ? selectedCourse.name : '';
        };

        // ✅ Generate context-appropriate empty message
        const getFilteredEmptyMessage = () => {
            const courseName = getSelectedCourseName();
            const status = filters.value.status;

            if (courseName && status) {
                return `No ${status} quizzes found for "${courseName}" course.`;
            } else if (courseName) {
                return `No quizzes found for "${courseName}" course.`;
            } else if (status) {
                return `No ${status} quizzes found. Try selecting a different status or clearing filters.`;
            } else {
                return 'Try adjusting your filter criteria or clear all filters to see available quizzes.';
            }
        };

        // ✅ Clear all filters
        const clearFilters = () => {
            filters.value = {
                course_id: '',
                status: '',
            };
            applyFilters();
        };

        // Debounced filter application
        const applyFilters = debounce(() => {
            isLoading.value = true;
            router.get(
                route('admin.quizzes.index'),
                filters.value,
                {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => {
                        isLoading.value = false;
                    },
                }
            );
        }, 300);

        // Watch for filter changes
        watch(filters, applyFilters, { deep: true });

        // Navigate to a specific page
        const goToPage = (url) => {
            if (url && !isLoading.value) {
                isLoading.value = true;
                router.get(url, {}, {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => {
                        isLoading.value = false;
                    },
                });
            }
        };

        // Show delete confirmation modal
        const confirmDelete = (quizId) => {
            quizToDelete.value = quizId;
            showDeleteModal.value = true;
        };

        // Delete quiz
        const deleteQuiz = () => {
            if (isLoading.value) return;
            isLoading.value = true;
            router.delete(route('admin.quizzes.destroy', quizToDelete.value), {
                onSuccess: () => {
                    showDeleteModal.value = false;
                    quizToDelete.value = null;
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        };

        return {
            breadcrumbs,
            filters,
            applyFilters,
            goToPage,
            showDeleteModal,
            confirmDelete,
            deleteQuiz,
            isLoading,
            hasActiveFilters,
            getFilteredEmptyMessage,
            clearFilters,
        };
    },
};
</script>

<style scoped>
/* General Layout */
.max-w-7xl {
    @apply px-4 sm:px-6 lg:px-8;
}

/* Table Styling */
table {
    @apply w-full border-collapse;
}

th,
td {
    @apply text-left align-middle;
}

th {
    @apply font-semibold text-gray-600 text-xs uppercase tracking-wider;
}

tbody tr {
    @apply transition-colors duration-150;
}

tbody tr:hover {
    @apply bg-gray-50;
}

/* Status Badges */
.rounded-full {
    @apply px-2.5 py-1 text-xs font-semibold;
}

/* Buttons */
button:disabled,
button:disabled:hover {
    @apply opacity-50 cursor-not-allowed;
}

/* Empty State Styling */
.empty-state-icon {
    @apply transition-transform duration-200;
}

.empty-state-icon:hover {
    @apply transform scale-110;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
    table {
        @apply table-auto;
    }

    th,
    td {
        @apply px-3 py-2 text-xs;
    }

    .space-x-3 > :not([hidden]) ~ :not([hidden]) {
        @apply mx-1;
    }

    .text-sm {
        @apply text-xs;
    }

    .px-4 {
        @apply px-2;
    }

    .py-2 {
        @apply py-1;
    }
}

/* Modal Styling */
.modal-content {
    @apply transform transition-all duration-200;
}
</style>
