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

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'settings' => 'nullable|array',
            'instructionType' => 'nullable|string'
        ]);

        $geminiService = app(GeminiService::class);
        $settings = $request->input('settings', []);
        $instructionType = $request->input('instructionType', 'default');
        
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
}