<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
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
    User,
    Building,
    Star,
    CheckCircle,
    Calendar,
    Loader2,
    ArrowLeft,
    Users,
    Shield
} from 'lucide-vue-next'

const props = defineProps({
    managers: Array,
    departments: Array,
    employees: Array,
    roleTypes: Object,
})

const isMounted = ref(true)

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
    form.user_id = value === 'none' ? '' : value
}

const handleDepartmentChange = (value: string) => {
    if (!isMounted.value) return
    form.department_id = value === 'none' ? '' : value
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
    form.manages_user_id = value === 'none' ? '' : value
}

const handleManagementTypeChange = (value: string) => {
    if (!isMounted.value) return
    form.management_type = value
}

const handlePrimaryRoleChange = (checked: boolean) => {
    if (!isMounted.value) return
    form.is_primary = checked
}

// ✅ FIXED: Filter employees by selected department
const departmentEmployees = computed(() => {
    if (!form.department_id) return []
    
    return (props.employees || []).filter(emp => 
        emp.department_id === parseInt(form.department_id)
    )
})

// ✅ Filter employees that can be managed (exclude self)
const availableEmployees = computed(() => {
    return departmentEmployees.value.filter(emp => 
        emp.id !== parseInt(form.user_id)
    )
})

// ✅ Watch for management type changes
watch(() => form.management_type, (newType) => {
    if (!isMounted.value) return
    if (newType !== 'specific_user') {
        form.manages_user_id = ''
    }
})

function submit() {
    if (!isMounted.value) return

    if (form.management_type !== 'specific_user') {
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

const getDepartmentName = (departmentId) => {
    const dept = props.departments?.find(d => d.id == departmentId)
    return dept ? dept.name : ''
}

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Manager Roles', href: route('admin.manager-roles.index') },
    { name: 'Assign Role', href: route('admin.manager-roles.create') }
]

onUnmounted(() => {
    isMounted.value = false
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-8 bg-background text-foreground">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Assign Manager Role</h1>
                <p class="mt-2 text-sm text-muted-foreground">Create and configure management assignments with role types and responsibilities</p>
            </div>

            <form @submit.prevent="submit" class="max-w-6xl mx-auto space-y-8">
                <!-- Basic Information -->
                <Card class="bg-card border-border">
                    <CardHeader class="bg-card text-card-foreground">
                        <div class="flex items-center">
                            <div class="shrink-0 mr-4">
                                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-primary/10">
                                    <Shield class="h-6 w-6 text-primary" />
                                </div>
                            </div>
                            <div>
                                <CardTitle class="text-card-foreground">Basic Assignment Information</CardTitle>
                                <CardDescription class="text-muted-foreground">Select the manager, department, and role details</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="bg-card text-card-foreground">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Manager Selection -->
                            <div class="space-y-2">
                                <Label for="manager" class="text-foreground">Manager *</Label>
                                <Select
                                    :model-value="form.user_id || 'none'"
                                    @update:model-value="handleManagerChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="manager" class="bg-background border-border text-foreground">
                                        <SelectValue placeholder="Select Manager" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-popover border-border text-popover-foreground">
                                        <SelectItem value="none" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">Select Manager</SelectItem>
                                        <SelectItem v-for="manager in managers" :key="manager.id" :value="manager.id.toString()" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">
                                            {{ manager.name }} ({{ manager.level }}) - {{ manager.department }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.user_id" class="text-destructive text-sm">{{ form.errors.user_id }}</div>
                            </div>

                            <!-- Department Selection -->
                            <div class="space-y-2">
                                <Label for="department" class="text-foreground">Department *</Label>
                                <Select
                                    :model-value="form.department_id || 'none'"
                                    @update:model-value="handleDepartmentChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="department" class="bg-background border-border text-foreground">
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-popover border-border text-popover-foreground">
                                        <SelectItem value="none" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">Select Department</SelectItem>
                                        <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id.toString()" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">
                                            {{ dept.name }} ({{ dept.department_code }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.department_id" class="text-destructive text-sm">{{ form.errors.department_id }}</div>
                            </div>

                            <!-- Role Type -->
                            <div class="space-y-2">
                                <Label for="role-type" class="text-foreground">Role Type *</Label>
                                <Select
                                    :model-value="form.role_type || 'none'"
                                    @update:model-value="handleRoleTypeChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="role-type" class="bg-background border-border text-foreground">
                                        <SelectValue placeholder="Select Role Type" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-popover border-border text-popover-foreground">
                                        <SelectItem value="none" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">Select Role Type</SelectItem>
                                        <SelectItem v-for="(label, value) in roleTypes" :key="value" :value="value" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.role_type" class="text-destructive text-sm">{{ form.errors.role_type }}</div>
                            </div>

                            <!-- Authority Level -->
                            <div class="space-y-2">
                                <Label for="authority-level" class="text-foreground">Authority Level *</Label>
                                <Select
                                    :model-value="form.authority_level.toString()"
                                    @update:model-value="handleAuthorityLevelChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="authority-level" class="bg-background border-border text-foreground">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent class="bg-popover border-border text-popover-foreground">
                                        <SelectItem value="1" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">High Authority</SelectItem>
                                        <SelectItem value="2" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">Medium Authority</SelectItem>
                                        <SelectItem value="3" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">Low Authority</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.authority_level" class="text-destructive text-sm">{{ form.errors.authority_level }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Management Type Selection -->
                <Card class="bg-card border-border">
                    <CardHeader class="bg-card text-card-foreground">
                        <CardTitle class="text-card-foreground">Management Responsibilities</CardTitle>
                        <CardDescription class="text-muted-foreground">Choose the type of management responsibilities for this role</CardDescription>
                    </CardHeader>
                    <CardContent class="bg-card text-card-foreground">
                        <RadioGroup
                            :model-value="form.management_type"
                            @update:model-value="handleManagementTypeChange"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4"
                        >
                            <Card
                                v-for="type in managementTypes"
                                :key="type.value"
                                class="relative cursor-pointer transition-all hover:shadow-md bg-card border-border"
                                :class="{
                                    'border-primary bg-primary/5 shadow-md': form.management_type === type.value,
                                    'hover:border-primary/50': form.management_type !== type.value
                                }"
                                @click="handleManagementTypeChange(type.value)"
                            >
                                <CardContent class="p-6 bg-card text-card-foreground">
                                    <div class="flex items-start space-x-3">
                                        <RadioGroupItem
                                            :value="type.value"
                                            :id="`type-${type.value}`"
                                            class="mt-1 border-border text-primary"
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
                                                <Label :for="`type-${type.value}`" class="font-semibold cursor-pointer text-card-foreground">
                                                    {{ type.label }}
                                                </Label>
                                            </div>
                                            <p class="text-sm text-muted-foreground cursor-pointer">{{ type.description }}</p>
                                        </div>
                                    </div>

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

                <!-- Conditional: Specific User Selection -->
                <Card v-if="form.management_type === 'specific_user'" class="bg-card border-border">
                    <CardContent class="p-6 bg-card text-card-foreground">
                        <Alert class="mb-6 bg-muted/50 border-border">
                            <User class="h-4 w-4 text-foreground" />
                            <AlertDescription class="text-foreground">
                                <h4 class="font-semibold mb-1">Direct Management Assignment</h4>
                                <p class="text-sm text-muted-foreground">Select a specific L1 employee who will report directly to this manager for daily tasks, performance reviews, and career development.</p>
                            </AlertDescription>
                        </Alert>

                        <div class="space-y-2">
                            <Label for="employee" class="text-foreground">Select L1 Employee to Manage *</Label>

                            <Select
                                :model-value="form.manages_user_id || 'none'"
                                @update:model-value="handleEmployeeChange"
                                :disabled="form.processing || !form.department_id"
                            >
                                <SelectTrigger id="employee" class="bg-background border-border text-foreground">
                                    <SelectValue placeholder="Choose an L1 employee" />
                                </SelectTrigger>
                                <SelectContent class="bg-popover border-border text-popover-foreground">
                                    <SelectItem value="none" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">Choose an L1 employee</SelectItem>
                                    <SelectItem v-for="employee in availableEmployees" :key="employee.id" :value="employee.id.toString()" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground">
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

                <!-- Information Panel based on selection -->
                <Alert
                    v-if="form.management_type && form.management_type !== 'specific_user'"
                    class="border-border"
                    :class="{
                        'bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800': form.management_type === 'department_wide',
                        'bg-muted border-border': form.management_type === 'no_management'
                    }"
                >
                    <component
                        :is="form.management_type === 'department_wide' ? Building : Star"
                        class="h-4 w-4"
                        :class="{
                            'text-green-600 dark:text-green-400': form.management_type === 'department_wide',
                            'text-muted-foreground': form.management_type === 'no_management'
                        }"
                    />
                    <AlertDescription class="text-foreground">
                        <h4 class="font-semibold mb-2">
                            {{ managementTypes.find(t => t.value === form.management_type)?.label }}
                        </h4>
                        <p class="text-sm text-muted-foreground">
                            <span v-if="form.management_type === 'department_wide'">
                                This grants oversight authority over the entire {{ getDepartmentName(form.department_id) }} department. The manager can make department-wide decisions, set policies, allocate resources, and provide strategic direction for all department employees.
                            </span>
                            <span v-else-if="form.management_type === 'no_management'">
                                This role is designed for individual contributors, technical specialists, consultants, or advisory positions. The person will have expertise and authority in their domain but won't manage other people directly. Perfect for senior developers, architects, analysts, or subject matter experts.
                            </span>
                        </p>
                    </AlertDescription>
                </Alert>

                <!-- Dates and Settings -->
                <Card class="bg-card border-border">
                    <CardHeader class="bg-card text-card-foreground">
                        <CardTitle class="text-card-foreground">Assignment Details</CardTitle>
                        <CardDescription class="text-muted-foreground">Set the timeframe and additional settings for this role</CardDescription>
                    </CardHeader>
                    <CardContent class="bg-card text-card-foreground">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Start Date -->
                            <div class="space-y-2">
                                <Label for="start-date" class="text-foreground">Start Date *</Label>
                                <Input
                                    id="start-date"
                                    type="date"
                                    v-model="form.start_date"
                                    :disabled="form.processing"
                                    required
                                    class="bg-background border-border text-foreground placeholder:text-muted-foreground"
                                />
                                <div v-if="form.errors.start_date" class="text-destructive text-sm">{{ form.errors.start_date }}</div>
                            </div>

                            <!-- End Date -->
                            <div class="space-y-2">
                                <Label for="end-date" class="text-foreground">End Date <span class="text-muted-foreground font-normal">(Optional)</span></Label>
                                <Input
                                    id="end-date"
                                    type="date"
                                    v-model="form.end_date"
                                    :disabled="form.processing"
                                    class="bg-background border-border text-foreground placeholder:text-muted-foreground"
                                />
                                <p class="text-sm text-muted-foreground">Leave empty for permanent assignment</p>
                                <div v-if="form.errors.end_date" class="text-destructive text-sm">{{ form.errors.end_date }}</div>
                            </div>
                        </div>

                        <Separator class="my-6 bg-border" />

                        <!-- Primary Role -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_primary"
                                :checked="form.is_primary"
                                @update:checked="handlePrimaryRoleChange"
                                :disabled="form.processing"
                                class="border-border data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label for="is_primary" class="font-semibold cursor-pointer text-foreground">
                                    Primary Role
                                </Label>
                                <p class="text-sm text-muted-foreground">
                                    Primary roles have higher authority and receive priority notifications. Only one primary role is recommended per person per department.
                                </p>
                            </div>
                        </div>

                        <Separator class="my-6 bg-border" />

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes" class="text-foreground">Notes <span class="text-muted-foreground font-normal">(Optional)</span></Label>
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                :disabled="form.processing"
                                placeholder="Additional notes about this role assignment, responsibilities, special conditions, or expectations..."
                                class="bg-background border-border text-foreground placeholder:text-muted-foreground"
                            />
                            <div v-if="form.errors.notes" class="text-destructive text-sm">{{ form.errors.notes }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-border">
                    <Button
                        type="submit"
                        class="w-full sm:w-auto bg-primary text-primary-foreground hover:bg-primary/90"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span v-if="form.processing">Creating Assignment...</span>
                        <span v-else">Create Role Assignment</span>
                    </Button>
                    <Button
                        :as="Link"
                        :href="route('admin.manager-roles.index')"
                        variant="secondary"
                        class="w-full sm:w-auto bg-secondary text-secondary-foreground hover:bg-secondary/80"
                        :disabled="form.processing"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
