<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header with Add Button -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Quiz Attempts</h1>
                <div class="flex flex-col gap-2 sm:flex-row sm:gap-3">
                    <Button as-child>
                        <Link :href="route('admin.quizzes.create')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create Quiz
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('admin.quiz-attempts.index')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Quiz-attempts
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Filter Quizzes</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:gap-6">
                        <div class="space-y-2">
                            <Label for="course_id">Course</Label>
                            <Select v-model="filters.course_id" :disabled="isLoading" @update:model-value="applyFilters">
                                <SelectTrigger id="course_id">
                                    <SelectValue placeholder="All Courses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Courses</SelectItem>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="filters.status" :disabled="isLoading" @update:model-value="applyFilters">
                                <SelectTrigger id="status">
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                    <SelectItem value="archived">Archived</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="mt-4 flex justify-end">
                        <Button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            variant="outline"
                            size="sm"
                            :disabled="isLoading"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Quizzes Table or Empty State -->
            <Card>
                <CardContent class="p-0">
                    <!-- Show table when quizzes exist -->
                    <div v-if="quizzes.data && quizzes.data.length > 0" class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Title
                                </th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Course
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Status
                                </th>
                                <th scope="col" class="hidden md:table-cell px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Questions
                                </th>
                                <th scope="col" class="hidden md:table-cell px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Attempts
                                </th>
                                <th scope="col" class="hidden lg:table-cell px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Total Points
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider sm:px-6">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                            <tr
                                v-for="quiz in quizzes.data"
                                :key="quiz.id"
                                class="hover:bg-muted/50 transition-colors duration-150"
                            >
                                <td class="px-4 py-4 text-sm font-medium text-foreground sm:px-6">
                                    <div class="max-w-xs truncate">{{ quiz.title }}</div>
                                    <!-- Mobile: Show course name below title -->
                                    <div class="sm:hidden text-xs text-muted-foreground mt-1">
                                        {{ quiz.course ? quiz.course.name : 'N/A' }}
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell px-4 py-4 text-sm text-muted-foreground sm:px-6">
                                    <div class="max-w-xs truncate">{{ quiz.course ? quiz.course.name : 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm sm:px-6">
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': quiz.status === 'published',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': quiz.status === 'draft',
                                            'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': quiz.status === 'archived',
                                        }"
                                    >
                                        {{ quiz.status.charAt(0).toUpperCase() + quiz.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="hidden md:table-cell px-4 py-4 text-sm text-muted-foreground sm:px-6">
                                    {{ quiz.questions_count }}
                                </td>
                                <td class="hidden md:table-cell px-4 py-4 text-sm text-muted-foreground sm:px-6">
                                    {{ quiz.attempts_count }}
                                </td>
                                <td class="hidden lg:table-cell px-4 py-4 text-sm text-muted-foreground sm:px-6">
                                    {{ quiz.total_points }}
                                </td>
                                <td class="px-4 py-4 text-sm font-medium sm:px-6">
                                    <div class="flex items-center gap-1 sm:gap-2">
                                        <Button variant="outline" size="sm" as-child>
                                            <Link :href="route('admin.quizzes.show', quiz.id)">
                                                <svg class="w-4 h-4 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span class="hidden sm:inline">View</span>
                                            </Link>
                                        </Button>
                                        <Button variant="outline" size="sm" as-child>
                                            <Link :href="route('admin.quizzes.edit', quiz.id)">
                                                <svg class="w-4 h-4 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span class="hidden sm:inline">Edit</span>
                                            </Link>
                                        </Button>
                                        <Button
                                            v-if="!quiz.attempts_count"
                                            @click="confirmDelete(quiz.id)"
                                            variant="outline"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="isLoading"
                                        >
                                            <svg class="w-4 h-4 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span class="hidden sm:inline">Delete</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State - Show when no quizzes found -->
                    <div v-else class="text-center py-12 px-4">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-muted mb-4">
                            <svg class="h-8 w-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>

                        <!-- Dynamic message based on filter state -->
                        <h3 class="text-lg font-semibold text-foreground mb-2">
                            {{ hasActiveFilters ? 'No Quizzes Match Your Filters' : 'No Quizzes Available' }}
                        </h3>

                        <p class="text-muted-foreground mb-6 max-w-sm mx-auto text-sm">
                            <span v-if="hasActiveFilters">
                                {{ getFilteredEmptyMessage() }}
                            </span>
                            <span v-else>
                                Get started by creating your first quiz to assess student knowledge and engagement.
                            </span>
                        </p>

                        <!-- Action buttons -->
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <Button as-child>
                                <Link :href="route('admin.quizzes.create')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create First Quiz
                                </Link>
                            </Button>

                            <Button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                variant="outline"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination - Only show when there are quizzes -->
            <div v-if="quizzes.data && quizzes.data.length > 0 && quizzes.meta" class="mt-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing <span class="font-medium text-foreground">{{ quizzes.meta.from || 0 }}</span> to
                        <span class="font-medium text-foreground">{{ quizzes.meta.to || 0 }}</span> of
                        <span class="font-medium text-foreground">{{ quizzes.meta.total || 0 }}</span> quizzes
                    </div>
                    <div class="flex flex-wrap gap-1 justify-center sm:justify-end">
                        <Button
                            v-for="link in quizzes.meta.links"
                            :key="link.label"
                            :disabled="!link.url || isLoading"
                            @click="goToPage(link.url)"
                            :variant="link.active ? 'default' : 'outline-solid'"
                            size="sm"
                            v-html="link.label"
                        ></Button>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <Modal :show="showDeleteModal" @close="showDeleteModal = false">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-semibold text-foreground mb-3">Confirm Deletion</h2>
                    <p class="text-sm text-muted-foreground mb-6">
                        Are you sure you want to delete this quiz? This action cannot be undone.
                    </p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button
                            @click="showDeleteModal = false"
                            variant="outline"
                            :disabled="isLoading"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="deleteQuiz"
                            variant="destructive"
                            :disabled="isLoading"
                        >
                            <span v-if="isLoading" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                </svg>
                                Deleting...
                            </span>
                            <span v-else>Delete</span>
                        </Button>
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
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default {
    components: {
        AdminLayout,
        Link,
        Modal,
        Button,
        Card,
        CardContent,
        CardHeader,
        CardTitle,
        Label,
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
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
            
            // Filter out "all" values before sending to server
            const filteredParams = Object.fromEntries(
                Object.entries(filters.value).filter(([key, value]) => value && value !== 'all')
            );
            
            router.get(
                route('admin.quizzes.index'),
                filteredParams,
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
