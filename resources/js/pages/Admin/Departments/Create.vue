<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { onUnmounted, nextTick, ref } from 'vue'

const props = defineProps({
    parentDepartments: Array,
})

// Add a mounted flag to prevent operations on unmounted component
const isMounted = ref(true)

const form = useForm({
    name: '',
    description: '',
    parent_id: '',
    department_code: '',
    is_active: true,
})

function submit() {
    if (!isMounted.value) return // Prevent submission if unmounted

    form.post('/admin/departments', {
        onSuccess: () => {
            // Will redirect automatically on success
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    })
}

// Generate department code from name with safety check
const generateCode = async () => {
    if (!isMounted.value || !form.name) return // Safety check

    await nextTick() // Wait for DOM updates

    if (form.name && isMounted.value) {
        form.department_code = form.name
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .substring(0, 10);
    }
}

// Handle parent department change with safety - FIXED: Handle null/none values
const handleParentChange = (value) => {
    if (!isMounted.value) return
    // Convert 'none' back to empty string for form submission
    form.parent_id = value === 'none' ? '' : value
}

// Handle switch change with safety
const handleSwitchChange = (value) => {
    if (!isMounted.value) return
    form.is_active = value
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Departments', href: route('admin.departments.index') },
    { name: 'Create Department', href: route('admin.departments.create') }
]

// Cleanup on unmount
onUnmounted(() => {
    isMounted.value = false
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <h1 class="text-xl sm:text-2xl font-bold text-foreground mb-6">Create New Department</h1>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>Department Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            <!-- Department Name -->
                            <div class="col-span-full space-y-2">
                                <Label for="name" class="font-semibold">Department Name *</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    @input="generateCode"
                                    :disabled="form.processing"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-destructive text-sm">{{ form.errors.name }}</div>
                            </div>

                            <!-- Department Code -->
                            <div class="col-span-1 space-y-2">
                                <Label for="department_code" class="font-semibold">Department Code *</Label>
                                <Input
                                    id="department_code"
                                    type="text"
                                    v-model="form.department_code"
                                    :disabled="form.processing"
                                    maxlength="20"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">Short code for the department (e.g., HR, IT, SALES)</p>
                                <div v-if="form.errors.department_code" class="text-destructive text-sm">{{ form.errors.department_code }}</div>
                            </div>

                            <!-- Parent Department - FIXED: Use 'none' instead of empty string -->
                            <div class="col-span-1 space-y-2">
                                <Label for="parent_id" class="font-semibold">Parent Department</Label>
                                <Select
                                    :key="`parent-select-${parentDepartments?.length || 0}`"
                                    :model-value="form.parent_id || 'none'"
                                    @update:model-value="handleParentChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="parent_id">
                                        <SelectValue placeholder="None (Top Level)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None (Top Level)</SelectItem>
                                        <SelectItem
                                            v-for="dept in (parentDepartments || [])"
                                            :key="`dept-${dept.id}`"
                                            :value="dept.id.toString()"
                                        >
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.parent_id" class="text-destructive text-sm">{{ form.errors.parent_id }}</div>
                            </div>

                            <!-- Description -->
                            <div class="col-span-full space-y-2">
                                <Label for="description" class="font-semibold">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    :disabled="form.processing"
                                />
                                <div v-if="form.errors.description" class="text-destructive text-sm">{{ form.errors.description }}</div>
                            </div>

                            <!-- Status - FIXED: Explicit event handling -->
                            <div class="col-span-full space-y-3">
                                <div class="flex items-center space-x-3">
                                    <Switch
                                        id="is_active"
                                        :checked="form.is_active"
                                        @update:checked="handleSwitchChange"
                                        :disabled="form.processing"
                                    />
                                    <div class="space-y-1">
                                        <Label for="is_active" class="text-sm font-medium cursor-pointer">
                                            Active Department
                                        </Label>
                                        <p class="text-xs text-muted-foreground">Inactive departments won't be available for user assignment</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t">
                            <Button
                                type="submit"
                                class="w-full sm:w-auto"
                                :disabled="form.processing || !isMounted"
                            >
                                <svg v-if="form.processing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="form.processing">Creating Department...</span>
                                <span v-else>Create Department</span>
                            </Button>
                            <Button
                                :as="Link"
                                :href="route('admin.departments.index')"
                                variant="secondary"
                                class="w-full sm:w-auto"
                                :disabled="form.processing"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
