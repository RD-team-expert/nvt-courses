<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'

const props = defineProps({
  course: Object,
})

// Format status for display
const formatStatus = (status) => {
  const statusMap = {
    'pending': 'Pending',
    'in_progress': 'In Progress',
    'completed': 'Completed'
  }
  return statusMap[status] || status
}

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '—'
  
  // Create a date object with the date string
  // Use the split method to ensure we're only using the date part
  const dateParts = dateString.split('T')[0].split('-');
  if (dateParts.length !== 3) {
    // If not in expected format, try standard parsing
    const date = new Date(dateString);
    return date.toLocaleDateString();
  }
  
  // Create a date object with the year, month, day
  // Note: months are 0-indexed in JavaScript Date
  const date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
  return date.toLocaleDateString();
}

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Course Management', href: route('admin.courses.index') },
  { name: props.course.name, href: route('admin.courses.show', props.course.id) }
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container px-4 mx-auto py-4 sm:py-6 max-w-7xl">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-xl sm:text-2xl font-bold">{{ course.name }}</h1>
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
          <Button asChild>
            <Link :href="`/admin/courses/${course.id}/edit`">
              Edit Course
            </Link>
          </Button>
          <Button
            variant="destructive"
            asChild
          >
            <Link
              :href="`/admin/courses/${course.id}`"
              method="delete"
              as="button"
              confirm="Are you sure you want to delete this course?"
              confirm-button="Delete"
              cancel-button="Cancel"
            >
              Delete Course
            </Link>
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        <!-- Course Details -->
        <Card class="md:col-span-2">
          <CardHeader>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-0">
              <CardTitle class="text-lg sm:text-xl">Course Details</CardTitle>
              <Badge 
                :variant="course.status === 'in_progress' ? 'default' : course.status === 'pending' ? 'secondary' : 'outline-solid'"
                class="self-start sm:self-auto w-fit"
              >
                {{ formatStatus(course.status) }}
              </Badge>
            </div>
          </CardHeader>
          <CardContent>
            <div class="prose prose-sm sm:prose-lg max-w-none mb-6 text-foreground" v-html="course.description"></div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-muted-foreground mb-1">Start Date</p>
                <p class="font-medium text-foreground">{{ formatDate(course.start_date) }}</p>
              </div>
              <div>
                <p class="text-muted-foreground mb-1">End Date</p>
                <p class="font-medium text-foreground">{{ formatDate(course.end_date) }}</p>
              </div>
              <div>
                <p class="text-muted-foreground mb-1">Level</p>
                <p class="font-medium capitalize text-foreground">{{ course.level || '—' }}</p>
              </div>
              <div>
                <p class="text-muted-foreground mb-1">Duration</p>
                <p class="font-medium text-foreground">{{ course.duration ? `${course.duration} hours` : '—' }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <!-- Enrolled Users -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg sm:text-xl">Enrolled Users</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="course.users && course.users.length > 0">
              <div class="space-y-3">
                <div v-for="user in course.users" :key="user.id" class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-0 p-3 border border-border rounded-lg">
                  <div class="flex items-center gap-3">
                    <Avatar class="h-8 w-8">
                      <AvatarImage :src="user.avatar" :alt="user.name" />
                      <AvatarFallback>{{ user.name.charAt(0).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <div>
                      <p class="font-medium text-foreground">{{ user.name }}</p>
                      <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                    </div>
                  </div>
                  <Badge 
                    :variant="user.pivot.user_status === 'completed' ? 'default' : 'secondary'"
                    class="self-start sm:self-auto w-fit"
                  >
                    {{ user.pivot.user_status === 'completed' ? 'Completed' : 'Enrolled' }}
                  </Badge>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-4 text-muted-foreground">
              No users enrolled in this course yet.
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>