<script setup lang="ts">
import { defineProps } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItemType } from '@/types';
import Pagination from '@/components/Pagination.vue';

const props = defineProps({
  users: Object, // Changed from Array to Object to support pagination
});

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'User Management', href: route('admin.users.index') }
];

function confirmDelete(e) {
  if (!confirm('Are you sure you want to delete this user?')) {
    e.preventDefault();
  }
}

// Handle pagination
const handlePageChange = (page) => {
  router.get(route('admin.users.index'), {
    page
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true
  });
}
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 sm:px-0">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold">User Management</h1>
        <Link 
          href="/admin/users/create" 
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
        >
          Create New User
        </Link>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Email</th>
              <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                <div class="font-medium text-gray-900">{{ user.name }}</div>
                <div class="text-xs text-gray-500 sm:hidden mt-1">{{ user.email }}</div>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                <div class="text-gray-500">{{ user.email }}</div>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                  <Link :href="`/admin/users/${user.id}/edit`" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                  <Link 
                    :href="`/admin/users/${user.id}`" 
                    method="delete" 
                    as="button" 
                    type="button"
                    @click="confirmDelete"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="px-4 sm:px-6 py-3 bg-white border-t border-gray-200">
          <Pagination 
            v-if="users.data && users.data.length > 0"
            :links="users.links" 
            @page-changed="handlePageChange" 
            class="mt-4"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
  