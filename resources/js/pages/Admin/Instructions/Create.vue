<!-- Create Instruction Page -->
<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'

// Icons
import {
    ArrowLeft,
    Plus,
    FileText,
    Settings,
    AlertCircle,
    CheckCircle2
} from 'lucide-vue-next'

const form = useForm({
    name: '',
    type: '',
    content: '',
    is_active: true,
    is_default: false
})

function submit() {
    form.post(route('admin.instructions.store'))
}
</script>

<template>
    <AppLayout title="Create Instruction">
        <template #header>
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-primary/10">
                    <FileText class="h-6 w-6 text-primary" />
                </div>
                <div>
                    <h2 class="text-xl font-semibold">Create New Instruction</h2>
                    <p class="text-sm text-muted-foreground">Add a new instruction template for the system</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-blue-100">
                                <Plus class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <CardTitle>Instruction Details</CardTitle>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Fill in the information below to create a new instruction
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Name Field -->
                            <div class="space-y-2">
                                <Label for="name">
                                    Name <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Enter instruction name"
                                    required
                                    autofocus
                                />
                                <div v-if="form.errors.name" class="text-sm text-destructive flex items-center mt-2">
                                    <AlertCircle class="mr-1 h-4 w-4" />
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Type Field -->
                            <div class="space-y-2">
                                <Label for="type">
                                    Type (used as identifier) <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="type"
                                    v-model="form.type"
                                    type="text"
                                    placeholder="e.g., coding, academic, general"
                                    required
                                />
                                <p class="text-sm text-muted-foreground">
                                    Use a unique identifier like 'coding', 'academic', etc.
                                </p>
                                <div v-if="form.errors.type" class="text-sm text-destructive flex items-center">
                                    <AlertCircle class="mr-1 h-4 w-4" />
                                    {{ form.errors.type }}
                                </div>
                            </div>

                            <!-- Content Field -->
                            <div class="space-y-2">
                                <Label for="content">
                                    Instruction Content <span class="text-destructive">*</span>
                                </Label>
                                <Textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="6"
                                    placeholder="Enter the detailed instruction content..."
                                    required
                                    class="resize-none"
                                />
                                <div v-if="form.errors.content" class="text-sm text-destructive flex items-center">
                                    <AlertCircle class="mr-1 h-4 w-4" />
                                    {{ form.errors.content }}
                                </div>
                            </div>

                            <!-- Settings Section -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2 mb-4">
                                    <Settings class="h-5 w-5 text-primary" />
                                    <Label class="text-base font-medium">Settings</Label>
                                </div>

                                <!-- Active Checkbox -->
                                <div class="flex items-start space-x-3 p-4 border rounded-lg">
                                    <Checkbox
                                        id="is_active"
                                        :checked="form.is_active"
                                        @update:checked="form.is_active = $event"
                                    />
                                    <div class="flex-1">
                                        <Label
                                            for="is_active"
                                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            Active
                                        </Label>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            Enable this instruction for use in the system
                                        </p>
                                    </div>
                                    <div v-if="form.errors.is_active" class="text-sm text-destructive flex items-center">
                                        <AlertCircle class="mr-1 h-4 w-4" />
                                        {{ form.errors.is_active }}
                                    </div>
                                </div>

                                <!-- Default Checkbox -->
                                <div class="flex items-start space-x-3 p-4 border rounded-lg">
                                    <Checkbox
                                        id="is_default"
                                        :checked="form.is_default"
                                        @update:checked="form.is_default = $event"
                                    />
                                    <div class="flex-1">
                                        <Label
                                            for="is_default"
                                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            Set as Default
                                        </Label>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            Only one instruction can be set as default.
                                        </p>
                                    </div>
                                    <div v-if="form.errors.is_default" class="text-sm text-destructive flex items-center">
                                        <AlertCircle class="mr-1 h-4 w-4" />
                                        {{ form.errors.is_default }}
                                    </div>
                                </div>
                            </div>

                            <!-- Info Alert -->
                            <Alert>
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>
                                    Instructions are used to guide the system behavior. Make sure the content is clear and well-defined.
                                </AlertDescription>
                            </Alert>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-6">
                                <Button asChild variant="outline">
                                    <Link :href="route('admin.instructions.index')">
                                        <ArrowLeft class="mr-2 h-4 w-4" />
                                        Cancel
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="min-w-24"
                                >
                                    <div v-if="form.processing" class="flex items-center">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Creating...
                                    </div>
                                    <div v-else class="flex items-center">
                                        <CheckCircle2 class="mr-2 h-4 w-4" />
                                        Create
                                    </div>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
