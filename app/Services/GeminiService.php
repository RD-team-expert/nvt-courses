<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GeminiService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;
    protected $instructions = [
        'default' => 'You are a helpful, friendly AI assistant. Provide accurate and concise information.',
        'coding' => 'You are a coding expert. Focus on providing clean, efficient code examples and technical explanations. Include code snippets when appropriate.',
        'academic' => 'You are an academic assistant. Provide well-researched, detailed responses with citations when possible. Focus on educational content.',
        'creative' => 'You are a creative writing assistant. Help with generating creative content, stories, and ideas. Be imaginative and inspirational.',
        'business' => 'You are a business consultant. Provide professional advice on business strategies, marketing, and management topics.'
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.gemini.api_key');
        // Updated URL to use the correct API endpoint
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent';
    }

    public function generateContent($prompt, $settings = [], $instructionType = 'default')
    {
        try {
            // Get the instruction based on type or use custom instruction if provided
            $instruction = '';
            
            if (isset($settings['customInstruction']) && !empty($settings['customInstruction'])) {
                $instruction = $settings['customInstruction'];
            } else {
                // This will be overridden by the controller if user data is included
                $instruction = $this->instructions[$instructionType] ?? $this->instructions['default'];
            }
            
            // Add concise mode instruction if enabled
            if (isset($settings['conciseMode']) && $settings['conciseMode']) {
                $instruction .= "\n\nPlease provide brief, concise responses.";
            }
            
            // Add user information to the instruction if authenticated
            if (auth()->check()) {
                $user = auth()->user();
                $instruction .= "\n\nYou are talking to " . $user->name . ". Always address them by name in your responses.";
                
                // Fetch courses with proper column selection
                try {
                    // Get user's enrolled courses with only existing columns
                    $enrolledCourses = DB::table('course_user')
                        ->join('courses', 'course_user.course_id', '=', 'courses.id')
                        ->where('course_user.user_id', $user->id)
                        ->select('courses.*', 'course_user.created_at as enrolled_at', 'course_user.user_status')
                        ->get();
                    
                    if ($enrolledCourses && $enrolledCourses->count() > 0) {
                        $instruction .= "\n\nUser's enrolled courses information:";
                        foreach ($enrolledCourses as $course) {
                            $instruction .= "\n- " . $course->name; // Using name instead of title based on Course model
                            if (!empty($course->description)) {
                                $instruction .= ": " . $course->description;
                            }
                            // Include enrollment details if available
                            if (isset($course->enrolled_at)) {
                                $enrolledDate = new \DateTime($course->enrolled_at);
                                $instruction .= " (Enrolled on: " . $enrolledDate->format('Y-m-d') . ")";
                            }
                            if (!empty($course->user_status)) {
                                $instruction .= " (Status: " . $course->user_status . ")";
                            }
                            if (!empty($course->level)) {
                                $instruction .= " (Level: " . $course->level . ")";
                            }
                        }
                        $instruction .= "\n\nYou have " . $enrolledCourses->count() . " enrolled course(s) in total.";
                    } else {
                        $instruction .= "\n\nYou currently don't have any enrolled courses.";
                    }
                } catch (\Exception $e) {
                    Log::error('Error accessing course data: ' . $e->getMessage(), [
                        'exception' => $e,
                        'trace' => $e->getTraceAsString()
                    ]);
                    $instruction .= "\n\nI have access to your basic user information, but I cannot access your course data at this time.";
                }
            }
            
            // Prepend instruction to the prompt
            $fullPrompt = $instruction . "\n\n" . $prompt;
            
            // Build the request payload
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ]
            ];
            
            // Add generation settings if provided
            if (!empty($settings)) {
                $generationConfig = [];
                
                if (isset($settings['temperature'])) {
                    $generationConfig['temperature'] = (float) $settings['temperature'];
                }
                
                if (isset($settings['topK'])) {
                    $generationConfig['topK'] = (int) $settings['topK'];
                }
                
                if (isset($settings['topP'])) {
                    $generationConfig['topP'] = (float) $settings['topP'];
                }
                
                if (isset($settings['maxOutputTokens'])) {
                    $generationConfig['maxOutputTokens'] = (int) $settings['maxOutputTokens'];
                }
                
                if (!empty($generationConfig)) {
                    $payload['generationConfig'] = $generationConfig;
                }
            }
            
            // Log the payload for debugging
            Log::debug('Gemini API Request Payload', ['payload' => $payload]);
            
            $response = $this->client->post($this->baseUrl . '?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            // Log the response for debugging
            Log::debug('Gemini API Response', ['response' => $result]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getInstructionTypes()
    {
        return array_keys($this->instructions);
    }
}