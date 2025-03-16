<script setup lang="ts">
import { defineProps } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItemType } from '@/types';

const props = defineProps({
  user: Object,
});

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
  { name: 'Dashboard', href: route('dashboard') },
  { name: 'Users', href: route('admin.users.index') },
  { name: 'Edit User', href: route('admin.users.edit', props.user.id) }
];

// Initialize the form with existing user data
const form = useForm({
  name: props.user.name,
  email: props.user.email,
  role: props.user.role || 'user',
  password: '',
  password_confirmation: '',
});

function submit() {
  form.put(`/admin/users/${props.user.id}`);
}
</script>

<template>
  <AdminLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 sm:px-0">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4 sm:gap-0">
        <h1 class="text-xl sm:text-2xl font-bold">Edit User</h1>
      </div>

      <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
        <form @submit.prevent="submit" class="max-w-2xl">
          <div class="mb-4">
            <label for="name" class="block font-semibold mb-1">Name</label>
            <input 
              id="name"
              type="text" 
              v-model="form.name" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
          </div>

          <div class="mb-4">
            <label for="email" class="block font-semibold mb-1">Email</label>
            <input 
              id="email"
              type="email" 
              v-model="form.email" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
            <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
          </div>

          <div class="mb-4">
            <label for="role" class="block font-semibold mb-1">Role</label>
            <select 
              id="role"
              v-model="form.role" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
            <div v-if="form.errors.role" class="text-red-600 text-sm mt-1">{{ form.errors.role }}</div>
          </div>

          <div class="mb-4">
            <label for="password" class="block font-semibold mb-1">Password</label>
            <input 
              id="password"
              type="password" 
              v-model="form.password" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
            <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
            <p class="text-sm text-gray-600 mt-1">Leave blank if you don't want to change it.</p>
          </div>

          <div class="mb-4">
            <label for="password_confirmation" class="block font-semibold mb-1">Confirm Password</label>
            <input 
              id="password_confirmation"
              type="password" 
              v-model="form.password_confirmation" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
          </div>

          <div class="flex flex-col sm:flex-row gap-2">
            <button 
              type="submit" 
              class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Updating...' : 'Update User' }}
            </button>
            <Link 
              href="/admin/users" 
              class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition text-center w-full sm:w-auto"
            >
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
  