<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap justify-center gap-1 sm:hidden">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null"
                 class="px-4 py-2 text-sm text-gray-500 rounded-md border border-gray-300 bg-gray-100"
                 v-html="link.label"></div>

            <Link v-else
                  :href="link.url"
                  as="button"
                  method="get"
                  preserve-scroll
                  class="px-4 py-2 text-sm rounded-md border"
                  :class="{
              'bg-blue-600 text-white border-blue-600': link.active,
              'text-gray-700 border-gray-300 hover:bg-gray-50': !link.active
            }"
                  v-html="link.label" />
        </template>
    </div>

    <div v-if="links && links.length > 3" class="hidden sm:flex sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex items-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null"
                     class="px-4 py-2 text-sm text-gray-500 rounded-md border border-gray-300 bg-gray-100"
                     v-html="link.label"></div>

                <Link v-else
                      :href="link.url"
                      as="button"
                      method="get"
                      preserve-scroll
                      class="px-4 py-2 text-sm rounded-md border"
                      :class="{
                'bg-blue-600 text-white border-blue-600': link.active,
                'text-gray-700 border-gray-300 hover:bg-gray-50': !link.active
              }"
                      v-html="link.label" />
            </template>
        </div>
    </div>
</template>
