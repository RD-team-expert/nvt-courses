<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class VideoStorageService
{
    protected string $disk;
    protected int $maxSize;
    protected array $allowedMimes;

    public function __construct()
    {
        $this->disk = config('filesystems.video_disk', 'videos');
        $this->maxSize = (int) config('filesystems.video_max_size', 512000); // KB
        $this->allowedMimes = explode(',', config('filesystems.video_allowed_mimes', 'mp4,webm,avi,mov'));
    }

    /**
     * Upload and store video file
     *
     * @param UploadedFile $file
     * @param int|null $categoryId
     * @return array ['file_path', 'file_size', 'mime_type', 'duration_seconds', 'thumbnail_path']
     */
    public function uploadVideo(UploadedFile $file, ?int $categoryId = null): array
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);

            // Organize by category and date
            $path = $this->generateStoragePath($categoryId);
            $fullPath = $path . '/' . $filename;

            // Store file
            Storage::disk($this->disk)->putFileAs($path, $file, $filename);



            // Extract metadata
            $metadata = $this->extractMetadata($fullPath);

            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($fullPath);

            return [
                'file_path' => $fullPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'duration_seconds' => $metadata['duration'] ?? null,
                'thumbnail_path' => $thumbnailPath,
            ];

        } catch (\Exception $e) {

            throw $e;
        }
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        $fileSizeKB = $file->getSize() / 1024;
        if ($fileSizeKB > $this->maxSize) {
            throw new \Exception("File size exceeds maximum allowed size of {$this->maxSize}KB");
        }

        // Check MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedMimes)) {
            throw new \Exception("File type '{$extension}' is not allowed. Allowed types: " . implode(', ', $this->allowedMimes));
        }

        // Additional security check
        if (!$file->isValid()) {
            throw new \Exception("Invalid file upload");
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        return $safeName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Generate organized storage path
     */
    protected function generateStoragePath(?int $categoryId): string
    {
        $date = now()->format('Y/m');

        if ($categoryId) {
            return "files/category_{$categoryId}/{$date}";
        }

        return "files/general/{$date}";
    }

    /**
     * Extract video metadata (duration, resolution, etc.)
     *
     * Simple implementation - can be enhanced with FFMpeg if available
     */
    protected function extractMetadata(string $filePath): array
    {
        try {
            $fullPath = Storage::disk($this->disk)->path($filePath);

            // Try to use getID3 library if available
            if (class_exists('getID3')) {
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($fullPath);

                return [
                    'duration' => isset($fileInfo['playtime_seconds']) ? (int) $fileInfo['playtime_seconds'] : null,
                    'resolution' => $fileInfo['video']['resolution_x'] ?? null . 'x' . $fileInfo['video']['resolution_y'] ?? null,
                    'bitrate' => $fileInfo['bitrate'] ?? null,
                ];
            }

            // Fallback: Try FFprobe command if available
            if ($this->commandExists('ffprobe')) {
                $output = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath));
                $duration = $output ? (int) trim($output) : null;

                return ['duration' => $duration];
            }

            return ['duration' => null];

        } catch (\Exception $e) {

            return ['duration' => null];
        }
    }

    /**
     * Generate thumbnail from video
     */
    protected function generateThumbnail(string $videoPath): ?string
    {
        try {
            $thumbnailPath = 'thumbnails/' . pathinfo($videoPath, PATHINFO_FILENAME) . '.jpg';
            $fullVideoPath = Storage::disk($this->disk)->path($videoPath);
            $fullThumbnailPath = Storage::disk($this->disk)->path($thumbnailPath);

            // Ensure thumbnail directory exists
            $thumbnailDir = dirname($fullThumbnailPath);
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Try FFmpeg if available
            if ($this->commandExists('ffmpeg')) {
                $command = sprintf(
                    'ffmpeg -i %s -ss 00:00:01.000 -vframes 1 %s 2>&1',
                    escapeshellarg($fullVideoPath),
                    escapeshellarg($fullThumbnailPath)
                );

                exec($command, $output, $returnCode);

                if ($returnCode === 0 && file_exists($fullThumbnailPath)) {
                    return $thumbnailPath;
                }
            }

            return null;

        } catch (\Exception $e) {

            return null;
        }
    }

    /**
     * Delete video file from storage
     */
    public function deleteVideo(string $filePath): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($filePath)) {
                Storage::disk($this->disk)->delete($filePath);

                return true;
            }

            return false;
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * Delete thumbnail file
     */
    public function deleteThumbnail(string $thumbnailPath): bool
    {
        try {
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                Storage::disk($this->disk)->delete($thumbnailPath);
                return true;
            }
            return false;
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * Get storage disk name
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Get full storage path for a file
     */
    public function getFullPath(string $filePath): string
    {
        return Storage::disk($this->disk)->path($filePath);
    }

    /**
     * Check if command exists on system
     */
    protected function commandExists(string $command): bool
    {
        $whereIsCommand = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $process = proc_open(
            "$whereIsCommand $command",
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if ($process !== false) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return !empty(trim($output));
        }

        return false;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $allFiles = Storage::disk($this->disk)->allFiles('files');
        $totalSize = 0;

        foreach ($allFiles as $file) {
            $totalSize += Storage::disk($this->disk)->size($file);
        }

        return [
            'total_files' => count($allFiles),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'disk' => $this->disk,
        ];
    }
}
