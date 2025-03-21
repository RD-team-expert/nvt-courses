<template>
  <form @submit.prevent="submit" class="space-y-4 sm:space-y-6 px-2 sm:px-0">
    <div>
      <label for="name" class="block text-sm font-medium text-gray-700">Course Name</label>
      <input 
        id="name" 
        v-model="form.name" 
        type="text" 
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
        required
      >
      <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
    </div>
    
    <div>
      <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
      <div class="mt-1">
        <!-- HTML Editor Component -->
        <editor
          v-model="form.description"
          :api-key="tinymceApiKey"
          :init="{
            height: 300,
            menubar: false,
            plugins: [
              'advlist autolink lists link image charmap print preview anchor',
              'searchreplace visualblocks code fullscreen',
              'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | \
              alignleft aligncenter alignright alignjustify | \
              bullist numlist outdent indent | removeformat | help',
            mobile: {
              menubar: false,
              toolbar: 'undo redo | bold italic | link | bullist numlist',
              toolbar_mode: 'scrolling'
            }
          }"
        />
      </div>
      <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
    </div>
    
    <div>
      <label for="image" class="block text-sm font-medium text-gray-700">Course Image</label>
      <div class="mt-1 flex flex-col sm:flex-row items-start sm:items-center sm:space-x-4 space-y-3 sm:space-y-0">
        <img 
          v-if="imagePreview || form.image_path" 
          :src="imagePreview || `/storage/${form.image_path}`" 
          alt="Course image preview" 
          class="h-32 w-full sm:w-auto object-cover rounded-md"
        >
        <div 
          v-else
          class="h-32 w-full sm:w-48 flex items-center justify-center bg-gray-100 rounded-md"
        >
          <span class="text-gray-400">No image</span>
        </div>
        
        <div class="flex items-center">
          <label 
            for="image-upload" 
            class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50"
          >
            Change Image
          </label>
          <input 
            id="image-upload" 
            type="file" 
            class="hidden" 
            @change="handleImageUpload"
            accept="image/*"
          >
          <button 
            v-if="imagePreview || form.image_path" 
            type="button" 
            @click="removeImage" 
            class="ml-2 text-sm text-red-600 hover:text-red-800"
          >
            Remove
          </button>
        </div>
      </div>
      <div v-if="form.errors.image" class="text-red-500 text-sm mt-1">{{ form.errors.image }}</div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
      <div>
        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
        <input 
          id="start_date" 
          v-model="form.start_date" 
          type="date" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
        >
        <div v-if="form.errors.start_date" class="text-red-500 text-sm mt-1">{{ form.errors.start_date }}</div>
      </div>
      
      <div>
        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
        <input 
          id="end_date" 
          v-model="form.end_date" 
          type="date" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
        >
        <div v-if="form.errors.end_date" class="text-red-500 text-sm mt-1">{{ form.errors.end_date }}</div>
      </div>
      
      <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select 
          id="status" 
          v-model="form.status" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
        >
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
        <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">{{ form.errors.status }}</div>
      </div>
      
      <div>
        <label for="level" class="block text-sm font-medium text-gray-700">Level</label>
        <select 
          id="level" 
          v-model="form.level" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
        >
          <option value="">Select Level</option>
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
        <div v-if="form.errors.level" class="text-red-500 text-sm mt-1">{{ form.errors.level }}</div>
      </div>
      
      <div>
        <label for="duration" class="block text-sm font-medium text-gray-700">Duration (hours)</label>
        <input 
          id="duration" 
          v-model="form.duration" 
          type="number" 
          min="1"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
        >
        <div v-if="form.errors.duration" class="text-red-500 text-sm mt-1">{{ form.errors.duration }}</div>
      </div>
    </div>
    
    <div class="flex flex-col sm:flex-row sm:justify-end space-y-2 sm:space-y-0 sm:space-x-3">
      <Link 
        :href="route('admin.courses.index')" 
        class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 text-center"
      >
        Cancel
      </Link>
      <button 
        type="submit" 
        class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90"
        :disabled="form.processing"
      >
        {{ form.processing ? 'Saving...' : 'Save Course' }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Editor from '@tinymce/tinymce-vue'

const props = defineProps({
  course: Object,
  mode: {
    type: String,
    default: 'create'
  }
})

// Format dates for the form inputs (YYYY-MM-DD format required by date inputs)
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  // Create a date object without timezone conversion
  // By using YYYY-MM-DD format directly, we avoid timezone issues
  const parts = dateString.split('T')[0].split('-');
  if (parts.length !== 3) {
    // If the date isn't in the expected format, try to parse it
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return ''; // Invalid date
    return date.toISOString().split('T')[0]; // Returns YYYY-MM-DD
  }
  
  // Return the date in YYYY-MM-DD format
  return parts.join('-');
}

// Initialize form with course data if in edit mode
const form = useForm({
  name: props.course?.name || '',
  description: props.course?.description || '',
  image: null,
  image_path: props.course?.image_path || null,
  start_date: formatDate(props.course?.start_date) || '',
  end_date: formatDate(props.course?.end_date) || '',
  status: props.course?.status || 'pending',
  level: props.course?.level || '',
  duration: props.course?.duration || '',
})

// For image preview
const imagePreview = ref(null)

// Handle image upload
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

// Remove image
function removeImage() {
  form.image = null
  form.image_path = null
  imagePreview.value = null
}

// Submit form
function submit() {
  // Ensure dates are properly formatted before submission
  if (form.start_date) {
    form.start_date = formatDate(form.start_date);
  }
  if (form.end_date) {
    form.end_date = formatDate(form.end_date);
  }
  
  if (props.mode === 'edit') {
    form.post(route('admin.courses.update', props.course.id), {
      onSuccess: () => {
        // Reset form after successful submission
        form.reset()
        imagePreview.value = null
      },
    })
  } else {
    form.post(route('admin.courses.store'), {
      onSuccess: () => {
        // Reset form after successful submission
        form.reset()
        imagePreview.value = null
      },
    })
  }
}
</script>