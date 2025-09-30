<!-- Pagination Component -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

// shadcn-vue components
import { Button } from '@/components/ui/button'

defineProps({
    links: Array,
})

// Get button variant based on state
const getButtonVariant = (link: any) => {
    if (link.active) return 'default'
    return 'outline'
}

// Get button classes for disabled state
const getButtonClasses = (link: any) => {
    if (!link.url) {
        return 'cursor-not-allowed opacity-50'
    }
    return ''
}
</script>

<template>
    <!-- Mobile View -->
    <div v-if="links && links.length > 3" class="flex flex-wrap justify-center gap-1 sm:hidden">
        <template v-for="(link, key) in links" :key="key">
            <!-- Disabled Button for null URLs -->
            <Button
                v-if="link.url === null"
                :variant="getButtonVariant(link)"
                size="sm"
                disabled
                class="cursor-not-allowed opacity-50"
                v-html="link.label"
            />

            <!-- Active/Clickable Button -->
            <Button
                v-else
                asChild
                :variant="getButtonVariant(link)"
                size="sm"
            >
                <Link
                    :href="link.url"
                    as="button"
                    method="get"
                    preserve-scroll
                    v-html="link.label"
                />
            </Button>
        </template>
    </div>

    <!-- Desktop View -->
    <div v-if="links && links.length > 3" class="hidden sm:flex sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex items-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <!-- Disabled Button for null URLs -->
                <Button
                    v-if="link.url === null"
                    :variant="getButtonVariant(link)"
                    size="sm"
                    disabled
                    class="cursor-not-allowed opacity-50"
                    v-html="link.label"
                />

                <!-- Active/Clickable Button -->
                <Button
                    v-else
                    asChild
                    :variant="getButtonVariant(link)"
                    size="sm"
                >
                    <Link
                        :href="link.url"
                        as="button"
                        method="get"
                        preserve-scroll
                        v-html="link.label"
                    />
                </Button>
            </template>
        </div>
    </div>
</template>
