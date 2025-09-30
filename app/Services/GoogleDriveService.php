<?php
// app/Services/GoogleDriveService.php - Simplified Version

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('app.google_drive.api_key');
        $this->baseUrl = config('app.google_drive.api_base_url');
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
     * Generate streaming URL for audio file
     */
    public function generateStreamingUrl(string $fileId): string
    {
        return "{$this->baseUrl}/files/{$fileId}?alt=media&key={$this->apiKey}";
    }

    /**
     * Process Google Drive URL and return streaming URL - Simplified
     */
    public function processUrl(string $url): ?string
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::error('Google Drive API key not configured');
            return null;
        }

        $fileId = $this->extractFileId($url);

        if (!$fileId) {
            Log::warning('Could not extract file ID from URL', ['url' => $url]);
            return null;
        }

        // Since your API test shows the file is accessible,
        // generate the streaming URL directly
        $streamingUrl = $this->generateStreamingUrl($fileId);

        Log::info('Generated Google Drive streaming URL', [
            'original_url' => $url,
            'file_id' => $fileId,
            'streaming_url' => $streamingUrl
        ]);

        return $streamingUrl;
    }
}
