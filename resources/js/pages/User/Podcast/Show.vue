<template>
    <Head :title="post.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            <!-- Back button -->
            <Button as-child variant="ghost" class="-ml-2">
                <Link href="/blog">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back to Blog
                </Link>
            </Button>

            <!-- Main post card -->
            <Card class="bg-card border-border overflow-hidden">

                <!-- Thumbnail -->
                <div v-if="post.thumbnail_url" class="relative aspect-video bg-black overflow-hidden">
                    <img
                        :src="post.thumbnail_url"
                        :alt="post.title"
                        class="w-full h-full object-cover"
                    />
                </div>

                <CardContent class="p-4 sm:p-6 md:p-8 space-y-6">

                    <!-- Tags -->
                    <div v-if="(post.tags || []).length > 0" class="flex flex-wrap gap-2">
                        <Badge
                            v-for="tag in post.tags"
                            :key="tag"
                            variant="secondary"
                            class="text-xs bg-background border border-border"
                        >
                            {{ tag }}
                        </Badge>
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl md:text-3xl font-bold text-foreground leading-tight">
                        {{ post.title }}
                    </h1>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground border-b border-border pb-6">
                        <span class="flex items-center gap-1.5">
                            <User class="h-4 w-4" />
                            {{ post.creator.name }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-4 w-4" />
                            {{ formatDate(post.published_at) }}
                        </span>
                        <span v-if="post.mediable_type" class="flex items-center gap-1.5">
                            <PlaySquare v-if="post.mediable_type === 'video'" class="h-4 w-4" />
                            <Music v-else class="h-4 w-4" />
                            {{ post.mediable_type === 'video' ? 'Video' : 'Audio' }}
                        </span>
                    </div>

                    <!-- Video Player -->
                    <div v-if="post.media_type === 'video' && post.media_url" class="space-y-2">
                        <h3 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                            <PlaySquare class="h-4 w-4" />
                            {{ post.mediable?.name }}
                        </h3>
                        <div class="rounded-xl overflow-hidden bg-black aspect-video">
                            <video
                                :src="post.media_url"
                                class="w-full h-full"
                                controls
                                controlsList="nodownload"
                            />
                        </div>
                    </div>

                    <!-- Audio Player -->
                    <div v-else-if="post.media_type === 'audio' && post.media_url" class="space-y-2">
                        <h3 class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                            <Music class="h-4 w-4" />
                            {{ post.mediable?.name }}
                        </h3>
                        <div class="rounded-xl border border-border bg-muted/30 p-4 sm:p-6 flex items-center gap-4 flex-wrap sm:flex-nowrap">
                            <div class="p-3 rounded-full bg-primary/10 shrink-0">
                                <Music class="h-8 w-8 text-primary" />
                            </div>
                            <div class="flex-1 min-w-0 w-full sm:w-auto">
                                <p class="font-medium text-foreground mb-2 truncate">{{ post.mediable?.name }}</p>
                                <audio
                                    :src="post.media_url"
                                    class="w-full"
                                    controls
                                    controlsList="nodownload"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- No media -->
                    <div v-else-if="post.mediable" class="rounded-xl border border-border bg-muted/30 p-6 text-center text-muted-foreground text-sm">
                        <PlaySquare class="h-8 w-8 mx-auto mb-2 opacity-30" />
                        Media is currently unavailable
                    </div>

                    <!-- Description -->
                    <div v-if="post.description" class="prose prose-sm dark:prose-invert max-w-none text-foreground leading-relaxed whitespace-pre-line">
                        {{ post.description }}
                    </div>

                    <!-- Like button -->
                    <div class="flex items-center gap-4 pt-4 border-t border-border">
                        <button
                            @click="toggleLike"
                            :disabled="likeError"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg border transition-all text-sm font-medium"
                            :class="liked
                                ? 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400'
                                : 'bg-background border-border text-muted-foreground hover:border-red-300 hover:text-red-500'"
                        >
                            <Heart
                                class="h-4 w-4 transition-transform"
                                :class="{ 'fill-current scale-110': liked }"
                            />
                            {{ liked ? 'Liked' : 'Like' }}
                            <span class="ml-1 font-bold">{{ likesCount }}</span>
                            <span v-if="likeError" class="ml-1 text-xs text-destructive">!</span>
                        </button>

                        <span class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MessageCircle class="h-4 w-4" />
                            {{ comments.length }} comment{{ comments.length !== 1 ? 's' : '' }}
                        </span>
                    </div>

                </CardContent>
            </Card>

            <!-- Comments section -->
            <Card class="bg-card border-border">
                <CardHeader class="px-4 sm:px-6 md:px-8 pt-4 sm:pt-6 md:pt-8">
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <MessageCircle class="h-5 w-5" />
                        Comments
                        <Badge variant="outline" class="ml-auto">{{ comments.length }}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4 sm:px-6 md:px-8 pb-4 sm:pb-6 md:pb-8 space-y-6">

                    <!-- Add comment form -->
                    <div class="space-y-3">
                        <Textarea
                            v-model="newComment"
                            placeholder="Write a comment..."
                            rows="3"
                            class="resize-none"
                            :disabled="submittingComment"
                        />
                        <div class="flex justify-end">
                            <Button
                                @click="submitComment"
                                :disabled="!newComment.trim() || submittingComment"
                                class="bg-primary text-primary-foreground hover:bg-primary/90"
                            >
                                <Loader2 v-if="submittingComment" class="h-4 w-4 mr-2 animate-spin" />
                                <Send v-else class="h-4 w-4 mr-2" />
                                {{ submittingComment ? 'Posting...' : 'Post Comment' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div v-if="comments.length > 0" class="border-t border-border" />

                    <!-- Comments list -->
                    <div class="space-y-4">
                        <div
                            v-for="comment in comments"
                            :key="comment.id"
                            class="flex gap-3 group"
                        >
                            <!-- Avatar -->
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
                                {{ comment.user.name.charAt(0).toUpperCase() }}
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-sm font-semibold text-foreground truncate">
                                            {{ comment.user.name }}
                                        </span>
                                        <span class="text-xs text-muted-foreground shrink-0">
                                            {{ comment.created_at }}
                                        </span>
                                    </div>
                                    <!-- Delete button — visible to comment owner or admin -->
                                    <button
                                        v-if="canDeleteComment(comment)"
                                        @click="deleteComment(comment.id)"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive p-1 rounded shrink-0"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <p class="text-sm text-foreground leading-relaxed break-words">
                                    {{ comment.body }}
                                </p>
                            </div>
                        </div>

                        <!-- Empty comments -->
                        <div v-if="comments.length === 0" class="text-center py-8 text-muted-foreground text-sm">
                            <MessageCircle class="h-8 w-8 mx-auto mb-2 opacity-20" />
                            No comments yet. Be the first to comment!
                        </div>
                    </div>

                </CardContent>
            </Card>

            <!-- Related posts -->
            <div v-if="relatedPosts.length > 0" class="space-y-4">
                <h2 class="text-xl font-bold text-foreground flex items-center gap-2">
                    <Rss class="h-5 w-5 text-blue-600" />
                    More Posts
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <Link
                        v-for="related in relatedPosts"
                        :key="related.id"
                        :href="`/blog/${related.slug}`"
                        class="group block"
                    >
                        <Card class="h-full bg-card border-border hover:border-primary/50 hover:shadow-md transition-all overflow-hidden">
                            <div class="aspect-video bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 overflow-hidden">
                                <img
                                    v-if="related.thumbnail_url"
                                    :src="related.thumbnail_url"
                                    :alt="related.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <Rss class="h-8 w-8 text-blue-400 opacity-40" />
                                </div>
                            </div>
                            <CardContent class="p-3 sm:p-4">
                                <h3 class="font-semibold text-foreground text-sm leading-snug group-hover:text-primary transition-colors line-clamp-2">
                                    {{ related.title }}
                                </h3>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ formatDate(related.published_at) }}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import {
    ArrowLeft, PlaySquare, Music, Heart, MessageCircle,
    Calendar, User, Trash2, Send, Loader2, Rss,
} from 'lucide-vue-next'

interface Comment {
    id: number
    body: string
    created_at: string
    user: { id: number; name: string }
}

interface Post {
    id: number
    title: string
    slug: string
    excerpt: string | null
    description: string | null
    thumbnail_url: string | null
    published_at: string | null
    tags: string[]
    likes_count: number
    comments_count: number
    liked_by_user: boolean
    media_url: string | null
    media_type: 'video' | 'audio' | null
    mediable: { id: number; name: string } | null
    mediable_type: 'video' | 'audio' | null
    creator: { id: number; name: string }
    comments: Comment[]
}

interface RelatedPost {
    id: number
    title: string
    slug: string
    thumbnail_url: string | null
    published_at: string | null
}

const props = defineProps<{
    post: Post
    relatedPosts: RelatedPost[]
}>()

const page = usePage()
const currentUser = page.props.auth?.user as { id: number; is_admin?: boolean } | null

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Blog', href: '/blog' },
    { title: props.post.title, href: '#' },
]

// ── Likes — Optimistic UI + Debounce ──────────────────
//
// How this works:
// 1. User clicks → button updates INSTANTLY (no waiting)
// 2. We wait 400ms before sending to server (debounce)
// 3. If user clicks again within 400ms → cancel, restart timer
//    This means rapid like/unlike only sends ONE request total
// 4. Server responds → sync the real value
// 5. If server fails → roll back to last known good state
//
const liked      = ref(props.post.liked_by_user)
const likesCount = ref(props.post.likes_count)
const likeError  = ref(false)

// These track the last confirmed server state for rollback
let serverLiked      = props.post.liked_by_user
let serverLikesCount = props.post.likes_count
let debounceTimer: ReturnType<typeof setTimeout> | null = null

function toggleLike() {
    if (likeError.value) return

    // Step 1 — Optimistic update (instant, no network wait)
    liked.value      = !liked.value
    likesCount.value = liked.value ? likesCount.value + 1 : likesCount.value - 1

    // Step 2 — Debounce: cancel previous pending request, start new timer
    if (debounceTimer) clearTimeout(debounceTimer)

    debounceTimer = setTimeout(async () => {
        try {
            const response = await fetch(`/api/blog/${props.post.id}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
            })

            if (!response.ok) throw new Error(`Server error: ${response.status}`)

            const data = await response.json()

            // Step 3 — Sync with real server values
            liked.value      = data.liked
            likesCount.value = data.likes_count

            // Update rollback reference to new confirmed state
            serverLiked      = data.liked
            serverLikesCount = data.likes_count

        } catch (e) {
            // Step 4 — Rollback to last confirmed server state
            console.error('Like failed:', e)
            liked.value      = serverLiked
            likesCount.value = serverLikesCount
            likeError.value  = true

            // Auto-clear the error indicator after 3 seconds
            setTimeout(() => { likeError.value = false }, 3000)
        }
    }, 400)
}

// Clean up timer if user navigates away mid-debounce
onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer)
})

// ── Comments ───────────────────────────────────────────
const comments          = ref<Comment[]>(props.post.comments)
const newComment        = ref('')
const submittingComment = ref(false)

async function submitComment() {
    if (!newComment.value.trim() || submittingComment.value) return
    submittingComment.value = true

    try {
        const response = await fetch(`/api/blog/${props.post.id}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ body: newComment.value }),
        })

        if (!response.ok) throw new Error(`Server error: ${response.status}`)

        const data = await response.json()
        comments.value.unshift(data.comment)
        newComment.value = ''
    } catch (e) {
        console.error('Comment failed:', e)
    } finally {
        submittingComment.value = false
    }
}

async function deleteComment(commentId: number) {
    if (!confirm('Delete this comment?')) return

    try {
        const response = await fetch(`/api/blog/comments/${commentId}`, {
            method: 'DELETE',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        })

        if (!response.ok) throw new Error(`Server error: ${response.status}`)

        comments.value = comments.value.filter(c => c.id !== commentId)
    } catch (e) {
        console.error('Delete failed:', e)
    }
}

function canDeleteComment(comment: Comment): boolean {
    if (!currentUser) return false
    return comment.user.id === currentUser.id || !!currentUser.is_admin
}

// ── Helpers ────────────────────────────────────────────
function formatDate(dateStr: string | null): string {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
    return match ? decodeURIComponent(match[1]) : ''
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