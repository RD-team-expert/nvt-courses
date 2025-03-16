<template>
  <AppLayout :title="title">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 dark:bg-dark-900 transition-colors duration-200">
      <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-dark-50">{{ title }}</h1>
        <slot name="actions"></slot>
      </div>
      
      <div class="flex justify-center w-full">
        <div class="w-full max-w-6xl mx-auto dark:text-dark-50">
          <slot></slot>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
  title: {
    type: String,
    required: true
  }
});
</script>