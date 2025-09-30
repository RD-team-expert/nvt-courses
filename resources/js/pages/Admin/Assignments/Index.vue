<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'

const props = defineProps({
    assignments: Object
})

const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString()
}

const getStatusVariant = (status) => {
    const variants = {
        'pending': 'secondary',
        'accepted': 'default',
        'declined': 'destructive',
        'completed': 'outline'
    }
    return variants[status] || 'secondary'
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <Card class="overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/50">
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Course
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                User
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Status
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Assigned
                            </TableHead>
                            <TableHead class="text-muted-foreground font-medium uppercase text-xs">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="assignment in assignments.data" :key="assignment.id" class="hover:bg-muted/50">
                            <TableCell>
                                <div class="font-medium text-foreground">{{ assignment.course.name }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-foreground">{{ assignment.user.name }}</div>
                                <div class="text-muted-foreground text-sm">{{ assignment.user.email }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusVariant(assignment.status)">
                                    {{ assignment.status.charAt(0).toUpperCase() + assignment.status.slice(1) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatDate(assignment.assigned_at) }}
                            </TableCell>
                            <TableCell>
                                <!-- Actions can be added here -->
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="assignments.links" class="border-t bg-background p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} assignments
                        </div>
                        <div class="flex space-x-1">
                            <Button
                                v-for="link in assignments.links"
                                :key="link.label"
                                :as="Link"
                                :href="link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                size="sm"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AdminLayout>
</template>
