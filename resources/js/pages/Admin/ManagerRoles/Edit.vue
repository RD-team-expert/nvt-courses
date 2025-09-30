<!--
  Edit Manager Role Assignment Page
  Update existing management role assignments and responsibilities
-->
<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, watch, computed, onUnmounted } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    User,
    Building,
    Star,
    CheckCircle,
    Info,
    Loader2,
    ArrowLeft,
    Users,
    Shield,
    Save
} from 'lucide-vue-next'

const props = defineProps({
    role: Object,
    managers: Array,
    departments: Array,
    employees: Array,
    roleTypes: Object,
})

// Add a mounted flag to prevent operations on unmounted component
const isMounted = ref(true)

// Modal states
const showNotification = ref(false)
const showConfirmation = ref(false)
const showLoading = ref(false)

// Notification states
const notification = ref({
    type: 'info',
    title: '',
    message: ''
})

// Confirmation states
const confirmation = ref({
    title: '',
    message: '',
    action: null as (() => void) | null
})

const loading = ref({
    message: 'Loading...'
})

// Form setup with existing data
const form = useForm({
    user_id: props.role.user_id,
    department_id: props.role.department_id,
    role_type: props.role.role_type,
    manages_user_id: props.role.manages_user_id,
    management_type: props.role.manages_user_id ? 'specific_user' : 'department_wide',
    is_primary: props.role.is_primary,
    authority_level: props.role.authority_level,
    start_date: props.role.start_date,
    end_date: props.role.end_date,
    notes: props.role.notes,
})

// Management type options
const managementTypes = [
    {
        value: 'specific_user',
        label: 'Manages Specific User',
        description: 'Direct 1:1 management relationship with selected employee',
        icon: User,
        color: 'blue'
    },
    {
        value: 'department_wide',
        label: 'Department-wide Management',
        description: 'Oversees entire department operations and policies',
        icon: Building,
        color: 'green'
    },
    {
        value: 'no_management',
        label: 'No Management Responsibilities',
        description: 'Individual contributor, specialist, or advisory role',
        icon: Star,
        color: 'gray'
    }
]

// Handle select changes
const handleManagerChange = (value: string) => {
    if (!isMounted.value) return
    form.user_id = value === 'none' ? '' : parseInt(value)
}

const handleDepartmentChange = (value: string) => {
    if (!isMounted.value) return
    form.department_id = value === 'none' ? '' : parseInt(value)
}

const handleRoleTypeChange = (value: string) => {
    if (!isMounted.value) return
    form.role_type = value === 'none' ? '' : value
}

const handleAuthorityLevelChange = (value: string) => {
    if (!isMounted.value) return
    form.authority_level = parseInt(value)
}

const handleEmployeeChange = (value: string) => {
    if (!isMounted.value) return
    form.manages_user_id = value === 'none' ? null : parseInt(value)
}

const handleManagementTypeChange = (value: string) => {
    if (!isMounted.value) return
    form.management_type = value
}

const handlePrimaryRoleChange = (checked: boolean) => {
    if (!isMounted.value) return
    form.is_primary = checked
}

// Helper functions
const showNotificationModal = (type: string, title: string, message: string) => {
    notification.value = { type, title, message }
    showNotification.value = true
}

const showConfirmationModal = (title: string, message: string, action: () => void) => {
    confirmation.value = { title, message, action }
    showConfirmation.value = true
}

// Filter employees by selected department
const departmentEmployees = computed(() => {
    if (!form.department_id) return []
    return props.employees?.filter(emp => emp.department_id === form.department_id) || []
})

// Available employees for management (excluding self)
const availableEmployees = computed(() => {
    return departmentEmployees.value.filter(emp => emp.id !== parseInt(form.user_id))
})

// Watch for department changes
watch(() => form.department_id, (newDeptId) => {
    if (!isMounted.value) return

    if (newDeptId && form.manages_user_id) {
        // Check if currently managed user is still in the new department
        const currentManagedUser = departmentEmployees.value.find(emp => emp.id === form.manages_user_id)
        if (!currentManagedUser) {
            form.manages_user_id = null
        }
    }
})

// Watch for management type changes
watch(() => form.management_type, (newType) => {
    if (!isMounted.value) return

    if (newType !== 'specific_user') {
        form.manages_user_id = null
    }
})

// Submit form with confirmation
const submitForm = () => {
    if (!isMounted.value) return

    showConfirmationModal(
        'Update Manager Role',
        'Are you sure you want to update this manager role assignment? This will change the current management structure.',
        () => {
            showLoading.value = true
            loading.value.message = 'Updating manager role...'

            // Clear manages_user_id if not managing specific user
            if (form.management_type !== 'specific_user') {
                form.manages_user_id = null
            }

            form.put(route('admin.manager-roles.update', props.role.id), {
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'Manager role updated successfully!')

                    // Redirect after a short delay
                    setTimeout(() => {
                        router.visit(route('admin.manager-roles.show', props.role.id))
                    }, 1500)
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Update failed:', errors)

                    const errorMessage = errors.role_conflict || 'Failed to update manager role. Please try again.'
                    showNotificationModal('error', 'Error', errorMessage)
                }
            })
        }
    )
}

// Handle confirmation
const handleConfirmation = () => {
    showConfirmation.value = false
    if (confirmation.value.action) {
        confirmation.value.action()
    }
}

// Close modals
const closeNotification = () => {
    showNotification.value = false
}

const closeConfirmation = () => {
    showConfirmation.value = false
}

// Helper function to get selected department name
const getDepartmentName = (departmentId) => {
    const dept = props.departments?.find(d => d.id === departmentId)
    return dept ? dept.name : ''
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Manager Roles', href: route('admin.manager-roles.index') },
    { name: 'Edit Role', href: route('admin.manager-roles.edit', props.role.id) }
]

// Cleanup on unmount
onUnmounted(() => {
    isMounted.value = false
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Edit Manager Role Assignment</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Update the management role for {{ role.manager?.name }} in {{ role.department?.name }}
                </p>
            </div>

            <!-- Current Assignment Info -->
            <Alert>
                <Info class="h-4 w-4" />
                <AlertDescription>
                    <h3 class="font-semibold mb-2">Current Assignment</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Manager:</span>
                            <span class="ml-1">{{ role.manager?.name }} ({{ role.manager?.level }})</span>
                        </div>
                        <div>
                            <span class="font-medium">Department:</span>
                            <span class="ml-1">{{ role.department?.name }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Role:</span>
                            <span class="ml-1">{{ roleTypes[role.role_type] }}</span>
                        </div>
                    </div>
                    <div v-if="role.managed_user" class="mt-2 text-sm">
                        <span class="font-medium">Currently Manages:</span>
                        <span class="ml-1">{{ role.managed_user.name }} ({{ role.managed_user.email }})</span>
                    </div>
                </AlertDescription>
            </Alert>

            <form @submit.prevent="submitForm" class="max-w-6xl mx-auto space-y-8">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center">
                            <div class="shrink-0 mr-4">
                                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-primary/10">
                                    <Shield class="h-6 w-6 text-primary" />
                                </div>
                            </div>
                            <div>
                                <CardTitle>Basic Assignment Information</CardTitle>
                                <CardDescription>Update the manager, department, and role details</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Manager Selection -->
                            <div class="space-y-2">
                                <Label for="manager">Manager *</Label>
                                <Select
                                    :model-value="form.user_id?.toString() || 'none'"
                                    @update:model-value="handleManagerChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="manager">
                                        <SelectValue placeholder="Select Manager" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Select Manager</SelectItem>
                                        <SelectItem v-for="manager in managers" :key="manager.id" :value="manager.id.toString()">
                                            {{ manager.name }} ({{ manager.level }}) - {{ manager.department }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.user_id" class="text-destructive text-sm">{{ form.errors.user_id }}</div>
                            </div>

                            <!-- Department Selection -->
                            <div class="space-y-2">
                                <Label for="department">Department *</Label>
                                <Select
                                    :model-value="form.department_id?.toString() || 'none'"
                                    @update:model-value="handleDepartmentChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="department">
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Select Department</SelectItem>
                                        <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
                                            {{ dept.name }} ({{ dept.department_code }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.department_id" class="text-destructive text-sm">{{ form.errors.department_id }}</div>
                            </div>

                            <!-- Role Type -->
                            <div class="space-y-2">
                                <Label for="role-type">Role Type *</Label>
                                <Select
                                    :model-value="form.role_type || 'none'"
                                    @update:model-value="handleRoleTypeChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="role-type">
                                        <SelectValue placeholder="Select Role Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Select Role Type</SelectItem>
                                        <SelectItem v-for="(label, value) in roleTypes" :key="value" :value="value">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.role_type" class="text-destructive text-sm">{{ form.errors.role_type }}</div>
                            </div>

                            <!-- Authority Level -->
                            <div class="space-y-2">
                                <Label for="authority-level">Authority Level *</Label>
                                <Select
                                    :model-value="form.authority_level?.toString()"
                                    @update:model-value="handleAuthorityLevelChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="authority-level">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">High Authority</SelectItem>
                                        <SelectItem value="2">Medium Authority</SelectItem>
                                        <SelectItem value="3">Low Authority</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.authority_level" class="text-destructive text-sm">{{ form.errors.authority_level }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Management Type Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle>Management Responsibilities</CardTitle>
                        <CardDescription>Choose the type of management responsibilities for this role</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <RadioGroup
                            :model-value="form.management_type"
                            @update:model-value="handleManagementTypeChange"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4"
                        >
                            <Card
                                v-for="type in managementTypes"
                                :key="type.value"
                                class="relative cursor-pointer transition-all hover:shadow-md"
                                :class="{
                                    'border-primary bg-primary/5 shadow-md': form.management_type === type.value,
                                    'hover:border-primary/50': form.management_type !== type.value
                                }"
                                @click="handleManagementTypeChange(type.value)"
                            >
                                <CardContent class="p-6">
                                    <div class="flex items-start space-x-3">
                                        <RadioGroupItem
                                            :value="type.value"
                                            :id="`type-${type.value}`"
                                            class="mt-1"
                                        />
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <component
                                                    :is="type.icon"
                                                    class="w-5 h-5"
                                                    :class="{
                                                        'text-blue-500': type.color === 'blue',
                                                        'text-green-500': type.color === 'green',
                                                        'text-muted-foreground': type.color === 'gray'
                                                    }"
                                                />
                                                <Label :for="`type-${type.value}`" class="font-semibold cursor-pointer">
                                                    {{ type.label }}
                                                </Label>
                                            </div>
                                            <p class="text-sm text-muted-foreground cursor-pointer">{{ type.description }}</p>
                                        </div>
                                    </div>

                                    <!-- Selected indicator -->
                                    <div v-if="form.management_type === type.value" class="absolute top-2 right-2">
                                        <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                                            <CheckCircle class="w-4 h-4 text-primary-foreground" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </RadioGroup>
                    </CardContent>
                </Card>

                <!-- Conditional Specific User Selection -->
                <Card v-if="form.management_type === 'specific_user'">
                    <CardContent class="p-6">
                        <Alert class="mb-6">
                            <User class="h-4 w-4" />
                            <AlertDescription>
                                <h4 class="font-semibold mb-1">Direct Management Assignment</h4>
                                <p class="text-sm">Select a specific L1 employee who will report directly to this manager for daily tasks, performance reviews, and career development.</p>
                            </AlertDescription>
                        </Alert>

                        <div class="space-y-2">
                            <Label for="employee">Select L1 Employee to Manage *</Label>

                            <Select
                                :model-value="form.manages_user_id?.toString() || 'none'"
                                @update:model-value="handleEmployeeChange"
                                :disabled="form.processing || !form.department_id"
                            >
                                <SelectTrigger id="employee">
                                    <SelectValue placeholder="Choose an L1 employee" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Choose an L1 employee</SelectItem>
                                    <SelectItem v-for="employee in availableEmployees" :key="employee.id" :value="employee.id.toString()">
                                        {{ employee.name }} ({{ employee.level }}) - {{ employee.email }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <p v-if="!form.department_id" class="text-sm text-muted-foreground">
                                Please select a department first to see available L1 employees
                            </p>
                            <p v-else-if="availableEmployees.length === 0" class="text-sm text-muted-foreground">
                                No L1 employees available in this department
                            </p>
                            <div v-if="form.errors.manages_user_id" class="text-destructive text-sm">{{ form.errors.manages_user_id }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Dates and Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle>Assignment Details</CardTitle>
                        <CardDescription>Set the timeframe and additional settings for this role</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Start Date -->
                            <div class="space-y-2">
                                <Label for="start-date">Start Date *</Label>
                                <Input
                                    id="start-date"
                                    type="date"
                                    v-model="form.start_date"
                                    :disabled="form.processing"
                                    required
                                />
                                <div v-if="form.errors.start_date" class="text-destructive text-sm">{{ form.errors.start_date }}</div>
                            </div>

                            <!-- End Date (Optional) -->
                            <div class="space-y-2">
                                <Label for="end-date">End Date <span class="text-muted-foreground font-normal">(Optional)</span></Label>
                                <Input
                                    id="end-date"
                                    type="date"
                                    v-model="form.end_date"
                                    :disabled="form.processing"
                                />
                                <p class="text-sm text-muted-foreground">Leave empty for permanent assignment</p>
                                <div v-if="form.errors.end_date" class="text-destructive text-sm">{{ form.errors.end_date }}</div>
                            </div>
                        </div>

                        <Separator class="my-6" />

                        <!-- Primary Role -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_primary"
                                :checked="form.is_primary"
                                @update:checked="handlePrimaryRoleChange"
                                :disabled="form.processing"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label for="is_primary" class="font-semibold cursor-pointer">
                                    Primary Role
                                </Label>
                                <p class="text-sm text-muted-foreground">
                                    Primary roles have higher authority and receive priority notifications. Only one primary role is recommended per person per department.
                                </p>
                            </div>
                        </div>

                        <Separator class="my-6" />

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Notes <span class="text-muted-foreground font-normal">(Optional)</span></Label>
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                :disabled="form.processing"
                                placeholder="Additional notes about this role assignment, responsibilities, special conditions, or expectations..."
                            />
                            <div v-if="form.errors.notes" class="text-destructive text-sm">{{ form.errors.notes }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        <span v-if="form.processing">Updating Assignment...</span>
                        <span v-else>Update Role Assignment</span>
                    </Button>

                    <Button
                        :as="Link"
                        :href="route('admin.manager-roles.show', role.id)"
                        variant="secondary"
                        class="w-full sm:w-auto"
                        :disabled="form.processing"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                </div>
            </form>
        </div>

        <!-- Success/Error Notification Dialog -->
        <Dialog v-model:open="showNotification">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <CheckCircle v-if="notification.type === 'success'" class="h-5 w-5 text-green-500 mr-2" />
                        <Info v-else class="h-5 w-5 text-red-500 mr-2" />
                        {{ notification.title }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ notification.message }}
                    </DialogDescription>
                </DialogHeader>
            </DialogContent>
        </Dialog>

        <!-- Confirmation Dialog -->
        <AlertDialog v-model:open="showConfirmation">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ confirmation.title }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ confirmation.message }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeConfirmation">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="handleConfirmation">Yes, Update</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Loading Dialog -->
        <Dialog v-model:open="showLoading">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <Loader2 class="h-5 w-5 animate-spin mr-2" />
                        {{ loading.message }}
                    </DialogTitle>
                </DialogHeader>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
