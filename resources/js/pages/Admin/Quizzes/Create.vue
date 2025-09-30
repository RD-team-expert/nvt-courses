<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Create Quiz</h1>
                <Button variant="outline" as-child>
                    <Link :href="route('admin.quizzes.index')">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Quizzes
                    </Link>
                </Button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitQuiz">
                <!-- Quiz Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Quiz Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <Label for="course_id">Course</Label>
                            <Select v-model="form.course_id" :disabled="form.processing">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Course" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.course_id" class="mt-1 text-xs text-destructive">{{ form.errors.course_id }}</span>
                        </div>
                        <div>
                            <Label for="status">Status</Label>
                            <Select v-model="form.status" :disabled="form.processing">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                    <SelectItem value="archived">Archived</SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.status" class="mt-1 text-xs text-destructive">{{ form.errors.status }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <Label for="title">Title</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                :disabled="form.processing"
                                placeholder="Enter quiz title"
                            />
                            <span v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                :disabled="form.processing"
                                placeholder="Enter quiz description (optional)"
                            />
                            <span v-if="form.errors.description" class="mt-1 text-xs text-destructive">{{ form.errors.description }}</span>
                        </div>
                        <div>
                            <Label for="pass_threshold">Pass Threshold (%)</Label>
                            <Input
                                id="pass_threshold"
                                v-model.number="form.pass_threshold"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :disabled="form.processing"
                                placeholder="Enter pass threshold (e.g., 80.00)"
                            />
                            <span v-if="form.errors.pass_threshold" class="mt-1 text-xs text-destructive">{{ form.errors.pass_threshold }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Questions -->
             <Card class="mt-8">
                 <CardHeader>
                     <CardTitle>Questions ({{ form.questions.length }}/20)</CardTitle>
                 </CardHeader>
                 <CardContent>
                    <div v-for="(question, index) in form.questions" :key="index"
                         class="border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-100">Question {{ index + 1 }}</h3>
                            <Button
                                v-if="form.questions.length > 1"
                                @click="removeQuestion(index)"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                :disabled="form.processing"
                            >
                                Remove
                            </Button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <Label :for="`question_text_${index}`">Question Text</Label>
                                <Input
                                    :id="`question_text_${index}`"
                                    v-model="question.question_text"
                                    type="text"
                                    :disabled="form.processing"
                                    placeholder="Enter question text"
                                />
                                <span v-if="form.errors[`questions.${index}.question_text`]" class="mt-1 text-xs text-destructive">{{ form.errors[`questions.${index}.question_text`] }}</span>
                            </div>
                            <div>
                                <Label :for="`type_${index}`">Type</Label>
                                <Select v-if="question" v-model="question.type" @update:model-value="resetQuestionOptions(index)" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="radio">Radio (Single Choice)</SelectItem>
                                        <SelectItem value="checkbox">Checkbox (Multiple Choice)</SelectItem>
                                        <SelectItem value="text">Text (Open-ended)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors[`questions.${index}.type`]" class="mt-1 text-xs text-destructive">
                                    {{ form.errors[`questions.${index}.type`] }}
                                </span>
                            </div>
                            <!-- Only show points field for non-text questions -->
                            <div v-if="question && question.type !== 'text'">
                                <Label :for="`points_${index}`">Points</Label>
                                <Input
                                    :id="`points_${index}`"
                                    v-model.number="question.points"
                                    type="number"
                                    min="0"
                                    :disabled="form.processing"
                                    placeholder="Enter points"
                                />
                                <span v-if="form.errors[`questions.${index}.points`]" class="mt-1 text-xs text-destructive">
                                    {{ form.errors[`questions.${index}.points`] }}
                                </span>
                            </div>
                        </div>

                        <!-- Only show options and correct answers for non-text questions -->
                        <div v-if="question && question.type !== 'text'" class="mt-4">
                            <h4 class="mb-2 text-sm font-medium text-gray-100">Options</h4>
                            <div v-for="(option, optIndex) in question.options" :key="optIndex" class="mb-2 flex items-center gap-2">
                                <Input
                                    v-model="question.options[optIndex]"
                                    type="text"
                                    class="flex-1"
                                    :disabled="form.processing"
                                    placeholder="Enter option"
                                />
                                <Button
                                    v-if="question.options.length > 2"
                                    @click="removeOption(index, optIndex)"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    :disabled="form.processing"
                                >
                                    Remove
                                </Button>
                            </div>
                            <span v-if="form.errors[`questions.${index}.options`]" class="mt-1 text-xs text-destructive">
                                {{ form.errors[`questions.${index}.options`] }}
                            </span>
                            <Button
                                @click="addOption(index)"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="mt-2"
                                :disabled="form.processing || question.options.length >= 10"
                            >
                                Add Option
                            </Button>

                            <!-- Correct answers section -->
                            <div class="mt-4">
                                <label class="mb-1 block text-sm font-medium text-gray-100">Correct Answer(s)</label>
                                <div v-if="question && question.type === 'radio'" class="space-y-2">
                                    <div v-for="(option, optIndex) in question.options" :key="`radio_${index}_${optIndex}`" class="flex items-center">
                                        <input
                                            :id="`correct_answer_${index}_${optIndex}`"
                                            :checked="question.correct_answer === option"
                                            :value="option"
                                            type="radio"
                                            :name="`correct_answer_${index}`"
                                            class="h-4 w-4 border-gray-300 border text-indigo-600 focus:ring-indigo-500"
                                            :disabled="form.processing"
                                            @change="updateCorrectAnswer(index, option)"
                                        />
                                        <label :for="`correct_answer_${index}_${optIndex}`" class="ml-2 text-sm text-gray-300">
                                            {{ option || 'Option ' + (optIndex + 1) }}
                                        </label>
                                    </div>
                                </div>
                                <div v-else-if="question && question.type === 'checkbox'" class="space-y-2">
                                    <div
                                        v-for="(option, optIndex) in question.options"
                                        :key="`checkbox_${index}_${optIndex}_${option}`"
                                        class="flex items-center"
                                    >
                                        <input
                                            :id="`correct_answer_${index}_${optIndex}`"
                                            :value="option"
                                            type="checkbox"
                                            v-model="question.correct_answer"
                                            class="h-4 w-4 border-gray-300 border text-indigo-600 focus:ring-indigo-500"
                                            :disabled="form.processing"
                                        />
                                        <label :for="`correct_answer_${index}_${optIndex}`" class="ml-2 text-sm text-gray-300">
                                            {{ option || 'Option ' + (optIndex + 1) }}
                                        </label>
                                    </div>
                                </div>
                                <span v-if="form.errors[`questions.${index}.correct_answer`]" class="mt-1 text-xs text-destructive">
                                    {{ form.errors[`questions.${index}.correct_answer`] }}
                                </span>
                            </div>
                            <!-- Correct Answer Explanation -->
                            <div class="mt-4">
                                <Label :for="`correct_answer_explanation_${index}`">Correct Answer Explanation</Label>
                                <Textarea
                                    :id="`correct_answer_explanation_${index}`"
                                    v-model="question.correct_answer_explanation"
                                    rows="2"
                                    :disabled="form.processing"
                                    placeholder="Explain why this is correct (optional)"
                                />
                                <span v-if="form.errors[`questions.${index}.correct_answer_explanation`]" class="mt-1 text-xs text-destructive">
                                    {{ form.errors[`questions.${index}.correct_answer_explanation`] }}
                                </span>
                            </div>
                        </div>

                        <!-- Show a note for text questions -->
                        <div v-if="question && question.type === 'text'" class="mt-4 rounded-lg bg-blue-50 p-3">
                            <p class="text-sm text-blue-700">
                                📝 This is an open-ended text question. Students will provide their own written response.
                            </p>
                        </div>
                    </div>

                    <Button
                        @click="addQuestion"
                        type="button"
                        class="inline-flex items-center"
                        :disabled="form.processing || form.questions.length >= 20"
                    >
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Question
                    </Button>
                </CardContent>
            </Card>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
                    <Button
                        as-child
                        variant="outline"
                        :disabled="form.processing"
                        @click.prevent="confirmDiscard"
                    >
                        <Link :href="route('admin.quizzes.index')">
                            Cancel
                        </Link>
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                            </svg>
                            Saving...
                        </span>
                        <span v-else>Create Quiz</span>
                    </Button>
                </div>
            </form>

            <!-- Discard Confirmation Modal -->
            <Modal :show="showDiscardModal" @close="showDiscardModal = false">
                <div class="p-6 sm:p-8">
                    <h2 class="mb-3 text-xl font-semibold text-gray-900">Discard Changes</h2>
                    <p class="mb-6 text-sm text-gray-600">Are you sure you want to discard your changes? This action cannot be undone.</p>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="showDiscardModal = false"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-100 transition-colors duration-200 hover:bg-gray-200 focus:outline-hidden focus:ring-2 focus:ring-gray-300"
                            :disabled="form.processing"
                        >
                            Cancel
                        </button>
                        <button
                            @click="discardChanges"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500"
                            :disabled="form.processing"
                        >
                            Discard
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>

<script>
import { Link, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default {
    components: {
        AdminLayout,
        Link,
        Modal,
        Button,
        Input,
        Label,
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
        SelectValue,
        Textarea,
        Card,
        CardContent,
        CardHeader,
        CardTitle,
    },
    props: {
        courses: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        // Reactive form state
        const form = useForm({
            course_id: '',
            title: '',
            description: '',
            status: 'draft',
            pass_threshold: 80.00, // New field for dynamic pass threshold
            questions: [
                {
                    question_text: '',
                    type: 'radio',
                    points: 0,
                    options: ['', ''],
                    correct_answer: '',
                    correct_answer_explanation: '', // New field for explanation
                },
            ],
            processing: false,
        });

        // State for discard modal
        const showDiscardModal = ref(false);

        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Quizzes', route: 'admin.quizzes.index' },
            { name: 'Create', route: null },
        ];

        // Add a new question
        const addQuestion = () => {
            if (form.questions.length < 20) {
                form.questions.push({
                    question_text: '',
                    type: 'radio',
                    points: 0,
                    options: ['', ''],
                    correct_answer: '',
                    correct_answer_explanation: '', // Initialize with empty explanation
                });
            }
        };

        // Remove a question
        const removeQuestion = (index) => {
            if (form.questions.length > 1) {
                form.questions.splice(index, 1);
            }
        };

        // Add an option to a question
        const addOption = (questionIndex) => {
            if (form.questions[questionIndex].options.length < 10) {
                form.questions[questionIndex].options.push('');
            }
        };

        // Remove an option from a question
        const removeOption = (questionIndex, optionIndex) => {
            if (form.questions[questionIndex].options.length > 2) {
                const question = form.questions[questionIndex];
                const removedOption = question.options[optionIndex];
                question.options.splice(optionIndex, 1);

                // Update correct_answer if it matches the removed option
                if (question.type === 'radio' && question.correct_answer === removedOption) {
                    question.correct_answer = '';
                } else if (question.type === 'checkbox' && Array.isArray(question.correct_answer)) {
                    question.correct_answer = question.correct_answer.filter((opt) => opt !== removedOption);
                }
            }
        };

        // Reset options and correct_answer when question type changes
        const resetQuestionOptions = (index) => {
            const question = form.questions[index];
            if (question.type === 'text') {
                question.options = [];
                question.correct_answer = '';
                question.points = 0;
                question.correct_answer_explanation = ''; // Reset explanation for text
            } else {
                if (!question.options || question.options.length < 2) {
                    question.options = ['', ''];
                }
                // Initialize correct_answer based on type
                if (question.type === 'checkbox') {
                    question.correct_answer = [];
                } else {
                    question.correct_answer = '';
                }
                question.correct_answer_explanation = ''; // Reset for non-text
            }
        };

        // Update correct_answer based on selected option(s)
        const updateCorrectAnswer = (index, option) => {
            const question = form.questions[index];
            if (question.type === 'radio') {
                question.correct_answer = option;
            }
            // Note: checkbox handling is done by v-model in template
        };

        // Submit the quiz
        const submitQuiz = () => {
            const formData = {
                course_id: form.course_id,
                title: form.title,
                description: form.description,
                status: form.status,
                pass_threshold: form.pass_threshold, // Include new field
                questions: form.questions.map((question) => ({
                    question_text: question.question_text,
                    type: question.type,
                    points: question.type === 'text' ? 0 : question.points,
                    options: question.options || [],
                    correct_answer: Array.isArray(question.correct_answer)
                        ? question.correct_answer
                        : question.correct_answer
                            ? [question.correct_answer]
                            : [],
                    correct_answer_explanation: question.correct_answer_explanation || '', // Include new field
                })),
            };

            console.log('Submitting data:', formData); // Debug log

            form.processing = true;
            router.post(route('admin.quizzes.store'), formData, {
                onSuccess: () => {
                    router.visit(route('admin.quizzes.index'), { replace: true });
                },
                onError: (errors) => {
                    console.log('Submission errors:', errors);
                    form.processing = false;
                },
                onFinish: () => {
                    form.processing = false;
                },
            });
        };

        // Show discard modal
        const confirmDiscard = () => {
            if (form.isDirty) {
                showDiscardModal.value = true;
            } else {
                discardChanges();
            }
        };

        // Discard changes and navigate back
        const discardChanges = () => {
            form.reset();
            showDiscardModal.value = false;
            router.visit(route('admin.quizzes.index'));
        };

        return {
            form,
            addQuestion,
            removeQuestion,
            addOption,
            removeOption,
            resetQuestionOptions,
            updateCorrectAnswer,
            submitQuiz,
            showDiscardModal,
            confirmDiscard,
            discardChanges,
            breadcrumbs,
        };
    },
};
</script>

<style scoped>
/* General Layout */
.max-w-7xl {
    @apply px-4 sm:px-6 lg:px-8;
}

/* Form Styling */
form {
    @apply space-y-6;
}

input,
select,
textarea {
    @apply transition-colors duration-200;
}

input:disabled,
select:disabled,
textarea:disabled,
button:disabled {
    @apply cursor-not-allowed opacity-50;
}

/* Question Sections */
.bg-gray-50 {
    @apply transition-all duration-200;
}

/* Buttons */
button,
a {
    @apply transition-colors duration-200;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
    .grid-cols-1 {
        @apply space-y-4;
    }

    .sm:col-span-2 {
        @apply col-span-1;
    }

    .px-4 {
        @apply px-2;
    }

    .py-2 {
        @apply py-1;
    }

    .text-sm {
        @apply text-xs;
    }
}

/* Modal Styling */
.modal-content {
    @apply transform transition-all duration-200;
}
</style>
