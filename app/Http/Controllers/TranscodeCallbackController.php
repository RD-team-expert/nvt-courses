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
        Log::info('Transcode callback received', $request->all());

        // Verify project key matches our configured key
        if ($request->project_key !== $this->vpsClient->getProjectKey()) {
            Log::warning('Invalid project key in transcode callback', [
                'received' => $request->project_key,
                'expected' => $this->vpsClient->getProjectKey(),
            ]);
            return response()->json(['error' => 'Invalid project key'], 403);
        }

        $success = $this->transcodingService->handleCallback($request->all());

        return response()->json(['success' => $success]);
    }
}
