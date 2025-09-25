<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    userLevel: Object,
    existingLevels: Array,
})

const form = useForm({
    code: props.userLevel.code,
    name: props.userLevel.name,
    hierarchy_level: props.userLevel.hierarchy_level,
    description: props.userLevel.description,
    can_manage_levels: props.userLevel.can_manage_levels || [],
})

function submit() {
    form.put(route('admin.user-levels.update', props.userLevel.id), {
        onSuccess: () => {
            // Will redirect automatically on success
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    })
}

// Toggle manageable level
const toggleManageableLevel = (levelCode: string) => {
    const index = form.can_manage_levels.indexOf(levelCode)
    if (index > -1) {
        form.can_manage_levels.splice(index, 1)
    } else {
        form.can_manage_levels.push(levelCode)
    }
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Levels', href: route('admin.user-levels.index') },
    { name: props.userLevel.name, href: route('admin.user-levels.show', props.userLevel.id) },
    { name: 'Edit', href: route('admin.user-levels.edit', props.userLevel.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-4">Edit User Level: {{ userLevel.name }}</h1>

            <form @submit.prevent="submit" class="max-w-2xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                    <!-- Hierarchy Level -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Hierarchy Level</label>
                        <select
                            v-model="form.hierarchy_level"
                            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            required
                        >
                            <option value="">Select Level</option>
                            <option value="1">Level 1 (Employees)</option>
                            <option value="2">Level 2 (Managers)</option>
                            <option value="3">Level 3 (Senior Managers)</option>
                            <option value="4">Level 4 (Directors)</option>
                            <option value="5">Level 5 (Executive)</option>
                        </select>
                        <div v-if="form.errors.hierarchy_level" class="text-red-600 text-sm mt-1">{{ form.errors.hierarchy_level }}</div>
                    </div>

                    <!-- Level Code -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Level Code</label>
                        <input
                            type="text"
                            v-model="form.code"
                            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            maxlength="10"
                            placeholder="e.g., L1, L2, MGR"
                            required
                        />
                        <div v-if="form.errors.code" class="text-red-600 text-sm mt-1">{{ form.errors.code }}</div>
                    </div>

                    <!-- Level Name -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Level Name</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            placeholder="e.g., Employee, Direct Manager, Senior Manager"
                            required
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Description -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="border px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            placeholder="Describe the responsibilities and scope of this level"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
                    </div>

                    <!-- Can Manage Levels -->
                    <div class="col-span-full" v-if="existingLevels.length > 0">
                        <label class="block font-semibold mb-2">Can Manage Levels</label>
                        <p class="text-sm text-gray-600 mb-3">Select which levels this level can manage:</p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div v-for="level in existingLevels" :key="level.code" class="flex items-center">
                                <input
                                    :id="`manage_${level.code}`"
                                    type="checkbox"
                                    :value="level.code"
                                    @change="toggleManageableLevel(level.code)"
                                    :checked="form.can_manage_levels.includes(level.code)"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    :disabled="form.processing"
                                />
                                <label :for="`manage_${level.code}`" class="ml-2 block text-sm text-gray-900">
                                    {{ level.code }} - {{ level.name }}
                                </label>
                            </div>
                        </div>

                        <div v-if="form.errors.can_manage_levels" class="text-red-600 text-sm mt-1">{{ form.errors.can_manage_levels }}</div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 mb-4">
                    <button
                        type="submit"
                        class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition w-full sm:w-auto font-medium flex items-center justify-center gap-2"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Updating Level...</span>
                        <span v-else>Update User Level</span>
                    </button>
                    <Link
                        :href="route('admin.user-levels.show', userLevel.id)"
                        class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition w-full sm:w-auto text-center font-medium"
                        :class="{ 'pointer-events-none opacity-50': form.processing }"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
