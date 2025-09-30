<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" @close="$emit('close')" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black bg-opacity-25 dark:bg-black dark:bg-opacity-60" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl dark:shadow-2xl transition-all border border-gray-200 dark:border-gray-700">
                            <DialogTitle as="h3" class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                Clock In
                            </DialogTitle>

                            <div class="mt-4">
                                <form @submit.prevent="submitForm">
                                    <div class="mb-4">
                                        <label for="course_id" class="block font-semibold mb-1 text-gray-900 dark:text-white">
                                            Select Course (Optional)
                                        </label>
                                        <div class="relative">
                                            <select
                                                id="course_id"
                                                v-model="form.course_id"
                                                class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 placeholder-gray-500 dark:placeholder-gray-400"
                                            >
                                                <option value="" class="bg-white dark:bg-gray-700 text-gray-900 dark:text-white">General Attendance (No Course)</option>
                                                <option v-for="course in courses" :key="course.id" :value="course.id" class="bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                                    {{ course.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 justify-end">
                                        <button
                                            type="button"
                                            @click="$emit('close')"
                                            class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors duration-200"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="isSubmitting"
                                            class="bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                        >
                                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Clock In
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  courses: {
    type: Array,
    default: () => []
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
  course_id: ''
});

function submitForm() {
  emit('submit', {
    course_id: form.value.course_id || null
  });
}
</script>
