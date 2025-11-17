<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    /**
     * Stream a local video file to the user
     * Supports byte-range requests (for video seeking)
     *
     * @param Request $request
     * @param Video $video
     * @return StreamedResponse
     */
    public function stream(Request $request, Video $video)
    {
        // 1. Security: Verify the request signature (signed URL)
        if (!$request->hasValidSignature()) {
            Log::warning('❌ Invalid signature for video stream', [
                'video_id' => $video->id,
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            abort(403, 'Invalid or expired video link');
        }

        // 2. Verify user is authenticated
        if (!auth()->check()) {
            Log::warning('❌ Unauthenticated user tried to access video', [
                'video_id' => $video->id,
                'ip' => $request->ip(),
            ]);
            abort(401, 'Authentication required');
        }

        // 3. Verify video is stored locally
        if ($video->storage_type !== 'local') {
            Log::error('❌ Attempted to stream non-local video', [
                'video_id' => $video->id,
                'storage_type' => $video->storage_type,
                'user_id' => auth()->id(),
            ]);
            abort(400, 'This video cannot be streamed from local storage');
        }

        // 4. Verify file exists
        if (!$video->file_path || !Storage::disk('videos')->exists($video->file_path)) {
            Log::error('❌ Video file not found', [
                'video_id' => $video->id,
                'file_path' => $video->file_path,
                'user_id' => auth()->id(),
            ]);
            abort(404, 'Video file not found');
        }

        // 5. Get file information
        $path = Storage::disk('videos')->path($video->file_path);
        $fileSize = filesize($path);
        $mimeType = $video->mime_type ?? 'video/mp4';

        // 6. Handle byte-range requests (for video seeking)
        $stream = fopen($path, 'rb');
        $start = 0;
        $end = $fileSize - 1;
        $length = $fileSize;
        $status = 200;

        // Check if client requested a specific byte range
        if ($request->header('Range')) {
            $range = $request->header('Range');

            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];
                $end = $matches[2] !== '' ? (int) $matches[2] : $end;

                // Validate range
                if ($start > $end || $start > $fileSize - 1 || $end > $fileSize - 1) {
                    Log::warning('⚠️ Invalid byte range requested', [
                        'video_id' => $video->id,
                        'range' => $range,
                        'file_size' => $fileSize,
                        'user_id' => auth()->id(),
                    ]);
                    fclose($stream);
                    return response('Requested Range Not Satisfiable', 416)
                        ->header('Content-Range', "bytes */{$fileSize}");
                }

                $length = $end - $start + 1;
                $status = 206; // Partial Content

                fseek($stream, $start);

                Log::info('📹 Streaming video with byte range', [
                    'video_id' => $video->id,
                    'video_name' => $video->name,
                    'range' => "{$start}-{$end}/{$fileSize}",
                    'user_id' => auth()->id(),
                ]);
            }
        } else {
            Log::info('📹 Streaming complete video', [
                'video_id' => $video->id,
                'video_name' => $video->name,
                'size' => $fileSize,
                'user_id' => auth()->id(),
            ]);
        }

        // 7. Create streamed response
        $response = new StreamedResponse(function () use ($stream, $start, $length) {
            $bufferSize = 1024 * 8; // 8KB buffer
            $bytesRead = 0;

            while (!feof($stream) && $bytesRead < $length) {
                $readSize = min($bufferSize, $length - $bytesRead);
                echo fread($stream, $readSize);
                flush();
                $bytesRead += $readSize;
            }

            fclose($stream);
        }, $status);

        // 8. Set response headers
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', $length);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        if ($status === 206) {
            $response->headers->set('Content-Range', "bytes {$start}-{$end}/{$fileSize}");
        }

        return $response;
    }
}
