<template>
    <AppLayout title="Edit Instruction">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Instruction
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <Label for="name" value="Name" />
                                <Input
                                    id="name"
                                    type="text"
                                    class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <Label for="type" value="Type (used as identifier)" />
                                <Input
                                    id="type"
                                    type="text"
                                    class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    v-model="form.type"
                                    required
                                />
                                <InputError :message="form.errors.type" class="mt-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Use a unique identifier like 'coding', 'academic', etc.
                                </p>
                            </div>

                            <div class="mb-4">
                                <Label for="content" value="Instruction Content" />
                                <textarea
                                    id="content"
                                    class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    v-model="form.content"
                                    rows="6"
                                    required
                                ></textarea>
                                <InputError :message="form.errors.content" class="mt-2" />
                            </div>

                            <div class="mb-4 flex items-center">
                                <Checkbox id="is_active" v-model:checked="form.is_active" />
                                <Label for="is_active" value="Active" class="ml-2" />
                                <InputError :message="form.errors.is_active" class="mt-2" />
                            </div>

                            <div class="mb-4 flex items-center">
                                <Checkbox id="is_default" v-model:checked="form.is_default" />
                                <Label for="is_default" value="Set as Default" class="ml-2" />
                                <InputError :message="form.errors.is_default" class="mt-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                    Only one instruction can be set as default.
                                </p>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <Link
                                    :href="route('admin.instructions.index')"
                                    class="mr-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition"
                                >
                                    Cancel
                                </Link>
                                <Button class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Update
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3'; // Updated import
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

export default defineComponent({
    components: {
        AppLayout,
        Link,
        Button,
        Input,
        Label,
        InputError,
        Checkbox
    },
    props: {
        instruction: Object
    },
    setup(props) {
        const form = useForm({
            name: props.instruction.name,
            type: props.instruction.type,
            content: props.instruction.content,
            is_active: props.instruction.is_active,
            is_default: props.instruction.is_default
        });

        return { form };
    },
    methods: {
        submit() {
            this.form.put(route('admin.instructions.update', this.instruction.id));
        }
    }
});
</script>