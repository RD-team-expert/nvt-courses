<template>
    <div class="chunk-uploader">
        <input
            ref="fileInput"
            type="file"
            :accept="accept"
            class="filepond"
        />
        <input type="hidden" :name="name" v-model="fileData" />
    </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import 'filepond/dist/filepond.min.css';

const props = defineProps({
    name: {
        type: String,
        default: 'file'
    },
    accept: {
        type: String,
        default: 'video/*'
    },
    maxFileSize: {
        type: String,
        default: '500MB'
    },
    modelValue: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'uploaded', 'error', 'progress']);

const fileInput = ref(null);
const fileData = ref(props.modelValue);
let pond = null;

// Register FilePond plugins
FilePond.registerPlugin(
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize
);

// ✅ Get XSRF token from cookie (Laravel's way)
const getXSRFToken = () => {
    const name = 'XSRF-TOKEN=';
    const decodedCookie = decodeURIComponent(document.cookie);
    const cookies = decodedCookie.split(';');

    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(name) === 0) {
            return cookie.substring(name.length, cookie.length);
        }
    }
    return null;
};

onMounted(() => {
    const xsrfToken = getXSRFToken();

    if (!xsrfToken) {
        console.warn('⚠️ XSRF-TOKEN cookie not found. This is normal on first load.');
    }

    // Create FilePond instance
    pond = FilePond.create(fileInput.value, {
        acceptedFileTypes: ['video/*'],
        maxFileSize: props.maxFileSize,
        chunkUploads: true,
        chunkSize: 2097152, // 2MB chunks
        chunkRetryDelays: [500, 1000, 3000],
        server: {
            url: window.location.origin,
            process: {
                url: '/admin/videos/upload-chunk',
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken || '', // ✅ Use XSRF cookie token
                    'Accept': 'application/json',
                },
                withCredentials: true, // ✅ Important: send cookies
                onload: (response) => {
                    console.log('✅ Upload response:', response);
                    const data = JSON.parse(response);
                    fileData.value = JSON.stringify(data);
                    emit('update:modelValue', JSON.stringify(data));
                    emit('uploaded', data);
                    return data.path;
                },
                onerror: (response) => {
                    console.error('❌ Upload error:', response);
                    const error = response || 'Upload failed';
                    emit('error', error);
                    return error;
                }
            },
            revert: null,
            restore: null,
            load: null,
            fetch: null,
        },
        labelIdle: 'Drag & Drop your video or <span class="filepond--label-action">Browse</span>',
        labelFileProcessing: 'Uploading',
        labelFileProcessingComplete: 'Upload complete',
        labelFileProcessingAborted: 'Upload cancelled',
        labelFileProcessingError: 'Error during upload',
        labelTapToCancel: 'tap to cancel',
        labelTapToRetry: 'tap to retry',
        labelTapToUndo: 'tap to undo',
    });

    // Track progress
    pond.on('processfileprogress', (file, progress) => {
        emit('progress', Math.round(progress * 100));
    });

    console.log('✅ FilePond initialized');
});

onBeforeUnmount(() => {
    if (pond) {
        pond.destroy();
    }
});

watch(() => props.modelValue, (newValue) => {
    fileData.value = newValue;
});
</script>

<style scoped>
.chunk-uploader {
    width: 100%;
}

/* Custom FilePond styling */
:deep(.filepond--root) {
    font-family: inherit;
}

:deep(.filepond--drop-label) {
    min-height: 200px;
}

:deep(.filepond--panel-root) {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
}

:deep(.filepond--drip-blob) {
    background-color: #007bff;
}
</style>
