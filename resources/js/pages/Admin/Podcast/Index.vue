<template>
    <Head title="Blog Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                        <Rss class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                        Blog Management
                    </h1>
                    <p class="text-muted-foreground">Manage your internal blog posts and podcasts</p>
                </div>
                <Button as-child class="bg-primary text-primary-foreground hover:bg-primary/90">
                    <Link href="/admin/podcasts/create">
                        <Plus class="h-4 w-4 mr-2" />
                        New Post
                    </Link>
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-blue-500 rounded-lg">
                            <Rss class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ stats.total }}</div>
                            <div class="text-sm text-blue-700 dark:text-blue-300">Total Posts</div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-green-500 rounded-lg">
                            <Globe class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ stats.published }}</div>
                            <div class="text-sm text-green-700 dark:text-green-300">Published</div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/50 border border-orange-200/50 dark:border-orange-800/50">
                    <CardContent class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-orange-500 rounded-lg">
                            <FileEdit class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ stats.drafts }}</div>
                            <div class="text-sm text-orange-700 dark:text-orange-300">Drafts</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Table -->
            <Card class="bg-card border-border">
                <CardHeader>
                    <CardTitle class="text-xl">All Posts</CardTitle>
                    <CardDescription class="text-muted-foreground">
                        Manage your blog posts — drafts are only visible to admins
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-border">
                                <TableHead class="text-foreground">Post</TableHead>
                                <TableHead class="text-foreground">Media</TableHead>
                                <TableHead class="text-foreground">Status</TableHead>
                                <TableHead class="text-foreground">Tags</TableHead>
                                <TableHead class="text-foreground">Engagement</TableHead>
                                <TableHead class="text-foreground">Published</TableHead>
                                <TableHead class="text-right text-foreground">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="post in posts"
                                :key="post.id"
                                class="hover:bg-accent/50 border-border"
                            >
                                <!-- Post info -->
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="w-14 h-14 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950 dark:to-purple-950 flex items-center justify-center shrink-0 border border-muted">
                                            <img
                                                v-if="post.thumbnail_url"
                                                :src="post.thumbnail_url"
                                                :alt="post.title"
                                                class="w-full h-full object-cover"
                                            />
                                            <Rss v-else class="h-6 w-6 text-blue-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-foreground truncate max-w-[200px]">
                                                {{ post.title }}
                                            </div>
                                            <div class="text-xs text-muted-foreground truncate max-w-[200px] mt-0.5">
                                                {{ post.excerpt || 'No excerpt' }}
                                            </div>
                                            <div class="text-xs text-muted-foreground mt-1">
                                                by {{ post.creator.name }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Media -->
                                <TableCell>
                                    <div v-if="post.mediable" class="flex items-center gap-2">
                                        <Badge variant="outline" class="gap-1 text-xs bg-background border-border">
                                            <PlaySquare v-if="post.mediable_type === 'video'" class="h-3 w-3" />
                                            <Music v-else class="h-3 w-3" />
                                            {{ post.mediable_type === 'video' ? 'Video' : 'Audio' }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground truncate max-w-[120px]">
                                            {{ post.mediable.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">No media</span>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge
                                        :class="post.status === 'published'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-green-200 dark:border-green-800'
                                            : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300 border-orange-200 dark:border-orange-800'"
                                        class="gap-1 border text-xs"
                                    >
                                        <Globe v-if="post.status === 'published'" class="h-3 w-3" />
                                        <FileEdit v-else class="h-3 w-3" />
                                        {{ post.status === 'published' ? 'Published' : 'Draft' }}
                                    </Badge>
                                </TableCell>

                                <!-- Tags -->
                                <TableCell>
                                    <div class="flex flex-wrap gap-1 max-w-[150px]">
                                        <Badge
                                            v-for="tag in (post.tags || []).slice(0, 3)"
                                            :key="tag"
                                            variant="secondary"
                                            class="text-xs bg-background border border-border text-foreground"
                                        >
                                            {{ tag }}
                                        </Badge>
                                        <span v-if="(post.tags || []).length === 0" class="text-xs text-muted-foreground">
                                            No tags
                                        </span>
                                    </div>
                                </TableCell>

                                <!-- Engagement -->
                                <TableCell>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex items-center gap-1 text-muted-foreground">
                                            <Heart class="h-3 w-3" />
                                            <span>{{ post.likes_count }} likes</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-muted-foreground">
                                            <MessageCircle class="h-3 w-3" />
                                            <span>{{ post.comments_count }} comments</span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Published date -->
                                <TableCell class="text-sm text-muted-foreground">
                                    <span v-if="post.published_at">
                                        {{ new Date(post.published_at).toLocaleDateString() }}
                                    </span>
                                    <span v-else class="italic">Not published</span>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="sm" class="text-foreground hover:bg-accent">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="bg-popover border-border text-popover-foreground">
                                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                            <DropdownMenuItem
                                                @click="viewPost(post)"
                                                class="cursor-pointer hover:bg-accent"
                                            >
                                                <Eye class="h-4 w-4 mr-2" />
                                                View
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="editPost(post)"
                                                class="cursor-pointer hover:bg-accent"
                                            >
                                                <Edit class="h-4 w-4 mr-2" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="toggleStatus(post)"
                                                class="cursor-pointer hover:bg-accent"
                                            >
                                                <component
                                                    :is="post.status === 'published' ? EyeOff : Globe"
                                                    class="h-4 w-4 mr-2"
                                                />
                                                {{ post.status === 'published' ? 'Unpublish' : 'Publish' }}
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator class="bg-border" />
                                            <DropdownMenuItem
                                                @click="deletePost(post)"
                                                class="text-destructive focus:text-destructive cursor-pointer"
                                            >
                                                <Trash2 class="h-4 w-4 mr-2" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state -->
                            <TableRow v-if="posts.length === 0" class="border-border">
                                <TableCell colspan="7" class="text-center py-16 text-muted-foreground">
                                    <Rss class="h-16 w-16 mx-auto mb-4 opacity-20" />
                                    <div class="text-lg font-medium text-foreground mb-2">No blog posts yet</div>
                                    <p class="mb-4">Create your first post to get started</p>
                                    <Button as-child class="bg-primary text-primary-foreground">
                                        <Link href="/admin/podcasts/create">
                                            <Plus class="h-4 w-4 mr-2" />
                                            Create First Post
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Rss, Plus, Globe, FileEdit, PlaySquare, Music, Heart,
    MessageCircle, MoreHorizontal, Edit, Trash2, Eye, EyeOff,
} from 'lucide-vue-next'

interface Post {
    id: number
    title: string
    slug: string
    excerpt: string | null
    status: 'draft' | 'published'
    published_at: string | null
    tags: string[]
    thumbnail_url: string | null
    likes_count: number
    comments_count: number
    mediable_type: 'video' | 'audio' | null
    mediable_id: number | null
    mediable: { id: number; name: string } | null
    creator: { id: number; name: string }
    created_at: string
}

const props = defineProps<{ posts: Post[] }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Blog Management', href: '/admin/podcasts' },
]

const stats = computed(() => ({
    total:     props.posts.length,
    published: props.posts.filter(p => p.status === 'published').length,
    drafts:    props.posts.filter(p => p.status === 'draft').length,
}))

function viewPost(post: Post) {
    router.visit(`/blog/${post.slug}`)
}

function editPost(post: Post) {
    router.visit(`/admin/podcasts/${post.id}/edit`)
}

function toggleStatus(post: Post) {
    router.post(`/admin/podcasts/${post.id}/toggle-status`, {}, {
        preserveScroll: true,
    })
}

function deletePost(post: Post) {
    if (confirm(`Delete "${post.title}"? This cannot be undone.`)) {
        router.delete(`/admin/podcasts/${post.id}`, {
            preserveScroll: true,
        })
    }
}
</script>