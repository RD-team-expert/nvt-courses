<template>
    <Head :title="`Edit ${tier.tier_name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Edit {{ tier.tier_name }} - {{ userLevel.name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Modify tier properties and performance requirements
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit">
                            <div class="space-y-6">
                                <!-- Tier Name -->
                                <div>
                                    <label for="tier_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Tier Name
                                    </label>
                                    <input
                                        id="tier_name"
                                        v-model="form.tier_name"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    />
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Name identifier for this performance tier (e.g., Tier 1, Bronze, Junior)
                                    </p>
                                    <div v-if="errors.tier_name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.tier_name }}
                                    </div>
                                </div>

                                <!-- Tier Order -->
                                <div>
                                    <label for="tier_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Tier Order
                                    </label>
                                    <input
                                        id="tier_order"
                                        v-model.number="form.tier_order"
                                        type="number"
                                        min="1"
                                        max="10"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    />
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Higher numbers indicate higher performance tiers (1 = lowest, 3 = highest)
                                    </p>
                                    <div v-if="errors.tier_order" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.tier_order }}
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Description
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="Describe what this tier represents and its requirements..."
                                    ></textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Optional description of what this tier represents and performance requirements
                                    </p>
                                    <div v-if="errors.description" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ errors.description }}
                                    </div>
                                </div>

                                <!-- Other Tiers Info -->
                                <div v-if="existingTiers.length > 0" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-md">
                                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                        Other Tiers for {{ userLevel.name }}:
                                    </h4>
                                    <ul class="text-sm text-blue-700 dark:text-blue-300">
                                        <li v-for="existingTier in existingTiers" :key="existingTier.tier_order" class="flex justify-between">
                                            <span>{{ existingTier.tier_name }}</span>
                                            <span>Order: {{ existingTier.tier_order }}</span>
                                        </li>
                                    </ul>
                                    <p class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                        Make sure tier order doesn't conflict with existing tiers.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-6 gap-3">
                                <Link
                                    :href="route('admin.user-level-tiers.show', [userLevel.id, tier.id])"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="bg-green-500 hover:bg-green-700 disabled:bg-green-300 text-white font-bold py-2 px-4 rounded"
                                >
                                    Update Tier
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import breadcrumbs from '@/components/Breadcrumbs.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    userLevel: Object,
    tier: Object,
    existingTiers: Array,
    errors: Object
});

const form = useForm({
    tier_name: props.tier.tier_name,
    tier_order: props.tier.tier_order,
    description: props.tier.description
});

const submit = () => {
    form.put(route('admin.user-level-tiers.update', [props.userLevel.id, props.tier.id]));
};
</script>
