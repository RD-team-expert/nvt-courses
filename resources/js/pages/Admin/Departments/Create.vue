<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    parentDepartments: Array,
})

const form = useForm({
    name: '',
    description: '',
    parent_id: '',
    department_code: '',
    is_active: true,
})

function submit() {
    form.post('/admin/departments', {
        onSuccess: () => {
            // Will redirect automatically on success
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    })
}

// Generate department code from name
const generateCode = () => {
    if (form.name) {
        form.department_code = form.name
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .substring(0, 10);
    }
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Departments', href: route('admin.departments.index') },
    { name: 'Create Department', href: route('admin.departments.create') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-4">Create New Department</h1>

            <form @submit.prevent="submit" class="max-w-2xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                    <!-- Department Name -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Department Name</label>
                        <input
                            type="text"
                            v-model="form.name"
                            @input="generateCode"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            required
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Department Code -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Department Code</label>
                        <input
                            type="text"
                            v-model="form.department_code"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                            maxlength="20"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-1">Short code for the department (e.g., HR, IT, SALES)</p>
                        <div v-if="form.errors.department_code" class="text-red-600 text-sm mt-1">{{ form.errors.department_code }}</div>
                    </div>

                    <!-- Parent Department -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-1">Parent Department</label>
                        <select
                            v-model="form.parent_id"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                        >
                            <option value="">None (Top Level)</option>
                            <option v-for="dept in parentDepartments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.parent_id" class="text-red-600 text-sm mt-1">{{ form.errors.parent_id }}</div>
                    </div>

                    <!-- Description -->
                    <div class="col-span-full">
                        <label class="block font-semibold mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="form.processing"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
                    </div>

                    <!-- Status -->
                    <div class="col-span-full">
                        <div class="flex items-center">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                :disabled="form.processing"
                            />
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Active Department
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Inactive departments won't be available for user assignment</p>
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
                        <span v-if="form.processing">Creating Department...</span>
                        <span v-else>Create Department</span>
                    </button>
                    <Link
                        :href="route('admin.departments.index')"
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
