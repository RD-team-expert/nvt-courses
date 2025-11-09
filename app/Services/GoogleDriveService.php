<?php
// app/Services/GoogleDriveService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $driveKeyManager;
    protected $baseUrl;

    /**
     * Inject the DriveKeyManager into this service
     */
    public function __construct(DriveKeyManager $driveKeyManager)
    {
        // 🚦 Connect to the "traffic cop"
        $this->driveKeyManager = $driveKeyManager;
        $this->baseUrl = config('app.google_drive.api_base_url');

        Log::info('✅ GoogleDriveService initialized', [
            'base_url' => $this->baseUrl,
            'timestamp' => now(),
        ]);
    }

    /**
     * Extract file ID from Google Drive URL
     */
    public function extractFileId(string $url): ?string
    {
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9-_]+)/',  // /file/d/FILE_ID/view or /preview
            '/[?&]id=([a-zA-Z0-9-_]+)/',     // ?id=FILE_ID
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Generate streaming URL for a file using a specific API key
     */
    public function generateStreamingUrl(string $fileId, string $apiKey): string
    {
        return "{$this->baseUrl}/files/{$fileId}?alt=media&key={$apiKey}";
    }

    /**
     * Process Google Drive URL and return streaming URL with load-balanced API key
     * 
     * This is the MAIN function that:
     * 1. Extracts the file ID from Google Drive URL
     * 2. Asks DriveKeyManager for an available API key
     * 3. Returns the streaming URL with that key
     */
    public function processUrl(string $url): ?array
    {
        // Step 1: Extract file ID
        $fileId = $this->extractFileId($url);

        if (!$fileId) {
            Log::warning('❌ Could not extract file ID from URL', ['url' => $url]);
            return null;
        }

        // Step 2: Get an available API key from the manager
        $keyData = $this->driveKeyManager->getAvailableKey();

        if (!$keyData) {
            Log::error('❌ No API keys available - all keys are at capacity!', [
                'file_id' => $fileId,
                'url' => $url,
            ]);
            
            // Return null so controller can show error to user
            return null;
        }

        // Step 3: Generate streaming URL with the assigned API key
        $streamingUrl = $this->generateStreamingUrl($fileId, $keyData['api_key']);

        Log::info('✅ Generated Google Drive streaming URL', [
            'original_url' => $url,
            'file_id' => $fileId,
            'key_used' => $keyData['key_name'],
            'key_id' => $keyData['id'],
            'streaming_url' => $streamingUrl,
            'key_utilization' => "{$keyData['active_users']}/{$keyData['max_users']}",
        ]);

        // Return both the URL and the key info
        // We need the key_id to release it later!
        return [
            'streaming_url' => $streamingUrl,
            'key_id' => $keyData['id'],
            'key_name' => $keyData['key_name'],
            'file_id' => $fileId,
        ];
    }

    /**
     * Release the API key when user stops watching
     * Call this when:
     * - User closes the video
     * - User navigates away
     * - Session ends
     */
    public function releaseKey(int $keyId): void
    {
        $this->driveKeyManager->releaseKey($keyId);
        
        Log::info('🔓 Released Google Drive API key', [
            'key_id' => $keyId,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get status of all API keys (for monitoring)
     */
    public function getKeyStatus(): array
    {
        return $this->driveKeyManager->getKeyStatus();
    }
}
