<script setup lang="ts">

import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import Editor from '@tinymce/tinymce-vue'
import { ref } from 'vue'

const imagePreview = ref(null)

const form = useForm({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  status: 'pending',
  level: '',
  duration: '',
  image: null,
})

function handleImageUpload(e) {
  const file = e.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeImage() {
  form.image = null
  imagePreview.value = null
}

function submit() {
  form.post('/admin/courses', {
    forceFormData: true,
  })
}
</script>

<template>
  <AdminLayout>
    <div class="px-4 sm:px-0">
      <h1 class="text-xl sm:text-2xl font-bold mb-4">Create New Course</h1>

      <form @submit.prevent="submit" class="max-w-4xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
          <!-- Course Name -->
          <div class="col-span-full">
            <label class="block font-semibold mb-1">Course Name</label>
            <input 
              type="text" 
              v-model="form.name" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
              required 
            />
            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
          </div>

          <!-- Course Image -->
          <div class="col-span-full">
            <label class="block font-semibold mb-1">Course Image</label>
            <div class="mt-1 flex flex-col sm:flex-row items-start sm:items-center sm:space-x-4 space-y-3 sm:space-y-0">
              <div v-if="imagePreview" class="h-32 w-full sm:w-48 overflow-hidden rounded-md">
                <img :src="imagePreview" alt="Course image preview" class="h-full w-full object-cover" />
              </div>
              <div v-else class="h-32 w-full sm:w-48 flex items-center justify-center bg-gray-100 rounded-md">
                <span class="text-gray-400">No image</span>
              </div>

              <div class="flex items-center">
                <label for="image-upload"
                  class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50">
                  Upload Image
                </label>
                <input id="image-upload" type="file" class="hidden" @change="handleImageUpload" accept="image/*" />
                <button v-if="imagePreview" type="button" @click="removeImage"
                  class="ml-2 text-sm text-red-600 hover:text-red-800">
                  Remove
                </button>
              </div>
            </div>
            <div v-if="form.errors.image" class="text-red-600 text-sm mt-1">{{ form.errors.image }}</div>
          </div>

          <!-- Course Description -->
          <div class="col-span-full">
            <label class="block font-semibold mb-1">Description</label>
            <Editor
              v-model="form.description"
              :api-key="'r1racrxd2joy9wp9xp9sj91ka9j4m3humenifqvwtx9s6i3y'"
              :init="{
                toolbar_mode: 'sliding',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                mobile: {
                  menubar: false,
                  toolbar: 'undo redo | bold italic | link | bullist numlist',
                  toolbar_mode: 'scrolling'
                }
              }"
            />
            <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
          </div>

          <!-- Start Date -->
          <div class="col-span-1">
            <label class="block font-semibold mb-1">Start Date</label>
            <input 
              type="date" 
              v-model="form.start_date" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
            <div v-if="form.errors.start_date" class="text-red-600 text-sm mt-1">{{ form.errors.start_date }}</div>
          </div>

          <!-- End Date -->
          <div class="col-span-1">
            <label class="block font-semibold mb-1">End Date</label>
            <input 
              type="date" 
              v-model="form.end_date" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
            <div v-if="form.errors.end_date" class="text-red-600 text-sm mt-1">{{ form.errors.end_date }}</div>
          </div>

          <!-- Status -->
          <div class="col-span-1">
            <label class="block font-semibold mb-1">Status</label>
            <select 
              v-model="form.status" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
            <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">{{ form.errors.status }}</div>
          </div>

          <!-- Level -->
          <div class="col-span-1">
            <label class="block font-semibold mb-1">Level</label>
            <select 
              v-model="form.level" 
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select Level</option>
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
            <div v-if="form.errors.level" class="text-red-600 text-sm mt-1">{{ form.errors.level }}</div>
          </div>

          <!-- Duration (hours) -->
          <div class="col-span-1">
            <label class="block font-semibold mb-1">Duration (hours)</label>
            <input 
              type="number" 
              v-model="form.duration" 
              min="0.5"
              step="0.5"
              class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" 
            />
            <div v-if="form.errors.duration" class="text-red-600 text-sm mt-1">{{ form.errors.duration }}</div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 mb-4">
          <button 
            type="submit" 
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto"
            :disabled="form.processing"
          >
            <span v-if="form.processing">Creating...</span>
            <span v-else>Create Course</span>
          </button>
          <Link 
            href="/admin/courses" 
            class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition w-full sm:w-auto text-center mt-2 sm:mt-0"
          >
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>