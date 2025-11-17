<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

            abort(403, 'Invalid or expired video link');
        }

        // 2. Verify user is authenticated
        if (!auth()->check()) {

            abort(401, 'Authentication required');
        }

        // 3. Verify video is stored locally
        if ($video->storage_type !== 'local') {

            abort(400, 'This video cannot be streamed from local storage');
        }

        // 4. Verify file exists
        if (!$video->file_path || !Storage::disk('videos')->exists($video->file_path)) {

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

                    fclose($stream);
                    return response('Requested Range Not Satisfiable', 416)
                        ->header('Content-Range', "bytes */{$fileSize}");
                }

                $length = $end - $start + 1;
                $status = 206; // Partial Content

                fseek($stream, $start);


            }
        } else {

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
