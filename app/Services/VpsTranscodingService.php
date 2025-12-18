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
            return false;
        }

        try {
            // Generate direct public URL to the video file
            $videoUrl = Storage::disk('public')->url($video->file_path);
            
            // Ensure it's an absolute URL
            if (!str_starts_with($videoUrl, 'http')) {
                $videoUrl = url($videoUrl);
            }
            
            $requestData = [
                'video_id' => (string) $video->id,
                'video_url' => $videoUrl,
                'callback_url' => route('transcode.callback'),
                'qualities' => ['720p', '480p', '360p'],
            ];
            
            $this->vpsClient->sendTranscodeRequest($requestData);
            $video->update(['transcode_status' => 'processing']);
            
            Log::info("Transcoding requested for video {$video->id}");
            return true;

        } catch (\Exception $e) {
            $video->update(['transcode_status' => 'failed']);
            Log::error("Transcoding request failed for video {$video->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Handle transcoding callback from VPS
     * Downloads quality variants and updates video status
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
        $successCount = 0;
        $totalQualities = count($data['download_urls'] ?? []);
        
        foreach ($data['download_urls'] as $quality => $url) {
            try {
                $this->downloadAndStoreQuality($video, $quality, $url);
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to download {$quality} for video {$video->id}: {$e->getMessage()}");
            }
        }

        // Mark as completed if at least one quality was downloaded
        if ($successCount > 0) {
            $video->update(['transcode_status' => 'completed']);
            Log::info("Transcoding completed for video {$video->id} ({$successCount}/{$totalQualities} qualities)");
            return true;
        }
        
        $video->update(['transcode_status' => 'failed']);
        return false;
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
