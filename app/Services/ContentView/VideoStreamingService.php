<?php

namespace App\Services\ContentView;

use App\Services\GoogleDriveService;
use App\Models\Video;
use App\Models\ModuleContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VideoStreamingService
{
    protected GoogleDriveService $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Get streaming URL for video with load-balanced API key
     * NOW SUPPORTS BOTH: Google Drive + Local Storage
     *
     * @param Video $video The video model
     * @param ModuleContent $content The content model (for session tracking)
     * @return array|null ['streaming_url' => string, 'key_id' => int|null, 'key_name' => string] or null
     */
    public function getStreamingUrl(Video $video, ModuleContent $content): ?array
    {
        if (!$video) {

            return null;
        }

        // ============================================
        // ✅ NEW: Route based on storage type
        // ============================================

        // Check if video is stored locally
        if ($video->storage_type === 'local') {
            return $this->getLocalStreamingUrl($video, $content);
        }

        // Default to Google Drive (for backward compatibility)
        // This handles both: storage_type = 'google_drive' AND old videos without storage_type
        return $this->getGoogleDriveStreamingUrl($video, $content);
    }

    /**
     * ✅ EXISTING METHOD: Google Drive streaming (unchanged logic)
     * Handles Google Drive videos with API key rotation
     *
     * @param Video $video
     * @param ModuleContent $content
     * @return array|null
     */
    protected function getGoogleDriveStreamingUrl(Video $video, ModuleContent $content): ?array
    {
        if (!$video->google_drive_url) {

            return null;
        }

        // ✅ EXISTING LOGIC: Get URL WITHOUT incrementing active_users
        // We'll pass increment: false to tell GoogleDriveService to NOT increment
        $result = $this->googleDriveService->processUrl(
            $video->google_drive_url,
            $increment = false  // Don't increment on page load
        );

        if (!$result) {

            return null;
        }

        // ✅ EXISTING LOGIC: Store key_id for later use (when session starts)
        $this->storeKeyInSession($content->id, $result);



        return $result;
    }

    /**
     * ✅ NEW METHOD: Local storage streaming
     * Handles videos stored on local server
     *
     * @param Video $video
     * @param ModuleContent $content
     * @return array
     */
    protected function getLocalStreamingUrl(Video $video, ModuleContent $content): array
{
    if (!$video->file_path) {
        Log::error('Video file path is empty for local video', [
            'video_id' => $video->id,
            'content_id' => $content->id,
        ]);
        
        return [
            'streaming_url' => null,
            'key_id' => null,
            'key_name' => 'local_storage_error',
        ];
    }

    // ✅ FIX: Generate simple route URL (not temporarySignedRoute)
    $streamingUrl = route('video.stream', ['video' => $video->id]);

    Log::info('Generated local streaming URL', [
        'video_id' => $video->id,
        'content_id' => $content->id,
        'url' => $streamingUrl,
    ]);

    $localKeyData = [
        'streaming_url' => $streamingUrl,
        'key_id' => null,
        'key_name' => 'local_storage',
    ];

    $this->storeKeyInSession($content->id, $localKeyData);

    return $localKeyData;
}


    /**
     * Process Google Drive PDF URL for embedding
     * ✅ EXISTING METHOD: Unchanged
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

            return $url; // Return original URL as fallback
        }

        // Return embed URL for better compatibility
        $embedUrl = "https://drive.google.com/file/d/{$fileId}/preview";



        return $embedUrl;
    }

    /**
     * Store API key information in session
     * ✅ EXISTING METHOD: Unchanged (works for both Google Drive and local)
     *
     * @param int $contentId The content ID
     * @param array $keyData The key data from GoogleDriveService or local generator
     */
    protected function storeKeyInSession(int $contentId, array $keyData): void
    {
        session([
            'content_' . $contentId . '_key_id' => $keyData['key_id'] ?? null,
            'content_' . $contentId . '_key_name' => $keyData['key_name'] ?? null,
            'content_' . $contentId . '_streaming_url' => $keyData['streaming_url'] ?? null, // ✅ NEW: Store streaming URL
        ]);

    }

    /**
     * Get the assigned API key ID from session
     * ✅ EXISTING METHOD: Unchanged
     *
     * @param int $contentId The content ID
     * @return int|null The key ID or null
     */
    public function getAssignedKeyId(int $contentId): ?int
    {
        return session('content_' . $contentId . '_key_id');
    }

    /**
     * Release the API key when user stops watching
     * ✅ UPDATED: Now only releases Google Drive keys (local videos have no keys)
     *
     * Call this when:
     * - User closes the video
     * - User navigates away
     * - Session ends
     *
     * @param int $contentId The content ID
     */
    public function releaseApiKey(int $contentId): void
    {
        $keyId = session('content_' . $contentId . '_key_id');
        $keyName = session('content_' . $contentId . '_key_name');

        // ✅ SAFE: Only release if it's a Google Drive key (has key_id)
        if ($keyId) {
            $this->googleDriveService->releaseKey($keyId);


        } elseif ($keyName === 'local_storage') {
            // ✅ NEW: For local storage, just log (no key to release)

        }

        // ✅ Always clear session data
        session()->forget([
            'content_' . $contentId . '_key_id',
            'content_' . $contentId . '_key_name',
            'content_' . $contentId . '_streaming_url',
        ]);
    }

    /**
     * Check if content has an assigned API key or active session
     * ✅ UPDATED: Now works for both Google Drive (key) and local (session)
     *
     * @param int $contentId The content ID
     * @return bool
     */
    public function hasAssignedKey(int $contentId): bool
    {
        return session()->has('content_' . $contentId . '_key_id')
            || session()->has('content_' . $contentId . '_key_name');
    }

    /**
     * Get current key/session status for debugging
     * ✅ EXISTING METHOD: Enhanced with streaming URL
     *
     * @param int $contentId The content ID
     * @return array
     */
    public function getKeyStatus(int $contentId): array
    {
        return [
            'has_key' => $this->hasAssignedKey($contentId),
            'key_id' => $this->getAssignedKeyId($contentId),
            'key_name' => session('content_' . $contentId . '_key_name'),
            'streaming_url' => session('content_' . $contentId . '_streaming_url'),
        ];
    }

    /**
     * ✅ NEW METHOD: Check if video is using local storage
     * Helper method for other services/controllers
     *
     * @param int $contentId
     * @return bool
     */
    public function isLocalStorage(int $contentId): bool
    {
        $keyName = session('content_' . $contentId . '_key_name');
        return $keyName === 'local_storage';
    }

    /**
     * ✅ NEW METHOD: Get storage type from session
     *
     * @param int $contentId
     * @return string 'google_drive' | 'local' | 'unknown'
     */
    public function getStorageType(int $contentId): string
    {
        $keyName = session('content_' . $contentId . '_key_name');

        if ($keyName === 'local_storage') {
            return 'local';
        } elseif ($keyName && $keyName !== 'local_storage') {
            return 'google_drive';
        }

        return 'unknown';
    }
}
