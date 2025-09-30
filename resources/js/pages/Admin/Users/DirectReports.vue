<!--
  Manager Direct Reports Page
  Interface for viewing a manager's team members and department-wide roles
-->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Users,
    User,
    UserCheck,
    Building2,
    Star,
    Eye,
    GitBranch,
    Plus,
    BarChart3,
    Grid3X3,
    Crown
} from 'lucide-vue-next'

const props = defineProps({
    manager: Object,
    directReports: Array,
    departmentRoles: Array,
    stats: Object,
})

// Get level badge variant
const getLevelVariant = (hierarchyLevel: number) => {
    const variants = {
        1: 'secondary',
        2: 'default',
        3: 'outline',
        4: 'destructive',
    }
    return variants[hierarchyLevel] || 'secondary'
}

// Get role badge variant
const getRoleVariant = (roleType: string) => {
    const variants = {
        'direct_manager': 'default',
        'project_manager': 'secondary',
        'department_head': 'outline',
        'senior_manager': 'destructive',
        'team_lead': 'secondary',
    }
    return variants[roleType] || 'secondary'
}

// Get user status variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'default'
        case 'inactive': return 'destructive'
        case 'on_leave': return 'secondary'
        default: return 'outline'
    }
}

// Get user initials for avatar
const getUserInitials = (name: string) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
}

// Computed stats
const computedStats = computed(() => ({
    totalReports: props.directReports?.length || 0,
    activeReports: props.directReports?.filter(r => r.status === 'active').length || 0,
    departmentRoles: props.departmentRoles?.length || 0,
    primaryReports: props.directReports?.filter(r => r.is_primary).length || 0,
}))

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.manager?.name || 'Manager', href: '#' },
    { name: 'Direct Reports', href: route('admin.users.direct-reports', props.manager?.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Manager Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-16 w-16">
                                <AvatarFallback class="bg-primary text-primary-foreground text-xl font-bold">
                                    {{ getUserInitials(manager?.name || 'Unknown') }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 class="text-2xl font-bold text-foreground">{{ manager?.name || 'Unknown Manager' }}'s Team</h1>
                                <p class="text-sm text-muted-foreground">{{ manager?.email || 'No email' }}</p>
                                <div class="mt-2 flex items-center space-x-4 flex-wrap gap-2">
                                    <Badge v-if="manager?.level" variant="outline">
                                        Level: {{ manager.level }}
                                    </Badge>
                                    <Badge v-if="manager?.department" variant="outline">
                                        {{ manager.department }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <Button
                                :as="Link"
                                :href="route('admin.users.organizational', manager?.id)"
                                v-if="manager?.id"
                                variant="outline"
                            >
                                <Eye class="mr-2 h-4 w-4" />
                                View Profile
                            </Button>
                            <Button
                                :as="Link"
                                :href="route('admin.users.reporting-chain', manager?.id)"
                                v-if="manager?.id"
                            >
                                <GitBranch class="mr-2 h-4 w-4" />
                                Reporting Chain
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <Users class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Total Reports</div>
                                <div class="text-2xl font-bold text-foreground">{{ computedStats.totalReports }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <UserCheck class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Active</div>
                                <div class="text-2xl font-bold text-foreground">{{ computedStats.activeReports }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Building2 class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Dept Roles</div>
                                <div class="text-2xl font-bold text-foreground">{{ computedStats.departmentRoles }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <Star class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Primary</div>
                                <div class="text-2xl font-bold text-foreground">{{ computedStats.primaryReports }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Direct Reports List -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Users class="mr-2 h-5 w-5" />
                                Direct Reports
                            </CardTitle>
                            <CardDescription>Team members directly reporting to this manager</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="directReports && directReports.length > 0" class="space-y-4">
                                <div v-for="report in directReports" :key="report.id"
                                     class="p-4 rounded-lg border hover:bg-muted/50 transition-colors"
                                     :class="{ 'bg-primary/5 border-primary/20': report.is_primary }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <Avatar>
                                                <AvatarFallback class="bg-muted text-muted-foreground">
                                                    {{ getUserInitials(report.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 mb-1">
                                                    <h3 class="font-medium text-foreground">{{ report.name }}</h3>
                                                    <Badge v-if="report.is_primary" variant="default" class="text-xs">
                                                        <Star class="mr-1 h-3 w-3" />
                                                        Primary Report
                                                    </Badge>
                                                </div>
                                                <p class="text-sm text-muted-foreground">{{ report.email }}</p>
                                                <div class="flex items-center space-x-2 mt-2 flex-wrap gap-1">
                                                    <Badge v-if="report.employee_code" variant="outline" class="text-xs">
                                                        ID: {{ report.employee_code }}
                                                    </Badge>
                                                    <Badge v-if="report.level" :variant="getLevelVariant(report.level_hierarchy)" class="text-xs">
                                                        {{ report.level }}
                                                    </Badge>
                                                    <Badge v-if="report.department" variant="outline" class="text-xs">
                                                        {{ report.department }}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <div class="text-right">
                                                <Badge v-if="report.role_display" :variant="getRoleVariant(report.role_type)" class="mb-1">
                                                    {{ report.role_display }}
                                                </Badge>
                                                <div v-if="report.start_date" class="text-xs text-muted-foreground">
                                                    Since {{ report.start_date }}
                                                </div>
                                            </div>

                                            <Badge :variant="getStatusVariant(report.status)">
                                                {{ report.status }}
                                            </Badge>

                                            <Button
                                                :as="Link"
                                                :href="route('admin.users.organizational', report.id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-12">
                                <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <h3 class="text-lg font-medium text-foreground mb-2">No direct reports</h3>
                                <p class="text-sm text-muted-foreground">This manager doesn't have any direct reports assigned.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Department-Wide Roles Sidebar -->
                <div class="space-y-6">
                    <!-- Department Roles -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Crown class="mr-2 h-5 w-5" />
                                Department-Wide Roles
                            </CardTitle>
                            <CardDescription>Additional roles and responsibilities across departments</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="departmentRoles && departmentRoles.length > 0" class="space-y-3">
                                <div v-for="role in departmentRoles" :key="role.id"
                                     class="border rounded-lg p-3"
                                     :class="{ 'border-primary bg-primary/5': role.is_primary }">
                                    <div class="flex items-center justify-between mb-2">
                                        <Badge :variant="getRoleVariant(role.role_type)">
                                            {{ role.role_display }}
                                        </Badge>
                                        <Badge v-if="role.is_primary" variant="default" class="text-xs">
                                            <Star class="mr-1 h-3 w-3" />
                                            Primary
                                        </Badge>
                                    </div>
                                    <div class="text-sm font-medium text-foreground mb-1">{{ role.department }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        Authority Level {{ role.authority_level }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-6">
                                <Building2 class="mx-auto h-8 w-8 text-muted-foreground mb-2" />
                                <p class="text-sm text-muted-foreground">No department-wide roles</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                            <CardDescription>Common management tasks and operations</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <Button
                                    :as="Link"
                                    :href="route('admin.manager-roles.create')"
                                    variant="outline"
                                    class="w-full justify-start"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Assign New Role
                                </Button>

                                <Button
                                    :as="Link"
                                    :href="route('admin.manager-roles.index')"
                                    variant="outline"
                                    class="w-full justify-start"
                                >
                                    <BarChart3 class="mr-2 h-4 w-4" />
                                    View All Roles
                                </Button>

                                <Button
                                    :as="Link"
                                    :href="route('admin.manager-roles.matrix')"
                                    variant="outline"
                                    class="w-full justify-start"
                                >
                                    <Grid3X3 class="mr-2 h-4 w-4" />
                                    Matrix View
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
