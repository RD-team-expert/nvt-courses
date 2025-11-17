<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ChunkUploadController extends Controller
{
    /**
     * Handle chunked file upload
     */
    public function upload(Request $request)
    {
        try {
            // Create the file receiver
            $receiver = new FileReceiver('filepond', $request, HandlerFactory::classFromRequest($request));

            // Check if the upload is success
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }

            // Receive the file
            $save = $receiver->receive();

            // Check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                // Save the file and return success response
                return $this->saveFile($save->getFile());
            }

            // We are in chunk mode, continue uploading
            $handler = $save->handler();

            return response()->json([
                "done" => $handler->getPercentageDone(),
                "status" => true
            ]);

        } catch (UploadMissingFileException $e) {
            return response()->json([
                'error' => 'No file was uploaded'
            ], 422);

        } catch (\Exception $e) {
            Log::error('Chunk upload error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save the finished file
     */
    protected function saveFile($file)
    {
        $fileName = $this->createFilename($file);

        // Store file in storage/app/chunks
        $finalPath = storage_path('app/chunks/' . $fileName);

        // Create directory if it doesn't exist
        $directory = dirname($finalPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Move the file
        $file->move($directory, $fileName);

        Log::info('Chunk upload completed', [
            'filename' => $fileName,
            'path' => 'chunks/' . $fileName
        ]);

        return response()->json([
            'path' => 'chunks/' . $fileName,
            'name' => $fileName,
            'mime_type' => $file->getMimeType()
        ]);
    }

    /**
     * Create a unique filename
     */
    protected function createFilename($file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace('.' . $extension, '', $file->getClientOriginalName());
        $filename = Str::slug($filename) . '_' . time() . '.' . $extension;

        return $filename;
    }
}
