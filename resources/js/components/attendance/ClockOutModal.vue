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
                                Clock Out
                            </DialogTitle>

                            <div class="mt-4">
                                <form @submit.prevent="submitForm">
                                    <div class="mb-4">
                                        <label class="block font-semibold mb-2 text-gray-900 dark:text-white">
                                            How was your session? <span class="text-red-500 dark:text-red-400">*</span>
                                        </label>
                                        <div class="flex items-center justify-center space-x-1">
                                            <button
                                                v-for="rating in 5"
                                                :key="rating"
                                                type="button"
                                                @click="form.rating = rating"
                                                class="p-1 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 rounded-full transition-all"
                                            >
                                                <svg
                                                    :class="[
                            'h-8 w-8 transition-colors duration-150',
                            rating <= form.rating ? 'text-yellow-400 dark:text-yellow-300' : 'text-gray-300 dark:text-gray-600 hover:text-gray-400 dark:hover:text-gray-500'
                          ]"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p v-if="errors.rating" class="text-red-500 dark:text-red-400 text-sm mt-1 text-center">{{ errors.rating }}</p>
                                    </div>

                                    <div class="mb-4">
                                        <label for="comment" class="block font-semibold mb-1 text-gray-900 dark:text-white">
                                            Comments <span class="text-red-500 dark:text-red-400">*</span>
                                        </label>
                                        <div class="relative">
                      <textarea
                          id="comment"
                          v-model="form.comment"
                          rows="4"
                          class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 rounded w-full focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 resize-none placeholder-gray-500 dark:placeholder-gray-400"
                          placeholder="Share your thoughts about this work session..."
                          required
                      ></textarea>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ form.comment.length }}/500 characters
                                        </p>
                                        <p v-if="errors.comment" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ errors.comment }}</p>
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
                                            class="bg-red-600 dark:bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700 dark:hover:bg-red-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                        >
                                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Clock Out
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
  initialForm: {
    type: Object,
    default: () => ({
      rating: null,
      comment: ''
    })
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
  rating: props.initialForm.rating,
  comment: props.initialForm.comment
});

const errors = ref({
  rating: '',
  comment: ''
});

// Reset form when initialForm changes
watch(() => props.initialForm, (newVal) => {
  form.value = {
    rating: newVal.rating,
    comment: newVal.comment
  };
}, { deep: true });

// Limit comment to 500 characters
watch(() => form.value.comment, (newVal) => {
  if (newVal.length > 500) {
    form.value.comment = newVal.substring(0, 500);
  }
});

function submitForm() {
  // Reset errors
  errors.value = {
    rating: '',
    comment: ''
  };

  let hasErrors = false;

  if (!form.value.rating) {
    errors.value.rating = 'Please select a rating before submitting';
    hasErrors = true;
  }

  if (!form.value.comment || form.value.comment.trim() === '') {
    errors.value.comment = 'Please provide comments about your session';
    hasErrors = true;
  }

  if (hasErrors) {
    return;
  }

  emit('submit', {
    rating: form.value.rating,
    comment: form.value.comment
  });
}
</script>
