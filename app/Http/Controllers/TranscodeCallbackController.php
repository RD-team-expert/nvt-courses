<?php

namespace App\Http\Controllers;

use App\Services\VpsTranscodingService;
use App\Services\VpsApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranscodeCallbackController extends Controller
{
    public function __construct(
        protected VpsTranscodingService $transcodingService,
        protected VpsApiClient $vpsClient
    ) {}

    /**
     * Handle webhook callback from VPS transcoding service
     * Route: POST /api/transcode/callback
     */
    public function handle(Request $request)
    {
        Log::info('=== TRANSCODE CALLBACK RECEIVED ===', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Verify project key matches our configured key
        $receivedKey = $request->input('project_key');
        $expectedKey = $this->vpsClient->getProjectKey();
        
        Log::info('Project key verification:', [
            'received' => $receivedKey,
            'expected' => $expectedKey,
            'match' => $receivedKey === $expectedKey,
        ]);
        
        if ($receivedKey !== $expectedKey) {
            Log::warning('Invalid project key in transcode callback');
            return response()->json(['error' => 'Invalid project key'], 403);
        }

        Log::info('Calling handleCallback...');
        
        try {
            $success = $this->transcodingService->handleCallback($request->all());
            
            Log::info('Callback handled:', ['success' => $success]);
            
            return response()->json(['success' => $success]);
            
        } catch (\Exception $e) {
            Log::error('Callback handling failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
