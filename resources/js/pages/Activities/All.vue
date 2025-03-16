<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Clock, Filter, Search, X, Calendar, User, Tag, ChevronDown } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';

const props = defineProps({
  activities: Object,
  isAdmin: {
    type: Boolean,
    default: false
  }
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'All Activities',
    href: '/activities',
  },
];

// Search and filter functionality
const searchQuery = ref('');
const showFilters = ref(false);
const selectedAction = ref('');
const dateRange = ref('');

const actionOptions = [
  { value: '', label: 'All Actions' },
  { value: 'create', label: 'Create' },
  { value: 'update', label: 'Update' },
  { value: 'delete', label: 'Delete' },
  { value: 'enroll', label: 'Enroll' },
  { value: 'login', label: 'Login' },
  { value: 'complete', label: 'Complete' },
  { value: 'attendance', label: 'Attendance' },
];

const dateRangeOptions = [
  { value: '', label: 'All Time' },
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
  { value: 'week', label: 'This Week' },
  { value: 'month', label: 'This Month' },
];

const filteredActivities = computed(() => {
  if (!props.activities?.data) return [];
  
  return props.activities.data.filter(activity => {
    // Search filter
    const matchesSearch = !searchQuery.value || 
      activity.description.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (activity.user?.name && activity.user.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
    
    // Action filter
    const matchesAction = !selectedAction.value || activity.action === selectedAction.value;
    
    // Date range filter (simplified for demo)
    let matchesDate = true;
    if (dateRange.value) {
      const activityDate = new Date(activity.created_at);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (dateRange.value === 'today') {
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        matchesDate = activityDate >= today && activityDate < tomorrow;
      } else if (dateRange.value === 'yesterday') {
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        matchesDate = activityDate >= yesterday && activityDate < today;
      } else if (dateRange.value === 'week') {
        const weekStart = new Date(today);
        weekStart.setDate(weekStart.getDate() - weekStart.getDay());
        matchesDate = activityDate >= weekStart;
      } else if (dateRange.value === 'month') {
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        matchesDate = activityDate >= monthStart;
      }
    }
    
    return matchesSearch && matchesAction && matchesDate;
  });
});

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
};

// Format time ago for activity feed
const timeAgo = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  
  if (diffInSeconds < 60) {
    return 'just now';
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours} hour${hours > 1 ? 's' : ''} ago`;
  } else {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} day${days > 1 ? 's' : ''} ago`;
  }
};

// Get action color class
const getActionColorClass = (action) => {
  const colorMap = {
    'create': 'bg-blue-100 text-blue-800 border-blue-200',
    'update': 'bg-green-100 text-green-800 border-green-200',
    'delete': 'bg-red-100 text-red-800 border-red-200',
    'enroll': 'bg-purple-100 text-purple-800 border-purple-200',
    'login': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'complete': 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'attendance': 'bg-teal-100 text-teal-800 border-teal-200',
  };
  
  return colorMap[action] || 'bg-gray-100 text-gray-800 border-gray-200';
};

// Clear all filters
const clearFilters = () => {
  searchQuery.value = '';
  selectedAction.value = '';
  dateRange.value = '';
  showFilters.value = false;
};
</script>

<template>
  <Head title="All Activities" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4 sm:p-6">
      <div class="bg-white rounded-xl border border-sidebar-border/70 p-4 sm:p-6 shadow-sm">
        <!-- Header with title and search -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
          <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2 sm:mb-0">
            All Activities
          </h1>
          
          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search class="h-4 w-4 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search activities..."
                class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <button 
                v-if="searchQuery" 
                @click="searchQuery = ''"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <X class="h-4 w-4 text-gray-400 hover:text-gray-600" />
              </button>
            </div>
            
            <button 
              @click="showFilters = !showFilters"
              class="flex items-center justify-center gap-1 px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            >
              <Filter class="h-4 w-4" />
              <span>Filters</span>
              <ChevronDown 
                class="h-4 w-4 transition-transform" 
                :class="{ 'rotate-180': showFilters }"
              />
            </button>
          </div>
        </div>
        
        <!-- Filters panel -->
        <div 
          v-if="showFilters"
          class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200 animate-fadeIn"
        >
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
              <select 
                v-model="selectedAction"
                class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="option in actionOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
              <select 
                v-model="dateRange"
                class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option v-for="option in dateRangeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            
            <div class="flex items-end">
              <button 
                @click="clearFilters"
                class="w-full sm:w-auto px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Clear Filters
              </button>
            </div>
          </div>
        </div>
        
        <!-- Activity list -->
        <div class="space-y-4">
          <div v-if="filteredActivities.length === 0" class="text-center py-8">
            <Clock class="h-12 w-12 mx-auto text-gray-400 mb-2" />
            <h3 class="text-lg font-medium text-gray-900">No activities found</h3>
            <p class="text-gray-500">
              {{ searchQuery || selectedAction || dateRange ? 'Try adjusting your filters' : 'Activity history will appear here' }}
            </p>
          </div>
          
          <div 
            v-for="activity in filteredActivities" 
            :key="activity.id" 
            class="flex flex-col sm:flex-row sm:items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-start flex-1">
              <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0 mr-3 overflow-hidden">
                <img v-if="activity.user?.avatar" :src="activity.user.avatar" class="w-full h-full object-cover" alt="User avatar" />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-500 font-medium text-sm">
                  {{ activity.user?.name ? activity.user.name.charAt(0).toUpperCase() : 'S' }}
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                  <p class="text-sm font-medium text-gray-900 break-words">{{ activity.description }}</p>
                  <div class="mt-1 sm:mt-0 text-xs text-gray-500 sm:ml-4 whitespace-nowrap">
                    {{ timeAgo(activity.created_at) }}
                  </div>
                </div>
                <div class="flex flex-wrap items-center mt-2 gap-2">
                  <span 
                    v-if="activity.action" 
                    class="px-2 py-1 text-xs rounded-full border"
                    :class="getActionColorClass(activity.action)"
                  >
                    {{ activity.action }}
                  </span>
                  
                  <div class="flex items-center text-xs text-gray-500">
                    <Calendar class="h-3 w-3 mr-1" />
                    {{ formatDate(activity.created_at) }}
                  </div>
                  
                  <div v-if="activity.user?.name" class="flex items-center text-xs text-gray-500">
                    <User class="h-3 w-3 mr-1" />
                    {{ activity.user.name }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div class="mt-6">
          <Pagination :links="activities.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.animate-fadeIn {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>