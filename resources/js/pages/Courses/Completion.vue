<template>
  <AppLayout title="Course Completion">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-gray-800">
                Congratulations on completing "{{ course.name }}"!
              </h2>
              <p class="mt-2 text-gray-600">
                We'd love to hear your feedback on this course.
              </p>
            </div>

            <div v-if="completion && completion.rating" class="mb-8 p-4 bg-green-50 rounded-lg">
              <h3 class="text-lg font-semibold text-green-700">
                You've already rated this course
              </h3>
              <div class="mt-2">
                <div class="flex items-center">
                  <span class="text-gray-700 mr-2">Your rating:</span>
                  <div class="flex">
                    <StarIcon v-for="i in 5" :key="i" 
                      :class="[
                        'h-5 w-5', 
                        i <= completion.rating ? 'text-yellow-400' : 'text-gray-300'
                      ]" 
                    />
                  </div>
                </div>
                <div v-if="completion.feedback" class="mt-2">
                  <span class="text-gray-700">Your feedback:</span>
                  <p class="mt-1 text-gray-600">{{ completion.feedback }}</p>
                </div>
              </div>
              <div class="mt-4">
                <Link :href="route('courses.show', course.id)" class="text-indigo-600 hover:text-indigo-800">
                  Return to course
                </Link>
              </div>
            </div>

            <form v-else @submit.prevent="submitRating" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  How would you rate this course?
                </label>
                <div class="mt-1 flex items-center">
                  <button 
                    v-for="star in 5" 
                    :key="star"
                    type="button"
                    @click="rating = star"
                    class="focus:outline-none"
                  >
                    <StarIcon 
                      class="h-8 w-8" 
                      :class="star <= rating ? 'text-yellow-400' : 'text-gray-300'"
                    />
                  </button>
                </div>
                <p v-if="errors.rating" class="mt-2 text-sm text-red-600">
                  {{ errors.rating }}
                </p>
              </div>

              <div>
                <label for="feedback" class="block text-sm font-medium text-gray-700">
                  Your feedback (optional)
                </label>
                <div class="mt-1">
                  <textarea
                    id="feedback"
                    v-model="feedback"
                    rows="4"
                    class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Share your thoughts about this course..."
                  ></textarea>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <Link :href="route('courses.show', course.id)" class="text-indigo-600 hover:text-indigo-800">
                  Return to course
                </Link>
                <button
                  type="submit"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  :disabled="processing"
                >
                  {{ processing ? 'Submitting...' : 'Submit Feedback' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/24/solid';
const props = defineProps({
  course: Object,
  completion: Object
});

const form = useForm({
  rating: props.completion?.rating || 0,
  feedback: props.completion?.feedback || ''
});

const rating = ref(props.completion?.rating || 0);
const feedback = ref(props.completion?.feedback || '');
const errors = ref({});
const processing = ref(false);

const submitRating = () => {
  form.rating = rating.value;
  form.feedback = feedback.value;
  processing.value = true;
  
  form.post(route('courses.rating.submit', props.course.id), {
    onSuccess: () => {
      processing.value = false;
    },
    onError: (e) => {
      errors.value = e;
      processing.value = false;
    }
  });
};
</script>