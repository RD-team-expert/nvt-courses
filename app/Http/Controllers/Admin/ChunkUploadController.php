<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ChunkUploadController extends Controller
{
    /**
     * Handle initial POST request to start chunked upload
     * FilePond sends this WITHOUT file data to get a transfer ID
     */
    public function upload(Request $request)
    {
        try {
            Log::info('=== INITIAL POST: Starting chunk upload ===');
            
            $uploadLength = $request->header('Upload-Length');
            $uploadName = $request->header('Upload-Name');
            
            // ✅ FIX: FilePond might send base64-encoded filename
            if ($uploadName) {
                $decoded = base64_decode($uploadName, true);
                if ($decoded !== false && mb_check_encoding($decoded, 'UTF-8')) {
                    $uploadName = $decoded;
                }
            }
            
            Log::info('Upload metadata:', [
                'Upload-Length' => $uploadLength,
                'Upload-Name' => $uploadName,
                'Upload-Name-Raw' => $request->header('Upload-Name'),
            ]);

            // FilePond expects a unique transfer ID as plain text response
            $transferId = uniqid('upload_', true);
            
            // Create directory for chunks
            $chunkDir = "chunks/{$transferId}";
            Storage::disk('local')->makeDirectory($chunkDir);
            
            // Store metadata for later assembly
            Storage::disk('local')->put("{$chunkDir}/metadata.json", json_encode([
                'upload_length' => $uploadLength,
                'upload_name' => $uploadName,
                'transfer_id' => $transferId,
                'started_at' => now()->toISOString(),
            ]));
            
            Log::info('Transfer initialized:', [
                'transfer_id' => $transferId,
                'chunk_directory' => $chunkDir,
            ]);

            // Return transfer ID as plain text (critical!)
            return response($transferId, 200)
                ->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            Log::error('Initial upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to initialize upload'], 500);
        }
    }

    /**
     * Handle PATCH requests with actual chunk data
     * FilePond sends raw binary data in the request body
     */
    public function uploadChunk(Request $request)
    {
        try {
            Log::info('=== PATCH: Receiving chunk ===');
            
            // Get transfer ID from query parameter
            $transferId = $request->query('patch');
            
            if (!$transferId) {
                Log::error('No transfer ID provided in PATCH request');
                return response()->json(['error' => 'Transfer ID required'], 400);
            }

            $uploadOffset = (int) $request->header('Upload-Offset', 0);
            $uploadLength = (int) $request->header('Upload-Length');
            
            // Get raw chunk data from request body
            $chunkData = $request->getContent();
            $chunkSize = strlen($chunkData);

            Log::info('Chunk details:', [
                'transfer_id' => $transferId,
                'offset' => $uploadOffset,
                'chunk_size' => $chunkSize,
                'total_length' => $uploadLength,
            ]);

            if ($chunkSize === 0) {
                Log::warning('Empty chunk received');
                return response()->json(['error' => 'Empty chunk'], 400);
            }

            // Save chunk with offset as filename for ordered assembly
            $chunkDir = "chunks/{$transferId}";
            $chunkPath = "{$chunkDir}/chunk_{$uploadOffset}";
            
            Storage::disk('local')->put($chunkPath, $chunkData);
            
            Log::info('Chunk saved:', ['path' => $chunkPath]);

            // Calculate total uploaded so far
            $totalUploaded = $this->calculateTotalUploaded($transferId);
            $isComplete = ($totalUploaded >= $uploadLength);
            $progress = round(($totalUploaded / $uploadLength) * 100, 2);

            Log::info('Upload progress:', [
                'total_uploaded' => $totalUploaded,
                'expected_total' => $uploadLength,
                'percentage' => $progress . '%',
                'is_complete' => $isComplete,
            ]);

            // If upload is complete, assemble the file
            if ($isComplete) {
                Log::info('All chunks received, assembling file...');
                return $this->assembleFile($transferId);
            }

            // Return current offset for incomplete upload (204 No Content)
            return response('', 204)
                ->header('Upload-Offset', $totalUploaded);

        } catch (\Exception $e) {
            Log::error('Chunk upload failed:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Chunk upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate total uploaded size from all chunks
     */
    protected function calculateTotalUploaded($transferId)
    {
        $chunkDir = "chunks/{$transferId}";
        
        if (!Storage::disk('local')->exists($chunkDir)) {
            return 0;
        }

        $chunks = Storage::disk('local')->files($chunkDir);
        $totalSize = 0;

        foreach ($chunks as $chunk) {
            // Skip metadata file
            if (basename($chunk) === 'metadata.json') {
                continue;
            }
            $totalSize += Storage::disk('local')->size($chunk);
        }

        return $totalSize;
    }

    /**
     * Assemble all chunks into final file
     */
   protected function assembleFile($transferId)
{
    try {
        $chunkDir = "chunks/{$transferId}";
        
        // Get metadata
        $metadataPath = "{$chunkDir}/metadata.json";
        if (!Storage::disk('local')->exists($metadataPath)) {
            Log::error('Metadata file not found', ['path' => $metadataPath]);
            return response()->json(['error' => 'Metadata not found'], 404);
        }
        
        $metadata = json_decode(Storage::disk('local')->get($metadataPath), true);
        
        $originalName = $metadata['upload_name'] ?? null;
        
        if (empty($originalName) || $originalName === 'null') {
            Log::warning('Original filename missing from metadata');
            $originalName = 'video_' . time() . '.mp4';
        }
        
        $expectedSize = $metadata['upload_length'] ?? 0;

        Log::info('Assembling file:', [
            'transfer_id' => $transferId,
            'original_name' => $originalName,
            'expected_size' => $expectedSize,
        ]);

        // Get all chunks, excluding metadata
        $chunks = collect(Storage::disk('local')->files($chunkDir))
            ->filter(fn($file) => basename($file) !== 'metadata.json')
            ->sort(function ($a, $b) {
                // ✅ FIX: Better sorting to handle large offsets
                $offsetA = (int) str_replace('chunk_', '', basename($a));
                $offsetB = (int) str_replace('chunk_', '', basename($b));
                return $offsetA <=> $offsetB;
            })
            ->values();

        Log::info('Found chunks:', [
            'count' => $chunks->count(),
            'chunks' => $chunks->map(fn($c) => [
                'name' => basename($c),
                'size' => Storage::disk('local')->size($c),
            ])->toArray(),
        ]);

        if ($chunks->isEmpty()) {
            throw new \Exception('No chunks found to assemble');
        }

        // Generate safe filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'mp4';
        }
        
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        if (empty($filename)) {
            $filename = 'video_' . time();
        }
        
        $safeFilename = Str::slug($filename) . '_' . time() . '.' . $extension;
        $finalPath = "videos/{$safeFilename}";

        Log::info('Final filename:', [
            'original' => $originalName,
            'safe_filename' => $safeFilename,
            'final_path' => $finalPath,
        ]);

        // Make sure videos directory exists
        if (!Storage::disk('public')->exists('videos')) {
            Storage::disk('public')->makeDirectory('videos');
        }

        // ✅ CRITICAL FIX: Use binary-safe file operations
        $outputPath = Storage::disk('public')->path($finalPath);
        
        Log::info('Assembling to:', ['output_path' => $outputPath]);
        
        // Open output file in binary write mode
        $output = fopen($outputPath, 'wb');
        
        if (!$output) {
            throw new \Exception('Could not create output file: ' . $outputPath);
        }

        $totalBytesWritten = 0;
        
        // Write each chunk in order
        foreach ($chunks as $index => $chunk) {
            $chunkPath = Storage::disk('local')->path($chunk);
            $chunkSize = filesize($chunkPath);
            
            Log::info("Writing chunk {$index}:", [
                'chunk' => basename($chunk),
                'size' => $chunkSize,
            ]);
            
            // Read chunk in binary mode
            $chunkHandle = fopen($chunkPath, 'rb');
            
            if (!$chunkHandle) {
                fclose($output);
                throw new \Exception("Could not read chunk: " . basename($chunk));
            }
            
            // Copy chunk to output file
            $bytesWritten = stream_copy_to_stream($chunkHandle, $output);
            
            if ($bytesWritten === false) {
                fclose($chunkHandle);
                fclose($output);
                throw new \Exception("Failed to write chunk: " . basename($chunk));
            }
            
            $totalBytesWritten += $bytesWritten;
            
            fclose($chunkHandle);
            
            Log::info("Chunk written:", [
                'bytes' => $bytesWritten,
                'total_so_far' => $totalBytesWritten,
            ]);
        }
        
        fclose($output);

        Log::info('All chunks assembled:', [
            'total_bytes' => $totalBytesWritten,
            'expected' => $expectedSize,
        ]);

        $actualSize = filesize($outputPath);

        // Verify size
        if ($actualSize != $expectedSize) {
            Log::warning('File size mismatch:', [
                'expected' => $expectedSize,
                'actual' => $actualSize,
                'difference' => abs($actualSize - $expectedSize),
            ]);
        }

        // Verify file is a valid video
        if ($actualSize < 1000) {
            throw new \Exception('Assembled file is too small to be a valid video');
        }

        // Clean up chunks
        Storage::disk('local')->deleteDirectory($chunkDir);

        Log::info('File assembled successfully:', [
            'original_name' => $originalName,
            'safe_filename' => $safeFilename,
            'final_path' => $finalPath,
            'file_size' => $actualSize,
            'output_path' => $outputPath,
        ]);

        // Get mime type safely
        $mimeType = 'video/mp4';
        if (function_exists('mime_content_type') && file_exists($outputPath)) {
            try {
                $detectedMime = mime_content_type($outputPath);
                if ($detectedMime) {
                    $mimeType = $detectedMime;
                }
            } catch (\Exception $e) {
                Log::warning('Could not detect mime type: ' . $e->getMessage());
            }
        }

        // Return file information as JSON
        $responseData = [
            'path' => $finalPath,
            'name' => $safeFilename,
            'original_name' => $originalName,
            'url' => Storage::disk('public')->url($finalPath),
            'mime_type' => $mimeType,
            'size' => $actualSize,
        ];

        Log::info('Returning final response:', $responseData);

        return response()->json($responseData, 200)
            ->header('Content-Type', 'application/json');

    } catch (\Exception $e) {
        Log::error('File assembly failed:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'error' => 'Chunk upload failed',
            'message' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Handle HEAD request to check upload progress (for resume)
     */
    public function checkUpload(Request $request)
    {
        try {
            $transferId = $request->query('patch');
            
            if (!$transferId) {
                return response('', 404);
            }

            $totalUploaded = $this->calculateTotalUploaded($transferId);
            
            return response('', 200)
                ->header('Upload-Offset', $totalUploaded);

        } catch (\Exception $e) {
            Log::error('Upload check failed: ' . $e->getMessage());
            return response('', 404);
        }
    }

    /**
     * Handle file revert (if user cancels upload)
     */
    public function revert(Request $request)
    {
        try {
            // FilePond sends the transfer ID in the request body
            $transferId = $request->getContent();
            
            Log::info('Reverting upload:', ['transfer_id' => $transferId]);
            
            if ($transferId) {
                $chunkDir = "chunks/{$transferId}";
                if (Storage::disk('local')->exists($chunkDir)) {
                    Storage::disk('local')->deleteDirectory($chunkDir);
                    Log::info('Upload reverted successfully');
                }
            }
            
            return response('', 200);

        } catch (\Exception $e) {
            Log::error('Revert failed: ' . $e->getMessage());
            return response()->json(['error' => 'Revert failed'], 500);
        }
    }
}
