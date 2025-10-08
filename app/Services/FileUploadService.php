<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileUploadService
{
    protected $allowedMimeTypes = [
        'pdf' => ['application/pdf'],
        'image' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
    ];

    protected $maxFileSizes = [
        'pdf' => 10 * 1024 * 1024,      // 10MB for PDFs
        'image' => 2 * 1024 * 1024,     // 2MB for images
    ];

    /**
     * Upload PDF file for course content
     */
    public function uploadCoursePdf(UploadedFile $file, string $courseId = null): array
    {
        $this->validateFile($file, 'pdf');

        $filename = $this->generateUniqueFilename($file, 'pdf');
        $directory = 'course-content/pdfs' . ($courseId ? "/{$courseId}" : '');

        $path = Storage::disk('public')->putFileAs(
            $directory,
            $file,
            $filename
        );

        Log::info('PDF uploaded successfully', [
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'size' => $file->getSize(),
            'course_id' => $courseId
        ]);

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ];
    }

    /**
     * Upload course thumbnail/image
     */
    public function uploadCourseImage(UploadedFile $file, string $courseId = null): array
    {
        $this->validateFile($file, 'image');

        $filename = $this->generateUniqueFilename($file, 'image');
        $directory = 'course-online/images' . ($courseId ? "/{$courseId}" : '');

        $path = Storage::disk('public')->putFileAs(
            $directory,
            $file,
            $filename
        );

        Log::info('Course image uploaded successfully', [
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'size' => $file->getSize(),
            'course_id' => $courseId
        ]);

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ];
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            $deleted = Storage::disk('public')->delete($path);

            Log::info('File deleted', [
                'path' => $path,
                'success' => $deleted
            ]);

            return $deleted;
        }

        Log::warning('File not found for deletion', ['path' => $path]);
        return false;
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $path): ?array
    {
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'size' => Storage::disk('public')->size($path),
            'last_modified' => Storage::disk('public')->lastModified($path),
            'mime_type' => Storage::disk('public')->mimeType($path)
        ];
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file, string $type): void
    {
        // Check if file type is allowed
        if (!isset($this->allowedMimeTypes[$type])) {
            throw new \InvalidArgumentException("Unsupported file type: {$type}");
        }

        // Validate MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes[$type])) {
            throw new \InvalidArgumentException(
                "Invalid file type. Allowed types for {$type}: " .
                implode(', ', $this->allowedMimeTypes[$type])
            );
        }

        // Validate file size
        if (isset($this->maxFileSizes[$type]) && $file->getSize() > $this->maxFileSizes[$type]) {
            $maxSizeMB = round($this->maxFileSizes[$type] / 1024 / 1024, 1);
            throw new \InvalidArgumentException("File size exceeds maximum allowed size of {$maxSizeMB}MB for {$type}");
        }

        // Additional security checks
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException("File upload error: " . $file->getErrorMessage());
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file, string $prefix = ''): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomString = Str::random(8);

        $prefix = $prefix ? "{$prefix}_" : '';

        return "{$prefix}{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Get allowed file types for frontend validation
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Get max file sizes for frontend validation
     */
    public function getMaxFileSizes(): array
    {
        return array_map(function($size) {
            return round($size / 1024 / 1024, 1) . 'MB';
        }, $this->maxFileSizes);
    }
}
