<template>
    <input
        type="checkbox"
        :checked="checked"
        :value="value"
        @change="$emit('update:checked', $event.target.checked)"
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
    />
</template>

<script setup>
defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
});

defineEmits(['update:checked']);
</script>