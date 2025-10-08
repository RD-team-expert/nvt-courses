<template>
    <Head :title="course.name" />

    <div class="min-h-screen bg-gray-50 dark:bg-background">
        <!-- Course Header -->
        <div class="bg-white dark:bg-card shadow-sm border-b dark:border-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('user.courses.index')"
                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            My Courses
                        </Link>
                        <div class="flex items-center space-x-3">
                            <img
                                v-if="course.image_path"
                                :src="course.image_path"
                                :alt="course.name"
                                class="w-12 h-12 rounded-lg object-cover border dark:border-border"
                            />
                            <div v-else class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-foreground">{{ course.name }}</h1>
                                <p class="text-gray-600 dark:text-muted-foreground">{{ course.difficulty_level }} • {{ course.estimated_duration }}min</p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Overview -->
                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-muted-foreground mb-1">Course Progress</div>
                        <div class="flex items-center space-x-2">
                            <div class="w-32 bg-gray-200 dark:bg-muted rounded-full h-2">
                                <div
                                    class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: userProgress.overall_percentage + '%' }"
                                ></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-foreground">
                                {{ userProgress.overall_percentage }}%
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-muted-foreground mt-1">
                            {{ userProgress.completed_modules }}/{{ course.modules.length }} modules completed
                        </div>
                    </div>
                </div>

                <p v-if="course.description" class="text-gray-700 dark:text-muted-foreground mt-4 max-w-4xl">
                    {{ course.description }}
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Course Modules -->
                <div class="lg:col-span-3">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-foreground mb-6">Course Modules</h2>

                    <div class="space-y-4">
                        <div
                            v-for="(module, moduleIndex) in course.modules"
                            :key="module.id"
                            class="bg-white dark:bg-card rounded-lg shadow-sm border dark:border-border overflow-hidden"
                        >
                            <!-- Module Header -->
                            <div
                                class="p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-muted/50 transition-colors duration-200"
                                @click="toggleModule(module.id)"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 flex-1">
                                        <div class="flex items-center space-x-3">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-sm font-bold">
                                                {{ module.order_number }}
                                            </span>
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-foreground">{{ module.name }}</h3>
                                                <p v-if="module.description" class="text-sm text-gray-600 dark:text-muted-foreground mt-1">{{ module.description }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-4">
                                        <!-- Module Progress -->
                                        <div v-if="module.progress" class="text-right">
                                            <div class="text-xs text-gray-500 dark:text-muted-foreground">Progress</div>
                                            <div class="flex items-center space-x-2 mt-1">
                                                <div class="w-16 bg-gray-200 dark:bg-muted rounded-full h-1.5">
                                                    <div
                                                        class="bg-green-500 h-1.5 rounded-full transition-all duration-300"
                                                        :style="{ width: module.progress.percentage + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-xs font-medium text-gray-700 dark:text-foreground">
                                                    {{ module.progress.percentage }}%
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Module Status -->
                                        <div class="flex items-center space-x-2">
                                            <span v-if="module.is_locked" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900/20 text-gray-600 dark:text-gray-400">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                                Locked
                                            </span>
                                            <span v-else-if="module.is_completed" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Completed
                                            </span>
                                            <span v-else-if="module.is_started" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1" />
                                                </svg>
                                                In Progress
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900/20 text-gray-600 dark:text-gray-400">
                                                Not Started
                                            </span>
                                        </div>

                                        <!-- Expand/Collapse -->
                                        <svg
                                            class="w-5 h-5 text-gray-400 dark:text-muted-foreground transition-transform duration-200"
                                            :class="{ 'rotate-180': expandedModules.includes(module.id) }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Module Content -->
                            <div v-show="expandedModules.includes(module.id)" class="border-t dark:border-border">
                                <div v-if="module.content && module.content.length > 0" class="divide-y divide-gray-200 dark:divide-border">
                                    <div
                                        v-for="(content, contentIndex) in module.content"
                                        :key="content.id"
                                        class="p-4 hover:bg-gray-50 dark:hover:bg-muted/30 transition-colors duration-200"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4 flex-1">
                                                <!-- Content Type Icon -->
                                                <div class="flex-shrink-0">
                                                    <div v-if="content.content_type === 'video'" class="w-10 h-10 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <div v-else class="w-10 h-10 bg-red-100 dark:bg-red-900/20 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                </div>

                                                <!-- Content Info -->
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-sm font-medium text-gray-900 dark:text-foreground">{{ content.title }}</h4>
                                                    <div class="flex items-center space-x-4 mt-1">
                                                        <span class="text-xs text-gray-500 dark:text-muted-foreground">
                                                            {{ content.content_type.toUpperCase() }}
                                                        </span>
                                                        <span v-if="content.duration" class="text-xs text-gray-500 dark:text-muted-foreground">
                                                            {{ Math.floor(content.duration / 60) }}:{{ String(content.duration % 60).padStart(2, '0') }}
                                                        </span>
                                                        <span v-if="content.is_required" class="text-xs text-red-600 dark:text-red-400">
                                                            Required
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Content Actions -->
                                            <div class="flex items-center space-x-3">
                                                <!-- Progress Indicator -->
                                                <div v-if="content.progress" class="flex items-center space-x-2">
                                                    <div class="w-12 bg-gray-200 dark:bg-muted rounded-full h-1">
                                                        <div
                                                            class="bg-blue-500 h-1 rounded-full transition-all duration-300"
                                                            :style="{ width: content.progress.percentage + '%' }"
                                                        ></div>
                                                    </div>
                                                    <span class="text-xs text-gray-600 dark:text-muted-foreground">
                                                        {{ content.progress.percentage }}%
                                                    </span>
                                                </div>

                                                <!-- Action Button -->
                                                <button
                                                    v-if="!content.is_locked"
                                                    @click="openContent(content)"
                                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                                                >
                                                    {{ content.progress?.is_completed ? 'Review' : (content.content_type === 'video' ? 'Watch' : 'Read') }}
                                                </button>
                                                <span v-else class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-400 dark:text-muted-foreground">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    Locked
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty Module -->
                                <div v-else class="p-8 text-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-muted-foreground">This module doesn't have any content yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-6">
                        <!-- Course Stats -->
                        <div class="bg-white dark:bg-card rounded-lg shadow-sm border dark:border-border p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-4">Course Overview</h3>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Total Modules</dt>
                                    <dd class="text-2xl font-bold text-gray-900 dark:text-foreground">{{ course.modules.length }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Completed</dt>
                                    <dd class="text-2xl font-bold text-green-600 dark:text-green-400">{{ userProgress.completed_modules }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-muted-foreground">Time Spent</dt>
                                    <dd class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ userProgress.time_spent }}h</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Next Up -->
                        <div v-if="nextContent" class="bg-white dark:bg-card rounded-lg shadow-sm border dark:border-border p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-4">Next Up</h3>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                    <svg v-if="nextContent.content_type === 'video'" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-foreground">{{ nextContent.title }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-muted-foreground">{{ nextContent.module_name }}</p>
                                </div>
                            </div>
                            <button
                                @click="openContent(nextContent)"
                                class="w-full mt-4 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                            >
                                Continue Learning
                            </button>
                        </div>

                        <!-- Course Certificate -->
                        <div v-if="userProgress.overall_percentage === 100" class="bg-white dark:bg-card rounded-lg shadow-sm border dark:border-border p-6">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-foreground mb-2">Congratulations!</h3>
                                <p class="text-sm text-gray-600 dark:text-muted-foreground mb-4">You've completed this course</p>
                                <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200">
                                    Download Certificate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'

// Props from controller
const props = defineProps({
    course: {
        type: Object,
        required: true
    },
    userProgress: {
        type: Object,
        required: true
    },
    nextContent: {
        type: Object,
        default: null
    }
})

// Component state
const expandedModules = ref([])

// Computed properties
const firstIncompleteModule = computed(() => {
    return props.course.modules.find(module => !module.is_completed)
})

// Methods
const toggleModule = (moduleId) => {
    const index = expandedModules.value.indexOf(moduleId)
    if (index > -1) {
        expandedModules.value.splice(index, 1)
    } else {
        expandedModules.value.push(moduleId)
    }
}

const openContent = (content) => {
    // Navigate to content viewer
    router.visit(route('user.content.show', content.id))
}

// Auto-expand first incomplete module
if (firstIncompleteModule.value) {
    expandedModules.value.push(firstIncompleteModule.value.id)
}
</script>
