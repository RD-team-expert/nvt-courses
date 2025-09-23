<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Evaluation Configurations</h1>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Category
                </button>
            </div>

            <!-- Categories List -->
            <div v-if="hasConfigs" class="space-y-6">
                <div
                    v-for="config in configs"
                    :key="config?.id || Math.random()"
                    class="rounded-xl bg-white p-6 shadow-sm border border-gray-200"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ config?.name || 'Unnamed Category' }}
                            </h3>
                            <p class="text-sm text-gray-600">Max Score: {{ config?.max_score || 0 }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="editConfig(config)"
                                class="inline-flex items-center rounded-lg bg-yellow-100 px-3 py-1 text-sm font-semibold text-yellow-700 transition-colors duration-200 hover:bg-yellow-200"
                            >
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </button>
                            <button
                                @click="confirmDeleteCategory(config)"
                                class="inline-flex items-center rounded-lg bg-red-100 px-3 py-1 text-sm font-semibold text-red-700 transition-colors duration-200 hover:bg-red-200"
                            >
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Types Section -->
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-700">Evaluation Types</h4>
                            <button
                                @click="showTypeModal(config)"
                                class="inline-flex items-center rounded-lg bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700 transition-colors duration-200 hover:bg-blue-200"
                            >
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Type
                            </button>
                        </div>

                        <div v-if="config?.types && config.types.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="type in config.types"
                                :key="type?.id || Math.random()"
                                class="flex items-center justify-between rounded-lg bg-gray-50 p-3 border border-gray-100 hover:border-gray-200 transition-colors duration-200"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ type?.type_name || 'Unnamed Type' }}</p>
                                    <p class="text-xs text-gray-600">Score: {{ type?.score_value || 0 }}</p>
                                </div>
                                <button
                                    @click="confirmDeleteType(type)"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200 hover:text-red-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500"
                                    title="Delete type"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No evaluation types configured</p>
                            <button
                                @click="showTypeModal(config)"
                                class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                Add your first type
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl bg-white p-12 shadow-sm border border-gray-200 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 mb-4">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v1a2 2 0 002 2h2m0-6h6a2 2 0 012 2v1a2 2 0 01-2 2H9m0-6v6m0 0h6m-6 0v4a2 2 0 002 2h4a2 2 0 002-2v-4m-6 0h6" />
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No evaluation configurations</h3>
                <p class="text-gray-600 mb-6">Get started by creating your first evaluation category to organize your assessment criteria.</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create First Category
                </button>
            </div>

            <!-- Total Score Configuration -->
            <div v-if="hasConfigs" class="mt-8 rounded-xl bg-white p-6 shadow-sm border border-gray-200">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-lg font-semibold text-gray-900">Total Score Configuration</h2>
                        <p class="text-sm text-gray-600">Set the overall scoring distribution across categories</p>
                    </div>
                </div>
                <form @submit.prevent="setTotalScore" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="lg:col-span-1">
                            <label for="total_score" class="block text-sm font-medium text-gray-700 mb-1">Total Score</label>
                            <input
                                id="total_score"
                                v-model.number="totalScoreForm.total_score"
                                type="number"
                                min="1"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                placeholder="Enter total score"
                            />
                        </div>
                        <div
                            v-for="config in configs"
                            :key="`total_${config?.id}`"
                            class="space-y-1"
                        >
                            <label :for="`config_score_${config?.id}`" class="block text-sm font-medium text-gray-700">
                                {{ config?.name || 'Unnamed' }}
                            </label>
                            <input
                                :id="`config_score_${config?.id}`"
                                v-model.number="totalScoreForm.config_scores[config?.id]"
                                type="number"
                                min="0"
                                :max="config?.max_score || 0"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :placeholder="`Max: ${config?.max_score || 0}`"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                            :disabled="totalScoreForm.processing"
                        >
                            <span v-if="totalScoreForm.processing" class="flex items-center">
                                <svg class="mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                </svg>
                                Saving...
                            </span>
                            <span v-else>Save Total Score</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Incentives Configuration -->
            <div class="mt-8 rounded-xl bg-white p-6 shadow-sm border border-gray-200">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-lg font-semibold text-gray-900">Incentives Configuration</h2>
                        <p class="text-sm text-gray-600">Define reward tiers based on performance scores</p>
                    </div>
                </div>
                <form @submit.prevent="setIncentives" class="space-y-4">
                    <div class="space-y-3">
                        <div
                            v-for="(incentive, index) in incentiveForm.incentives"
                            :key="index"
                            class="relative flex flex-col sm:flex-row sm:items-end space-y-3 sm:space-y-0 sm:space-x-4 p-4 border border-gray-200 rounded-lg bg-gradient-to-r from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 transition-all duration-200"
                        >
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label :for="`min_score_${index}`" class="block text-sm font-medium text-gray-700 mb-1">Min Score</label>
                                    <input
                                        :id="`min_score_${index}`"
                                        v-model.number="incentive.min_score"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label :for="`max_score_${index}`" class="block text-sm font-medium text-gray-700 mb-1">Max Score</label>
                                    <input
                                        :id="`max_score_${index}`"
                                        v-model.number="incentive.max_score"
                                        type="number"
                                        :min="incentive.min_score || 0"
                                        placeholder="100"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label :for="`incentive_amount_${index}`" class="block text-sm font-medium text-gray-700 mb-1">Amount ($)</label>
                                    <input
                                        :id="`incentive_amount_${index}`"
                                        v-model.number="incentive.incentive_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                            <button
                                @click.prevent="confirmRemoveIncentive(index)"
                                type="button"
                                class="inline-flex items-center rounded-lg bg-red-100 px-3 py-2 text-sm font-semibold text-red-700 transition-colors duration-200 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-sm"
                                :disabled="incentiveForm.incentives.length <= 1"
                                :class="{ 'opacity-50 cursor-not-allowed': incentiveForm.incentives.length <= 1 }"
                            >
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 pt-4 border-t border-gray-200">
                        <button
                            @click.prevent="addIncentive"
                            type="button"
                            class="inline-flex items-center rounded-lg bg-green-100 px-4 py-2 text-sm font-semibold text-green-700 transition-colors duration-200 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-sm"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Incentive Tier
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                            :disabled="incentiveForm.processing"
                        >
                            <span v-if="incentiveForm.processing" class="flex items-center">
                                <svg class="mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                </svg>
                                Saving...
                            </span>
                            <span v-else">Save Incentives</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create/Edit Category Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModals" max-width="md">
            <div class="p-6 sm:p-8">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ showEditModal ? 'Edit Category' : 'Create New Category' }}
                        </h2>
                        <p class="text-sm text-gray-600">
                            {{ showEditModal ? 'Update the category information below' : 'Add a new evaluation category to organize your assessments' }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="showEditModal ? updateCategory() : createCategory()" class="space-y-6">
                    <div>
                        <label for="category_name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                        <input
                            id="category_name"
                            v-model="categoryForm.name"
                            type="text"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 placeholder-gray-400"
                            placeholder="Enter a descriptive category name"
                            required
                        />
                        <span v-if="categoryForm.errors.name" class="mt-2 flex items-center text-sm text-red-600">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ categoryForm.errors.name }}
                        </span>
                    </div>

                    <div>
                        <label for="max_score" class="block text-sm font-medium text-gray-700 mb-2">Maximum Score</label>
                        <input
                            id="max_score"
                            v-model.number="categoryForm.max_score"
                            type="number"
                            min="1"
                            max="1000"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 placeholder-gray-400"
                            placeholder="Enter the maximum possible score"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">The highest score that can be assigned in this category</p>
                        <span v-if="categoryForm.errors.max_score" class="mt-2 flex items-center text-sm text-red-600">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ categoryForm.errors.max_score }}
                        </span>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <button
                            @click="closeModals"
                            type="button"
                            class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                            :disabled="categoryForm.processing"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                            :disabled="categoryForm.processing"
                        >
                            <span v-if="categoryForm.processing" class="flex items-center">
                                <svg class="mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                </svg>
                                {{ showEditModal ? 'Updating...' : 'Creating...' }}
                            </span>
                            <span v-else>
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ showEditModal ? 'Update Category' : 'Create Category' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Add Type Modal -->
        <Modal :show="showAddTypeModal" @close="closeTypeModal" max-width="md">
            <div class="p-6 sm:p-8">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-semibold text-gray-900">Add Evaluation Type</h2>
                        <p class="text-sm text-gray-600">
                            Create a new evaluation type for
                            <span class="font-medium">{{ selectedConfig?.name || 'this category' }}</span>
                        </p>
                    </div>
                </div>

                <form @submit.prevent="addType" class="space-y-6">
                    <div>
                        <label for="type_name" class="block text-sm font-medium text-gray-700 mb-2">Type Name</label>
                        <input
                            id="type_name"
                            v-model="typeForm.type_name"
                            type="text"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 placeholder-gray-400"
                            placeholder="e.g., Excellent, Good, Fair, Poor"
                            required
                        />
                        <span v-if="typeForm.errors.type_name" class="mt-2 flex items-center text-sm text-red-600">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ typeForm.errors.type_name }}
                        </span>
                    </div>

                    <div>
                        <label for="score_value" class="block text-sm font-medium text-gray-700 mb-2">Score Value</label>
                        <input
                            id="score_value"
                            v-model.number="typeForm.score_value"
                            type="number"
                            min="0"
                            :max="selectedConfig?.max_score || 0"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 placeholder-gray-400"
                            :placeholder="`Enter score (0 - ${selectedConfig?.max_score || 0})`"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Maximum allowed: {{ selectedConfig?.max_score || 0 }} points
                        </p>
                        <span v-if="typeForm.errors.score_value" class="mt-2 flex items-center text-sm text-red-600">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ typeForm.errors.score_value }}
                        </span>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <button
                            @click="closeTypeModal"
                            type="button"
                            class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                            :disabled="typeForm.processing"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm"
                            :disabled="typeForm.processing"
                        >
                            <span v-if="typeForm.processing" class="flex items-center">
                                <svg class="mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                </svg>
                                Adding...
                            </span>
                            <span v-else>
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Add Type
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Category Confirmation Modal -->
        <Modal :show="showDeleteCategoryModal" @close="showDeleteCategoryModal = false" max-width="md">
            <div class="p-6 sm:p-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Evaluation Category</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Are you sure you want to delete
                            <span class="font-semibold text-gray-900">"{{ categoryToDelete?.name || 'this category' }}"</span>?
                        </p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-red-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-sm text-red-700">
                                    <p class="font-medium mb-1">This action cannot be undone.</p>
                                    <p>This will permanently delete:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>The evaluation category</li>
                                        <li>All associated evaluation types</li>
                                        <li>Any related scoring configurations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                @click="showDeleteCategoryModal = false"
                                class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteCategory"
                                class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-sm"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Type Confirmation Modal -->
        <Modal :show="showDeleteTypeModal" @close="showDeleteTypeModal = false" max-width="sm">
            <div class="p-6 sm:p-8">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Evaluation Type</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Are you sure you want to delete the evaluation type
                        <span class="font-semibold text-gray-900">"{{ typeToDelete?.type_name || 'this type' }}"</span>?
                        This action cannot be undone.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <button
                            @click="showDeleteTypeModal = false"
                            class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deleteType"
                            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-sm"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Type
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Remove Incentive Confirmation Modal -->
        <Modal :show="showRemoveIncentiveModal" @close="showRemoveIncentiveModal = false" max-width="sm">
            <div class="p-6 sm:p-8">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Remove Incentive Tier</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Are you sure you want to remove this incentive tier? This action cannot be undone.
                    </p>
                    <div class="flex justify-center space-x-3">
                        <button
                            @click="showRemoveIncentiveModal = false"
                            class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                        >
                            Cancel
                        </button>
                        <button
                            @click="removeIncentive"
                            class="inline-flex items-center rounded-lg bg-yellow-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 shadow-sm"
                        >
                            Remove Tier
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'

export default {
    components: {
        AdminLayout,
        Modal,
    },
    props: {
        configs: {
            type: Array,
            default: () => [],
        },
        incentives: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        // Reactive states
        const showCreateModal = ref(false)
        const showEditModal = ref(false)
        const showAddTypeModal = ref(false)
        const showDeleteCategoryModal = ref(false)
        const showDeleteTypeModal = ref(false)
        const showRemoveIncentiveModal = ref(false)

        const selectedConfig = ref(null)
        const categoryToDelete = ref(null)
        const typeToDelete = ref(null)
        const incentiveIndexToRemove = ref(null)

        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Dashboard', route: 'admin.dashboard' },
            { name: 'Evaluations', route: null },
        ]

        // Forms
        const categoryForm = useForm({
            name: '',
            max_score: 0,
        })

        const typeForm = useForm({
            type_name: '',
            score_value: 0,
        })

        const totalScoreForm = useForm({
            total_score: 0,
            config_scores: {},
        })

        // Initialize incentive form with existing data or default
        const incentiveForm = useForm({
            incentives: props.incentives && props.incentives.length > 0
                ? props.incentives.map(incentive => ({
                    min_score: incentive.min_score || 0,
                    max_score: incentive.max_score || 0,
                    incentive_amount: parseFloat(incentive.incentive_amount) || 0
                }))
                : [{ min_score: 0, max_score: 0, incentive_amount: 0 }],
        })

        // Computed properties
        const hasConfigs = computed(() => {
            return props.configs &&
                Array.isArray(props.configs) &&
                props.configs.length > 0 &&
                props.configs.every(config => config && typeof config === 'object')
        })

        // Initialize forms with existing data
        if (hasConfigs.value) {
            props.configs.forEach(config => {
                if (config && config.id) {
                    totalScoreForm.config_scores[config.id] = config.max_score || 0
                }
            })
        }

        // Update incentive form when props change
        const updateIncentiveForm = () => {
            if (props.incentives && props.incentives.length > 0) {
                incentiveForm.incentives = props.incentives.map(incentive => ({
                    min_score: incentive.min_score || 0,
                    max_score: incentive.max_score || 0,
                    incentive_amount: parseFloat(incentive.incentive_amount) || 0
                }))
            } else if (incentiveForm.incentives.length === 0) {
                incentiveForm.incentives = [{ min_score: 0, max_score: 0, incentive_amount: 0 }]
            }
        }

        // Call update function on component mount
        updateIncentiveForm()

        // Methods
        const closeModals = () => {
            showCreateModal.value = false
            showEditModal.value = false
            categoryForm.reset()
            categoryForm.clearErrors()
        }

        const closeTypeModal = () => {
            showAddTypeModal.value = false
            typeForm.reset()
            typeForm.clearErrors()
            selectedConfig.value = null
        }

        const createCategory = () => {
            categoryForm.post(route('admin.evaluations.store'), {
                onSuccess: () => {
                    closeModals()
                },
            })
        }

        const editConfig = (config) => {
            if (!config) return
            categoryForm.name = config.name || ''
            categoryForm.max_score = config.max_score || 0
            selectedConfig.value = config
            showEditModal.value = true
        }

        const updateCategory = () => {
            if (!selectedConfig.value?.id) return
            categoryForm.put(route('admin.evaluations.update', selectedConfig.value.id), {
                onSuccess: () => {
                    closeModals()
                    selectedConfig.value = null
                },
            })
        }

        const confirmDeleteCategory = (config) => {
            categoryToDelete.value = config
            showDeleteCategoryModal.value = true
        }

        const deleteCategory = () => {
            if (!categoryToDelete.value?.id) return
            router.delete(route('admin.evaluations.destroy', categoryToDelete.value.id), {
                onSuccess: () => {
                    showDeleteCategoryModal.value = false
                    categoryToDelete.value = null
                },
            })
        }

        const showTypeModal = (config) => {
            if (!config) return
            selectedConfig.value = config
            typeForm.reset()
            typeForm.clearErrors()
            showAddTypeModal.value = true
        }

        const addType = () => {
            if (!selectedConfig.value?.id) return
            typeForm.post(route('admin.evaluations.types.store', selectedConfig.value.id), {
                onSuccess: () => {
                    closeTypeModal()
                },
            })
        }

        const confirmDeleteType = (type) => {
            typeToDelete.value = type
            showDeleteTypeModal.value = true
        }

        const deleteType = () => {
            if (!typeToDelete.value?.id) return
            router.delete(route('admin.evaluations.types.destroy', typeToDelete.value.id), {
                onSuccess: () => {
                    showDeleteTypeModal.value = false
                    typeToDelete.value = null
                },
                onError: (errors) => {
                    console.error('Delete failed:', errors)
                }
            })
        }

        const setTotalScore = () => {
            totalScoreForm.post(route('admin.evaluations.set-total-score'))
        }

        const setIncentives = () => {
            incentiveForm.post(route('admin.evaluations.set-incentives'), {
                onSuccess: () => {
                    // Form will be updated automatically when page reloads with new data
                },
                onError: (errors) => {
                    console.error('Incentive save errors:', errors)
                }
            })
        }

        const addIncentive = () => {
            incentiveForm.incentives.push({ min_score: 0, max_score: 0, incentive_amount: 0 })
        }

        const confirmRemoveIncentive = (index) => {
            if (incentiveForm.incentives.length <= 1) return
            incentiveIndexToRemove.value = index
            showRemoveIncentiveModal.value = true
        }

        const removeIncentive = () => {
            if (incentiveIndexToRemove.value !== null && incentiveForm.incentives.length > 1) {
                incentiveForm.incentives.splice(incentiveIndexToRemove.value, 1)
            }
            showRemoveIncentiveModal.value = false
            incentiveIndexToRemove.value = null
        }

        return {
            // Reactive states
            showCreateModal,
            showEditModal,
            showAddTypeModal,
            showDeleteCategoryModal,
            showDeleteTypeModal,
            showRemoveIncentiveModal,
            selectedConfig,
            categoryToDelete,
            typeToDelete,
            incentiveIndexToRemove,
            breadcrumbs,

            // Forms
            categoryForm,
            typeForm,
            totalScoreForm,
            incentiveForm,

            // Computed
            hasConfigs,

            // Methods
            closeModals,
            closeTypeModal,
            createCategory,
            editConfig,
            updateCategory,
            confirmDeleteCategory,
            deleteCategory,
            showTypeModal,
            addType,
            confirmDeleteType,
            deleteType,
            setTotalScore,
            setIncentives,
            addIncentive,
            confirmRemoveIncentive,
            removeIncentive,
            updateIncentiveForm,
        }
    },
}
</script>

<style scoped>
/* Responsive design improvements */
@media (max-width: 640px) {
    .grid-cols-1 {
        @apply space-y-3;
    }

    .sm:grid-cols-2 {
        @apply grid-cols-1;
    }

    .lg:grid-cols-3 {
        @apply grid-cols-1;
    }

    .sm:flex-row {
        @apply flex-col;
    }

    .sm:space-x-3 {
        @apply space-x-0 space-y-3;
    }
}

/* Custom scrollbar for better UX */
.space-y-6 {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f9fafb;
}

.space-y-6::-webkit-scrollbar {
    width: 6px;
}

.space-y-6::-webkit-scrollbar-track {
    background: #f9fafb;
}

.space-y-6::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.space-y-6::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
