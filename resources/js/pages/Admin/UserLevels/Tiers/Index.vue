<template>
    <Head :title="`${userLevel.name} - Tiers`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ userLevel.name }} - Performance Tiers
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Manage performance tiers within {{ userLevel.name }} level for evaluation and incentives
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button
                        :as="Link"
                        :href="route('admin.user-levels.show', userLevel.id)"
                        variant="outline"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Level
                    </Button>
                    <Button @click="showCreateTierModal = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Add New Tier
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="shrink-0">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <Layers class="h-5 w-5 text-white" />
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-muted-foreground">Total Tiers</div>
                                    <div class="text-2xl font-bold text-foreground">{{ stats.total_tiers || 0 }}</div>
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
                                    <div class="text-sm font-medium text-muted-foreground">Users in Tiers</div>
                                    <div class="text-2xl font-bold text-foreground">{{ stats.total_users_with_tiers || 0 }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="shrink-0">
                                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                        <Badge class="h-5 w-5 text-white" />
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-muted-foreground">Level</div>
                                    <div class="text-lg font-bold text-foreground">{{ userLevel.code }}</div>
                                    <div class="text-xs text-muted-foreground">{{ userLevel.name }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="shrink-0">
                                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                        <TrendingUp class="h-5 w-5 text-white" />
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="text-sm font-medium text-muted-foreground">Hierarchy</div>
                                    <div class="text-lg font-bold text-foreground">Level {{ userLevel.hierarchy_level || 0 }}</div>
                                    <div class="text-xs text-muted-foreground">Authority rank</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tier Management Section -->
                <Card>
                    <CardHeader>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <CardTitle class="flex items-center">
                                    <Layers class="mr-2 h-5 w-5" />
                                    Performance Tier Management
                                </CardTitle>
                                <CardDescription>
                                    Create and manage performance tiers for evaluation and incentive calculations
                                </CardDescription>
                            </div>
                            <Button @click="showCreateTierModal = true" size="sm">
                                <Plus class="mr-2 h-4 w-4" />
                                Add New Tier
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Current Tiers Display -->
                        <div v-if="tiers && tiers.length > 0" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-medium text-foreground">Current Tiers ({{ tiers.length }})</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <Card
                                    v-for="tier in tiers"
                                    :key="tier.id"
                                    class="hover:shadow-md transition-shadow duration-200 border-l-4"
                                    :class="getTierCardBorderClass(tier.tier_order)"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-start justify-between">
                                            <div class="space-y-2 flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <Badge :variant="getTierBadgeVariant(tier.tier_order)" class="text-xs">
                                                        T{{ tier.tier_order }}
                                                    </Badge>
                                                    <span class="font-medium text-foreground">{{ tier.tier_name }}</span>
                                                </div>
                                                <p v-if="tier.description" class="text-sm text-muted-foreground">
                                                    {{ tier.description }}
                                                </p>
                                                <div class="flex items-center space-x-4 text-xs text-muted-foreground">
                                                    <div class="flex items-center">
                                                        <Users class="h-3 w-3 mr-1" />
                                                        {{ tier.users_count || 0 }} users
                                                    </div>
                                                    <div class="flex items-center">
                                                        <Calendar class="h-3 w-3 mr-1" />
                                                        {{ formatDate(tier.created_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex space-x-1 ml-2">
                                                <Button
                                                    @click="editTier(tier)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0"
                                                >
                                                    <Edit class="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    @click="confirmDeleteTier(tier)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <Card v-else class="border-dashed">
                            <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-muted mb-6">
                                    <Layers class="h-8 w-8 text-muted-foreground" />
                                </div>
                                <h3 class="text-lg font-semibold text-foreground mb-2">No Performance Tiers</h3>
                                <p class="text-sm text-muted-foreground mb-6 max-w-sm">
                                    Create performance tiers to organize users within {{ userLevel.name }} for evaluation and incentive purposes.
                                </p>
                                <div class="flex space-x-3">
                                    <Button @click="showCreateTierModal = true">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Create Your First Tier
                                    </Button>
                                    <Button @click="bulkCreateDefaultTiers" variant="outline">
                                        <Layers class="mr-2 h-4 w-4" />
                                        Create Default Tiers
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </CardContent>
                </Card>

                <!-- Detailed Tiers Table -->
                <Card v-if="tiers && tiers.length > 0">
                    <CardHeader>
                        <CardTitle>Tier Details</CardTitle>
                        <CardDescription>Detailed view of all performance tiers with management options</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Order</TableHead>
                                        <TableHead>Tier Name</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Users</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="tier in tiers" :key="tier.id" class="hover:bg-muted/50">
                                        <TableCell>
                                            <Badge :variant="getTierBadgeVariant(tier.tier_order)" class="text-xs">
                                                Tier {{ tier.tier_order }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="font-medium">{{ tier.tier_name }}</TableCell>
                                        <TableCell class="max-w-xs">
                                            <span v-if="tier.description" class="text-sm text-muted-foreground">
                                                {{ tier.description.length > 60 ? tier.description.substring(0, 60) + '...' : tier.description }}
                                            </span>
                                            <span v-else class="text-sm text-muted-foreground italic">No description</span>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center space-x-1">
                                                <Users class="h-3 w-3 text-muted-foreground" />
                                                <span class="text-sm">{{ tier.users_count || 0 }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(tier.created_at) }}
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <Button
                                                    :as="Link"
                                                    :href="route('admin.user-level-tiers.show', [userLevel.id, tier.id])"
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Eye class="mr-1 h-3 w-3" />
                                                    View
                                                </Button>
                                                <Button
                                                    @click="editTier(tier)"
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Edit class="mr-1 h-3 w-3" />
                                                    Edit
                                                </Button>
                                                <Button
                                                    @click="confirmDeleteTier(tier)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 class="mr-1 h-3 w-3" />
                                                    Delete
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Create/Edit Tier Modal -->
        <Dialog v-model:open="showCreateTierModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-purple-100">
                            <Layers class="h-5 w-5 text-purple-600" />
                        </div>
                        <div>
                            <DialogTitle>{{ editingTier ? 'Edit Performance Tier' : 'Create New Performance Tier' }}</DialogTitle>
                            <DialogDescription>
                                {{ editingTier ? 'Update the tier details below' : 'Define a new performance tier for user evaluation and incentive calculations' }}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <form @submit.prevent="submitTierForm" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="tier_name">Tier Name *</Label>
                        <Input
                            id="tier_name"
                            v-model="tierForm.tier_name"
                            placeholder="e.g., High Performer, Standard, Developing"
                            required
                            :disabled="tierForm.processing"
                        />
                        <p class="text-xs text-muted-foreground">Choose a descriptive name for this performance tier</p>
                        <span v-if="tierForm.errors.tier_name" class="text-sm text-destructive">
                            {{ tierForm.errors.tier_name }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <Label for="tier_order">Tier Order *</Label>
                        <Select
                            :model-value="tierForm.tier_order?.toString() || ''"
                            @update:model-value="(value) => tierForm.tier_order = parseInt(value)"
                        >
                            <SelectTrigger id="tier_order">
                                <SelectValue placeholder="Select tier order" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="order in availableTierOrders"
                                    :key="order"
                                    :value="order.toString()"
                                >
                                    Tier {{ order }} {{ getTierOrderLabel(order) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-muted-foreground">
                            Lower numbers indicate higher performance (Tier 1 = highest, Tier 3 = lowest)
                        </p>
                        <span v-if="tierForm.errors.tier_order" class="text-sm text-destructive">
                            {{ tierForm.errors.tier_order }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="tierForm.description"
                            rows="3"
                            placeholder="Describe the performance criteria and expectations for this tier..."
                            :disabled="tierForm.processing"
                        />
                        <p class="text-xs text-muted-foreground">Optional description to help with tier assignment</p>
                        <span v-if="tierForm.errors.description" class="text-sm text-destructive">
                            {{ tierForm.errors.description }}
                        </span>
                    </div>

                    <!-- Tier Preview -->
                    <Alert v-if="tierForm.tier_name && tierForm.tier_order" class="bg-purple-50 border-purple-200">
                        <Layers class="h-4 w-4" />
                        <AlertDescription>
                            <div class="font-medium text-purple-900 mb-1">Tier Preview</div>
                            <div class="flex items-center space-x-2">
                                <Badge :variant="getTierBadgeVariant(tierForm.tier_order)" class="text-xs">
                                    T{{ tierForm.tier_order }}
                                </Badge>
                                <span class="text-sm">{{ tierForm.tier_name }}</span>
                            </div>
                            <p v-if="tierForm.description" class="text-sm text-purple-700 mt-1">
                                {{ tierForm.description }}
                            </p>
                        </AlertDescription>
                    </Alert>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeTierModal"
                            :disabled="tierForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="tierForm.processing || !tierForm.tier_name || !tierForm.tier_order"
                        >
                            <Loader2 v-if="tierForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Plus v-else-if="!editingTier" class="mr-2 h-4 w-4" />
                            <Save v-else class="mr-2 h-4 w-4" />
                            {{ tierForm.processing
                            ? (editingTier ? 'Updating...' : 'Creating...')
                            : (editingTier ? 'Update Tier' : 'Create Tier')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Modal -->
        <AlertDialog v-model:open="showDeleteTierModal">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div class="flex items-start space-x-3">
                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                            <AlertTriangle class="h-5 w-5 text-red-600" />
                        </div>
                        <div>
                            <AlertDialogTitle>Delete Performance Tier</AlertDialogTitle>
                            <AlertDialogDescription>
                                Are you sure you want to delete the tier "{{ tierToDelete?.tier_name }}"?
                            </AlertDialogDescription>
                        </div>
                    </div>
                </AlertDialogHeader>

                <Alert variant="destructive" class="my-4">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div class="font-medium mb-2">This action will:</div>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            <li>Permanently delete this performance tier</li>
                            <li>Remove tier assignment from {{ tierToDelete?.users_count || 0 }} users</li>
                            <li>Affect related incentive calculations</li>
                        </ul>
                        <p class="mt-2 font-medium">This action cannot be undone.</p>
                    </AlertDescription>
                </Alert>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="deleteTier"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Tier
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
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
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    Plus,
    Edit,
    Trash2,
    Layers,
    ArrowUpDown,
    Save,
    Loader2,
    AlertTriangle,
    Users,
    TrendingUp,
    Calendar,
    Eye,
    ArrowLeft
} from 'lucide-vue-next'

const props = defineProps({
    userLevel: Object,
    tiers: Array,
    stats: Object
})

// Breadcrumbs
const breadcrumbs = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Levels', href: route('admin.user-levels.index') },
    { name: props.userLevel.name, href: route('admin.user-levels.show', props.userLevel.id) },
    { name: 'Tiers', href: null }
]

// Reactive variables
const showCreateTierModal = ref(false)
const showDeleteTierModal = ref(false)
const editingTier = ref(null)
const tierToDelete = ref(null)

// Tier form
const tierForm = useForm({
    tier_name: '',
    tier_order: null,
    description: ''
})

// Available tier orders (1-5, excluding already used orders)
const availableTierOrders = computed(() => {
    const existingOrders = props.tiers?.map(t => t.tier_order) || []
    const allOrders = [1, 2, 3, 4, 5]

    // If editing, include the current tier's order
    if (editingTier.value) {
        return allOrders.filter(order =>
            !existingOrders.includes(order) || order === editingTier.value.tier_order
        )
    }

    return allOrders.filter(order => !existingOrders.includes(order))
})

// Helper functions
const getTierBadgeVariant = (order) => {
    switch (order) {
        case 1: return 'default'      // Highest tier
        case 2: return 'secondary'    // Mid tier
        case 3: return 'outline'      // Lower tier
        default: return 'secondary'
    }
}

const getTierCardBorderClass = (order) => {
    switch (order) {
        case 1: return 'border-l-green-500'
        case 2: return 'border-l-blue-500'
        case 3: return 'border-l-yellow-500'
        default: return 'border-l-gray-500'
    }
}

const getTierOrderLabel = (order) => {
    switch (order) {
        case 1: return '(Highest Performance)'
        case 2: return '(Standard Performance)'
        case 3: return '(Developing Performance)'
        case 4: return '(Entry Level)'
        case 5: return '(Lowest Performance)'
        default: return ''
    }
}

const formatDate = (dateString) => {
    if (!dateString) return 'Unknown'
    return new Date(dateString).toLocaleDateString()
}

// Form methods
const submitTierForm = () => {
    const url = editingTier.value
        ? route('admin.user-level-tiers.update', [props.userLevel.id, editingTier.value.id])
        : route('admin.user-level-tiers.store', props.userLevel.id)

    const method = editingTier.value ? 'put' : 'post'

    tierForm[method](url, {
        onSuccess: () => {
            closeTierModal()
        }
    })
}

const editTier = (tier) => {
    editingTier.value = tier
    tierForm.tier_name = tier.tier_name
    tierForm.tier_order = tier.tier_order
    tierForm.description = tier.description || ''
    showCreateTierModal.value = true
}

const closeTierModal = () => {
    showCreateTierModal.value = false
    editingTier.value = null
    tierForm.reset()
    tierForm.clearErrors()
}

const confirmDeleteTier = (tier) => {
    tierToDelete.value = tier
    showDeleteTierModal.value = true
}

const deleteTier = () => {
    if (!tierToDelete.value) return

    router.delete(route('admin.user-level-tiers.destroy', [props.userLevel.id, tierToDelete.value.id]), {
        onSuccess: () => {
            showDeleteTierModal.value = false
            tierToDelete.value = null
        }
    })
}

const bulkCreateDefaultTiers = () => {
    if (confirm('Create 3 default tiers (Tier 1: High Performer, Tier 2: Standard, Tier 3: Developing) for this level?')) {
        router.post(route('admin.user-level-tiers.bulk-create-default', props.userLevel.id))
    }
}

const reorderTiers = () => {
    // Implement tier reordering functionality
    alert('Tier reordering functionality coming soon!')
}
</script>
