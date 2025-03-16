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
        <div class="fixed inset-0 bg-black bg-opacity-25" />
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
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-xl font-bold text-gray-900 mb-4">
                Clock In
              </DialogTitle>
              
              <div class="mt-4">
                <form @submit.prevent="submitForm">
                  <div class="mb-4">
                    <label for="course_id" class="block font-semibold mb-1">
                      Select Course (Optional)
                    </label>
                    <div class="relative">
                      <select
                        id="course_id"
                        v-model="form.course_id"
                        class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="">General Attendance (No Course)</option>
                        <option v-for="course in courses" :key="course.id" :value="course.id">
                          {{ course.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="flex gap-2 justify-end">
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      :disabled="isSubmitting"
                      class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
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