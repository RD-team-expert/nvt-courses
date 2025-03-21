<template>
    <AppLayout title="Gemini AI">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gemini AI Assistant
            </h2>
        </template>

        <div class="py-6 md:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 md:p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <!-- Model settings panel -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Model Settings</h3>
                                <button 
                                    @click="showSettings = !showSettings" 
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center"
                                >
                                    {{ showSettings ? 'Hide Settings' : 'Show Settings' }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" :class="{'rotate-180': showSettings}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div v-if="showSettings" class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg shadow-inner transition-all duration-300">
                                <!-- Instruction Type Selection -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        AI Instruction Mode
                                        <span v-if="modelSettings.instructionType !== 'default'" class="ml-2 text-xs text-indigo-600 dark:text-indigo-400">
                                            ({{ modelSettings.instructionType.charAt(0).toUpperCase() + modelSettings.instructionType.slice(1) }} mode active)
                                        </span>
                                    </label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2">
                                        <button 
                                            v-for="type in instructionTypes" 
                                            :key="type"
                                            @click="modelSettings.instructionType = type"
                                            :class="[
                                                'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                                modelSettings.instructionType === type 
                                                    ? 'bg-indigo-600 text-white' 
                                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Custom Instruction -->
                                <div class="mb-4">
                                    <label for="customInstruction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Custom Instruction (overrides mode selection)
                                    </label>
                                    <textarea
                                        id="customInstruction"
                                        v-model="modelSettings.customInstruction"
                                        rows="2"
                                        class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 text-sm"
                                        placeholder="E.g., You are an expert in Laravel and Vue.js. Focus on providing practical code examples..."
                                    ></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to use the selected instruction mode above.</p>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Temperature -->
                                    <div>
                                        <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Temperature: {{ modelSettings.temperature.toFixed(2) }}
                                        </label>
                                        <div class="flex items-center">
                                            <span class="text-xs text-gray-500 mr-2">0.0</span>
                                            <input 
                                                id="temperature" 
                                                type="range" 
                                                min="0" 
                                                max="1" 
                                                step="0.01" 
                                                v-model.number="modelSettings.temperature"
                                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                                            >
                                            <span class="text-xs text-gray-500 ml-2">1.0</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Controls randomness: Lower values are more deterministic, higher values more creative.</p>
                                    </div>
                                    
                                    <!-- Top K -->
                                    <div>
                                        <label for="topK" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Top K: {{ modelSettings.topK }}
                                        </label>
                                        <div class="flex items-center">
                                            <span class="text-xs text-gray-500 mr-2">1</span>
                                            <input 
                                                id="topK" 
                                                type="range" 
                                                min="1" 
                                                max="40" 
                                                step="1" 
                                                v-model.number="modelSettings.topK"
                                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                                            >
                                            <span class="text-xs text-gray-500 ml-2">40</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Limits token selection to the K most likely tokens.</p>
                                    </div>
                                    
                                    <!-- Top P -->
                                    <div>
                                        <label for="topP" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Top P: {{ modelSettings.topP.toFixed(2) }}
                                        </label>
                                        <div class="flex items-center">
                                            <span class="text-xs text-gray-500 mr-2">0.0</span>
                                            <input 
                                                id="topP" 
                                                type="range" 
                                                min="0" 
                                                max="1" 
                                                step="0.01" 
                                                v-model.number="modelSettings.topP"
                                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                                            >
                                            <span class="text-xs text-gray-500 ml-2">1.0</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Nucleus sampling: Only consider tokens with the top P probability mass.</p>
                                    </div>
                                    
                                    <!-- Max Output Tokens -->
                                    <div>
                                        <label for="maxOutputTokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Max Output Tokens: {{ modelSettings.maxOutputTokens }}
                                        </label>
                                        <div class="flex items-center">
                                            <span class="text-xs text-gray-500 mr-2">50</span>
                                            <input 
                                                id="maxOutputTokens" 
                                                type="range" 
                                                min="50" 
                                                max="2048" 
                                                step="1" 
                                                v-model.number="modelSettings.maxOutputTokens"
                                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                                            >
                                            <span class="text-xs text-gray-500 ml-2">2048</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Maximum number of tokens that can be generated in the response.</p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 flex justify-end">
                                    <button 
                                        @click="resetModelSettings" 
                                        class="px-3 py-1 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 mr-2"
                                    >
                                        Reset to Defaults
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chat interface -->
                        <div class="mb-6 space-y-4">
                            <!-- Chat messages -->
                            <div v-if="chatHistory.length > 0" class="space-y-4 mb-6 max-h-[60vh] overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-inner" ref="chatContainer">
                                <div v-for="(message, index) in chatHistory" :key="index" 
                                    :class="[
                                        'p-4 rounded-lg shadow-md transition-all duration-300', 
                                        message.isUser ? 'ml-auto bg-indigo-100 dark:bg-indigo-900 max-w-[85%] md:max-w-[70%]' : 'bg-gray-100 dark:bg-gray-700 max-w-[85%] md:max-w-[70%] relative'
                                    ]">
                                    <div class="font-medium text-sm mb-2 flex justify-between items-center">
                                        <span class="flex items-center">
                                            <span class="w-2 h-2 rounded-full mr-2" :class="message.isUser ? 'bg-indigo-500' : 'bg-green-500'"></span>
                                            {{ message.isUser ? 'You' : 'Gemini AI' }}
                                            <span v-if="!message.isUser && index === currentTypingIndex" class="typing-indicator ml-2">
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                                <span class="dot"></span>
                                            </span>
                                        </span>
                                        <button 
                                            v-if="!message.isUser" 
                                            @click="copyToClipboard(message.text)"
                                            class="text-gray-500 hover:text-indigo-600 transition-colors p-1 rounded"
                                            title="Copy response"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div v-if="message.isUser" class="text-gray-800 dark:text-gray-200 break-words">
                                        {{ message.text }}
                                    </div>
                                    <div v-else class="prose prose-sm dark:prose-invert max-w-none break-words">
                                        <div v-if="message.isTyping" v-html="marked(message.displayedText)"></div>
                                        <div v-else v-html="message.html"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="text-center py-16 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-inner">
                                <div class="flex justify-center mb-4">
                                    <div class="p-4 bg-indigo-100 dark:bg-indigo-900 rounded-full">
                                        <svg class="h-12 w-12 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">Welcome to Gemini AI</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ask anything to get started with your AI assistant.</p>
                            </div>

                            <!-- Input area -->
                            <div class="mt-6">
                                <div class="relative">
                                    <textarea
                                        id="prompt"
                                        v-model="prompt"
                                        rows="3"
                                        class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Ask Gemini something..."
                                        @keydown.enter.ctrl.prevent="generateContent"
                                    ></textarea>
                                    <button
                                        @click="generateContent"
                                        class="absolute bottom-3 right-3 inline-flex items-center justify-center p-2 bg-indigo-600 border border-transparent rounded-full font-semibold text-white hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition"
                                        :disabled="isLoading || !prompt.trim()"
                                        title="Send message (Ctrl+Enter)"
                                    >
                                        <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Press Ctrl+Enter to send</p>
                            </div>
                        </div>

                        <!-- Copy notification -->
                        <div 
                            v-if="showCopyNotification" 
                            class="fixed bottom-4 right-4 bg-gray-800 dark:bg-gray-700 text-white px-4 py-2 rounded-md shadow-lg transition-opacity duration-300 z-50"
                            :class="{ 'opacity-100': showCopyNotification, 'opacity-0': !showCopyNotification }"
                        >
                            Copied to clipboard!
                        </div>

                        <!-- Error display -->
                        <div v-if="error" class="mt-4 p-4 bg-red-50 dark:bg-red-900 text-red-600 dark:text-red-200 rounded-md">
                            {{ error }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { marked } from 'marked';
import axios from 'axios';

export default defineComponent({
    components: {
        AppLayout,
    },
    data() {
        return {
            prompt: '',
            chatHistory: [],
            isLoading: false,
            error: null,
            showCopyNotification: false,
            typingInterval: null,
            typingSpeed: 20, // milliseconds between characters
            currentTypingIndex: -1,
            showSettings: false,
            instructionTypes: ['default', 'coding', 'academic', 'creative', 'business'],
            modelSettings: {
                temperature: 0.7,
                topK: 20,
                topP: 0.9,
                maxOutputTokens: 1024,
                instructionType: 'default',
                customInstruction: ''
            },
            defaultModelSettings: {
                temperature: 0.7,
                topK: 20,
                topP: 0.9,
                maxOutputTokens: 1024,
                instructionType: 'default',
                customInstruction: ''
            }
        };
    },
    methods: {
        async generateContent() {
            if (!this.prompt.trim()) return;
            
            // Add user message to chat history
            this.chatHistory.push({
                text: this.prompt,
                isUser: true
            });
            
            const userPrompt = this.prompt;
            this.prompt = ''; // Clear input field
            this.isLoading = true;
            this.error = null;
            
            try {
                // Use axios for the AJAX request with CSRF token and model settings
                const response = await axios.post(route('gemini.generate'), {
                    prompt: userPrompt,
                    settings: this.modelSettings,
                    instructionType: this.modelSettings.instructionType
                }, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                const result = response.data;
                
                // Format and add AI response to chat history
                if (result.candidates && result.candidates.length > 0) {
                    const content = result.candidates[0].content;
                    if (content && content.parts && content.parts.length > 0) {
                        const text = content.parts[0].text;
                        const messageObj = {
                            text: text,
                            html: marked(text),
                            isUser: false,
                            isTyping: true,
                            displayedText: ''
                        };
                        
                        this.chatHistory.push(messageObj);
                        this.scrollToBottom();
                        
                        // Start typewriter effect
                        this.currentTypingIndex = this.chatHistory.length - 1;
                        this.typeWriterEffect(text, messageObj);
                    }
                } else if (result.error) {
                    this.error = result.message || 'An error occurred while processing your request.';
                }
                
            } catch (error) {
                console.error('Error generating content:', error);
                this.error = error.response?.data?.message || 'An error occurred while processing your request.';
            } finally {
                this.isLoading = false;
            }
        },
        
        resetModelSettings() {
            this.modelSettings = { ...this.defaultModelSettings };
        },
        
        typeWriterEffect(fullText, messageObj) {
            // Clear any existing interval
            if (this.typingInterval) {
                clearInterval(this.typingInterval);
            }
            
            let i = 0;
            messageObj.displayedText = '';
            
            // Create a new interval for typing effect
            this.typingInterval = setInterval(() => {
                if (i < fullText.length) {
                    messageObj.displayedText += fullText.charAt(i);
                    i++;
                    this.scrollToBottom();
                } else {
                    // Typing complete
                    clearInterval(this.typingInterval);
                    this.typingInterval = null;
                    messageObj.isTyping = false;
                    this.currentTypingIndex = -1;
                }
            }, this.typingSpeed);
        },
        
        scrollToBottom() {
            this.$nextTick(() => {
                if (this.$refs.chatContainer) {
                    this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
                }
            });
        },
        
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showCopyNotification = true;
                setTimeout(() => {
                    this.showCopyNotification = false;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
    },
    mounted() {
        // Add event listener for window resize to adjust chat container height
        window.addEventListener('resize', this.scrollToBottom);
        
        // Initial scroll to bottom if there are messages
        this.scrollToBottom();
        
        // Fetch available instruction types
        this.fetchInstructionTypes();
    },
    beforeUnmount() {
        // Remove event listener
        window.removeEventListener('resize', this.scrollToBottom);
        
        // Clear typing interval if it exists
        if (this.typingInterval) {
            clearInterval(this.typingInterval);
        }
    },
    async fetchInstructionTypes() {
        try {
            const response = await axios.get(route('gemini.instructions'));
            if (response.data && response.data.types) {
                this.instructionTypes = response.data.types;
            }
        } catch (error) {
            console.error('Error fetching instruction types:', error);
            // Fallback to default instruction types if API call fails
        }
    }
});


</script>



<style>
/* Add some basic styling for the markdown content */
.prose pre {
    background-color: #f3f4f6;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    margin: 1rem 0;
    font-size: 0.875rem;
}

.prose code {
    background-color: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.prose ul, .prose ol {
    padding-left: 2rem;
    margin: 1rem 0;
}

.prose ul {
    list-style-type: disc;
}

.prose ol {
    list-style-type: decimal;
}

.prose p {
    margin-bottom: 0.75rem;
}

.prose h1, .prose h2, .prose h3, .prose h4 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.prose a {
    color: #4f46e5;
    text-decoration: underline;
}

/* Enhanced chat interface styles */
.chat-message-enter-active,
.chat-message-leave-active {
    transition: all 0.3s ease;
}

.chat-message-enter-from,
.chat-message-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
}

.bg-indigo-100 {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
}

.bg-gray-100 {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
}

textarea {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    resize: vertical;
    min-height: 80px;
}

textarea:focus {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

button {
    transition: all 0.2s ease;
}

button:hover:not(:disabled) {
    transform: translateY(-1px);
}

button:active:not(:disabled) {
    transform: translateY(1px);
}

@media (max-width: 640px) {
    .prose pre {
        padding: 0.75rem;
        font-size: 0.75rem;
    }
    
    .prose code {
        font-size: 0.75rem;
        padding: 0.125rem 0.25rem;
    }
}

/* Add typing indicator animation */
.typing-indicator {
    display: inline-flex;
    align-items: center;
}

.typing-indicator .dot {
    display: inline-block;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    margin-right: 3px;
    background: currentColor;
    animation: typing 1.4s infinite ease-in-out both;
}

.typing-indicator .dot:nth-child(1) {
    animation-delay: 0s;
}

.typing-indicator .dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator .dot:nth-child(3) {
    animation-delay: 0.4s;
    margin-right: 0;
}

@keyframes typing {
    0%, 100% {
        transform: scale(0.7);
        opacity: 0.5;
    }
    50% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Add a blinking cursor effect for the typing text */
.typing-cursor {
    display: inline-block;
    width: 2px;
    height: 1em;
    background-color: currentColor;
    margin-left: 2px;
    animation: blink 1s infinite;
    vertical-align: middle;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}

/* Enhance message appearance */
.bg-indigo-100, .bg-gray-100 {
    border-left: 3px solid transparent;
}

.bg-indigo-100 {
    border-left-color: #6366f1;
}

.bg-gray-100 {
    border-left-color: #10b981;
}

.dark .bg-indigo-900 {
    border-left-color: #818cf8;
}

.dark .bg-gray-700 {
    border-left-color: #34d399;
}

/* Improve textarea appearance */
textarea {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    resize: vertical;
    min-height: 80px;
    font-size: 0.95rem;
    line-height: 1.5;
}

textarea::placeholder {
    color: #9ca3af;
}

.dark textarea::placeholder {
    color: #6b7280;
}

/* Add subtle hover effect to messages */
.bg-indigo-100:hover, .bg-gray-100:hover {
    transform: translateY(-1px);
}
</style>