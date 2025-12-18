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
        // Verify project key matches our configured key
        $receivedKey = $request->input('project_key');
        $expectedKey = $this->vpsClient->getProjectKey();
        
        if ($receivedKey !== $expectedKey) {
            Log::warning('Invalid project key in transcode callback', [
                'received' => $receivedKey,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid project key'], 403);
        }
        
        try {
            $success = $this->transcodingService->handleCallback($request->all());
            return response()->json(['success' => $success]);
            
        } catch (\Exception $e) {
            Log::error("Transcode callback failed: {$e->getMessage()}");
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
