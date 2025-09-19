<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    existingLevels: Array,
})

const form = useForm({
    code: '',
    name: '',
    hierarchy_level: '',
    description: '',
    can_manage_levels: [],
})

const availableLevels = ref(props.existingLevels || [])

function submit() {
    form.post('/admin/user-levels', {
        onSuccess: () => {
            // Will redirect automatically on success
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    })
}

// Generate suggestions based on hierarchy level
const generateSuggestions = () => {
    const level = parseInt(form.hierarchy_level)

    if (level === 1) {
        form.code = 'L1'
        form.name = 'Employee'
        form.description = 'Front-line employees with no direct reports'
        form.can_manage_levels = []
    } else if (level === 2) {
        form.code = 'L2'
        form.name = 'Direct Manager'
        form.description = 'Team managers with direct reports'
        form.can_manage_levels = ['L1']
    } else if (level === 3) {
        form.code = 'L3'
        form.name = 'Senior Manager'
        form.description = 'Department heads and senior managers'
        form.can_manage_levels = ['L1', 'L2']
    } else if (level === 4) {
        form.code = 'L4'
        form.name = 'Director'
        form.description = 'Executive level with multiple department oversight'
        form.can_manage_levels = ['L1', 'L2', 'L3']
    }
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
    { name: 'Create Level', href: route('admin.user-levels.create') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-4">Create New User Level</h1>

            <form @submit.prevent="submit" class="max-w-2xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                    <!-- Hierarchy Level -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Hierarchy Level</label>
                        <select
                            v-model="form.hierarchy_level"
                            @change="generateSuggestions"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                        <p class="text-xs text-gray-500 mt-1">Higher numbers indicate higher authority levels</p>
                        <div v-if="form.errors.hierarchy_level" class="text-red-600 text-sm mt-1">{{ form.errors.hierarchy_level }}</div>
                    </div>

                    <!-- Level Code -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Level Code</label>
                        <input
                            type="text"
                            v-model="form.code"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            maxlength="10"
                            placeholder="e.g., L1, L2, MGR"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-1">Short identifier (e.g., L1, L2, L3, L4)</p>
                        <div v-if="form.errors.code" class="text-red-600 text-sm mt-1">{{ form.errors.code }}</div>
                    </div>

                    <!-- Level Name -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Level Name</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            placeholder="Describe the responsibilities and scope of this level"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
                    </div>

                    <!-- Can Manage Levels -->
                    <div class="col-span-full" v-if="availableLevels.length > 0">
                        <label class="block font-semibold mb-2">Can Manage Levels</label>
                        <p class="text-sm text-gray-600 mb-3">Select which levels this level can manage:</p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div v-for="level in availableLevels" :key="level.code" class="flex items-center">
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

                        <p class="text-xs text-gray-500 mt-2">Leave empty if this level cannot manage other users</p>
                        <div v-if="form.errors.can_manage_levels" class="text-red-600 text-sm mt-1">{{ form.errors.can_manage_levels }}</div>
                    </div>
                </div>

                <!-- Preview Card -->
                <div v-if="form.code" class="bg-gray-50 border rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Preview</h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center"
                             :class="{
                                 'bg-blue-100 text-blue-600': form.hierarchy_level == 1,
                                 'bg-green-100 text-green-600': form.hierarchy_level == 2,
                                 'bg-orange-100 text-orange-600': form.hierarchy_level == 3,
                                 'bg-red-100 text-red-600': form.hierarchy_level == 4,
                                 'bg-purple-100 text-purple-600': form.hierarchy_level >= 5,
                             }">
                            <span class="text-sm font-bold">{{ form.code }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">{{ form.name || 'Level Name' }}</div>
                            <div class="text-sm text-gray-500">Hierarchy Level {{ form.hierarchy_level || '?' }}</div>
                            <div v-if="form.can_manage_levels.length > 0" class="text-xs text-gray-400 mt-1">
                                Can manage: {{ form.can_manage_levels.join(', ') }}
                            </div>
                        </div>
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
                        <span v-if="form.processing">Creating Level...</span>
                        <span v-else>Create User Level</span>
                    </button>
                    <Link
                        :href="route('admin.user-levels.index')"
                        class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition w-full sm:w-auto text-center font-medium"
                        :class="{ 'pointer-events-none opacity-50': form.processing }"
                    >
                        Cancel
                    </Link>
                </div>

                <!-- Quick Setup Templates -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Setup Templates</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
                             @click="form.hierarchy_level = '1'; generateSuggestions()">
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold">L1</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium">Employee Level</div>
                                    <div class="text-xs text-gray-500">Front-line employees</div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
                             @click="form.hierarchy_level = '2'; generateSuggestions()">
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold">L2</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium">Manager Level</div>
                                    <div class="text-xs text-gray-500">Team managers</div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
                             @click="form.hierarchy_level = '3'; generateSuggestions()">
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold">L3</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium">Senior Manager</div>
                                    <div class="text-xs text-gray-500">Department heads</div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
                             @click="form.hierarchy_level = '4'; generateSuggestions()">
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-bold">L4</span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium">Director Level</div>
                                    <div class="text-xs text-gray-500">Executive level</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
