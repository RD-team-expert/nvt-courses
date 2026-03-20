<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VpsSubtitleService
{
    protected string $whisperUrl;

    public function __construct()
    {
        $this->whisperUrl = config('services.whisper.url');
    }

    /**
     * Send subtitle request to VPS Whisper
     * Called from VideoController::store() after video is created
     * Only works for local videos (they have a public URL we can send)
     */
    public function requestSubtitle(Video $video): bool
    {
        if (!$video->isLocal()) {
            Log::info("Subtitle skipped for video {$video->id} — not local storage");
            return false;
        }

        try {
            // Build the public URL to the video file on Hostinger
            $videoUrl = Storage::disk('public')->url($video->file_path);

            // Make sure it's absolute
            if (!str_starts_with($videoUrl, 'http')) {
                $videoUrl = url($videoUrl);
            }

            // The webhook URL your VPS will POST the result back to
            // $callbackUrl = route('subtitle.callback');
            $callbackUrl = config('services.whisper.callback_url');

            Log::info("Requesting subtitle for video {$video->id}", [
                'video_url'    => $videoUrl,
                'callback_url' => $callbackUrl,
            ]);

            // Send request to VPS Whisper /asr endpoint
           $response = Http::timeout(30)
    ->post("{$this->whisperUrl}/asr?" . http_build_query([
        'video_url'    => $videoUrl,
        'callback_url' => $callbackUrl,
        'video_id'     => (string) $video->id,
        'task'         => 'transcribe',
        'language'     => 'en',
        'output'       => 'vtt',
        'encode'       => 'true',
        'vad_filter'   => 'false',
    ]));
            if ($response->successful()) {
                $video->update(['subtitle_status' => 'processing']);
                Log::info("Subtitle job accepted for video {$video->id}");
                return true;
            }

            Log::error("Whisper rejected subtitle request for video {$video->id}: " . $response->body());
            $video->update(['subtitle_status' => 'failed']);
            return false;

        } catch (\Exception $e) {
            Log::error("Subtitle request failed for video {$video->id}: {$e->getMessage()}");
            $video->update(['subtitle_status' => 'failed']);
            return false;
        }
    }

    /**
     * Handle the callback from VPS Whisper
     * Saves the VTT file to disk and updates the video record
     */
    public function handleCallback(array $data): bool
    {
        $video = Video::find($data['video_id']);

        if (!$video) {
            Log::error("Subtitle callback: video not found for id: {$data['video_id']}");
            return false;
        }

        // Handle failed job from VPS
        if ($data['status'] === 'failed') {
            $video->update(['subtitle_status' => 'failed']);
            Log::error("Subtitle job failed on VPS for video {$video->id}: " . ($data['error'] ?? 'Unknown error'));
            return false;
        }

        // Make sure we actually received VTT content
        if (empty($data['vtt_content'])) {
            $video->update(['subtitle_status' => 'failed']);
            Log::error("Subtitle callback: empty vtt_content for video {$video->id}");
            return false;
        }

        try {
            // Save the VTT file to disk
            // e.g. storage/app/public/subtitles/1_ar.vtt
            $directory = 'subtitles';
            $fileName  = "{$video->id}_ar.vtt";
            $filePath  = "{$directory}/{$fileName}";

            Storage::disk('public')->makeDirectory($directory);
            Storage::disk('public')->put($filePath, $data['vtt_content']);

            // Update the video record
            $video->update([
                'subtitle_status'   => 'completed',
                'subtitle_vtt_path' => $filePath,
            ]);

            Log::info("Subtitle saved for video {$video->id} at {$filePath}");
            return true;

        } catch (\Exception $e) {
            $video->update(['subtitle_status' => 'failed']);
            Log::error("Subtitle save failed for video {$video->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Retry a failed subtitle job
     * Called from VideoController if admin clicks "retry"
     */
    public function retrySubtitle(Video $video): bool
    {
        // Delete old VTT file if it exists
        if ($video->subtitle_vtt_path && Storage::disk('public')->exists($video->subtitle_vtt_path)) {
            Storage::disk('public')->delete($video->subtitle_vtt_path);
        }

        $video->update([
            'subtitle_status'   => null,
            'subtitle_vtt_path' => null,
        ]);

        return $this->requestSubtitle($video);
    }
}