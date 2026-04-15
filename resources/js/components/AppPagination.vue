<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { 
  ChevronLeft, 
  ChevronRight, 
  ChevronsLeft, 
  ChevronsRight,
  Loader2 
} from 'lucide-vue-next'

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

const isLoading = ref(false)
const jumpToPage = ref<number | null>(null)

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

const navigate = async (url: string | null) => {
    if (!url || isLoading.value) return
    isLoading.value = true
    try {
        router.get(url, {}, { preserveScroll: true, preserveState: true })
    } finally {
        setTimeout(() => {
            isLoading.value = false
        }, 500)
    }
}

const navigateToPage = () => {
    if (!jumpToPage.value || !props.meta) return
    if (jumpToPage.value >= 1 && jumpToPage.value <= props.meta.last_page) {
        const targetLink = pageLinks.value.find(
            link => Number(decodeLabel(link.label)) === jumpToPage.value
        )
        if (targetLink?.url) {
            navigate(targetLink.url)
            jumpToPage.value = null
        }
    }
}

const visiblePages = computed(() => {
    if (!props.meta) return []
    const total = props.meta.last_page
    const current = props.meta.current_page
    const all = pageLinks.value

    if (all.length === 0) return []
    if (total <= 7) return all

    const result: (PaginationLink | 'ellipsis-start' | 'ellipsis-end')[] = []

    // Always show first page
    result.push(all[0])

    // Show ellipsis if needed
    if (current > 4) result.push('ellipsis-start')

    // Show pages around current
    const start = Math.max(2, current - 2)
    const end = Math.min(total - 1, current + 2)

    for (let i = start; i <= end; i++) {
        if (all[i - 1]) result.push(all[i - 1])
    }

    // Show ellipsis if needed
    if (current < total - 3) result.push('ellipsis-end')

    // Always show last page
    result.push(all[total - 1])

    return result
})

// Get range of items being shown
const itemRangeText = computed(() => {
    if (!props.meta) return ''
    const start = props.meta.from
    const end = props.meta.to
    const total = props.meta.total
    return `Showing ${start}-${end} of ${total} results`
})
</script>

<template>
    <div v-if="meta && meta.last_page > 1" class="mt-6">
        <!-- Desktop Pagination -->
        <div class="hidden sm:flex items-center justify-between">
            <!-- Left side - showing results info -->
            <div class="text-sm text-muted-foreground">
                {{ itemRangeText }}
            </div>

            <!-- Center - pagination buttons -->
            <div class="flex items-center gap-1">
                <!-- First Page -->
                <button
                    @click="navigate(pageLinks[0]?.url)"
                    :disabled="meta.current_page === 1 || isLoading"
                    class="pagination-btn px-2"
                    aria-label="First page"
                    title="First page"
                >
                    <ChevronsLeft class="h-4 w-4" />
                </button>

                <!-- Previous -->
                <button
                    @click="navigate(prevUrl)"
                    :disabled="meta.current_page === 1 || isLoading"
                    class="pagination-btn px-3"
                    aria-label="Previous page"
                    title="Previous page"
                >
                    <ChevronLeft class="h-4 w-4 mr-1" />
                    Prev
                </button>

                <!-- Page Numbers -->
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
                        :disabled="isLoading"
                        :aria-current="(item as PaginationLink).active ? 'page' : undefined"
                        class="pagination-btn min-w-[2.25rem]"
                        :class="{
                            'bg-primary text-primary-foreground hover:bg-primary/90 border-primary': 
                                (item as PaginationLink).active,
                            'opacity-50 cursor-not-allowed': isLoading && !(item as PaginationLink).active
                        }"
                    >
                        <Loader2 v-if="isLoading && (item as PaginationLink).active" class="h-3 w-3 animate-spin" />
                        <span v-else>{{ decodeLabel((item as PaginationLink).label) }}</span>
                    </button>
                </template>

                <!-- Next -->
                <button
                    @click="navigate(nextUrl)"
                    :disabled="meta.current_page === meta.last_page || isLoading"
                    class="pagination-btn px-3"
                    aria-label="Next page"
                    title="Next page"
                >
                    Next
                    <ChevronRight class="h-4 w-4 ml-1" />
                </button>

                <!-- Last Page -->
                <button
                    @click="navigate(pageLinks[pageLinks.length - 1]?.url)"
                    :disabled="meta.current_page === meta.last_page || isLoading"
                    class="pagination-btn px-2"
                    aria-label="Last page"
                    title="Last page"
                >
                    <ChevronsRight class="h-4 w-4" />
                </button>
            </div>

            <!-- Right side - jump to page -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">Go to</span>
                <input
                    type="number"
                    v-model.number="jumpToPage"
                    @keyup.enter="navigateToPage"
                    :min="1"
                    :max="meta.last_page"
                    class="w-16 h-8 rounded-md border border-input bg-background px-2 text-center text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                    placeholder="Page"
                />
                <button
                    @click="navigateToPage"
                    :disabled="!jumpToPage || isLoading"
                    class="px-2 py-1 text-sm rounded-md border border-input hover:bg-accent transition-colors"
                >
                    Go
                </button>
            </div>
        </div>

        <!-- Mobile Pagination (Simplified) -->
        <div class="flex sm:hidden flex-col gap-3">
            <!-- Results info -->
            <div class="text-center text-sm text-muted-foreground">
                {{ itemRangeText }}
            </div>

            <!-- Navigation buttons -->
            <div class="flex items-center justify-center gap-2">
                <button
                    @click="navigate(prevUrl)"
                    :disabled="meta.current_page === 1 || isLoading"
                    class="pagination-btn px-3"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                
                <div class="px-3 py-2 text-sm font-medium">
                    Page {{ meta.current_page }} of {{ meta.last_page }}
                </div>
                
                <button
                    @click="navigate(nextUrl)"
                    :disabled="meta.current_page === meta.last_page || isLoading"
                    class="pagination-btn px-3"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>

            <!-- Quick jump for mobile -->
            <div class="flex items-center justify-center gap-2">
                <input
                    type="number"
                    v-model.number="jumpToPage"
                    @keyup.enter="navigateToPage"
                    :min="1"
                    :max="meta.last_page"
                    class="w-20 h-8 rounded-md border border-input bg-background px-2 text-center text-sm"
                    placeholder="Page #"
                />
                <button
                    @click="navigateToPage"
                    :disabled="!jumpToPage || isLoading"
                    class="px-3 py-1 text-sm rounded-md border border-input hover:bg-accent"
                >
                    Jump
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pagination-btn {
    @apply inline-flex items-center justify-center rounded-md border border-input bg-background px-2.5 py-2
           text-sm font-medium text-foreground shadow-sm
           transition-all duration-150
           hover:bg-accent hover:text-accent-foreground hover:scale-105
           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2
           disabled:pointer-events-none disabled:opacity-40
           select-none cursor-pointer;
}

/* Remove spinner from number input */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 0.5;
}
</style>