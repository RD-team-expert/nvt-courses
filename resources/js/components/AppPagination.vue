<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaginationMeta {
    total: number
    per_page: number
    current_page: number
    last_page: number
    from: number
    to: number
}

const props = defineProps<{
    meta?: PaginationMeta
    links: PaginationLink[]
}>()

const decodeLabel = (label: string): string => {
    const txt = document.createElement('textarea')
    txt.innerHTML = label
    return txt.value
}

const prevUrl = computed(() => props.links[0]?.url ?? null)
const nextUrl = computed(() => props.links[props.links.length - 1]?.url ?? null)

const pageLinks = computed(() =>
    props.links.filter(link => {
        const decoded = decodeLabel(link.label)
        return !isNaN(Number(decoded)) && decoded.trim() !== ''
    })
)

const navigate = (url: string | null) => {
    if (!url) return
    router.get(url, {}, { preserveScroll: true, preserveState: true })
}

const visiblePages = computed(() => {
    if (!props.meta) return []
    const total   = props.meta.last_page
    const current = props.meta.current_page
    const all     = pageLinks.value

    if (all.length === 0) return []
    if (total <= 9) return all

    const result: (PaginationLink | 'ellipsis-start' | 'ellipsis-end')[] = []

    result.push(all[0])

    if (current > 4) result.push('ellipsis-start')

    const start = Math.max(2, current - 2)
    const end   = Math.min(total - 1, current + 2)

    for (let i = start; i <= end; i++) {
        if (all[i - 1]) result.push(all[i - 1])
    }

    if (current < total - 3) result.push('ellipsis-end')

    result.push(all[total - 1])

    return result
})
</script>

<template>
    <div
        v-if="meta && meta.last_page > 1"
        class="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 py-2"
    >
        <p class="text-sm text-muted-foreground order-2 sm:order-1">
            Showing
            <span class="font-semibold text-foreground">{{ meta.from }}</span>
            to
            <span class="font-semibold text-foreground">{{ meta.to }}</span>
            of
            <span class="font-semibold text-foreground">{{ meta.total }}</span>
            results
        </p>

        <div class="flex items-center gap-1 order-1 sm:order-2">
            <!-- <button
                :disabled="meta.current_page === 1"
                @click="navigate(prevUrl)"
                class="pagination-btn px-3"
                aria-label="Previous page"
            >
                <ChevronLeft class="h-4 w-4 mr-1" />
                Previous
            </button> -->

            <template v-for="(item, idx) in visiblePages" :key="idx">
                <span
                    v-if="item === 'ellipsis-start' || item === 'ellipsis-end'"
                    class="px-2 py-1.5 text-sm text-muted-foreground select-none"
                >
                    ...
                </span>
                <button
                    v-else
                    @click="navigate((item as PaginationLink).url)"
                    :disabled="!(item as PaginationLink).url"
                    :aria-current="(item as PaginationLink).active ? 'page' : undefined"
                    class="pagination-btn min-w-[2.25rem]"
                    :class="(item as PaginationLink).active
                        ? 'bg-primary text-primary-foreground hover:bg-primary/90 border-primary'
                        : ''"
                >
                    {{ decodeLabel((item as PaginationLink).label) }}
                </button>
            </template>

            <!-- <button
                :disabled="meta.current_page === meta.last_page"
                @click="navigate(nextUrl)"
                class="pagination-btn px-3"
                aria-label="Next page"
            >
                Next
                <ChevronRight class="h-4 w-4 ml-1" />
            </button> -->
        </div>
    </div>
</template>

<style scoped>
.pagination-btn {
    @apply inline-flex items-center justify-center rounded-md border border-input bg-background px-2.5 py-2
           text-sm font-medium text-foreground shadow-sm
           transition-colors duration-150
           hover:bg-accent hover:text-accent-foreground
           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2
           disabled:pointer-events-none disabled:opacity-40
           select-none cursor-pointer;
}
</style>
