<template>
    <Head title="Create Blog Post" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost">
                    <Link href="/admin/podcasts">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Blog
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Blog Post</h1>
                    <p class="text-muted-foreground">Add a new blog post for employees</p>
                </div>
            </div>

            <div class="space-y-6">

                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Post Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">

                        <!-- Title -->
                        <div class="space-y-2">
                            <Label for="title">Title <span class="text-destructive">*</span></Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Enter post title..."
                                :class="{ 'border-destructive': form.errors.title }"
                            />
                            <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <!-- Excerpt -->
                        <div class="space-y-2">
                            <Label for="excerpt">Short Description</Label>
                            <Textarea
                                id="excerpt"
                                v-model="form.excerpt"
                                placeholder="A short summary shown in the blog listing..."
                                rows="2"
                                :class="{ 'border-destructive': form.errors.excerpt }"
                            />
                            <p class="text-xs text-muted-foreground">{{ form.excerpt?.length || 0 }} / 500 characters</p>
                            <p v-if="form.errors.excerpt" class="text-sm text-destructive">{{ form.errors.excerpt }}</p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Full Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Full post content shown on the detail page..."
                                rows="6"
                            />
                        </div>

                    </CardContent>
                </Card>

                <!-- Media Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PlaySquare class="h-5 w-5" />
                            Attach Media
                        </CardTitle>
                        <CardDescription>
                            Link an existing video or audio to this post
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">

                        <!-- Media type selector -->
                        <div class="space-y-2">
                            <Label>Media Type</Label>
                            <RadioGroup v-model="form.mediable_type" class="grid grid-cols-3 gap-3">
                                <Label
                                    for="media_none"
                                    class="flex flex-col items-center justify-center rounded-lg border-2 border-muted bg-popover p-4 cursor-pointer hover:bg-accent"
                                    :class="{ 'border-primary': form.mediable_type === '' }"
                                >
                                    <RadioGroupItem value="" id="media_none" class="sr-only" />
                                    <Ban class="h-5 w-5 mb-2" />
                                    <span class="text-sm font-medium">None</span>
                                </Label>

                                <Label
                                    for="media_video"
                                    class="flex flex-col items-center justify-center rounded-lg border-2 border-muted bg-popover p-4 cursor-pointer hover:bg-accent"
                                    :class="{ 'border-primary': form.mediable_type === 'video' }"
                                >
                                    <RadioGroupItem value="video" id="media_video" class="sr-only" />
                                    <PlaySquare class="h-5 w-5 mb-2 text-blue-500" />
                                    <span class="text-sm font-medium">Video</span>
                                </Label>

                                <Label
                                    for="media_audio"
                                    class="flex flex-col items-center justify-center rounded-lg border-2 border-muted bg-popover p-4 cursor-pointer hover:bg-accent"
                                    :class="{ 'border-primary': form.mediable_type === 'audio' }"
                                >
                                    <RadioGroupItem value="audio" id="media_audio" class="sr-only" />
                                    <Music class="h-5 w-5 mb-2 text-purple-500" />
                                    <span class="text-sm font-medium">Audio</span>
                                </Label>
                            </RadioGroup>
                        </div>

                        <!-- Video search -->
                        <div v-if="form.mediable_type === 'video'" class="space-y-2">
                            <Label>Select Video</Label>
                            <Input
                                v-model="videoSearch"
                                placeholder="Search videos..."
                                class="mb-2"
                            />
                            <div class="border border-border rounded-lg overflow-hidden max-h-[250px] overflow-y-auto">
                                <div
                                    v-for="video in filteredVideos"
                                    :key="video.id"
                                    @click="selectMedia(video.id)"
                                    class="flex items-center justify-between px-4 py-3 hover:bg-accent cursor-pointer border-b border-border last:border-0"
                                    :class="{ 'bg-primary/10 border-l-2 border-l-primary': form.mediable_id === video.id }"
                                >
                                    <div class="flex items-center gap-3">
                                        <PlaySquare class="h-4 w-4 text-blue-500 shrink-0" />
                                        <span class="text-sm text-foreground">{{ video.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-muted-foreground">{{ video.formatted_duration }}</span>
                                        <CheckCircle2
                                            v-if="form.mediable_id === video.id"
                                            class="h-4 w-4 text-primary"
                                        />
                                    </div>
                                </div>
                                <div v-if="filteredVideos.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No videos found
                                </div>
                            </div>
                        </div>

                        <!-- Audio search -->
                        <div v-if="form.mediable_type === 'audio'" class="space-y-2">
                            <Label>Select Audio</Label>
                            <Input
                                v-model="audioSearch"
                                placeholder="Search audio..."
                                class="mb-2"
                            />
                            <div class="border border-border rounded-lg overflow-hidden max-h-[250px] overflow-y-auto">
                                <div
                                    v-for="audio in filteredAudios"
                                    :key="audio.id"
                                    @click="selectMedia(audio.id)"
                                    class="flex items-center justify-between px-4 py-3 hover:bg-accent cursor-pointer border-b border-border last:border-0"
                                    :class="{ 'bg-primary/10 border-l-2 border-l-primary': form.mediable_id === audio.id }"
                                >
                                    <div class="flex items-center gap-3">
                                        <Music class="h-4 w-4 text-purple-500 shrink-0" />
                                        <span class="text-sm text-foreground">{{ audio.name }}</span>
                                    </div>
                                    <CheckCircle2
                                        v-if="form.mediable_id === audio.id"
                                        class="h-4 w-4 text-primary"
                                    />
                                </div>
                                <div v-if="filteredAudios.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                    No audio found
                                </div>
                            </div>
                        </div>

                        <!-- Selected media confirmation -->
                        <div v-if="selectedMediaName" class="flex items-center gap-2 p-3 rounded-lg bg-primary/10 border border-primary/20 text-sm">
                            <CheckCircle2 class="h-4 w-4 text-primary shrink-0" />
                            <span>Selected: <strong>{{ selectedMediaName }}</strong></span>
                            <Button variant="ghost" size="sm" class="ml-auto h-6 px-2 text-xs" @click="clearMedia">
                                <X class="h-3 w-3" />
                            </Button>
                        </div>

                    </CardContent>
                </Card>

                <!-- Thumbnail + Tags + Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Post Settings
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">

                        <!-- Thumbnail -->
                        <div class="space-y-2">
                            <Label>Thumbnail Image</Label>
                            <div
                                class="border-2 border-dashed border-border rounded-lg p-6 text-center cursor-pointer hover:border-primary/50 hover:bg-accent/30 transition-colors"
                                @click="thumbnailInput?.click()"
                                @dragover.prevent
                                @drop.prevent="handleDrop"
                            >
                                <div v-if="thumbnailPreview">
                                    <img :src="thumbnailPreview" class="max-h-40 mx-auto rounded-lg object-cover" />
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="mt-2 text-destructive hover:text-destructive"
                                        type="button"
                                        @click.stop="clearThumbnail"
                                    >
                                        <X class="h-3 w-3 mr-1" /> Remove
                                    </Button>
                                </div>
                                <div v-else>
                                    <ImageIcon class="h-10 w-10 mx-auto text-muted-foreground mb-2" />
                                    <p class="text-sm text-muted-foreground">Click or drag to upload thumbnail</p>
                                    <p class="text-xs text-muted-foreground mt-1">PNG, JPG up to 2MB</p>
                                </div>
                            </div>
                            <input
                                ref="thumbnailInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleThumbnailChange"
                            />
                        </div>

                        <!-- Tags -->
                        <div class="space-y-2">
                            <Label>Tags</Label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="tagInput"
                                    placeholder="Add a tag and press Enter..."
                                    @keydown.enter.prevent="addTag"
                                />
                                <Button type="button" variant="outline" @click="addTag">
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                            <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2 mt-2">
                                <Badge
                                    v-for="tag in form.tags"
                                    :key="tag"
                                    variant="secondary"
                                    class="gap-1 cursor-pointer"
                                    @click="removeTag(tag)"
                                >
                                    {{ tag }}
                                    <X class="h-3 w-3" />
                                </Badge>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label>Publication Status</Label>
                            <RadioGroup v-model="form.status" class="grid grid-cols-2 gap-3">
                                <Label
                                    for="status_draft"
                                    class="flex items-center gap-3 rounded-lg border-2 border-muted bg-popover p-4 cursor-pointer hover:bg-accent"
                                    :class="{ 'border-primary': form.status === 'draft' }"
                                >
                                    <RadioGroupItem value="draft" id="status_draft" class="sr-only" />
                                    <FileEdit class="h-5 w-5 text-orange-500" />
                                    <div>
                                        <p class="text-sm font-medium">Draft</p>
                                        <p class="text-xs text-muted-foreground">Only visible to admins</p>
                                    </div>
                                </Label>
                                <Label
                                    for="status_published"
                                    class="flex items-center gap-3 rounded-lg border-2 border-muted bg-popover p-4 cursor-pointer hover:bg-accent"
                                    :class="{ 'border-primary': form.status === 'published' }"
                                >
                                    <RadioGroupItem value="published" id="status_published" class="sr-only" />
                                    <Globe class="h-5 w-5 text-green-500" />
                                    <div>
                                        <p class="text-sm font-medium">Published</p>
                                        <p class="text-xs text-muted-foreground">Visible to all employees</p>
                                    </div>
                                </Label>
                            </RadioGroup>
                        </div>

                    </CardContent>
                </Card>

                <!-- Submit -->
                <div class="flex items-center justify-between pb-6">
                    <Button as-child variant="outline">
                        <Link href="/admin/podcasts">Cancel</Link>
                    </Button>
                    <Button
                        @click="submit"
                        :disabled="form.processing"
                        class="bg-primary text-primary-foreground hover:bg-primary/90 min-w-[140px]"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                        <Save v-else class="h-4 w-4 mr-2" />
                        {{ form.status === 'published' ? 'Publish Post' : 'Save Draft' }}
                    </Button>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import {
    ArrowLeft, FileText, PlaySquare, Music, Ban, Settings,
    Plus, Save, Loader2, X, CheckCircle2, Globe, FileEdit,
    ImageIcon,
} from 'lucide-vue-next'

interface MediaItem {
    id: number
    name: string
    formatted_duration?: string
}

const props = defineProps<{
    videos: MediaItem[]
    audios: MediaItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Blog Management', href: '/admin/podcasts' },
    { title: 'Create Post', href: '#' },
]

// ── Form ───────────────────────────────────────────────
const form = useForm({
    title:         '',
    excerpt:       '',
    description:   '',
    mediable_type: '' as '' | 'video' | 'audio',
    mediable_id:   null as number | null,
    thumbnail:     null as File | null,
    status:        'draft' as 'draft' | 'published',
    tags:          [] as string[],
})

// ── Media search ───────────────────────────────────────
const videoSearch = ref('')
const audioSearch = ref('')

const filteredVideos = computed(() =>
    props.videos.filter(v =>
        v.name.toLowerCase().includes(videoSearch.value.toLowerCase())
    )
)

const filteredAudios = computed(() =>
    props.audios.filter(a =>
        a.name.toLowerCase().includes(audioSearch.value.toLowerCase())
    )
)

const selectedMediaName = computed(() => {
    if (!form.mediable_id) return null
    if (form.mediable_type === 'video') {
        return props.videos.find(v => v.id === form.mediable_id)?.name
    }
    if (form.mediable_type === 'audio') {
        return props.audios.find(a => a.id === form.mediable_id)?.name
    }
    return null
})

function selectMedia(id: number) {
    form.mediable_id = form.mediable_id === id ? null : id
}

function clearMedia() {
    form.mediable_id   = null
    form.mediable_type = ''
}

// ── Thumbnail ──────────────────────────────────────────
const thumbnailInput  = ref<HTMLInputElement | null>(null)
const thumbnailPreview = ref<string | null>(null)

function handleThumbnailChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    form.thumbnail = file
    thumbnailPreview.value = URL.createObjectURL(file)
}

function handleDrop(e: DragEvent) {
    const file = e.dataTransfer?.files?.[0]
    if (!file || !file.type.startsWith('image/')) return
    form.thumbnail = file
    thumbnailPreview.value = URL.createObjectURL(file)
}

function clearThumbnail() {
    form.thumbnail     = null
    thumbnailPreview.value = null
    if (thumbnailInput.value) thumbnailInput.value.value = ''
}

// ── Tags ───────────────────────────────────────────────
const tagInput = ref('')

function addTag() {
    const tag = tagInput.value.trim().toLowerCase()
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag)
    }
    tagInput.value = ''
}

function removeTag(tag: string) {
    form.tags = form.tags.filter(t => t !== tag)
}

// ── Submit ─────────────────────────────────────────────
function submit() {
    form.post('/admin/podcasts', {
        forceFormData: true,
    })
}
</script>