<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Create Tier for ${userLevel.name}`" />

        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Create New Tier for {{ userLevel.name }}
                        </h1>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 ml-11">
                        Define a new performance tier within the {{ userLevel.name }} level
                    </p>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit">
                            <div class="space-y-8">
                                <!-- Tier Name -->
                                <div class="relative">
                                    <div class="flex items-center mb-2">
                                        <label for="tier_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Tier Name
                                        </label>
                                        <span class="ml-1 text-red-500">*</span>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="tier_name"
                                            v-model="form.tier_name"
                                            type="text"
                                            class="pl-10 mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm transition duration-200"
                                            :class="{'border-red-300 dark:border-red-700': $page.props.errors.tier_name}"
                                            placeholder="e.g., Tier 1, Bronze, Junior"
                                            required
                                            @input="clearError('tier_name')"
                                        />
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Name identifier for this performance tier (e.g., Tier 1, Bronze, Junior)
                                    </p>
                                    <div v-if="$page.props.errors.tier_name" class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $page.props.errors.tier_name }}
                                    </div>
                                </div>

                                <!-- Tier Order -->
                                <div class="relative">
                                    <div class="flex items-center mb-2">
                                        <label for="tier_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Tier Order
                                        </label>
                                        <span class="ml-1 text-red-500">*</span>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                                            </svg>
                                        </div>
                                        <input
                                            id="tier_order"
                                            v-model.number="form.tier_order"
                                            type="number"
                                            min="1"
                                            max="10"
                                            class="pl-10 mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm transition duration-200"
                                            :class="{'border-red-300 dark:border-red-700': $page.props.errors.tier_order}"
                                            required
                                            @input="clearError('tier_order')"
                                        />
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Higher numbers indicate higher performance tiers (1 = lowest, 10 = highest)
                                    </p>
                                    <div v-if="$page.props.errors.tier_order" class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $page.props.errors.tier_order }}
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="relative">
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3 pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="4"
                                            class="pl-10 mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm transition duration-200"
                                            :class="{'border-red-300 dark:border-red-700': $page.props.errors.description}"
                                            placeholder="Describe what this tier represents and its requirements..."
                                            @input="clearError('description')"
                                        ></textarea>
                                    </div>
                                    <div class="flex justify-between mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Optional description of what this tier represents and performance requirements
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ form.description.length }}/500
                                        </p>
                                    </div>
                                    <div v-if="$page.props.errors.description" class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ $page.props.errors.description }}
                                    </div>
                                </div>

                                <!-- Existing Tiers Info -->
                                <div v-if="existingTiers.length > 0" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-md border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center mb-2">
                                        <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                            Existing Tiers for {{ userLevel.name }}:
                                        </h4>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-blue-200 dark:divide-blue-800">
                                            <thead>
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-wider">Tier Name</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-wider">Order</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-wider">Description</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-blue-200 dark:divide-blue-800">
                                            <tr v-for="tier in existingTiers" :key="tier.tier_order">
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-blue-700 dark:text-blue-300">{{ tier.tier_name }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-blue-700 dark:text-blue-300">{{ tier.tier_order }}</td>
                                                <td class="px-3 py-2 text-sm text-blue-700 dark:text-blue-300">{{ tier.description || 'No description' }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Fields marked with <span class="text-red-500">*</span> are required
                                </div>
                                <div class="flex items-center gap-3">
                                    <Link
                                        :href="route('admin.user-level-tiers.index', userLevel.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 flex items-center"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        <span>{{ form.processing ? 'Creating...' : 'Create Tier' }}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    userLevel: Object,
    existingTiers: Array,
});

// Setup breadcrumbs
const breadcrumbs = [
    { name: 'Admin', href: route('admin.users.index') },
    { name: 'User Levels', href: route('admin.user-levels.index') },
    { name: props.userLevel.name, href: route('admin.user-levels.show', props.userLevel.id) },
    { name: 'Tiers', href: route('admin.user-level-tiers.index', props.userLevel.id) },
    { name: 'Create Tier', href: '#' },
];

const form = useForm({
    tier_name: '',
    tier_order: props.existingTiers.length > 0
        ? Math.max(...props.existingTiers.map(t => t.tier_order)) + 1
        : 1,
    description: ''
});

const submit = () => {
    form.post(route('admin.user-level-tiers.store', props.userLevel.id));
};

const clearError = (field) => {
    if (props.$page?.props?.errors?.[field]) {
        props.$page.props.errors[field] = null;
    }
};
</script>
