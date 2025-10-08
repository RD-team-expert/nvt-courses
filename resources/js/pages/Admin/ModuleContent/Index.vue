<template>
    <Head :title="`${module.name} - Module Details`" />

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('admin.course-modules.index', course.id)"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center mb-4"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Modules
                </Link>

                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-lg font-bold">
                                {{ module.order_number }}
                            </span>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-foreground">{{ module.name }}</h1>
                                <p class="text-gray-600 dark:text-muted-foreground">{{ course.name }}</p>
                            </div>
                        </div>
                        <p v-if="module.description" class="text-gray-700 dark:text-muted-foreground mt-2 max-w-3xl">
                            {{ module.description }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('admin.course-modules.edit', [course.id, module.id])"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-border rounded-md text-gray-700 dark:text-foreground bg-white dark:bg-background hover:bg-gray-50 dark:hover:bg-muted focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Module
                        </Link>
                        <button
                            @click="showAddContentModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-white dark:focus:ring-offset-background transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Content
                        </button>
                    </div>
                </div>
            </div>

            <!-- Module Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-card rounded-lg shadow border dark:border-border p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Total Content</dt>
                            <dd class="text-2xl font-bold text-gray-900 dark:text-foreground">{{ module.content_count }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card rounded-lg shadow border dark:border-border p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Videos</dt>
                            <dd class="text-2xl font-bold text-gray-900 dark:text-foreground">{{ module.video_count }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card rounded-lg shadow border dark:border-border p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">PDFs</dt>
                            <dd class="text-2xl font-bold text-gray-900 dark:text-foreground">{{ module.pdf_count }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-card rounded-lg shadow border dark:border-border p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Duration</dt>
                            <dd class="text-2xl font-bold text-gray-900 dark:text-foreground">{{ module.estimated_duration || 0 }}m</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Settings -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2">
                    <!-- Content List -->
                    <div class="bg-white dark:bg-card shadow rounded-lg border dark:border-border">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-border">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-foreground">Module Content</h3>
                                <span class="text-sm text-gray-500 dark:text-muted-foreground">
                                    {{ module.content.length }} items
                                </span>
                            </div>
                        </div>

                        <div v-if="module.content.length > 0" class="divide-y divide-gray-200 dark:divide-border">
                            <div
                                v-for="(content, index) in module.content"
                                :key="content.id"
                                class="p-6 hover:bg-gray-50 dark:hover:bg-muted/50 transition-colors duration-200"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 flex-1">
                                        <!-- Order Number -->
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-muted text-gray-600 dark:text-muted-foreground text-sm font-medium">
                                            {{ content.order_number }}
                                        </span>

                                        <!-- Content Type Icon -->
                                        <div class="flex-shrink-0">
                                            <svg v-if="content.content_type === 'video'" class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>

                                        <!-- Content Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-foreground">
                                                {{ content.title }}
                                            </h4>
                                            <div class="flex items-center space-x-4 mt-1">
                                                <span class="text-xs text-gray-500 dark:text-muted-foreground">
                                                    {{ content.content_type.toUpperCase() }}
                                                </span>
                                                <span v-if="content.duration" class="text-xs text-gray-500 dark:text-muted-foreground">
                                                    {{ content.formatted_duration }}
                                                </span>
                                                <span v-if="content.video?.name" class="text-xs text-gray-500 dark:text-muted-foreground">
                                                    {{ content.video.name }}
                                                </span>
                                                <span v-if="content.tasks_count > 0" class="text-xs text-blue-600 dark:text-blue-400">
                                                    {{ content.tasks_count }} tasks
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Content Status & Actions -->
                                    <div class="flex items-center space-x-3">
                                        <div class="flex items-center space-x-2">
                                            <span v-if="content.is_required" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400">
                                                Required
                                            </span>
                                            <span :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                                content.is_active
                                                    ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                                                    : 'bg-gray-100 dark:bg-gray-900/20 text-gray-600 dark:text-gray-400'
                                            ]">
                                                {{ content.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>

                                        <button
                                            @click="confirmDeleteContent(content)"
                                            class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors duration-200"
                                            title="Remove Content"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="p-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 dark:text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-2">No content yet</h3>
                            <p class="text-gray-600 dark:text-muted-foreground mb-6">Add videos or PDFs to make this module interactive.</p>
                            <button
                                @click="showAddContentModal = true"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-white dark:focus:ring-offset-background transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add First Content
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Module Info Sidebar -->
                <div class="space-y-6">
                    <!-- Module Settings -->
                    <div class="bg-white dark:bg-card shadow rounded-lg border dark:border-border p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-4">Module Settings</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Order Number</dt>
                                <dd class="text-sm text-gray-900 dark:text-foreground">{{ module.order_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Duration</dt>
                                <dd class="text-sm text-gray-900 dark:text-foreground">{{ module.estimated_duration || 'Not set' }} minutes</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Required</dt>
                                <dd class="text-sm">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                        module.is_required
                                            ? 'bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400'
                                            : 'bg-gray-100 dark:bg-gray-900/20 text-gray-600 dark:text-gray-400'
                                    ]">
                                        {{ module.is_required ? 'Yes' : 'No' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Status</dt>
                                <dd class="text-sm">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                        module.is_active
                                            ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                                            : 'bg-gray-100 dark:bg-gray-900/20 text-gray-600 dark:text-gray-400'
                                    ]">
                                        {{ module.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-card shadow rounded-lg border dark:border-border p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <button
                                @click="showAddContentModal = true"
                                class="w-full flex items-center px-3 py-2 text-sm text-gray-700 dark:text-foreground hover:bg-gray-50 dark:hover:bg-muted rounded-md transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Content
                            </button>
                            <Link
                                :href="route('admin.course-modules.edit', [course.id, module.id])"
                                class="w-full flex items-center px-3 py-2 text-sm text-gray-700 dark:text-foreground hover:bg-gray-50 dark:hover:bg-muted rounded-md transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Module
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Content Modal Placeholder -->
            <div v-if="showAddContentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-black dark:bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-card border-gray-300 dark:border-border">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-foreground">Add Content</h3>
                        <div class="mt-4 space-y-3">
                            <p class="text-sm text-gray-500 dark:text-muted-foreground">Choose content type to add to this module:</p>
                            <div class="flex space-x-3 justify-center">
                                <Link
                                    :href="'/admin/course-online/' + course.id + '/modules/' + module.id + '/content/create?type=video'"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                                >
                                    Add Video
                                </Link>
                                <Link
                                    :href="'/admin/course-online/' + course.id + '/modules/' + module.id + '/content/create?type=pdf'"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                                >
                                    Upload PDF
                                </Link>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button
                                @click="showAddContentModal = false"
                                class="px-4 py-2 bg-gray-200 dark:bg-muted text-gray-800 dark:text-foreground rounded-md hover:bg-gray-300 dark:hover:bg-muted/80"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Content Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-black dark:bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-card border-gray-300 dark:border-border">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-foreground mt-4">Remove Content</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500 dark:text-muted-foreground">
                                Are you sure you want to remove "<strong>{{ contentToDelete?.title }}</strong>"?
                                This action cannot be undone.
                            </p>
                        </div>
                        <div class="flex justify-center space-x-4 py-3">
                            <button
                                @click="showDeleteModal = false"
                                class="px-4 py-2 bg-gray-200 dark:bg-muted text-gray-800 dark:text-foreground text-base font-medium rounded-md shadow-sm hover:bg-gray-300 dark:hover:bg-muted/80"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteContent"
                                class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'

// Props from controller
const props = defineProps({
    course: {
        type: Object,
        required: true
    },
    module: {
        type: Object,
        required: true
    }
})

// Component state
const showAddContentModal = ref(false)
const showDeleteModal = ref(false)
const contentToDelete = ref(null)

// Delete functionality
const confirmDeleteContent = (content) => {
    contentToDelete.value = content
    showDeleteModal.value = true
}

const deleteContent = () => {
    if (contentToDelete.value) {
        // This will need the ModuleContent delete route when implemented
        router.delete(`/admin/course-online/${props.course.id}/modules/${props.module.id}/content/${contentToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                contentToDelete.value = null
            }
        })
    }
}
</script>
