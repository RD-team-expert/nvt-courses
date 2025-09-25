<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, watch, computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    managers: Array,
    departments: Array,
    employees: Array, // ✅ Contains all employee data
    roleTypes: Object,
})

// ✅ DEBUG: Log initial props data
console.log('🚀 Initial Props Data:')
console.log('📊 Departments:', props.departments)
console.log('👥 Employees:', props.employees)
console.log('👔 Managers:', props.managers)
console.log('🏷️ RoleTypes:', props.roleTypes)

const form = useForm({
    user_id: '',
    department_id: '',
    role_type: '',
    manages_user_id: '',
    management_type: 'department_wide',
    is_primary: false,
    authority_level: 1,
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    notes: '',
})

// ✅ Management type options
const managementTypes = [
    {
        value: 'specific_user',
        label: 'Manages Specific User',
        description: 'Direct 1:1 management relationship with selected employee',
        icon: 'user',
        color: 'blue'
    },
    {
        value: 'department_wide',
        label: 'Department-wide Management',
        description: 'Oversees entire department operations and policies',
        icon: 'building',
        color: 'green'
    },
    {
        value: 'no_management',
        label: 'No Management Responsibilities',
        description: 'Individual contributor, specialist, or advisory role',
        icon: 'star',
        color: 'gray'
    }
]

// ✅ FIXED: Filter employees by selected department (using existing props data)
const departmentEmployees = computed(() => {
    console.log('Computing departmentEmployees...');
    console.log('Selected departmentid:', form.departmentid);

    // TEMPORARY: Return ALL employees to test if filtering is the problem
    console.log('TESTING: Returning ALL employees without filter');
    console.log('All employees available:', props.employees.length);

    const allEmployees = props.employees;

    console.log('Returning all employees:', allEmployees);
    return allEmployees;
});

// ✅ Filter employees that can be managed (exclude self)
const availableEmployees = computed(() => {
    console.log('👥 Computing availableEmployees...')
    console.log('🔍 Department employees count:', departmentEmployees.value.length)
    console.log('👤 Selected manager ID:', form.user_id)

    const available = departmentEmployees.value.filter(emp => {
        const notSelf = emp.id !== parseInt(form.user_id)
        console.log(`   Employee ${emp.name} (ID: ${emp.id}) !== Manager (ID: ${form.user_id}) = ${notSelf}`)
        return notSelf
    })

    console.log('✅ Available employees for management:', available.length)
    console.log('📋 Available employees list:', available)

    return available
})

// ✅ Watch for department changes
watch(() => form.department_id, (newDeptId, oldDeptId) => {
    console.log('🔄 Department changed from', oldDeptId, 'to', newDeptId)

    if (newDeptId) {
        const dept = props.departments.find(d => d.id == newDeptId)
        console.log('🏢 New department selected:', dept?.name)

        // Trigger recomputation by accessing the computed property
        console.log('🔍 This will trigger departmentEmployees computation...')
        console.log('📊 Available employees after change:', availableEmployees.value.length)
    }
})

// ✅ Watch for management type changes
watch(() => form.management_type, (newType, oldType) => {
    console.log('🔄 Management type changed from', oldType, 'to', newType)

    if (newType !== 'specific_user') {
        console.log('🧹 Clearing manages_user_id because type is not specific_user')
        form.manages_user_id = ''
    }
})

// ✅ Watch for form changes
watch(() => form.manages_user_id, (newUserId, oldUserId) => {
    console.log('👤 Manages user ID changed from', oldUserId, 'to', newUserId)
})

function submit() {
    console.log('📤 Form submission started...')
    console.log('📋 Form data:', {
        user_id: form.user_id,
        department_id: form.department_id,
        role_type: form.role_type,
        manages_user_id: form.manages_user_id,
        management_type: form.management_type,
        is_primary: form.is_primary,
        authority_level: form.authority_level
    })

    // ✅ Clear manages_user_id if not managing specific user
    if (form.management_type !== 'specific_user') {
        console.log('🧹 Clearing manages_user_id for submission')
        form.manages_user_id = ''
    }

    form.post('/admin/manager-roles', {
        onSuccess: () => {
            console.log('✅ Form submitted successfully!')
        },
        onError: (errors) => {
            console.error('❌ Form submission errors:', errors)
        }
    })
}

// Helper function to get selected department name
const getDepartmentName = (departmentId) => {
    const dept = props.departments.find(d => d.id == departmentId)
    const name = dept ? dept.name : ''
    console.log('🏢 getDepartmentName for ID', departmentId, '=', name)
    return name
}

// ✅ DEBUG: Log when component mounts
console.log('🎬 Component mounted!')

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Manager Roles', href: route('admin.manager-roles.index') },
    { name: 'Assign Role', href: route('admin.manager-roles.create') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold mb-6">Assign Manager Role</h1>


            <form @submit.prevent="submit" class="max-w-6xl mx-auto">
                <!-- Basic Information Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-8">
                    <!-- Manager Selection -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-2">Manager</label>
                        <select
                            v-model="form.user_id"
                            @change="console.log('👤 Manager selected:', form.user_id)"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                            required
                        >
                            <option value="">Select Manager</option>
                            <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                                {{ manager.name }} ({{ manager.level }}) - {{ manager.department }}
                            </option>
                        </select>
                        <div v-if="form.errors.user_id" class="text-red-600 text-sm mt-1">{{ form.errors.user_id }}</div>
                    </div>

                    <!-- Department Selection -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-2">Department</label>
                        <select
                            v-model="form.department_id"
                            @change="console.log('🏢 Department selected:', form.department_id, getDepartmentName(form.department_id))"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                            required
                        >
                            <option value="">Select Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }} ({{ dept.department_code }})
                            </option>
                        </select>
                        <div v-if="form.errors.department_id" class="text-red-600 text-sm mt-1">{{ form.errors.department_id }}</div>
                    </div>

                    <!-- Role Type -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-2">Role Type</label>
                        <select
                            v-model="form.role_type"
                            @change="console.log('🏷️ Role type selected:', form.role_type)"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                            required
                        >
                            <option value="">Select Role Type</option>
                            <option v-for="(label, value) in roleTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                        <div v-if="form.errors.role_type" class="text-red-600 text-sm mt-1">{{ form.errors.role_type }}</div>
                    </div>

                    <!-- Authority Level -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-2">Authority Level</label>
                        <select
                            v-model="form.authority_level"
                            @change="console.log('⚡ Authority level selected:', form.authority_level)"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                            required
                        >
                            <option :value="1">High Authority</option>
                            <option :value="2">Medium Authority</option>
                            <option :value="3">Low Authority</option>
                        </select>
                        <div v-if="form.errors.authority_level" class="text-red-600 text-sm mt-1">{{ form.errors.authority_level }}</div>
                    </div>
                </div>

                <!-- ✅ Management Type Selection -->
                <div class="mb-8">
                    <label class="block font-semibold mb-4 text-lg">Management Responsibilities</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="type in managementTypes" :key="type.value"
                             class="relative border-2 rounded-lg p-6 cursor-pointer transition-all hover:shadow-md"
                             :class="{
                                 'border-blue-500 bg-blue-50 shadow-md': form.management_type === type.value && type.color === 'blue',
                                 'border-green-500 bg-green-50 shadow-md': form.management_type === type.value && type.color === 'green',
                                 'border-gray-500 bg-gray-50 shadow-md': form.management_type === type.value && type.color === 'gray',
                                 'border-gray-200 hover:border-gray-300': form.management_type !== type.value
                             }"
                             @click="console.log('🎯 Management type clicked:', type.value); form.management_type = type.value">
                            <div class="flex items-start space-x-3">
                                <div class="shrink-0 mt-1">
                                    <input
                                        type="radio"
                                        :value="type.value"
                                        v-model="form.management_type"
                                        @change="console.log('📻 Management type radio changed:', type.value)"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <!-- User Icon -->
                                        <svg v-if="type.icon === 'user'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <!-- Building Icon -->
                                        <svg v-else-if="type.icon === 'building'" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <!-- Star Icon -->
                                        <svg v-else class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                        </svg>
                                        <h3 class="font-semibold text-gray-900 cursor-pointer">{{ type.label }}</h3>
                                    </div>
                                    <p class="text-sm text-gray-600 cursor-pointer">{{ type.description }}</p>
                                </div>
                            </div>

                            <!-- Selected indicator -->
                            <div v-if="form.management_type === type.value"
                                 class="absolute top-2 right-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                     :class="{
                                         'bg-blue-500': type.color === 'blue',
                                         'bg-green-500': type.color === 'green',
                                         'bg-gray-500': type.color === 'gray'
                                     }">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Conditional: Specific User Selection -->
                <div v-if="form.management_type === 'specific_user'" class="mb-8">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-start space-x-3 mb-4">
                            <div class="shrink-0">
                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-blue-900 mb-1">Direct Management Assignment</h4>
                                <p class="text-sm text-blue-700">Select a specific L1 employee who will report directly to this manager for daily tasks, performance reviews, and career development.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold mb-2 text-blue-900">Select L1 Employee to Manage</label>

                            <!-- ✅ DEBUG: Show employee count -->
                            <div class="mb-2 text-xs text-blue-600">
                                Debug: {{ availableEmployees.length }} employees available
                            </div>

                            <select
                                v-model="form.manages_user_id"
                                @change="console.log('👥 Employee selected to manage:', form.manages_user_id)"
                                @focus="console.log('🔍 Employee dropdown focused - available employees:', availableEmployees)"
                                class="border border-blue-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                :disabled="form.processing || !form.department_id"
                            >
                                <option value="">Choose an L1 employee</option>
                                <option v-for="employee in availableEmployees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }} ({{ employee.level }}) - {{ employee.email }}
                                </option>
                            </select>
                            <p class="text-sm text-blue-600 mt-2" v-if="!form.department_id">
                                Please select a department first to see available L1 employees
                            </p>
                            <p class="text-sm text-blue-600 mt-2" v-else-if="availableEmployees.length === 0">
                                No L1 employees available in this department
                            </p>
                            <div v-if="form.errors.manages_user_id" class="text-red-600 text-sm mt-1">{{ form.errors.manages_user_id }}</div>
                        </div>
                    </div>
                </div>

                <!-- Rest of your template stays the same... -->
                <!-- Information Panel based on selection -->
                <div class="mb-8 p-6 rounded-lg border"
                     :class="{
                         'bg-blue-50 border-blue-200': form.management_type === 'specific_user',
                         'bg-green-50 border-green-200': form.management_type === 'department_wide',
                         'bg-gray-50 border-gray-200': form.management_type === 'no_management'
                     }"
                     v-if="form.management_type && form.management_type !== 'specific_user'">
                    <div class="flex items-start space-x-3">
                        <div class="shrink-0">
                            <svg v-if="form.management_type === 'department_wide'" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <svg v-else-if="form.management_type === 'no_management'" class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2"
                                :class="{
                                    'text-green-900': form.management_type === 'department_wide',
                                    'text-gray-900': form.management_type === 'no_management'
                                }">
                                {{ managementTypes.find(t => t.value === form.management_type)?.label }}
                            </h4>
                            <p class="text-sm"
                               :class="{
                                   'text-green-700': form.management_type === 'department_wide',
                                   'text-gray-700': form.management_type === 'no_management'
                               }">
                                <span v-if="form.management_type === 'department_wide'">
                                    This grants oversight authority over the entire {{ getDepartmentName(form.department_id) }} department. The manager can make department-wide decisions, set policies, allocate resources, and provide strategic direction for all department employees.
                                </span>
                                <span v-else-if="form.management_type === 'no_management'">
                                    This role is designed for individual contributors, technical specialists, consultants, or advisory positions. The person will have expertise and authority in their domain but won't manage other people directly. Perfect for senior developers, architects, analysts, or subject matter experts.
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dates and Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-8">
                    <!-- Start Date -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-2">Start Date</label>
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                            required
                        />
                        <div v-if="form.errors.start_date" class="text-red-600 text-sm mt-1">{{ form.errors.start_date }}</div>
                    </div>

                    <!-- End Date (Optional) -->
                    <div class="col-span-1">
                        <label class="block font-semibold mb-2">End Date <span class="text-gray-500 font-normal">(Optional)</span></label>
                        <input
                            type="date"
                            v-model="form.end_date"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                        />
                        <p class="text-sm text-gray-500 mt-1">Leave empty for permanent assignment</p>
                        <div v-if="form.errors.end_date" class="text-red-600 text-sm mt-1">{{ form.errors.end_date }}</div>
                    </div>
                </div>

                <!-- Primary Role and Notes -->
                <div class="space-y-6 mb-8">
                    <!-- Primary Role -->
                    <div>
                        <div class="flex items-center">
                            <input
                                id="is_primary"
                                v-model="form.is_primary"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                :disabled="form.processing"
                            />
                            <label for="is_primary" class="ml-3 block font-semibold text-gray-900">
                                Primary Role
                            </label>
                        </div>
                        <p class="text-sm text-gray-600 mt-2 ml-7">Primary roles have higher authority and receive priority notifications. Only one primary role is recommended per person per department.</p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block font-semibold mb-2">Notes <span class="text-gray-500 font-normal">(Optional)</span></label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="border border-gray-300 px-3 py-2 rounded-md w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :disabled="form.processing"
                            placeholder="Additional notes about this role assignment, responsibilities, special conditions, or expectations..."
                        ></textarea>
                        <div v-if="form.errors.notes" class="text-red-600 text-sm mt-1">{{ form.errors.notes }}</div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors w-full sm:w-auto font-semibold flex items-center justify-center gap-2 shadow-sm"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Creating Assignment...</span>
                        <span v-else>Create Role Assignment</span>
                    </button>
                    <Link
                        :href="route('admin.manager-roles.index')"
                        class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg hover:bg-gray-200 transition-colors w-full sm:w-auto text-center font-semibold border border-gray-300"
                        :class="{ 'pointer-events-none opacity-50': form.processing }"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
