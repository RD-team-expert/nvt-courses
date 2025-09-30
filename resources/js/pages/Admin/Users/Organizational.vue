<!--
  User Organizational Profile Page
  Comprehensive view of a user's organizational structure, roles, and relationships
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
    User,
    Users,
    Building2,
    Crown,
    GitBranch,
    BarChart3,
    Eye,
    ArrowRight,
    Star,
    Calendar,
    Mail,
    IdCard,
    UserCheck
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    managerRoles: Array,
    directReports: Array,
    managers: Array,
})

// Get level badge variant
const getLevelVariant = (levelCode: string) => {
    const variants = {
        'L1': 'secondary',
        'L2': 'default',
        'L3': 'outline',
        'L4': 'destructive',
    }
    return variants[levelCode] || 'secondary'
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

// Stats
const stats = computed(() => ({
    managerRoles: props.managerRoles?.length || 0,
    directReports: props.directReports?.length || 0,
    managers: props.managers?.length || 0,
    isManager: (props.managerRoles?.length || 0) > 0
}))

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.user?.name || 'User', href: '#' },
    { name: 'Organizational Profile', href: route('admin.users.organizational', props.user?.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- User Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-16 w-16">
                                <AvatarFallback class="bg-muted text-muted-foreground text-xl font-bold">
                                    {{ getUserInitials(user?.name || 'Unknown') }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 class="text-2xl font-bold text-foreground">{{ user?.name || 'Unknown User' }}</h1>
                                <p class="text-sm text-muted-foreground flex items-center">
                                    <Mail class="mr-1 h-3 w-3" />
                                    {{ user?.email || 'No email' }}
                                </p>
                                <div class="mt-2 flex items-center space-x-2 flex-wrap gap-1">
                                    <Badge v-if="user?.employee_code" variant="outline" class="text-xs">
                                        <IdCard class="mr-1 h-3 w-3" />
                                        {{ user.employee_code }}
                                    </Badge>
                                    <Badge v-if="user?.level" :variant="getLevelVariant(user.level.code)">
                                        {{ user.level.code }} - {{ user.level.name }}
                                    </Badge>
                                    <Badge :variant="getStatusVariant(user?.status)">
                                        {{ user?.status || 'Unknown' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <Button
                                :as="Link"
                                :href="route('admin.users.reporting-chain', user?.id)"
                                v-if="user?.id"
                                variant="outline"
                            >
                                <GitBranch class="mr-2 h-4 w-4" />
                                Reporting Chain
                            </Button>
                            <Button
                                :as="Link"
                                :href="route('admin.users.direct-reports', user?.id)"
                                v-if="stats.isManager && user?.id"
                            >
                                <Users class="mr-2 h-4 w-4" />
                                View Team ({{ stats.directReports }})
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
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Crown class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Manager Roles</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.managerRoles }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <Users class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Direct Reports</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.directReports }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <User class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Reports To</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.managers }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <BarChart3 class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Hierarchy Level</div>
                                <div class="text-2xl font-bold text-foreground">{{ user?.level?.hierarchy_level || 'N/A' }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User Details -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <User class="mr-2 h-5 w-5" />
                            User Information
                        </CardTitle>
                        <CardDescription>Personal and organizational details</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">Department</div>
                                <div v-if="user?.department" class="flex items-center">
                                    <Building2 class="mr-2 h-4 w-4 text-muted-foreground" />
                                    <span class="text-sm text-foreground">
                                        {{ user.department.name }} ({{ user.department.code }})
                                        <span v-if="user.department.parent" class="text-muted-foreground ml-2">
                                            → {{ user.department.parent }}
                                        </span>
                                    </span>
                                </div>
                                <div v-else class="text-sm text-muted-foreground italic">No department assigned</div>
                            </div>

                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">User Level</div>
                                <Badge v-if="user?.level" :variant="getLevelVariant(user.level.code)">
                                    {{ user.level.code }} - {{ user.level.name }} (Level {{ user.level.hierarchy_level }})
                                </Badge>
                                <span v-else class="text-sm text-muted-foreground italic">No level assigned</span>
                            </div>

                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">Employee Code</div>
                                <div class="flex items-center">
                                    <IdCard class="mr-2 h-4 w-4 text-muted-foreground" />
                                    <span class="text-sm text-foreground">{{ user?.employee_code || 'Not assigned' }}</span>
                                </div>
                            </div>

                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">Join Date</div>
                                <div class="flex items-center">
                                    <Calendar class="mr-2 h-4 w-4 text-muted-foreground" />
                                    <span class="text-sm text-foreground">{{ user?.created_at || 'Unknown' }}</span>
                                </div>
                            </div>

                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">Status</div>
                                <Badge :variant="getStatusVariant(user?.status)">
                                    {{ user?.status || 'Unknown' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Manager Roles -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Crown class="mr-2 h-5 w-5" />
                            Manager Roles ({{ stats.managerRoles }})
                        </CardTitle>
                        <CardDescription>Management responsibilities and authority levels</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="managerRoles && managerRoles.length > 0" class="space-y-3">
                            <div v-for="role in managerRoles" :key="role.id"
                                 class="border rounded-lg p-4"
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
                                <div class="flex items-center space-x-4 text-xs text-muted-foreground">
                                    <span>Authority Level {{ role.authority_level }}</span>
                                    <span v-if="role.start_date">Since {{ role.start_date }}</span>
                                    <Badge :variant="role.is_active ? 'default' : 'destructive'" class="text-xs">
                                        {{ role.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <Crown class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-sm font-medium text-foreground mb-2">No manager roles</h3>
                            <p class="text-sm text-muted-foreground">This user is not assigned any management responsibilities.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Reports and Managers Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Direct Reports -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center">
                                    <Users class="mr-2 h-5 w-5" />
                                    Direct Reports ({{ stats.directReports }})
                                </CardTitle>
                                <CardDescription>Team members directly reporting to this user</CardDescription>
                            </div>
                            <Button
                                v-if="stats.directReports > 0 && user?.id"
                                :as="Link"
                                :href="route('admin.users.direct-reports', user.id)"
                                variant="ghost"
                                size="sm"
                            >
                                View All
                                <ArrowRight class="ml-1 h-3 w-3" />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="directReports && directReports.length > 0" class="space-y-3">
                            <div v-for="report in directReports.slice(0, 5)" :key="report.id"
                                 class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                                <Avatar class="h-8 w-8">
                                    <AvatarFallback class="bg-muted text-muted-foreground text-xs">
                                        {{ getUserInitials(report.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-foreground">{{ report.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ report.email }}</div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <Badge v-if="report.level" variant="outline" class="text-xs">{{ report.level }}</Badge>
                                        <Badge v-if="report.department" variant="outline" class="text-xs">{{ report.department }}</Badge>
                                    </div>
                                </div>
                            </div>
                            <div v-if="directReports.length > 5" class="text-center pt-2">
                                <Button
                                    :as="Link"
                                    :href="route('admin.users.direct-reports', user.id)"
                                    variant="ghost"
                                    size="sm"
                                >
                                    View {{ directReports.length - 5 }} more reports
                                    <ArrowRight class="ml-1 h-3 w-3" />
                                </Button>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-sm font-medium text-foreground mb-2">No direct reports</h3>
                            <p class="text-sm text-muted-foreground">This user doesn't manage anyone directly.</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Managers -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center">
                                    <UserCheck class="mr-2 h-5 w-5" />
                                    Reports To ({{ stats.managers }})
                                </CardTitle>
                                <CardDescription>Managers and supervisors this user reports to</CardDescription>
                            </div>
                            <Button
                                v-if="user?.id"
                                :as="Link"
                                :href="route('admin.users.reporting-chain', user.id)"
                                variant="ghost"
                                size="sm"
                            >
                                View Chain
                                <ArrowRight class="ml-1 h-3 w-3" />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="managers && managers.length > 0" class="space-y-3">
                            <div v-for="manager in managers" :key="manager.id"
                                 class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                                <Avatar class="h-8 w-8">
                                    <AvatarFallback class="bg-primary text-primary-foreground text-xs">
                                        {{ getUserInitials(manager.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-foreground">{{ manager.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ manager.email }}</div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <Badge v-if="manager.level" variant="outline" class="text-xs">{{ manager.level }}</Badge>
                                        <Badge v-if="manager.department" variant="outline" class="text-xs">{{ manager.department }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <UserCheck class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-sm font-medium text-foreground mb-2">No direct managers</h3>
                            <p class="text-sm text-muted-foreground">This user doesn't have assigned managers.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
