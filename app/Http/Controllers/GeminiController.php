<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use Inertia\Inertia;

class GeminiController extends Controller
{
    // Add this index method to handle the gemini.index route
    public function index()
    {
        return Inertia::render('Gemini/Index');
    }

    // Update your existing generate method to use the selected instruction
    public function generate(Request $request)
    {
        $prompt = $request->input('prompt');
        $settings = $request->input('settings', []);
        $instructionType = $request->input('instructionType', 'default');
        $customInstruction = $settings['customInstruction'] ?? '';
        $includeUserData = $request->input('includeUserData', false);
        
        // Get the instruction content based on type or use custom instruction
        $systemInstruction = '';
        if (!empty($customInstruction)) {
            $systemInstruction = $customInstruction;
        } else {
            // Fetch the instruction from the database
            $instruction = \App\Models\Instruction::where('type', $instructionType)
                ->where('is_active', true)
                ->first();
            
            if ($instruction) {
                $systemInstruction = $instruction->content;
            } else {
                // Fallback to default instruction
                $defaultInstruction = \App\Models\Instruction::where('is_default', true)
                    ->where('is_active', true)
                    ->first();
                
                $systemInstruction = $defaultInstruction ? $defaultInstruction->content : "You are Gemini, a helpful AI assistant.";
            }
        }
        
        // Add user data to the prompt if requested
        if ($includeUserData && auth()->check()) {
            $user = auth()->user();
            $userDataPrompt = "You are talking to " . $user->name . " (email: " . $user->email . ").";
            
            // Add any other relevant user data you want to include
            if (isset($user->role)) {
                $userDataPrompt .= " Their role is: " . $user->role . ".";
            }
            
            // Prepend user data to the system instruction
            $systemInstruction = $userDataPrompt . "\n\n" . $systemInstruction;
        }
        
        // Continue with your existing code to call the Gemini API
        $geminiService = app(GeminiService::class);
        
        $response = $geminiService->generateContent(
            $request->input('prompt'),
            $settings,
            $instructionType
        );
        
        return response()->json($response);
    }

    public function getInstructionTypes()
    {
        $geminiService = app(GeminiService::class);
        return response()->json([
            'types' => $geminiService->getInstructionTypes()
        ]);
    }

    // Add this method to your GeminiController
    public function getInstructions()
    {
        $instructions = \App\Models\Instruction::where('is_active', true)
            ->select('id', 'name', 'type', 'content', 'is_default')
            ->get();
        
        // If no instructions exist, create default ones
        if ($instructions->isEmpty()) {
            $defaultTypes = ['default', 'coding', 'academic', 'creative', 'business'];
            
            foreach ($defaultTypes as $index => $type) {
                $isDefault = ($type === 'default');
                $content = $this->getDefaultInstructionContent($type);
                
                \App\Models\Instruction::create([
                    'name' => ucfirst($type) . ' Mode',
                    'type' => $type,
                    'content' => $content,
                    'is_default' => $isDefault,
                    'is_active' => true
                ]);
            }
            
            // Fetch the newly created instructions
            $instructions = \App\Models\Instruction::where('is_active', true)
                ->select('id', 'name', 'type', 'content', 'is_default')
                ->get();
        }
        
        return response()->json([
            'instructions' => $instructions
        ]);
    }

    private function getDefaultInstructionContent($type)
    {
        switch ($type) {
            case 'default':
                return "You are Gemini, a helpful AI assistant. Provide accurate, helpful, and concise responses.";
            
            case 'coding':
                return "You are a coding expert. Provide clean, efficient, and well-documented code examples. Explain technical concepts clearly and offer best practices.";
            
            case 'academic':
                return "You are an academic assistant. Provide well-researched, factual information with proper citations when possible. Maintain a formal tone and structured responses.";
            
            case 'creative':
                return "You are a creative writing assistant. Help with generating creative content, stories, poems, and imaginative ideas. Be expressive and think outside the box.";
            
            case 'business':
                return "You are a business consultant. Provide professional advice on business strategies, marketing, management, and professional communication. Focus on actionable insights.";
            
            default:
                return "You are Gemini, a helpful AI assistant. Provide accurate, helpful, and concise responses.";
        }
    }
}