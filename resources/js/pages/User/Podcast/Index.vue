<template>
    <Head title="Blog" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto space-y-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            <!-- Header -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold text-foreground flex items-center justify-center gap-3">
                    <Rss class="h-8 w-8 text-blue-600" />
                    Blog
                </h1>
                <p class="text-muted-foreground text-lg">
                    Internal podcasts and media from the team
                </p>
            </div>

            <!-- Tag filters -->
            <div v-if="allTags.length > 0" class="flex flex-wrap gap-2 justify-center">
                <button
                    @click="activeTag = null"
                    :class="activeTag === null
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-background border border-border text-foreground hover:bg-accent'"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                >
                    All
                </button>
                <button
                    v-for="tag in allTags"
                    :key="tag"
                    @click="activeTag = activeTag === tag ? null : tag"
                    :class="activeTag === tag
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-background border border-border text-foreground hover:bg-accent'"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                >
                    {{ tag }}
                </button>
            </div>

            <!-- Stats row -->
            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>{{ filteredPosts.length }} post{{ filteredPosts.length !== 1 ? 's' : '' }}</span>
                <span v-if="activeTag" class="flex items-center gap-1">
                    Filtered by: <Badge variant="secondary" class="ml-1">{{ activeTag }}</Badge>
                    <button @click="activeTag = null" class="ml-1 hover:text-foreground">
                        <X class="h-3 w-3" />
                    </button>
                </span>
            </div>

            <!-- Posts grid -->
            <div v-if="filteredPosts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <Link
                    v-for="post in filteredPosts"
                    :key="post.id"
                    :href="`/blog/${post.slug}`"
                    class="group block"
                >
                    <Card class="h-full bg-card border-border hover:border-primary/50 hover:shadow-lg transition-all duration-200 overflow-hidden">

                        <!-- Thumbnail -->
                        <div class="relative aspect-video bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 overflow-hidden">
                            <img
                                v-if="post.thumbnail_url"
                                :src="post.thumbnail_url"
                                :alt="post.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Rss class="h-12 w-12 text-blue-400 opacity-50" />
                            </div>

                            <!-- Media type badge -->
                            <div class="absolute top-3 left-3">
                                <Badge
                                    v-if="post.mediable_type"
                                    class="gap-1 text-xs backdrop-blur-sm bg-black/50 text-white border-0"
                                >
                                    <PlaySquare v-if="post.mediable_type === 'video'" class="h-3 w-3" />
                                    <Music v-else class="h-3 w-3" />
                                    {{ post.mediable_type === 'video' ? 'Video' : 'Audio' }}
                                </Badge>
                            </div>
                        </div>

                        <CardContent class="p-4 sm:p-5 space-y-3">

                            <!-- Tags -->
                            <div v-if="(post.tags || []).length > 0" class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="tag in (post.tags || []).slice(0, 3)"
                                    :key="tag"
                                    variant="secondary"
                                    class="text-xs bg-background border border-border"
                                >
                                    {{ tag }}
                                </Badge>
                            </div>

                            <!-- Title -->
                            <h2 class="font-semibold text-foreground text-base sm:text-lg leading-snug group-hover:text-primary transition-colors line-clamp-2">
                                {{ post.title }}
                            </h2>

                            <!-- Excerpt -->
                            <p v-if="post.excerpt" class="text-sm text-muted-foreground line-clamp-2">
                                {{ post.excerpt }}
                            </p>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-2 border-t border-border">
                                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <User class="h-3 w-3" />
                                    <span class="truncate max-w-[80px] sm:max-w-none">{{ post.creator.name }}</span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground shrink-0">
                                    <span class="flex items-center gap-1">
                                        <Heart class="h-3 w-3" />
                                        {{ post.likes_count }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <MessageCircle class="h-3 w-3" />
                                        {{ post.comments_count }}
                                    </span>
                                    <span class="hidden sm:flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        {{ formatDate(post.published_at) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Date — shown only on mobile below footer -->
                            <p class="sm:hidden text-xs text-muted-foreground flex items-center gap-1">
                                <Calendar class="h-3 w-3" />
                                {{ formatDate(post.published_at) }}
                            </p>

                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-20">
                <Rss class="h-16 w-16 mx-auto text-muted-foreground opacity-20 mb-4" />
                <h3 class="text-lg font-medium text-foreground mb-2">No posts found</h3>
                <p class="text-muted-foreground">
                    {{ activeTag ? `No posts with tag "${activeTag}"` : 'No blog posts have been published yet.' }}
                </p>
                <button
                    v-if="activeTag"
                    @click="activeTag = null"
                    class="mt-4 text-sm text-primary hover:underline"
                >
                    Clear filter
                </button>
            </div>

        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import {
    Rss, PlaySquare, Music, Heart, MessageCircle,
    Calendar, X, User,
} from 'lucide-vue-next'

interface Post {
    id: number
    title: string
    slug: string
    excerpt: string | null
    thumbnail_url: string | null
    published_at: string | null
    tags: string[]
    likes_count: number
    comments_count: number
    mediable_type: 'video' | 'audio' | null
    creator: { id: number; name: string }
}

const props = defineProps<{
    posts: Post[]
    allTags: string[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Blog', href: '/blog' },
]

const activeTag = ref<string | null>(null)

const filteredPosts = computed(() => {
    if (!activeTag.value) return props.posts
    return props.posts.filter(p =>
        (p.tags || []).includes(activeTag.value!)
    )
})

function formatDate(dateStr: string | null): string {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>