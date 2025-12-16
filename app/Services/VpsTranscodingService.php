<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VpsTranscodingService
{
    public function __construct(
        protected VpsApiClient $vpsClient
    ) {}

    /**
     * Request transcoding for a video (called from VideoController::store)
     * Only works for local storage videos
     */
    public function requestTranscoding(Video $video): bool
    {
        // Only transcode local videos
        if (!$video->isLocal()) {
            Log::info("Skipping transcoding for non-local video {$video->id}");
            return false;
        }

        try {
            $response = $this->vpsClient->sendTranscodeRequest([
                'video_id' => (string) $video->id,
                'video_url' => route('video.stream', $video->id), // Uses existing VideoStreamController
                'callback_url' => route('transcode.callback'),
                'qualities' => ['720p', '480p', '360p'],
            ]);

            $video->update(['transcode_status' => 'processing']);
            
            Log::info("Transcoding requested for video {$video->id}");
            return true;

        } catch (\Exception $e) {
            $video->update(['transcode_status' => 'failed']);
            Log::error("Transcoding request failed for video {$video->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle transcoding callback from VPS
     */
    public function handleCallback(array $data): bool
    {
        $video = Video::find($data['video_id']);
        
        if (!$video) {
            Log::error("Video not found for callback: {$data['video_id']}");
            return false;
        }

        if ($data['status'] === 'failed') {
            $video->update(['transcode_status' => 'failed']);
            Log::error("Transcoding failed for video {$video->id}: " . ($data['error'] ?? 'Unknown'));
            return false;
        }

        // Download each quality variant
        foreach ($data['download_urls'] as $quality => $url) {
            $this->downloadAndStoreQuality($video, $quality, $url);
        }

        $video->update(['transcode_status' => 'completed']);
        Log::info("Transcoding completed for video {$video->id}");
        return true;
    }

    /**
     * Download and store a quality variant
     */
    protected function downloadAndStoreQuality(Video $video, string $quality, string $url): void
    {
        $directory = "videos/transcoded/{$video->id}";
        $fileName = "{$quality}.mp4";
        $relativePath = "{$directory}/{$fileName}";
        $savePath = storage_path("app/public/{$relativePath}");

        // Create directory
        Storage::disk('public')->makeDirectory($directory);

        // Download file from VPS
        $success = $this->vpsClient->downloadFile($url, $savePath);

        if ($success && file_exists($savePath)) {
            // Create or update quality record
            VideoQuality::updateOrCreate(
                ['video_id' => $video->id, 'quality' => $quality],
                [
                    'file_path' => $relativePath,
                    'file_size' => filesize($savePath),
                ]
            );
            Log::info("Downloaded {$quality} for video {$video->id}");
        } else {
            Log::error("Failed to download {$quality} for video {$video->id}");
        }
    }

    /**
     * Retry failed transcoding
     */
    public function retryTranscoding(Video $video): bool
    {
        // Delete existing quality records
        $video->qualities()->delete();
        
        $video->update(['transcode_status' => 'pending']);
        return $this->requestTranscoding($video);
    }
}
