<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
    show: boolean
    type?: 'success' | 'error' | 'warning' | 'info'
    title: string
    message: string
    autoClose?: boolean
    duration?: number
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    autoClose: false,
    duration: 3000
})

const emit = defineEmits<{
    close: []
}>()

const visible = ref(props.show)

watch(() => props.show, (newVal) => {
    visible.value = newVal
    if (newVal && props.autoClose) {
        setTimeout(() => {
            emit('close')
        }, props.duration)
    }
})

const getIcon = () => {
    switch (props.type) {
        case 'success':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'error':
            return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'warning':
            return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z'
        default:
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    }
}

const getColors = () => {
    switch (props.type) {
        case 'success':
            return {
                bg: 'bg-green-50',
                border: 'border-green-200',
                icon: 'text-green-400',
                text: 'text-green-800'
            }
        case 'error':
            return {
                bg: 'bg-red-50',
                border: 'border-red-200',
                icon: 'text-red-400',
                text: 'text-red-800'
            }
        case 'warning':
            return {
                bg: 'bg-yellow-50',
                border: 'border-yellow-200',
                icon: 'text-yellow-400',
                text: 'text-yellow-800'
            }
        default:
            return {
                bg: 'bg-blue-50',
                border: 'border-blue-200',
                icon: 'text-blue-400',
                text: 'text-blue-800'
            }
    }
}
</script>

<template>
    <div v-if="visible" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4" :class="getColors().bg">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                             :class="getColors().border">
                            <svg class="h-6 w-6" :class="getColors().icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon()" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium" :class="getColors().text" id="modal-title">
                                {{ title }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm" :class="getColors().text">
                                    {{ message }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
