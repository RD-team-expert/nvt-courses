<?php

namespace App\Services;


class GoogleDriveService
{
    protected $driveKeyManager;
    protected $baseUrl;

    public function __construct(DriveKeyManager $driveKeyManager)
    {
        $this->driveKeyManager = $driveKeyManager;
        $this->baseUrl = config('app.google_drive.api_base_url');


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
            return null;
        }

        // ✅ Step 2: Get available key WITHOUT incrementing (if shouldIncrement = false)
        $keyData = $this->driveKeyManager->getAvailableKey($shouldIncrement);

        if (!$keyData) {

            return null;
        }

        // Step 3: Generate streaming URL
        $streamingUrl = $this->generateStreamingUrl($fileId, $keyData['api_key']);


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


    }

    public function getKeyStatus(): array
    {
        return $this->driveKeyManager->getKeyStatus();
    }
}
