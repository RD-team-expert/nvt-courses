<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, Send, Volume2, Users, Filter } from 'lucide-vue-next'

interface Audio {
    id: number
    name: string
    description: string
    duration: number
    formatted_duration: string
}

interface User {
    id: number
    name: string
    email: string
    department_id: number | null
    department?: {
        id: number
        name: string
    }
}

interface Department {
    id: number
    name: string
    users_count: number
}

const props = defineProps<{
    audios: Audio[]
    users: User[]
    departments: Department[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audio Management', href: '/admin/audio' },
    { title: 'Audio Assignments', href: '/admin/audio-assignments' },
    { title: 'Create Assignment', href: '#' }
]

const form = useForm({
    audio_id: '',
    user_ids: [] as number[]
})

// Filters
const selectedDepartment = ref('all')
const nameSearch = ref('')

// Filter users based on department and name search
const filteredUsers = computed(() => {
    let filtered = props.users

    // Filter by department
    if (selectedDepartment.value && selectedDepartment.value !== 'all') {
        filtered = filtered.filter(user =>
            user.department_id?.toString() === selectedDepartment.value
        )
    }

    // Filter by name search
    if (nameSearch.value) {
        const search = nameSearch.value.toLowerCase()
        filtered = filtered.filter(user =>
            user.name.toLowerCase().includes(search) ||
            user.email.toLowerCase().includes(search)
        )
    }

    return filtered
})

// Select all filtered users
function selectAllUsers() {
    form.user_ids = filteredUsers.value.map(user => user.id)
}

// Clear all users
function clearAllUsers() {
    form.user_ids = []
}

// Submit form
function submit() {
    form.post('/admin/audio-assignments', {
        onSuccess: () => {
            // Form will redirect on success
        }
    })
}
</script>

<template>
    <Head title="Create Audio Assignment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button asChild variant="ghost">
                    <Link href="/admin/audio-assignments">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Assignments
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Audio Assignment</h1>
                    <p class="text-muted-foreground">Assign audio content to users by department</p>
                </div>
            </div>

            <!-- Assignment Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Audio Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Volume2 class="h-5 w-5" />
                            Select Audio
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Label for="audio-select" class="text-sm font-semibold">Audio Course *</Label>
                        <Select v-model="form.audio_id" :disabled="form.processing">
                            <SelectTrigger id="audio-select">
                                <SelectValue placeholder="Choose an audio course to assign..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="audio in audios" :key="audio.id" :value="audio.id.toString()">
                                    <div class="flex flex-col">
                                        <span>{{ audio.name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ audio.formatted_duration }}</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="form.errors.audio_id" class="text-destructive text-sm">{{ form.errors.audio_id }}</div>
                    </CardContent>
                </Card>

                <!-- User Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Select Users
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Filters -->
                        <div class="space-y-4 p-4 bg-muted/50 rounded-lg">
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Filter class="h-4 w-4" />
                                Filter Users
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Department Filter -->
                                <div class="space-y-2">
                                    <Label>Filter by Department</Label>
                                    <Select v-model="selectedDepartment">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Departments" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Departments</SelectItem>
                                            <SelectItem
                                                v-for="dept in departments"
                                                :key="dept.id"
                                                :value="dept.id.toString()"
                                            >
                                                {{ dept.name }} ({{ dept.users_count }} users)
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Name Search -->
                                <div class="space-y-2">
                                    <Label for="name-search">Search by Name</Label>
                                    <Input
                                        id="name-search"
                                        v-model="nameSearch"
                                        type="text"
                                        placeholder="Search users..."
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- User List -->
                        <div class="space-y-3">
                            <Label class="text-sm font-semibold">Select Users *</Label>
                            <Card class="bg-muted/50">
                                <CardContent class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto">
                                        <div
                                            v-for="user in filteredUsers"
                                            :key="user.id"
                                            class="flex items-center space-x-3 p-3 bg-background rounded-lg border hover:border-primary/50 hover:bg-accent/50 transition-colors cursor-pointer"
                                            :class="{ 'border-primary bg-accent': form.user_ids.includes(user.id) }"
                                        >
                                            <Checkbox
                                                :id="`user-${user.id}`"
                                                :checked="form.user_ids.includes(user.id)"
                                                @update:checked="(checked) => {
                                                    if (checked) {
                                                        form.user_ids.push(user.id)
                                                    } else {
                                                        form.user_ids = form.user_ids.filter(id => id !== user.id)
                                                    }
                                                }"
                                                :disabled="form.processing"
                                            />
                                            <Label
                                                :for="`user-${user.id}`"
                                                class="min-w-0 flex-1 cursor-pointer"
                                            >
                                                <span class="text-sm font-medium text-foreground block truncate">{{ user.name }}</span>
                                                <span class="text-xs text-muted-foreground block truncate">{{ user.email }}</span>
                                                <span v-if="user.department" class="text-xs text-muted-foreground block truncate">
                                                    {{ user.department.name }}
                                                </span>
                                            </Label>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <div v-if="form.errors.user_ids" class="text-destructive text-sm">{{ form.errors.user_ids }}</div>
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-muted-foreground">
                                    <span class="font-medium">{{ form.user_ids.length }}</span> user{{ form.user_ids.length !== 1 ? 's' : '' }} selected
                                    <span v-if="filteredUsers.length !== users.length" class="ml-2">
                                        ({{ filteredUsers.length }} shown)
                                    </span>
                                </p>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        @click="selectAllUsers"
                                        variant="link"
                                        size="sm"
                                        :disabled="form.processing"
                                        class="h-auto p-0 text-xs"
                                    >
                                        Select All
                                    </Button>
                                    <span class="text-muted-foreground">|</span>
                                    <Button
                                        type="button"
                                        @click="clearAllUsers"
                                        variant="link"
                                        size="sm"
                                        :disabled="form.processing"
                                        class="h-auto p-0 text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        Clear All
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Info Notice -->
                <Alert>
                    <AlertDescription>
                        <strong>Assignment Information:</strong> Selected users will receive an email notification with audio details and a direct login link. Managers will also be notified about their team members' assignments.
                    </AlertDescription>
                </Alert>

                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                    <Button
                        type="submit"
                        class="flex-1 sm:flex-none"
                        :disabled="form.processing || form.user_ids.length === 0 || !form.audio_id"
                    >
                        <Send class="h-4 w-4 mr-2" />
                        <span v-if="form.processing">Sending Assignments...</span>
                        <span v-else-if="form.user_ids.length === 0 || !form.audio_id">Select Audio and Users</span>
                        <span v-else>
                            Assign to {{ form.user_ids.length }} User{{ form.user_ids.length !== 1 ? 's' : '' }}
                        </span>
                    </Button>

                    <Button
                        :as="Link"
                        href="/admin/audio-assignments"
                        variant="secondary"
                        class="flex-1 sm:flex-none"
                        :class="{ 'pointer-events-none opacity-50': form.processing }"
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar for user selection */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: hsl(var(--muted));
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.5);
}
</style>
