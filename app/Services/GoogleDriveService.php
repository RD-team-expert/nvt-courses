<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $driveKeyManager;
    protected $baseUrl;

    public function __construct(DriveKeyManager $driveKeyManager)
    {
        $this->driveKeyManager = $driveKeyManager;
        $this->baseUrl = config('app.google_drive.api_base_url');

        Log::info('✅ GoogleDriveService initialized', [
            'base_url' => $this->baseUrl,
            'timestamp' => now(),
        ]);
    }

    public function extractFileId(string $url): ?string
    {
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9-_]+)/',
            '/[?&]id=([a-zA-Z0-9-_]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function generateStreamingUrl(string $fileId, string $apiKey): string
    {
        return "{$this->baseUrl}/files/{$fileId}?alt=media&key={$apiKey}";
    }

    /**
     * ✅ CHANGED: Added $shouldIncrement parameter
     */
    public function processUrl(string $url, bool $shouldIncrement = false): ?array
    {
        // Step 1: Extract file ID
        $fileId = $this->extractFileId($url);

        if (!$fileId) {
            Log::warning('❌ Could not extract file ID from URL', ['url' => $url]);
            return null;
        }

        // ✅ Step 2: Get available key WITHOUT incrementing (if shouldIncrement = false)
        $keyData = $this->driveKeyManager->getAvailableKey($shouldIncrement);

        if (!$keyData) {
            Log::error('❌ No API keys available - all keys are at capacity!', [
                'file_id' => $fileId,
                'url' => $url,
            ]);
            return null;
        }

        // Step 3: Generate streaming URL
        $streamingUrl = $this->generateStreamingUrl($fileId, $keyData['api_key']);

        Log::info('✅ Generated Google Drive streaming URL', [
            'file_id' => $fileId,
            'key_used' => $keyData['key_name'],
            'key_id' => $keyData['id'],
            'incremented' => $shouldIncrement ? 'YES' : 'NO', // ✅ NEW LOG
            'key_utilization' => "{$keyData['active_users']}/{$keyData['max_users']}",
        ]);

        return [
            'streaming_url' => $streamingUrl,
            'key_id' => $keyData['id'],
            'key_name' => $keyData['key_name'],
            'file_id' => $fileId,
        ];
    }

    public function releaseKey(int $keyId): void
    {
        $this->driveKeyManager->releaseKey($keyId);
        
        Log::info('🔓 Released Google Drive API key', [
            'key_id' => $keyId,
            'timestamp' => now(),
        ]);
    }

    public function getKeyStatus(): array
    {
        return $this->driveKeyManager->getKeyStatus();
    }
}
