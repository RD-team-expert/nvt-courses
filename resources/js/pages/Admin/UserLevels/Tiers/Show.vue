<template>

    <Head :title="`${tier.display_name}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ tier.display_name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Tier details and management options
                    </p>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('admin.user-level-tiers.index', userLevel.id)"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Back to Tiers
                    </Link>
                    <Link
                        :href="route('admin.user-level-tiers.edit', [userLevel.id, tier.id])"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Edit Tier
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Tier Details Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">Tier Information</h3>

                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tier Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">{{ tier.tier_name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tier Order</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ tier.tier_order }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Level</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ userLevel.name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Level Code</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ userLevel.code }}
                                    </span>
                                </dd>
                            </div>

                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ tier.description || 'No description provided' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ new Date(tier.created_at).toLocaleDateString() }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated At</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ new Date(tier.updated_at).toLocaleDateString() }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Future: Incentives Section (when implemented) -->
                <!-- <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">Incentives</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Incentives for this tier will be displayed here when implemented.
                        </p>
                    </div>
                </div> -->

                <!-- Actions Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">Actions</h3>
                        <div class="flex gap-3">
                            <Link
                                :href="route('admin.user-level-tiers.edit', [userLevel.id, tier.id])"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Edit Tier
                            </Link>
                            <button
                                @click="deleteTier"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Delete Tier
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import breadcrumbs from '@/components/Breadcrumbs.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    userLevel: Object,
    tier: Object
});

const deleteTier = () => {
    if (confirm(`Are you sure you want to delete "${props.tier.tier_name}"?`)) {
        router.delete(route('admin.user-level-tiers.destroy', [props.userLevel.id, props.tier.id]));
    }
};
</script>
