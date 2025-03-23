<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
        <div class="px-3 py-2 text-xs text-center text-gray-500 dark:text-gray-400">
            Made with ❤️ by R&D team
        </div>
    </AppSidebarLayout>
</template>
