<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { type BreadcrumbItemType } from '@/types';

const page = usePage();
const currentUrl = computed(() => page.url);

// Accept breadcrumbs from child components
const props = withDefaults(defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>(), {
  breadcrumbs: () => [{ name: 'Admin', href: '/admin' }]
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Main content -->
        <div class=" overflow-hidden shadow-sm sm:rounded-lg dark">
          <div class="p-6  border-b border-gray-200 dark">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
