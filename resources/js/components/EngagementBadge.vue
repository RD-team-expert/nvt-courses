<template>
    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="badgeClasses">
        {{ level }}
    </span>
</template>

<script>
import { computed } from 'vue'

export default {
    name: 'EngagementBadge',
    props: {
        level: {
            type: String,
            required: true,
            validator: (value) => ['High', 'Medium', 'Low', 'Very Low'].includes(value)
        }
    },
    setup(props) {
        const badgeClasses = computed(() => {
            const classes = {
                'High': 'bg-green-100 text-green-800',
                'Medium': 'bg-yellow-100 text-yellow-800',
                'Low': 'bg-orange-100 text-orange-800',
                'Very Low': 'bg-red-100 text-red-800'
            }
            return classes[props.level] || classes['Very Low']
        })

        return {
            badgeClasses
        }
    }
}
</script>
