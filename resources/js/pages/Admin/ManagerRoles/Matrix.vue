<!--
  Manager Roles Matrix View Page
  Visual representation of management hierarchy and department organization
-->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible'
import {
    Building,
    Users,
    UserCheck,
    CheckCircle,
    Zap,
    Plus,
    List,
    ChevronRight,
    Crown,
    ArrowRight
} from 'lucide-vue-next'

const props = defineProps({
    departments: Array,
})

const expandedDepartments = ref(new Set())
const selectedView = ref('hierarchy') // 'hierarchy' or 'table'

// Toggle department expansion
const toggleDepartment = (departmentId: number) => {
    if (expandedDepartments.value.has(departmentId)) {
        expandedDepartments.value.delete(departmentId)
    } else {
        expandedDepartments.value.add(departmentId)
    }
}

// Get role type badge variant
const getRoleTypeVariant = (roleType: string) => {
    const variants = {
        'Direct Manager': 'default',
        'Project Manager': 'secondary',
        'Department Head': 'outline',
        'Senior Manager': 'destructive',
        'Team Lead': 'secondary',
    }
    return variants[roleType] || 'outline'
}

// Get level badge variant
const getLevelVariant = (level: string) => {
    if (!level) return 'secondary'

    const variants = {
        'L1': 'default',
        'L2': 'secondary',
        'L3': 'outline',
        'L4': 'destructive',
    }
    return variants[level] || 'secondary'
}

// Compute statistics
const stats = computed(() => {
    let totalManagers = 0
    let totalReports = 0
    let departmentHeads = 0
    let directManagers = 0

    props.departments?.forEach(dept => {
        dept.roles?.forEach(role => {
            totalManagers++
            if (role.managed_user) totalReports++
            if (role.role_type === 'Department Head') departmentHeads++
            if (role.role_type === 'Direct Manager') directManagers++
        })
    })

    return {
        totalDepartments: props.departments?.length || 0,
        totalManagers,
        totalReports,
        departmentHeads,
        directManagers
    }
})

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Manager Roles', href: route('admin.manager-roles.index') },
    { name: 'Matrix View', href: route('admin.manager-roles.matrix') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Manager Roles Matrix</h1>
                    <p class="text-sm text-muted-foreground mt-1">Visual representation of management hierarchy and department organization</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Button :as="Link" :href="route('admin.manager-roles.create')" class="w-full sm:w-auto">
                        <Plus class="mr-2 h-4 w-4" />
                        Assign New Role
                    </Button>
                    <Button :as="Link" :href="route('admin.manager-roles.index')" variant="secondary" class="w-full sm:w-auto">
                        <List class="mr-2 h-4 w-4" />
                        List View
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">
                                    <Building class="h-5 w-5 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-xs font-medium text-muted-foreground">Departments</div>
                                <div class="text-lg font-semibold text-foreground">{{ stats.totalDepartments }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <Users class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-xs font-medium text-muted-foreground">Managers</div>
                                <div class="text-lg font-semibold text-foreground">{{ stats.totalManagers }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <UserCheck class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-xs font-medium text-muted-foreground">Direct Reports</div>
                                <div class="text-lg font-semibold text-foreground">{{ stats.totalReports }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <CheckCircle class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-xs font-medium text-muted-foreground">Dept Heads</div>
                                <div class="text-lg font-semibold text-foreground">{{ stats.departmentHeads }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                    <Zap class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-xs font-medium text-muted-foreground">Direct Mgrs</div>
                                <div class="text-lg font-semibold text-foreground">{{ stats.directManagers }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- View Toggle -->
            <Card>
                <CardContent class="p-6">
                    <Tabs v-model="selectedView" class="w-full">
                        <TabsList class="grid w-full grid-cols-2 max-w-md">
                            <TabsTrigger value="hierarchy">Hierarchy View</TabsTrigger>
                            <TabsTrigger value="table">Table View</TabsTrigger>
                        </TabsList>

                        <!-- Hierarchy View -->
                        <TabsContent value="hierarchy" class="mt-6">
                            <div class="space-y-4">
                                <div v-for="department in departments" :key="department.id">
                                    <Collapsible
                                        :open="expandedDepartments.has(department.id)"
                                        @update:open="() => toggleDepartment(department.id)"
                                    >
                                        <Card>
                                            <!-- Department Header -->
                                            <CollapsibleTrigger class="w-full">
                                                <CardContent class="p-6">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center space-x-4">
                                                            <div class="shrink-0">
                                                                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                                                                    <Building class="w-6 h-6 text-primary" />
                                                                </div>
                                                            </div>
                                                            <div class="text-left">
                                                                <h3 class="text-lg font-semibold text-foreground">{{ department.name }}</h3>
                                                                <p class="text-sm text-muted-foreground">{{ department.roles?.length || 0 }} manager roles</p>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="mr-3 text-sm text-muted-foreground">
                                                                {{ expandedDepartments.has(department.id) ? 'Collapse' : 'Expand' }}
                                                            </span>
                                                            <ChevronRight
                                                                :class="{ 'rotate-90': expandedDepartments.has(department.id) }"
                                                                class="w-5 h-5 text-muted-foreground transition-transform duration-200"
                                                            />
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </CollapsibleTrigger>

                                            <!-- Department Roles -->
                                            <CollapsibleContent>
                                                <div class="px-6 pb-6">
                                                    <div v-if="!department.roles || department.roles.length === 0" class="text-center py-8 text-muted-foreground">
                                                        <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                                                        <h3 class="mt-2 text-sm font-medium text-foreground">No managers assigned</h3>
                                                        <p class="mt-1 text-sm text-muted-foreground">This department doesn't have any manager roles yet.</p>
                                                    </div>

                                                    <div v-else class="grid gap-4">
                                                        <Card
                                                            v-for="(role, index) in department.roles"
                                                            :key="index"
                                                            class="relative"
                                                            :class="role.is_primary ? 'border-primary bg-primary/5' : ''"
                                                        >
                                                            <CardContent class="p-4">
                                                                <!-- Primary Badge -->
                                                                <div v-if="role.is_primary" class="absolute top-2 right-2">
                                                                    <Badge variant="default">
                                                                        <Crown class="mr-1 h-3 w-3" />
                                                                        Primary
                                                                    </Badge>
                                                                </div>

                                                                <div class="flex items-center justify-between">
                                                                    <div class="flex items-center space-x-4 flex-1">
                                                                        <!-- Manager Info -->
                                                                        <Avatar class="h-10 w-10">
                                                                            <AvatarFallback>
                                                                                {{ role.manager?.name?.charAt(0)?.toUpperCase() }}
                                                                            </AvatarFallback>
                                                                        </Avatar>
                                                                        <div class="min-w-0 flex-1">
                                                                            <div class="flex items-center space-x-2">
                                                                                <p class="text-sm font-medium text-foreground">{{ role.manager?.name }}</p>
                                                                                <Badge v-if="role.manager?.level" :variant="getLevelVariant(role.manager.level)" class="text-xs">
                                                                                    {{ role.manager.level }}
                                                                                </Badge>
                                                                            </div>
                                                                            <div class="mt-1">
                                                                                <Badge :variant="getRoleTypeVariant(role.role_type)" class="text-xs">
                                                                                    {{ role.role_type }}
                                                                                </Badge>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Arrow -->
                                                                        <div v-if="role.managed_user" class="shrink-0">
                                                                            <ArrowRight class="w-5 h-5 text-muted-foreground" />
                                                                        </div>

                                                                        <!-- Managed User Info -->
                                                                        <div v-if="role.managed_user" class="shrink-0">
                                                                            <div class="flex items-center space-x-2">
                                                                                <Avatar class="h-8 w-8">
                                                                                    <AvatarFallback class="bg-green-100 text-green-600 text-xs">
                                                                                        {{ role.managed_user.name?.charAt(0)?.toUpperCase() }}
                                                                                    </AvatarFallback>
                                                                                </Avatar>
                                                                                <div>
                                                                                    <p class="text-xs font-medium text-foreground">{{ role.managed_user.name }}</p>
                                                                                    <p v-if="role.managed_user.level" class="text-xs text-muted-foreground">{{ role.managed_user.level }}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Department-wide role indicator -->
                                                                        <div v-else class="shrink-0 text-center">
                                                                            <div class="w-8 h-8 bg-muted rounded-full flex items-center justify-center">
                                                                                <Building class="w-4 h-4 text-muted-foreground" />
                                                                            </div>
                                                                            <p class="text-xs text-muted-foreground mt-1">Department-wide</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </CardContent>
                                                        </Card>
                                                    </div>
                                                </div>
                                            </CollapsibleContent>
                                        </Card>
                                    </Collapsible>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Table View -->
                        <TabsContent value="table" class="mt-6">
                            <Card>
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Department</TableHead>
                                                <TableHead>Manager</TableHead>
                                                <TableHead>Role Type</TableHead>
                                                <TableHead>Manages</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <template v-for="department in departments" :key="department.id">
                                                <TableRow
                                                    v-for="(role, index) in department.roles"
                                                    :key="`${department.id}-${index}`"
                                                    :class="{ 'bg-primary/5': role.is_primary }"
                                                >
                                                    <TableCell>
                                                        <div class="font-medium text-foreground">{{ department.name }}</div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div class="flex items-center">
                                                            <Avatar class="h-8 w-8">
                                                                <AvatarFallback class="text-xs">
                                                                    {{ role.manager?.name?.charAt(0)?.toUpperCase() }}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            <div class="ml-3">
                                                                <div class="font-medium text-foreground">{{ role.manager?.name }}</div>
                                                                <div class="text-sm text-muted-foreground">{{ role.manager?.level || 'N/A' }}</div>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge :variant="getRoleTypeVariant(role.role_type)">
                                                            {{ role.role_type }}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div v-if="role.managed_user">
                                                            <div class="font-medium text-foreground">{{ role.managed_user.name }}</div>
                                                            <div class="text-sm text-muted-foreground">{{ role.managed_user.level || 'N/A' }}</div>
                                                        </div>
                                                        <div v-else class="text-sm text-muted-foreground">
                                                            Department-wide role
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge v-if="role.is_primary" variant="default">
                                                            <Crown class="mr-1 h-3 w-3" />
                                                            Primary
                                                        </Badge>
                                                        <Badge v-else variant="secondary">
                                                            Secondary
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            </template>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Empty state -->
                                <div v-if="!departments || departments.length === 0" class="text-center py-12">
                                    <Building class="mx-auto h-12 w-12 text-muted-foreground" />
                                    <h3 class="mt-2 text-sm font-medium text-foreground">No manager roles found</h3>
                                    <p class="mt-1 text-sm text-muted-foreground">Start by assigning manager roles to users.</p>
                                    <div class="mt-6">
                                        <Button :as="Link" :href="route('admin.manager-roles.create')">
                                            <Plus class="mr-2 h-4 w-4" />
                                            Assign New Role
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
