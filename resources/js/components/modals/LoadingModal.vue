<script setup lang="ts">
interface Props {
    show: boolean
    message?: string
}

withDefaults(defineProps<Props>(), {
    message: 'Loading...'
})
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-gray-500 bg-opacity-75">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 shadow-xl">
                <div class="flex items-center space-x-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="text-gray-700 font-medium">{{ message }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
