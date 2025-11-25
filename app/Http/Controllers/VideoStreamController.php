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
     */
    public function stream(Request $request, Video $video)
    {
        // 1. Verify user is authenticated
        if (!auth()->check()) {
            Log::warning('Unauthorized video access attempt', [
                'video_id' => $video->id,
                'ip' => $request->ip(),
            ]);
            abort(401, 'Authentication required');
        }

        // 2. Verify video is stored locally
        if ($video->storage_type !== 'local') {
            Log::error('Attempted to stream non-local video', [
                'video_id' => $video->id,
                'storage_type' => $video->storage_type,
            ]);
            abort(400, 'This video is not stored locally');
        }

        // 3. Verify file path exists in database
        if (!$video->file_path) {
            Log::error('Video file path is empty', [
                'video_id' => $video->id,
                'video_name' => $video->name,
            ]);
            abort(404, 'Video file path not found');
        }

        // 4. ✅ FIX: Check file exists in 'public' disk (not 'videos' disk)
        if (!Storage::disk('public')->exists($video->file_path)) {
            Log::error('Video file not found in storage', [
                'video_id' => $video->id,
                'file_path' => $video->file_path,
                'full_path' => Storage::disk('public')->path($video->file_path),
                'disk' => 'public',
            ]);
            abort(404, 'Video file not found on server');
        }

        // 5. Get file information
        $path = Storage::disk('public')->path($video->file_path);
        $fileSize = filesize($path);
        $mimeType = $video->mime_type ?? 'video/mp4';

        Log::info('Streaming video', [
            'video_id' => $video->id,
            'video_name' => $video->name,
            'file_path' => $video->file_path,
            'full_path' => $path,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'user_id' => auth()->id(),
        ]);

        // 6. Open file stream
        $stream = fopen($path, 'rb');
        
        if (!$stream) {
            Log::error('Could not open video file', [
                'path' => $path,
                'video_id' => $video->id,
            ]);
            abort(500, 'Could not open video file');
        }

        $start = 0;
        $end = $fileSize - 1;
        $length = $fileSize;
        $status = 200;

        // 7. Handle byte-range requests (for video seeking)
        if ($request->header('Range')) {
            $range = $request->header('Range');

            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];
                $end = $matches[2] !== '' ? (int) $matches[2] : $end;

                // Validate range
                if ($start > $end || $start > $fileSize - 1 || $end > $fileSize - 1) {
                    Log::warning('Invalid range request', [
                        'range' => $range,
                        'start' => $start,
                        'end' => $end,
                        'file_size' => $fileSize,
                    ]);
                    
                    fclose($stream);
                    return response('Requested Range Not Satisfiable', 416)
                        ->header('Content-Range', "bytes */{$fileSize}");
                }

                $length = $end - $start + 1;
                $status = 206; // Partial Content
                fseek($stream, $start);

                Log::debug('Serving byte range', [
                    'video_id' => $video->id,
                    'start' => $start,
                    'end' => $end,
                    'length' => $length,
                ]);
            }
        }

        // 8. Create streamed response
        $response = new StreamedResponse(function () use ($stream, $length) {
            $bufferSize = 1024 * 8; // 8KB buffer
            $bytesRead = 0;

            while (!feof($stream) && $bytesRead < $length && connection_status() == 0) {
                $readSize = min($bufferSize, $length - $bytesRead);
                $data = fread($stream, $readSize);
                
                if ($data === false) {
                    break;
                }
                
                echo $data;
                flush();
                $bytesRead += $readSize;
            }

            fclose($stream);
        }, $status);

        // 9. Set response headers
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', $length);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        $response->headers->set('Access-Control-Allow-Origin', $request->header('Origin', '*'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Range');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($status === 206) {
            $response->headers->set('Content-Range', "bytes {$start}-{$end}/{$fileSize}");
        }

        return $response;
    }
}
