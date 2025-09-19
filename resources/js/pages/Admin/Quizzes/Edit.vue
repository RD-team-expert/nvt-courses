<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Edit Quiz</h1>
                <Link
                    :href="route('admin.quizzes.index')"
                    class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Quizzes
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitQuiz" class="rounded-xl bg-white p-6 shadow-sm">
                <!-- Quiz Details -->
                <div class="mb-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Quiz Details</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="course_id" class="mb-1 block text-sm font-medium text-gray-700">Course</label>
                            <select
                                id="course_id"
                                v-model="form.course_id"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                            >
                                <option value="">Select a Course</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.course_id" class="mt-1 text-xs text-red-600">{{ form.errors.course_id }}</span>
                        </div>
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                            <span v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="title" class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                                placeholder="Enter quiz title"
                            />
                            <span v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                                placeholder="Enter quiz description (optional)"
                            ></textarea>
                            <span v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</span>
                        </div>
                        <div>
                            <label for="pass_threshold" class="mb-1 block text-sm font-medium text-gray-700">Pass Threshold (%)</label>
                            <input
                                id="pass_threshold"
                                v-model.number="form.pass_threshold"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                                placeholder="Enter pass threshold (e.g., 80.00)"
                            />
                            <span v-if="form.errors.pass_threshold" class="mt-1 text-xs text-red-600">{{ form.errors.pass_threshold }}</span>
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <div class="mb-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Questions ({{ form.questions.length }}/20)</h2>
                    <div v-for="(question, index) in form.questions" :key="index" class="mb-6 rounded-lg bg-gray-50 p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-700">Question {{ index + 1 }}</h3>
                            <button
                                v-if="form.questions.length > 1"
                                @click="removeQuestion(index)"
                                type="button"
                                class="text-sm font-semibold text-red-600 transition-colors duration-200 hover:text-red-800"
                                :disabled="form.processing"
                            >
                                Remove
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :for="`question_text_${index}`" class="mb-1 block text-sm font-medium text-gray-700">Question Text</label>
                                <input
                                    :id="`question_text_${index}`"
                                    v-model="question.question_text"
                                    type="text"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing"
                                    placeholder="Enter question text"
                                />
                                <span v-if="form.errors[`questions.${index}.question_text`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`questions.${index}.question_text`] }}
                                </span>
                            </div>
                            <div>
                                <label :for="`type_${index}`" class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                                <select
                                    :id="`type_${index}`"
                                    v-model="question.type"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing"
                                    @change="resetQuestionOptions(index)"
                                >
                                    <option value="radio">Radio (Single Choice)</option>
                                    <option value="checkbox">Checkbox (Multiple Choice)</option>
                                    <option value="text">Text (Open-ended)</option>
                                </select>
                                <span v-if="form.errors[`questions.${index}.type`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`questions.${index}.type`] }}
                                </span>
                            </div>
                            <!-- Only show points field for non-text questions -->
                            <div v-if="question.type !== 'text'">
                                <label :for="`points_${index}`" class="mb-1 block text-sm font-medium text-gray-700">Points</label>
                                <input
                                    :id="`points_${index}`"
                                    v-model.number="question.points"
                                    type="number"
                                    min="0"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing"
                                    placeholder="Enter points"
                                />
                                <span v-if="form.errors[`questions.${index}.points`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`questions.${index}.points`] }}
                                </span>
                            </div>
                        </div>

                        <!-- Only show options and correct answers for non-text questions -->
                        <div v-if="question.type !== 'text'" class="mt-4">
                            <h4 class="mb-2 text-sm font-medium text-gray-700">Options</h4>
                            <div v-for="(option, optIndex) in question.options" :key="optIndex" class="mb-2 flex items-center">
                                <input
                                    v-model="question.options[optIndex]"
                                    type="text"
                                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing"
                                    placeholder="Enter option"
                                />
                                <button
                                    v-if="question.options.length > 2"
                                    @click="removeOption(index, optIndex)"
                                    type="button"
                                    class="ml-2 text-sm font-semibold text-red-600 transition-colors duration-200 hover:text-red-800"
                                    :disabled="form.processing"
                                >
                                    Remove
                                </button>
                            </div>
                            <span v-if="form.errors[`questions.${index}.options`]" class="mt-1 text-xs text-red-600">
                                {{ form.errors[`questions.${index}.options`] }}
                            </span>
                            <button
                                @click="addOption(index)"
                                type="button"
                                class="mt-2 inline-flex items-center rounded-lg bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-700 transition-colors duration-200 hover:bg-indigo-200"
                                :disabled="form.processing || question.options.length >= 10"
                            >
                                Add Option
                            </button>

                            <!-- Correct answers section -->
                            <div class="mt-4">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Correct Answer(s)</label>
                                <div v-if="question.type === 'radio'" class="space-y-2">
                                    <div v-for="(option, optIndex) in question.options" :key="optIndex" class="flex items-center">
                                        <input
                                            :id="`correct_answer_${index}_${optIndex}`"
                                            :checked="question.correct_answer.includes(option)"
                                            :value="option"
                                            type="radio"
                                            :name="`correct_answer_${index}`"
                                            class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            :disabled="form.processing"
                                            @change="updateCorrectAnswer(index, option)"
                                        />
                                        <label :for="`correct_answer_${index}_${optIndex}`" class="ml-2 text-sm text-gray-600">
                                            {{ option || 'Option ' + (optIndex + 1) }}
                                        </label>
                                    </div>
                                </div>
                                <div v-else-if="question.type === 'checkbox'" class="space-y-2">
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
                                            class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            :disabled="form.processing"
                                        />
                                        <label :for="`correct_answer_${index}_${optIndex}`" class="ml-2 text-sm text-gray-600">
                                            {{ option || 'Option ' + (optIndex + 1) }}
                                        </label>
                                    </div>
                                </div>
                                <span v-if="form.errors[`questions.${index}.correct_answer`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`questions.${index}.correct_answer`] }}
                                </span>
                            </div>
                            <!-- Correct Answer Explanation -->
                            <div class="mt-4">
                                <label :for="`correct_answer_explanation_${index}`" class="mb-1 block text-sm font-medium text-gray-700">Correct Answer Explanation</label>
                                <textarea
                                    :id="`correct_answer_explanation_${index}`"
                                    v-model="question.correct_answer_explanation"
                                    rows="2"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing"
                                    placeholder="Explain why this is correct (optional)"
                                ></textarea>
                                <span v-if="form.errors[`questions.${index}.correct_answer_explanation`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`questions.${index}.correct_answer_explanation`] }}
                                </span>
                            </div>
                        </div>

                        <!-- Show a note for text questions -->
                        <div v-if="question.type === 'text'" class="mt-4 rounded-lg bg-blue-50 p-3">
                            <p class="text-sm text-blue-700">
                                📝 This is an open-ended text question. Students will provide their own written response.
                            </p>
                        </div>
                    </div>

                    <button
                        @click="addQuestion"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :disabled="form.processing || form.questions.length >= 20"
                    >
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Question
                    </button>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('admin.quizzes.index')"
                        @click.prevent="confirmDiscard"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                            </svg>
                            Saving...
                        </span>
                        <span v-else>Update Quiz</span>
                    </button>
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
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                            :disabled="form.processing"
                        >
                            Cancel
                        </button>
                        <button
                            @click="discardChanges"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
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
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

export default {
    components: {
        AdminLayout,
        Link,
        Modal,
    },
    props: {
        quiz: {
            type: Object,
            required: true,
        },
        courses: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        // Debug logging
        console.log('=== SETUP START ===');
        console.log('Quiz prop received:', props.quiz);
        console.log('Quiz questions:', props.quiz?.questions);
        console.log('Courses prop:', props.courses);

        // Helper function to safely process questions
        const processQuestions = (questions) => {
            console.log('Processing questions:', questions);

            if (!questions || !Array.isArray(questions)) {
                console.log('No questions found, returning default');
                return [
                    {
                        question_text: '',
                        type: 'radio',
                        points: 0,
                        options: ['', ''],
                        correct_answer: [],
                        correct_answer_explanation: '',
                    },
                ];
            }

            const processed = questions.map((question, index) => {
                console.log(`Processing question ${index}:`, question);

                if (!question) {
                    console.log(`Question ${index} is null/undefined`);
                    return {
                        question_text: '',
                        type: 'radio',
                        points: 0,
                        options: ['', ''],
                        correct_answer: [],
                        correct_answer_explanation: '',
                    };
                }

                // Handle text questions differently
                if (question.type === 'text') {
                    console.log(`Question ${index} is text type`);
                    return {
                        question_text: question.question_text || '',
                        type: 'text',
                        points: 0,
                        options: [],
                        correct_answer: [],
                        correct_answer_explanation: question.correct_answer_explanation || '',
                    };
                }

                // Handle radio/checkbox questions
                let options = ['', ''];
                if (question.options && Array.isArray(question.options)) {
                    console.log(`Question ${index} options:`, question.options);
                    options = question.options.map((option) => (typeof option === 'string' ? option : option?.option_text || ''));
                    if (options.length < 2) options = [...options, '', ''].slice(0, 2);
                } else {
                    console.log(`Question ${index} has no valid options`);
                }

                let correctAnswer = [];
                if (question.correct_answer && Array.isArray(question.correct_answer)) {
                    console.log(`Question ${index} correct_answer:`, question.correct_answer);
                    correctAnswer = question.correct_answer.map((answer) => (typeof answer === 'string' ? answer : answer?.option_text || ''));
                } else {
                    console.log(`Question ${index} has no valid correct_answer`);
                }

                const processedQuestion = {
                    question_text: question.question_text || '',
                    type: question.type || 'radio',
                    points: question.points || 0,
                    options: options,
                    correct_answer: correctAnswer,
                    correct_answer_explanation: question.correct_answer_explanation || '',
                };

                console.log(`Processed question ${index}:`, processedQuestion);
                return processedQuestion;
            });

            console.log('All processed questions:', processed);
            return processed;
        };

        // DECLARE form variable here - BEFORE any usage
        let form;

        try {
            console.log('Creating form...');
            // Initialize form with existing quiz data
            form = useForm({
                course_id: props.quiz?.course_id || '',
                title: props.quiz?.title || '',
                description: props.quiz?.description || '',
                status: props.quiz?.status || 'draft',
                pass_threshold: props.quiz?.pass_threshold || 80.00, // New field for dynamic pass threshold
                questions: processQuestions(props.quiz?.questions),
                processing: false,
            });

            console.log('Form created successfully:', form);
        } catch (error) {
            console.error('Error creating form:', error);
            throw error;
        }

        // State for discard modal
        const showDiscardModal = ref(false);

        // Add error handling to all functions
        const addQuestion = () => {
            try {
                console.log('Adding new question...');
                if (form.questions.length < 20) {
                    form.questions.push({
                        question_text: '',
                        type: 'radio',
                        points: 0,
                        options: ['', ''],
                        correct_answer: [],
                        correct_answer_explanation: '', // Initialize with empty explanation
                    });
                    console.log('Question added. Total questions:', form.questions.length);
                }
            } catch (error) {
                console.error('Error adding question:', error);
            }
        };

        const removeQuestion = (index) => {
            try {
                console.log('Removing question at index:', index);
                if (form.questions.length > 1) {
                    form.questions.splice(index, 1);
                    console.log('Question removed. Remaining questions:', form.questions.length);
                }
            } catch (error) {
                console.error('Error removing question:', error);
            }
        };

        const addOption = (questionIndex) => {
            try {
                console.log('Adding option to question:', questionIndex);
                if (form.questions[questionIndex].options.length < 10) {
                    form.questions[questionIndex].options.push('');
                    console.log('Option added. Total options:', form.questions[questionIndex].options.length);
                }
            } catch (error) {
                console.error('Error adding option:', error);
            }
        };

        const removeOption = (questionIndex, optionIndex) => {
            try {
                console.log('Removing option:', { questionIndex, optionIndex });
                if (form.questions[questionIndex].options.length > 2) {
                    const question = form.questions[questionIndex];
                    const removedOption = question.options[optionIndex];
                    question.options.splice(optionIndex, 1);

                    if (Array.isArray(question.correct_answer)) {
                        question.correct_answer = question.correct_answer.filter((opt) => opt !== removedOption);
                    }
                    console.log('Option removed successfully');
                }
            } catch (error) {
                console.error('Error removing option:', error);
            }
        };

        const resetQuestionOptions = (index) => {
            try {
                console.log('Resetting question options for index:', index);
                const question = form.questions[index];
                console.log('Question before reset:', question);

                if (question.type === 'text') {
                    question.options = [];
                    question.correct_answer = [];
                    question.points = 0;
                    question.correct_answer_explanation = ''; // Reset explanation for text
                } else {
                    if (!question.options || question.options.length < 2) {
                        question.options = ['', ''];
                    }
                    question.correct_answer = [];
                    question.correct_answer_explanation = ''; // Reset for non-text
                }

                console.log('Question after reset:', question);
            } catch (error) {
                console.error('Error resetting question options:', error);
            }
        };

        const updateCorrectAnswer = (index, option) => {
            try {
                console.log('Updating correct answer:', { index, option });
                const question = form.questions[index];

                if (question.type === 'radio') {
                    question.correct_answer = [option];
                } else if (question.type === 'checkbox') {
                    // Ensure correct_answer is always an array for checkboxes
                    if (!Array.isArray(question.correct_answer)) {
                        question.correct_answer = [];
                    }

                    const indexInArray = question.correct_answer.findIndex((answer) => answer === option);
                    if (indexInArray > -1) {
                        // Remove the option if it's already selected
                        question.correct_answer.splice(indexInArray, 1);
                    } else {
                        // Add the option if it's not selected
                        question.correct_answer.push(option);
                    }
                }

                console.log('Updated correct answer:', question.correct_answer);
            } catch (error) {
                console.error('Error updating correct answer:', error);
            }
        };

        const submitQuiz = () => {
            try {
                console.log('=== SUBMITTING QUIZ ===');
                console.log('Form data before submission:', form);

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
                        options: question.options,
                        correct_answer: Array.isArray(question.correct_answer)
                            ? question.correct_answer
                            : question.correct_answer
                                ? [question.correct_answer]
                                : [],
                        correct_answer_explanation: question.correct_answer_explanation || '', // Include new field
                    })),
                };

                console.log('Formatted data for submission:', formData);

                router.put(route('admin.quizzes.update', props.quiz.id), formData, {
                    onStart: () => {
                        console.log('Submission started');
                    },
                    onProgress: (progress) => {
                        console.log('Submission progress:', progress);
                    },
                    onSuccess: (page) => {
                        console.log('Submission successful:', page);
                        router.visit(route('admin.quizzes.index'), { replace: true });
                    },
                    onError: (errors) => {
                        console.error('Submission errors:', errors);
                    },
                    onFinish: () => {
                        console.log('Submission finished');
                    },
                });
            } catch (error) {
                console.error('Error in submitQuiz:', error);
            }
        };

        const confirmDiscard = () => {
            try {
                console.log('Confirming discard, form is dirty:', form.isDirty);
                if (form.isDirty) {
                    showDiscardModal.value = true;
                } else {
                    discardChanges();
                }
            } catch (error) {
                console.error('Error in confirmDiscard:', error);
            }
        };

        const discardChanges = () => {
            try {
                console.log('Discarding changes');
                form.reset();
                showDiscardModal.value = false;
                router.visit(route('admin.quizzes.index'));
            } catch (error) {
                console.error('Error discarding changes:', error);
            }
        };

        // Watch for changes with error handling
        watch(
            () => props.quiz,
            (newQuiz) => {
                try {
                    console.log('Quiz prop changed:', newQuiz);
                    if (newQuiz) {
                        form.course_id = newQuiz.course_id || '';
                        form.title = newQuiz.title || '';
                        form.description = newQuiz.description || '';
                        form.status = newQuiz.status || 'draft';
                        form.pass_threshold = newQuiz.pass_threshold || 80.00; // Update pass threshold
                        form.questions = processQuestions(newQuiz.questions);
                        console.log('Form updated from prop change');
                    }
                } catch (error) {
                    console.error('Error in quiz watch:', error);
                }
            },
            { deep: true },
        );

        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Quizzes', route: 'admin.quizzes.index' },
            { name: 'Edit', route: null },
        ];

        console.log('=== SETUP COMPLETE ===');

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
