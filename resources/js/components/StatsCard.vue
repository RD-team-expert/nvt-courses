<template>
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4" :class="borderColor">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="text-2xl">{{ icon }}</div>
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-500">{{ title }}</p>
                <p class="text-2xl font-semibold" :class="textColor">{{ value }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'

export default {
    name: 'StatsCard',
    props: {
        title: {
            type: String,
            required: true
        },
        value: {
            type: [String, Number],
            required: true
        },
        icon: {
            type: String,
            default: '📊'
        },
        color: {
            type: String,
            default: 'blue',
            validator: (value) => ['blue', 'green', 'yellow', 'red', 'purple', 'orange'].includes(value)
        }
    },
    setup(props) {
        const borderColor = computed(() => {
            const colors = {
                blue: 'border-blue-500',
                green: 'border-green-500',
                yellow: 'border-yellow-500',
                red: 'border-red-500',
                purple: 'border-purple-500',
                orange: 'border-orange-500'
            }
            return colors[props.color] || colors.blue
        })

        const textColor = computed(() => {
            const colors = {
                blue: 'text-blue-600',
                green: 'text-green-600',
                yellow: 'text-yellow-600',
                red: 'text-red-600',
                purple: 'text-purple-600',
                orange: 'text-orange-600'
            }
            return colors[props.color] || colors.blue
        })

        return {
            borderColor,
            textColor
        }
    }
}
</script>
