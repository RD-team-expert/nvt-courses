<!--
  User Reporting Chain Page
  Visual representation of the management hierarchy and reporting structure for a user
-->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    ArrowLeft,
    GitBranch,
    TrendingUp,
    Building2,
    Crown,
    User,
    MapPin,
    BarChart3
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    reportingChain: Array,
    chainLength: Number,
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

// Get user initials for avatar
const getUserInitials = (name: string) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
}

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.user?.name || 'User', href: '#' },
    { name: 'Reporting Chain', href: route('admin.users.reporting-chain', props.user?.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Reporting Chain</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Management hierarchy for {{ user?.name || 'Unknown User' }}
                    </p>
                </div>

                <Button
                    :as="Link"
                    :href="route('admin.users.organizational', user?.id)"
                    v-if="user?.id"
                    variant="outline"
                >
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Profile
                </Button>
            </div>

            <!-- Chain Overview -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-medium text-foreground">Chain Overview</h2>
                            <p class="text-sm text-muted-foreground">{{ chainLength }} management levels above this user</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-primary">{{ chainLength }}</div>
                            <div class="text-sm text-muted-foreground">Management Levels</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Reporting Chain Visualization -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <GitBranch class="mr-2 h-5 w-5" />
                        Management Hierarchy
                    </CardTitle>
                    <CardDescription>Visual representation of the reporting structure from bottom to top</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <!-- Current User (Bottom of Chain) -->
                        <div class="flex items-center">
                            <Card class="flex-1 bg-primary/5 border-primary/20">
                                <CardContent class="p-4">
                                    <div class="flex items-center space-x-4">
                                        <Avatar class="h-12 w-12">
                                            <AvatarFallback class="bg-primary text-primary-foreground text-lg font-medium">
                                                {{ getUserInitials(user?.name || 'Unknown') }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <div class="font-medium text-foreground">{{ user?.name || 'Unknown User' }}</div>
                                            <div class="text-sm text-muted-foreground">{{ user?.email || 'No email' }}</div>
                                            <div class="flex items-center space-x-2 mt-1 flex-wrap gap-1">
                                                <Badge v-if="user?.level" variant="secondary">
                                                    {{ user.level }}
                                                </Badge>
                                                <Badge v-if="user?.department" variant="outline" class="text-xs">
                                                    {{ user.department }}
                                                </Badge>
                                            </div>
                                        </div>
                                        <Badge variant="default" class="whitespace-nowrap">
                                            <MapPin class="mr-1 h-3 w-3" />
                                            YOU ARE HERE
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Chain Arrow Up -->
                        <div v-if="reportingChain && reportingChain.length > 0" class="flex justify-center">
                            <div class="flex flex-col items-center">
                                <TrendingUp class="w-6 h-6 text-muted-foreground" />
                                <span class="text-xs text-muted-foreground mt-1">Reports To</span>
                            </div>
                        </div>

                        <!-- Management Chain -->
                        <div v-for="(manager, index) in reportingChain" :key="manager.id" class="space-y-4">
                            <div class="flex items-center">
                                <Card class="flex-1"
                                      :class="index === reportingChain.length - 1 ? 'bg-destructive/5 border-destructive/20' : ''">
                                    <CardContent class="p-4">
                                        <div class="flex items-center space-x-4">
                                            <Avatar class="h-12 w-12">
                                                <AvatarFallback
                                                    :class="index === reportingChain.length - 1 ? 'bg-destructive text-destructive-foreground' : 'bg-muted text-muted-foreground'"
                                                    class="text-lg font-medium"
                                                >
                                                    {{ getUserInitials(manager.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex-1">
                                                <div class="font-medium text-foreground">{{ manager.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ manager.email }}</div>
                                                <div class="flex items-center space-x-2 mt-1 flex-wrap gap-1">
                                                    <Badge v-if="manager.level" :variant="getLevelVariant(manager.level_hierarchy)">
                                                        {{ manager.level }}
                                                    </Badge>
                                                    <Badge v-if="manager.department" variant="outline" class="text-xs">
                                                        {{ manager.department }}
                                                    </Badge>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <div class="text-right">
                                                    <div class="text-xs text-muted-foreground">Management Level</div>
                                                    <div class="text-sm font-medium text-foreground">{{ manager.position_level }}</div>
                                                </div>
                                                <Badge v-if="index === reportingChain.length - 1" variant="destructive" class="whitespace-nowrap">
                                                    <Crown class="mr-1 h-3 w-3" />
                                                    TOP EXECUTIVE
                                                </Badge>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Chain Arrow Up -->
                            <div v-if="index < reportingChain.length - 1" class="flex justify-center">
                                <div class="flex flex-col items-center">
                                    <TrendingUp class="w-6 h-6 text-muted-foreground" />
                                    <span class="text-xs text-muted-foreground mt-1">Reports To</span>
                                </div>
                            </div>
                        </div>

                        <!-- No Chain Message -->
                        <div v-if="!reportingChain || reportingChain.length === 0" class="text-center py-12">
                            <User class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">No reporting chain found</h3>
                            <p class="text-sm text-muted-foreground">This user doesn't have any managers assigned in the system.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Chain Summary -->
            <Card v-if="reportingChain && reportingChain.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <BarChart3 class="mr-2 h-5 w-5" />
                        Chain Summary
                    </CardTitle>
                    <CardDescription>Key metrics and statistics about this reporting chain</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-primary/10 rounded-full flex items-center justify-center">
                                <GitBranch class="h-8 w-8 text-primary" />
                            </div>
                            <div class="text-3xl font-bold text-primary">{{ chainLength }}</div>
                            <div class="text-sm text-muted-foreground">Management Levels</div>
                        </div>
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-green-500/10 rounded-full flex items-center justify-center">
                                <Crown class="h-8 w-8 text-green-600" />
                            </div>
                            <div class="text-3xl font-bold text-green-600">
                                {{ reportingChain[reportingChain.length - 1]?.level_hierarchy || 'N/A' }}
                            </div>
                            <div class="text-sm text-muted-foreground">Top Executive Level</div>
                        </div>
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-purple-500/10 rounded-full flex items-center justify-center">
                                <Building2 class="h-8 w-8 text-purple-600" />
                            </div>
                            <div class="text-3xl font-bold text-purple-600">
                                {{ new Set(reportingChain.map(m => m.department)).size }}
                            </div>
                            <div class="text-sm text-muted-foreground">Departments Involved</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Chain Path Visual -->
            <Card v-if="reportingChain && reportingChain.length > 0">
                <CardHeader>
                    <CardTitle>Hierarchy Path</CardTitle>
                    <CardDescription>Simplified view of the complete reporting path</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center space-x-2 overflow-x-auto pb-2">
                        <Badge variant="default" class="whitespace-nowrap">{{ user?.name }}</Badge>
                        <TrendingUp class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                        <template v-for="(manager, index) in reportingChain" :key="`path-${manager.id}`">
                            <Badge
                                :variant="index === reportingChain.length - 1 ? 'destructive' : 'outline'"
                                class="whitespace-nowrap"
                            >
                                {{ manager.name }}
                            </Badge>
                            <TrendingUp
                                v-if="index < reportingChain.length - 1"
                                class="h-4 w-4 text-muted-foreground flex-shrink-0"
                            />
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
