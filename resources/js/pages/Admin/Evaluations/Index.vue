<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
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
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

// Icons
import {
    Plus,
    Edit,
    Trash2,
    Settings,
    DollarSign,
    BarChart3,
    Tag,
    FileText,
    AlertTriangle,
    Loader2,
    CheckCircle,
    AlertCircle,
    Layers,
    Monitor,
    BookOpen
} from 'lucide-vue-next'

const props = defineProps<{
    configs?: Array<{
        id: number
        name: string
        max_score: number
        description?: string
        weight?: number
        applies_to?: string  // NEW: Added applies_to field
        types?: Array<{
            id: number
            type_name: string
            score_value: number
            description?: string
        }>
    }>
    incentives?: Array<{
        id: number
        min_score: number
        max_score: number
        incentive_amount: number
        user_level_id?: number
        user_level_tier_id?: number
        user_level?: {
            id: number
            name: string
            code: string
        }
        user_level_tier?: {
            id: number
            tier_name: string
            tier_order: number
        }
    }>
    userLevels?: Array<{
        id: number
        name: string
        code: string
        hierarchy_level: number
        tiers: Array<{
            id: number
            tier_name: string
            tier_order: number
        }>
    }>
}>()

// Reactive states
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAddTypeModal = ref(false)
const showDeleteCategoryModal = ref(false)
const showDeleteTypeModal = ref(false)
const showRemoveIncentiveModal = ref(false)

const selectedConfig = ref<any>(null)
const categoryToDelete = ref<any>(null)
const typeToDelete = ref<any>(null)
const incentiveIndexToRemove = ref<number | null>(null)

// Fixed breadcrumbs - changed 'admin.dashboard' to 'dashboard'
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'Configurations', href: null },
]

// Forms
const categoryForm = useForm({
    name: '',
    max_score: 0,
    description: '',
    applies_to: 'both',  // NEW: Added applies_to field with default value
})

const typeForm = useForm({
    type_name: '',
    score_value: 0,
    description: '',
})

const totalScoreForm = useForm({
    total_score: 0,
    config_scores: {} as Record<number, number>,
})

// Enhanced incentive form with Level + Tier support
const incentiveForm = useForm({
    incentives: props.incentives && props.incentives.length > 0
        ? props.incentives.map(incentive => ({
            user_level_id: incentive.user_level_id || null,
            user_level_tier_id: incentive.user_level_tier_id || null,
            min_score: incentive.min_score || 0,
            max_score: incentive.max_score || 0,
            incentive_amount: parseFloat(incentive.incentive_amount.toString()) || 0
        }))
        : [{
            user_level_id: null,
            user_level_tier_id: null,
            min_score: 0,
            max_score: 0,
            incentive_amount: 0
        }],
})

// Computed properties
const hasConfigs = computed(() => {
    return props.configs &&
        Array.isArray(props.configs) &&
        props.configs.length > 0 &&
        props.configs.every(config => config && typeof config === 'object')
})

const hasUserLevels = computed(() => {
    return props.userLevels && Array.isArray(props.userLevels) && props.userLevels.length > 0
})

// Initialize forms with existing data
if (hasConfigs.value) {
    props.configs!.forEach(config => {
        if (config && config.id) {
            totalScoreForm.config_scores[config.id] = config.max_score || 0
        }
    })
}

// Helper methods
const getAvailableTiers = (levelId: number | null) => {
    if (!levelId || !props.userLevels) return []
    const level = props.userLevels.find(l => l.id === levelId)
    return level ? level.tiers : []
}

const onLevelChange = (index: number) => {
    incentiveForm.incentives[index].user_level_tier_id = null
}

const getLevelName = (levelId: number | null) => {
    if (!levelId || !props.userLevels) return 'Unknown Level'
    const level = props.userLevels.find(l => l.id === levelId)
    return level ? `${level.name} (${level.code})` : 'Unknown Level'
}

const getTierName = (tierId: number | null) => {
    if (!tierId || !props.userLevels) return 'Unknown Tier'
    for (const level of props.userLevels) {
        const tier = level.tiers.find(t => t.id === tierId)
        if (tier) return `${tier.tier_name} (T${tier.tier_order})`
    }
    return 'Unknown Tier'
}

// Get badge variant based on score
const getScoreBadgeVariant = (score: number, maxScore: number) => {
    const percentage = (score / maxScore) * 100
    if (percentage >= 90) return 'default'
    if (percentage >= 75) return 'secondary'
    if (percentage >= 50) return 'outline'
    return 'destructive'
}

// NEW: Get badge variant for applies_to
const getAppliesToBadgeVariant = (appliesTo: string) => {
    if (appliesTo === 'both') return 'secondary'
    if (appliesTo === 'online') return 'default'
    return 'outline'
}

// NEW: Get icon for applies_to
const getAppliesToIcon = (appliesTo: string) => {
    if (appliesTo === 'online') return Monitor
    if (appliesTo === 'regular') return BookOpen
    return Layers
}

// Methods
const closeModals = () => {
    showCreateModal.value = false
    showEditModal.value = false
    categoryForm.reset()
    categoryForm.clearErrors()
}

const closeTypeModal = () => {
    showAddTypeModal.value = false
    typeForm.reset()
    typeForm.clearErrors()
    selectedConfig.value = null
}

const createCategory = () => {
    categoryForm.post(route('admin.evaluations.store'), {
        onSuccess: () => {
            closeModals()
        },
    })
}

const editConfig = (config: any) => {
    if (!config) return
    categoryForm.name = config.name || ''
    categoryForm.max_score = config.max_score || 0
    categoryForm.description = config.description || ''
    categoryForm.applies_to = config.applies_to || 'both'  // NEW: Set applies_to when editing
    selectedConfig.value = config
    showEditModal.value = true
}

const updateCategory = () => {
    if (!selectedConfig.value?.id) return
    categoryForm.put(route('admin.evaluations.update', selectedConfig.value.id), {
        onSuccess: () => {
            closeModals()
            selectedConfig.value = null
        },
    })
}

const confirmDeleteCategory = (config: any) => {
    categoryToDelete.value = config
    showDeleteCategoryModal.value = true
}

const deleteCategory = () => {
    if (!categoryToDelete.value?.id) return
    router.delete(route('admin.evaluations.destroy', categoryToDelete.value.id), {
        onSuccess: () => {
            showDeleteCategoryModal.value = false
            categoryToDelete.value = null
        },
    })
}

const showTypeModal = (config: any) => {
    if (!config) return
    selectedConfig.value = config
    typeForm.reset()
    typeForm.clearErrors()
    showAddTypeModal.value = true
}

const addType = () => {
    if (!selectedConfig.value?.id) return
    typeForm.post(route('admin.evaluations.types.store', selectedConfig.value.id), {  // ✅ Pass the config ID
        onSuccess: () => {
            closeTypeModal()
        },
    })
}


const confirmDeleteType = (type: any) => {
    typeToDelete.value = type
    showDeleteTypeModal.value = true
}

const deleteType = () => {
    if (!typeToDelete.value?.id) return
    router.delete(route('admin.evaluations.types.destroy', typeToDelete.value.id), {  // ✅ CORRECT
        onSuccess: () => {
            showDeleteTypeModal.value = false
            typeToDelete.value = null
        },
        onError: (errors) => {
            console.error('Delete failed:', errors)
        }
    })
}

const setTotalScore = () => {
    totalScoreForm.post(route('admin.evaluations.set-total-score'))
}

const setIncentives = () => {
    incentiveForm.post(route('admin.evaluations.set-incentives'), {  // ✅ CORRECT
        onSuccess: () => {
            // Form will be updated automatically when page reloads with new data
        },
        onError: (errors) => {
            console.error('Incentive save errors:', errors)
        }
    })
}


const addIncentive = () => {
    incentiveForm.incentives.push({
        user_level_id: null,
        user_level_tier_id: null,
        min_score: 0,
        max_score: 0,
        incentive_amount: 0
    })
}

const confirmRemoveIncentive = (index: number) => {
    if (incentiveForm.incentives.length <= 1) return
    incentiveIndexToRemove.value = index
    showRemoveIncentiveModal.value = true
}

const removeIncentive = () => {
    if (incentiveIndexToRemove.value !== null && incentiveForm.incentives.length > 1) {
        incentiveForm.incentives.splice(incentiveIndexToRemove.value, 1)
    }
    showRemoveIncentiveModal.value = false
    incentiveIndexToRemove.value = null
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Evaluation Configurations</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Manage evaluation categories, types, scoring, and level + tier based incentive structures
                    </p>
                </div>
                <Button @click="showCreateModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Category
                </Button>
            </div>

            <!-- Categories List -->
            <div v-if="hasConfigs" class="space-y-6">
                <Card
                    v-for="config in configs"
                    :key="config?.id || Math.random()"
                    class="hover:shadow-md transition-shadow duration-200"
                >
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5" />
                                    {{ config?.name || 'Unnamed Category' }}
                                    <!-- NEW: Badge showing applies_to -->
                                    <Badge
                                        v-if="config?.applies_to"
                                        :variant="getAppliesToBadgeVariant(config.applies_to)"
                                        class="text-xs"
                                    >
                                        <component :is="getAppliesToIcon(config.applies_to)" class="mr-1 h-3 w-3" />
                                        {{ config.applies_to === 'regular' ? 'Regular Only' :
                                        config.applies_to === 'online' ? 'Online Only' :
                                            'Both Types' }}
                                    </Badge>
                                </CardTitle>
                                <CardDescription class="mt-1">
                                    Max Score: {{ config?.max_score || 0 }} points
                                    <span v-if="config?.description"> • {{ config.description }}</span>
                                </CardDescription>
                            </div>
                            <div class="flex space-x-2">
                                <Button
                                    @click="editConfig(config)"
                                    variant="outline"
                                    size="sm"
                                >
                                    <Edit class="mr-1 h-4 w-4" />
                                    Edit
                                </Button>
                                <Button
                                    @click="confirmDeleteCategory(config)"
                                    variant="outline"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                >
                                    <Trash2 class="mr-1 h-4 w-4" />
                                    Delete
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Types Section -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <Tag class="h-4 w-4" />
                                    <Label class="text-sm font-medium">Evaluation Types</Label>
                                </div>
                                <Button
                                    @click="showTypeModal(config)"
                                    variant="outline"
                                    size="sm"
                                >
                                    <Plus class="mr-1 h-4 w-4" />
                                    Add Type
                                </Button>
                            </div>

                            <div v-if="config?.types && config.types.length > 0"
                                 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <Card
                                    v-for="type in config.types"
                                    :key="type?.id || Math.random()"
                                    class="relative hover:shadow-sm transition-shadow duration-200"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium">
                                                    {{ type?.type_name || 'Unnamed Type' }}
                                                </p>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <Badge
                                                        :variant="getScoreBadgeVariant(type?.score_value || 0, config?.max_score || 100)"
                                                        class="text-xs"
                                                    >
                                                        {{ type?.score_value || 0 }} pts
                                                    </Badge>
                                                </div>
                                                <p v-if="type?.description" class="text-xs text-muted-foreground mt-1">
                                                    {{ type.description }}
                                                </p>
                                            </div>
                                            <Button
                                                @click="confirmDeleteType(type)"
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- No types state -->
                            <Card v-else class="border-dashed">
                                <CardContent class="flex flex-col items-center justify-center py-8 text-center">
                                    <FileText class="h-8 w-8 text-muted-foreground mb-2" />
                                    <p class="text-sm text-muted-foreground mb-4">
                                        No evaluation types configured
                                    </p>
                                    <Button
                                        @click="showTypeModal(config)"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Add your first type
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else class="border-dashed">
                <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 mb-4">
                        <Settings class="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle class="mb-2">No evaluation configurations</CardTitle>
                    <CardDescription class="mb-6 max-w-sm">
                        Get started by creating your first evaluation category to organize your assessment criteria.
                    </CardDescription>
                    <Button @click="showCreateModal = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Create First Category
                    </Button>
                </CardContent>
            </Card>

            <!-- Total Score Configuration -->
            <Card v-if="hasConfigs" class="mt-8">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-primary/10">
                            <BarChart3 class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <CardTitle>Total Score Configuration</CardTitle>
                            <CardDescription>
                                Set the overall scoring distribution across categories
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="setTotalScore" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <Label for="total_score">Total Score</Label>
                                <Input
                                    id="total_score"
                                    v-model.number="totalScoreForm.total_score"
                                    type="number"
                                    min="1"
                                    placeholder="Enter total score"
                                />
                            </div>
                            <div
                                v-for="config in configs"
                                :key="`total_${config?.id}`"
                                class="space-y-1"
                            >
                                <Label :for="`config_score_${config?.id}`">
                                    {{ config?.name || 'Unnamed' }}
                                </Label>
                                <Input
                                    :id="`config_score_${config?.id}`"
                                    v-model.number="totalScoreForm.config_scores[config?.id]"
                                    type="number"
                                    min="0"
                                    :max="config?.max_score || 0"
                                    :placeholder="`Max: ${config?.max_score || 0}`"
                                />
                            </div>
                        </div>

                        <Separator />

                        <div class="flex justify-end pt-4">
                            <Button
                                type="submit"
                                :disabled="totalScoreForm.processing"
                            >
                                <Loader2 v-if="totalScoreForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <CheckCircle v-else class="mr-2 h-4 w-4" />
                                {{ totalScoreForm.processing ? 'Saving...' : 'Save Total Score' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- [INCENTIVES SECTION - KEEPING AS-IS, NO CHANGES NEEDED] -->
            <!-- Enhanced Level + Tier Based Incentives Configuration -->
            <Card class="mt-8">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-green-100">
                            <DollarSign class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="flex-1">
                            <CardTitle class="flex items-center gap-2">
                                Level + Tier Based Incentives
                                <Badge variant="secondary" class="text-xs">Enhanced</Badge>
                            </CardTitle>
                            <CardDescription>
                                Define reward tiers based on user level, tier, and performance scores
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Current Incentives Display -->
                    <div v-if="props.incentives && props.incentives.length > 0" class="mb-8">
                        <div class="flex items-center gap-2 mb-4">
                            <Layers class="h-5 w-5 text-muted-foreground" />
                            <Label class="text-base font-semibold">Current Incentive Structure</Label>
                            <Badge variant="outline" class="text-xs">{{ props.incentives.length }} rules</Badge>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
                            <Card
                                v-for="incentive in props.incentives"
                                :key="incentive.id"
                                class="border-l-4 border-l-green-500 hover:shadow-md transition-shadow"
                            >
                                <CardContent class="p-4">
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <Badge variant="secondary" class="text-xs">
                                                {{ incentive.user_level?.name || 'Unknown Level' }}
                                            </Badge>
                                            <Badge variant="outline" class="text-xs">
                                                {{ incentive.user_level_tier?.tier_name || 'Unknown Tier' }}
                                            </Badge>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="text-sm text-muted-foreground">
                                                Score Range: {{ incentive.min_score }}-{{ incentive.max_score }}
                                            </div>
                                            <div class="text-lg font-bold text-green-600">
                                                ${{ Number(incentive.incentive_amount).toFixed(2) }}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <Separator />
                    </div>

                    <!-- Warning if no user levels -->
                    <Alert v-if="!hasUserLevels" variant="destructive" class="mb-6">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>
                            <div class="font-medium">No User Levels Found</div>
                            <p class="text-sm mt-1">
                                You need to create user levels and tiers before configuring incentives.
                                <a :href="route('admin.organizational.index')" class="underline hover:no-underline">
                                    Go to Organizational Management
                                </a>
                            </p>
                        </AlertDescription>
                    </Alert>

                    <!-- Incentive Form continues... (keeping the rest as-is) -->
                    <form v-if="hasUserLevels" @submit.prevent="setIncentives" class="space-y-6">
                        <div class="flex items-center gap-2 mb-4">
                            <Plus class="h-5 w-5 text-muted-foreground" />
                            <Label class="text-base font-semibold">Configure New Incentives</Label>
                        </div>

                        <div class="space-y-4">
                            <Card
                                v-for="(incentive, index) in incentiveForm.incentives"
                                :key="index"
                                class="relative hover:shadow-sm transition-all duration-200"
                            >
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-end">
                                        <!-- Level Selection -->
                                        <div class="lg:col-span-1">
                                            <Label :for="`level_${index}`" class="text-sm font-medium">User Level</Label>
                                            <select
                                                :id="`level_${index}`"
                                                v-model="incentive.user_level_id"
                                                @change="onLevelChange(index)"
                                                class="mt-1 block w-full border-input bg-background px-3 py-2 text-sm ring-offset-background border rounded-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                required
                                            >
                                                <option value="">Select Level</option>
                                                <option
                                                    v-for="level in props.userLevels"
                                                    :key="level.id"
                                                    :value="level.id"
                                                >
                                                    {{ level.name }} ({{ level.code }})
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Tier Selection -->
                                        <div class="lg:col-span-1">
                                            <Label :for="`tier_${index}`" class="text-sm font-medium">Tier</Label>
                                            <select
                                                :id="`tier_${index}`"
                                                v-model="incentive.user_level_tier_id"
                                                class="mt-1 block w-full border-input bg-background px-3 py-2 text-sm ring-offset-background border rounded-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50"
                                                :disabled="!incentive.user_level_id"
                                                required
                                            >
                                                <option value="">Select Tier</option>
                                                <option
                                                    v-for="tier in getAvailableTiers(incentive.user_level_id)"
                                                    :key="tier.id"
                                                    :value="tier.id"
                                                >
                                                    {{ tier.tier_name }} (T{{ tier.tier_order }})
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Min Score -->
                                        <div class="lg:col-span-1">
                                            <Label :for="`min_score_${index}`" class="text-sm font-medium">Min Score</Label>
                                            <Input
                                                :id="`min_score_${index}`"
                                                v-model.number="incentive.min_score"
                                                type="number"
                                                min="0"
                                                placeholder="0"
                                                required
                                                class="mt-1"
                                            />
                                        </div>

                                        <!-- Max Score -->
                                        <div class="lg:col-span-1">
                                            <Label :for="`max_score_${index}`" class="text-sm font-medium">Max Score</Label>
                                            <Input
                                                :id="`max_score_${index}`"
                                                v-model.number="incentive.max_score"
                                                type="number"
                                                :min="incentive.min_score || 0"
                                                placeholder="100"
                                                required
                                                class="mt-1"
                                            />
                                        </div>

                                        <!-- Incentive Amount -->
                                        <div class="lg:col-span-1">
                                            <Label :for="`incentive_amount_${index}`" class="text-sm font-medium">Amount ($)</Label>
                                            <Input
                                                :id="`incentive_amount_${index}`"
                                                v-model.number="incentive.incentive_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                required
                                                class="mt-1"
                                            />
                                        </div>

                                        <!-- Remove Button -->
                                        <div class="lg:col-span-1 flex justify-end">
                                            <Button
                                                @click.prevent="confirmRemoveIncentive(index)"
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                :disabled="incentiveForm.incentives.length <= 1"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Preview -->
                                    <div v-if="incentive.user_level_id && incentive.user_level_tier_id" class="mt-4 p-3 bg-muted/50 rounded-md">
                                        <div class="text-xs text-muted-foreground">
                                            Preview: Users in <span class="font-medium">{{ getLevelName(incentive.user_level_id) }}</span>
                                            at <span class="font-medium">{{ getTierName(incentive.user_level_tier_id) }}</span>
                                            with scores {{ incentive.min_score }}-{{ incentive.max_score }}
                                            will receive <span class="font-semibold text-green-600">${{ incentive.incentive_amount || 0 }}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <Separator />

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 pt-4">
                            <Button
                                @click.prevent="addIncentive"
                                type="button"
                                variant="outline"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Add Level+Tier Incentive
                            </Button>
                            <Button
                                type="submit"
                                :disabled="incentiveForm.processing"
                            >
                                <Loader2 v-if="incentiveForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <CheckCircle v-else class="mr-2 h-4 w-4" />
                                {{ incentiveForm.processing ? 'Saving...' : 'Save Incentives' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <!-- Create/Edit Category Modal -->
        <Dialog :open="showCreateModal || showEditModal" @update:open="closeModals">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-primary/10">
                            <Settings class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <DialogTitle>
                                {{ showEditModal ? 'Edit Category' : 'Create New Category' }}
                            </DialogTitle>
                            <DialogDescription>
                                {{ showEditModal ? 'Update the category information below' : 'Add a new evaluation category to organize your assessments' }}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <form @submit.prevent="showEditModal ? updateCategory() : createCategory()" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="category_name">Category Name</Label>
                        <Input
                            id="category_name"
                            v-model="categoryForm.name"
                            placeholder="Enter a descriptive category name"
                            required
                        />
                        <span v-if="categoryForm.errors.name" class="text-sm text-destructive flex items-center">
                            <AlertCircle class="mr-1 h-4 w-4" />
                            {{ categoryForm.errors.name }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <Label for="max_score">Maximum Score</Label>
                        <Input
                            id="max_score"
                            v-model.number="categoryForm.max_score"
                            type="number"
                            min="1"
                            max="1000"
                            placeholder="Enter the maximum possible score"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            The highest score that can be assigned in this category
                        </p>
                        <span v-if="categoryForm.errors.max_score" class="text-sm text-destructive flex items-center">
                            <AlertCircle class="mr-1 h-4 w-4" />
                            {{ categoryForm.errors.max_score }}
                        </span>
                    </div>

                    <!-- NEW: Applies To Selection -->
                    <div class="space-y-2">
                        <Label for="applies_to">Applies To</Label>
                        <Select
                            v-model="categoryForm.applies_to"
                            :disabled="categoryForm.processing"
                        >
                            <SelectTrigger id="applies_to">
                                <SelectValue placeholder="Select course type..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="regular">
                                    <div class="flex items-center gap-2">
                                        <BookOpen class="h-4 w-4" />
                                        <span>Regular Courses Only</span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="online">
                                    <div class="flex items-center gap-2">
                                        <Monitor class="h-4 w-4" />
                                        <span>Online Courses Only</span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="both">
                                    <div class="flex items-center gap-2">
                                        <Layers class="h-4 w-4" />
                                        <span>Both Course Types</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-muted-foreground">
                            Specify where this evaluation category can be used
                        </p>
                        <span v-if="categoryForm.errors.applies_to" class="text-sm text-destructive flex items-center">
                            <AlertCircle class="mr-1 h-4 w-4" />
                            {{ categoryForm.errors.applies_to }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <Label for="category_description">Description (Optional)</Label>
                        <Textarea
                            id="category_description"
                            v-model="categoryForm.description"
                            placeholder="Provide additional details about this category..."
                            rows="3"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModals"
                            :disabled="categoryForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="categoryForm.processing"
                        >
                            <Loader2 v-if="categoryForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <CheckCircle v-else class="mr-2 h-4 w-4" />
                            {{ categoryForm.processing
                            ? (showEditModal ? 'Updating...' : 'Creating...')
                            : (showEditModal ? 'Update Category' : 'Create Category')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Add Type Modal (keeping as-is) -->
        <Dialog :open="showAddTypeModal" @update:open="closeTypeModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <Tag class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <DialogTitle>Add Evaluation Type</DialogTitle>
                            <DialogDescription>
                                Create a new evaluation type for
                                <span class="font-medium">{{ selectedConfig?.name || 'this category' }}</span>
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <form @submit.prevent="addType" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="type_name">Type Name</Label>
                        <Input
                            id="type_name"
                            v-model="typeForm.type_name"
                            placeholder="e.g., Excellent, Good, Fair, Poor"
                            required
                        />
                        <span v-if="typeForm.errors.type_name" class="text-sm text-destructive flex items-center">
                            <AlertCircle class="mr-1 h-4 w-4" />
                            {{ typeForm.errors.type_name }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <Label for="score_value">Score Value</Label>
                        <Input
                            id="score_value"
                            v-model.number="typeForm.score_value"
                            type="number"
                            min="0"
                            :max="selectedConfig?.max_score || 0"
                            :placeholder="`Enter score (0 - ${selectedConfig?.max_score || 0})`"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            Maximum allowed: {{ selectedConfig?.max_score || 0 }} points
                        </p>
                        <span v-if="typeForm.errors.score_value" class="text-sm text-destructive flex items-center">
                            <AlertCircle class="mr-1 h-4 w-4" />
                            {{ typeForm.errors.score_value }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <Label for="type_description">Description (Optional)</Label>
                        <Textarea
                            id="type_description"
                            v-model="typeForm.description"
                            placeholder="Describe this evaluation type..."
                            rows="2"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeTypeModal"
                            :disabled="typeForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="typeForm.processing"
                        >
                            <Loader2 v-if="typeForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <CheckCircle v-else class="mr-2 h-4 w-4" />
                            {{ typeForm.processing ? 'Adding...' : 'Add Type' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Category Confirmation -->
        <AlertDialog :open="showDeleteCategoryModal" @update:open="(value) => showDeleteCategoryModal = value">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div class="flex items-start gap-3">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-destructive/10">
                            <AlertTriangle class="h-6 w-6 text-destructive" />
                        </div>
                        <div class="flex-1">
                            <AlertDialogTitle>Delete Evaluation Category</AlertDialogTitle>
                            <AlertDialogDescription class="mt-2">
                                Are you sure you want to delete
                                <span class="font-semibold">{{ categoryToDelete?.name || 'this category' }}</span>?
                            </AlertDialogDescription>
                        </div>
                    </div>
                </AlertDialogHeader>

                <Alert variant="destructive" class="my-4">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <p class="font-medium mb-1">This action cannot be undone.</p>
                        <p>This will permanently delete:</p>
                        <ul class="list-disc list-inside mt-2 space-y-1 text-sm">
                            <li>The evaluation category</li>
                            <li>All associated evaluation types</li>
                            <li>Any related scoring configurations</li>
                        </ul>
                    </AlertDescription>
                </Alert>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="deleteCategory"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Category
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Delete Type Confirmation -->
        <AlertDialog :open="showDeleteTypeModal" @update:open="(value) => showDeleteTypeModal = value">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-destructive/10 mb-4">
                            <Trash2 class="h-8 w-8 text-destructive" />
                        </div>
                        <AlertDialogTitle>Delete Evaluation Type</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete the evaluation type
                            <span class="font-semibold">{{ typeToDelete?.type_name || 'this type' }}</span>?
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </div>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="deleteType"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Type
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Remove Incentive Confirmation -->
        <AlertDialog :open="showRemoveIncentiveModal" @update:open="(value) => showRemoveIncentiveModal = value">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                            <AlertTriangle class="h-8 w-8 text-yellow-600" />
                        </div>
                        <AlertDialogTitle>Remove Incentive Tier</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to remove this incentive tier? This action cannot be undone.
                        </AlertDialogDescription>
                    </div>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="removeIncentive" class="bg-yellow-600 text-white hover:bg-yellow-700">
                        Remove Tier
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AdminLayout>
</template>
