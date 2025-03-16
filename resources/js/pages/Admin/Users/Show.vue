<script setup lang="ts">
import { defineProps } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItemType } from '@/types';

const props = defineProps({
  user: Object,
});

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Users', href: route('admin.users.index') },
  { name: props.user.name, href: route('admin.users.show', props.user.id) }
];
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 sm:px-0">
      <div class="mb-4 sm:mb-6">
        <Link href="/admin/users" class="text-blue-600 hover:underline">
          &larr; Back to Users
        </Link>
      </div>

      <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-4 sm:px-6 sm:py-5 flex flex-col sm:flex-row justify-between gap-4 sm:gap-0">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">User Information</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Personal details and account information.</p>
          </div>
          <div>
            <Link 
              :href="`/admin/users/${user.id}/edit`" 
              class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center block"
            >
              Edit User
            </Link>
          </div>
        </div>
        <div class="border-t border-gray-200">
          <dl>
            <div class="bg-gray-50 px-4 py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">Full name</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ user.name }}</dd>
            </div>
            <div class="bg-white px-4 py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">Email address</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ user.email }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">Role</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                <span 
                  class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'"
                >
                  {{ user.role }}
                </span>
              </dd>
            </div>
            <div class="bg-white px-4 py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">Account created</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ new Date(user.created_at).toLocaleString() }}</dd>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">Last updated</dt>
              <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ new Date(user.updated_at).toLocaleString() }}</dd>
            </div>
          </dl>
        </div>
      </div>
      
      <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row gap-2 sm:space-x-3">
        <Link 
          :href="`/admin/users/${user.id}/edit`" 
          class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Edit
        </Link>
        <Link
          :href="`/admin/users/${user.id}`"
          method="delete"
          as="button"
          class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          @click="e => !confirm('Are you sure you want to delete this user?') && e.preventDefault()"
        >
          Delete
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>