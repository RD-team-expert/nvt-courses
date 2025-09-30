<!--
  Resend Login Links Page
  Interface for resending secure login links to users with expired access
-->
<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Search,
    Send,
    Users,
    Mail,
    BookOpen,
    Calendar,
    AlertTriangle,
    Loader2,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    users: Object,
    courses: Array,
    filters: Object
})

const selectedUsers = ref([])
const showBulkModal = ref(false)

// Search form
const searchForm = useForm({
    search: props.filters.search || ''
})

// Individual resend form
const resendForm = useForm({
    course_id: ''
})

// Bulk resend form
const bulkForm = useForm({
    user_ids: [],
    course_id: ''
})

// Computed properties
const hasSelectedUsers = computed(() => selectedUsers.value.length > 0)
const allSelected = computed(() =>
    selectedUsers.value.length === props.users.data.length && props.users.data.length > 0
)

// Methods
function search() {
    searchForm.get(route('admin.resend-login-links.index'), {
        preserveState: true,
        replace: true
    })
}

function resendToUser(user, courseId) {
    resendForm.course_id = courseId
    resendForm.post(route('admin.resend-login-links.resend', user.id), {
        preserveScroll: true,
        onSuccess: () => {
            resendForm.reset()
        }
    })
}

function toggleAllUsers(checked) {
    if (checked) {
        selectedUsers.value = props.users.data.map(user => user.id)
    } else {
        selectedUsers.value = []
    }
}

function openBulkModal() {
    bulkForm.user_ids = selectedUsers.value
    showBulkModal.value = true
}

function sendBulkEmails() {
    bulkForm.post(route('admin.resend-login-links.bulk'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedUsers.value = []
            showBulkModal.value = false
            bulkForm.reset()
        }
    })
}

function formatLastSeen(date) {
    if (!date) return 'Never'
    return new Date(date).toLocaleDateString()
}

// Get user initials for avatar
const getUserInitials = (name) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
}

// Handle course selection for individual resend
const handleCourseChange = (courseId, userId) => {
    if (courseId) {
        resendToUser({ id: userId }, courseId)
    }
}

// Handle bulk course selection
const handleBulkCourseChange = (courseId) => {
    bulkForm.course_id = courseId
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-foreground">Resend Login Links</h1>
                <p class="mt-2 text-muted-foreground">Send new secure login links to users with expired access</p>
            </div>

            <!-- Search and Bulk Actions -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <!-- Search -->
                        <div class="flex-1 max-w-md">
                            <form @submit.prevent="search">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="searchForm.search"
                                        type="text"
                                        placeholder="Search users by name or email..."
                                        class="pl-9"
                                    />
                                </div>
                            </form>
                        </div>

                        <!-- Bulk Actions -->
                        <div class="flex gap-3">
                            <Button
                                v-if="hasSelectedUsers"
                                @click="openBulkModal"
                            >
                                <Send class="mr-2 h-4 w-4" />
                                Send to {{ selectedUsers.length }} Users
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Users class="mr-2 h-5 w-5" />
                        Users with Expired Access
                    </CardTitle>
                    <CardDescription>Users who need new login links to access their courses</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">
                                        <Checkbox
                                            :checked="allSelected"
                                            @update:checked="toggleAllUsers"
                                            :indeterminate="selectedUsers.length > 0 && selectedUsers.length < users.data.length"
                                        />
                                    </TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Last Login</TableHead>
                                    <TableHead>Courses</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users.data" :key="user.id" class="hover:bg-muted/50">
                                    <TableCell>
                                        <Checkbox
                                            v-model:checked="selectedUsers"
                                            :value="user.id"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center space-x-3">
                                            <Avatar>
                                                <AvatarFallback class="bg-muted text-muted-foreground">
                                                    {{ getUserInitials(user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">{{ user.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="destructive">
                                            <AlertTriangle class="mr-1 h-3 w-3" />
                                            Expired Access
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center text-muted-foreground">
                                            <Calendar class="mr-1 h-3 w-3" />
                                            {{ formatLastSeen(user.login_token_expires_at) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center text-muted-foreground">
                                            <BookOpen class="mr-1 h-3 w-3" />
                                            {{ user.course_registrations?.length || 0 }} enrolled
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Select @update:model-value="(value) => handleCourseChange(value, user.id)">
                                                <SelectTrigger class="w-40">
                                                    <SelectValue placeholder="Select Course" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                                        {{ course.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Empty state -->
                        <div v-if="!users.data || users.data.length === 0" class="text-center py-12">
                            <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                            <p class="text-sm text-muted-foreground">No users with expired access match your search criteria.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.links && users.data.length > 0" class="flex items-center justify-between pt-4 border-t">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                        </div>
                        <div class="flex space-x-1">
                            <template v-for="(link, index) in users.links" :key="index">
                                <Button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    v-html="link.label"
                                />
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Send Modal -->
            <Dialog v-model:open="showBulkModal">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center">
                            <Mail class="mr-2 h-5 w-5" />
                            Send Login Links to Multiple Users
                        </DialogTitle>
                        <DialogDescription>
                            Send new secure login links to {{ selectedUsers.length }} selected users.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label>Select Course</Label>
                            <Select :model-value="bulkForm.course_id" @update:model-value="handleBulkCourseChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="Choose a course..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="p-4 bg-muted rounded-lg">
                            <div class="flex items-start space-x-2">
                                <AlertTriangle class="h-4 w-4 text-amber-500 mt-0.5" />
                                <div class="text-sm text-muted-foreground">
                                    This will send new login links to all selected users for the chosen course.
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button @click="showBulkModal = false" variant="outline">
                            Cancel
                        </Button>
                        <Button
                            @click="sendBulkEmails"
                            :disabled="!bulkForm.course_id || bulkForm.processing"
                        >
                            <Loader2 v-if="bulkForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Mail v-else class="mr-2 h-4 w-4" />
                            {{ bulkForm.processing ? 'Sending...' : 'Send Login Links' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
