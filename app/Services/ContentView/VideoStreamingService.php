<?php

namespace App\Services\ContentView;

use App\Services\GoogleDriveService;
use App\Models\Video;
use App\Models\ModuleContent;
use Illuminate\Support\Facades\Log;

class VideoStreamingService
{
    protected GoogleDriveService $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Get streaming URL for video with load-balanced API key
     * This combines video processing + API key assignment
     * 
     * @param Video $video The video model
     * @param ModuleContent $content The content model (for session tracking)
     * @return array|null ['streaming_url' => string, 'key_id' => int, 'key_name' => string] or null
     */
    public function getStreamingUrl(Video $video, ModuleContent $content): ?array
{
    if (!$video || !$video->google_drive_url) {
        Log::warning('❌ Video has no Google Drive URL', [
            'video_id' => $video?->id,
            'content_id' => $content->id,
        ]);
        return null;
    }

    // ✅ CHANGED: Get URL WITHOUT incrementing active_users
    // We'll pass increment: false to tell GoogleDriveService to NOT increment
    $result = $this->googleDriveService->processUrl(
        $video->google_drive_url,
        $increment = false  // ✅ NEW: Don't increment on page load
    );

    if (!$result) {
        Log::error('❌ Failed to process video URL', [
            'video_id' => $video->id,
            'content_id' => $content->id,
        ]);
        return null;
    }

    // ✅ Store key_id for later use (when session starts)
    $this->storeKeyInSession($content->id, $result);

    Log::info('✅ Video URL ready (active_users NOT incremented yet)', [
        'video_id' => $video->id,
        'content_id' => $content->id,
        'key_id' => $result['key_id'],
    ]);

    return $result;
}

    /**
     * Process Google Drive PDF URL for embedding
     * 
     * @param string $url The Google Drive PDF URL
     * @return string|null The processed embed URL or null
     */
    public function processGoogleDrivePdfUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Extract file ID from various Google Drive URL formats
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9-_]+)/',
            '/[?&]id=([a-zA-Z0-9-_]+)/',
            '/\/open\?id=([a-zA-Z0-9-_]+)/',
            '/\/view\?id=([a-zA-Z0-9-_]+)/',
        ];

        $fileId = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $fileId = $matches[1];
                break;
            }
        }

        if (!$fileId) {
            Log::warning('Could not extract file ID from Google Drive PDF URL', [
                'url' => $url
            ]);
            return $url; // Return original URL as fallback
        }

        // Return embed URL for better compatibility
        $embedUrl = "https://drive.google.com/file/d/{$fileId}/preview";

        Log::info('Google Drive PDF URL processed', [
            'original' => $url,
            'file_id' => $fileId,
            'embed_url' => $embedUrl,
        ]);

        return $embedUrl;
    }

    /**
     * Store API key information in session
     * This allows us to release the key later when user stops watching
     * 
     * @param int $contentId The content ID
     * @param array $keyData The key data from GoogleDriveService
     */
   protected function storeKeyInSession(int $contentId, array $keyData): void
{
    session([
        'content_' . $contentId . '_key_id' => $keyData['key_id'], // ✅ Changed
        'content_' . $contentId . '_key_name' => $keyData['key_name'], // ✅ Changed
    ]);

    Log::debug('🔑 API key stored in session', [
        'content_id' => $contentId,
        'key_id' => $keyData['key_id'],
    ]);
}
    /**
     * Get the assigned API key ID from session
     * 
     * @param int $contentId The content ID
     * @return int|null The key ID or null
     */
    public function getAssignedKeyId(int $contentId): ?int
{
    return session('content_' . $contentId . '_key_id'); // ✅ Changed
}

    /**
     * Release the API key when user stops watching
     * Call this when:
     * - User closes the video
     * - User navigates away
     * - Session ends
     * 
     * @param int $contentId The content ID
     */
    public function releaseApiKey(int $contentId): void
{
    $keyId = session('content_' . $contentId . '_key_id'); // ✅ Changed
    $keyName = session('content_' . $contentId . '_key_name'); // ✅ Changed

    if ($keyId) {
        $this->googleDriveService->releaseKey($keyId);

        session()->forget([
            'content_' . $contentId . '_key_id', // ✅ Changed
            'content_' . $contentId . '_key_name', // ✅ Changed
        ]);

        Log::info('🔓 API key released successfully', [
            'content_id' => $contentId,
            'key_id' => $keyId,
        ]);
    }
}

    /**
     * Check if content has an assigned API key
     * 
     * @param int $contentId The content ID
     * @return bool
     */
    public function hasAssignedKey(int $contentId): bool
{
    return session()->has('content_' . $contentId . '_key_id'); // ✅ Changed
}


    /**
     * Get current key status for debugging
     * 
     * @param int $contentId The content ID
     * @return array
     */
    public function getKeyStatus(int $contentId): array
{
    return [
        'has_key' => $this->hasAssignedKey($contentId),
        'key_id' => $this->getAssignedKeyId($contentId),
        'key_name' => session('content_' . $contentId . '_key_name'), // ✅ Changed
    ];
}
}
