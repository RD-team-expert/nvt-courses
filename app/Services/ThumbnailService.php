<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ThumbnailService
{
    protected $thumbnailSizes = [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 200],
        'large' => ['width' => 600, 'height' => 400],
    ];

    protected $quality = 85; // JPEG quality (1-100)

    /**
     * Generate thumbnail from course image using native GD
     */
    public function generateCourseThumbnail(string $imagePath, string $size = 'medium'): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        if (!isset($this->thumbnailSizes[$size])) {
            return null;
        }

        if (!Storage::disk('public')->exists($imagePath)) {
            return null;
        }

        try {
            $dimensions = $this->thumbnailSizes[$size];
            $sourceImagePath = Storage::disk('public')->path($imagePath);

            // Get image info
            $imageInfo = getimagesize($sourceImagePath);
            if (!$imageInfo) {
                return null;
            }

            [$sourceWidth, $sourceHeight, $imageType] = $imageInfo;

            // Create image resource based on type
            $sourceImage = $this->createImageFromFile($sourceImagePath, $imageType);
            if (!$sourceImage) {
                return null;
            }

            // Calculate dimensions maintaining aspect ratio
            $aspectRatio = $sourceWidth / $sourceHeight;
            $thumbWidth = $dimensions['width'];
            $thumbHeight = $dimensions['height'];

            // Create thumbnail with proper aspect ratio
            if ($thumbWidth / $thumbHeight > $aspectRatio) {
                $thumbWidth = $thumbHeight * $aspectRatio;
            } else {
                $thumbHeight = $thumbWidth / $aspectRatio;
            }

            // Create thumbnail canvas
            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

            // Handle transparency for PNG and GIF
            if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
                $this->preserveTransparency($thumbnail, $imageType);
            }

            // Resize image with high quality resampling
            imagecopyresampled(
                $thumbnail, $sourceImage,
                0, 0, 0, 0,
                $thumbWidth, $thumbHeight,
                $sourceWidth, $sourceHeight
            );

            // Generate thumbnail path
            $pathInfo = pathinfo($imagePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' .
                $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];

            // Ensure thumbnail directory exists
            $thumbnailDir = dirname(Storage::disk('public')->path($thumbnailPath));
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Save thumbnail
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
            $success = $this->saveImageByType($thumbnail, $thumbnailFullPath, $imageType);

            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);

            if ($success) {


                return $thumbnailPath;
            } else {
                return null;
            }

        } catch (\Exception $e) {


            return null;
        }
    }

    /**
     * Create image resource from file based on type
     */
    protected function createImageFromFile(string $filePath, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    /**
     * Save image by type
     */
    protected function saveImageByType($image, string $filePath, int $imageType): bool
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $filePath, $this->quality);
            case IMAGETYPE_PNG:
                // PNG uses compression level 0-9 (inverted from quality)
                $compression = 9 - round(($this->quality / 100) * 9);
                return imagepng($image, $filePath, $compression);
            case IMAGETYPE_GIF:
                return imagegif($image, $filePath);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $filePath, $this->quality);
            default:
                return false;
        }
    }

    /**
     * Preserve transparency for PNG and GIF images
     */
    protected function preserveTransparency($image, int $imageType): void
    {
        if ($imageType == IMAGETYPE_PNG) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefill($image, 0, 0, $transparent);
        } elseif ($imageType == IMAGETYPE_GIF) {
            $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefill($image, 0, 0, $transparent);
            imagecolortransparent($image, $transparent);
        }
    }

    /**
     * Generate multiple thumbnail sizes
     */
    public function generateMultipleThumbnails(string $imagePath): array
    {
        $thumbnails = [];

        foreach ($this->thumbnailSizes as $size => $dimensions) {
            $thumbnail = $this->generateCourseThumbnail($imagePath, $size);
            if ($thumbnail) {
                $thumbnails[$size] = [
                    'path' => $thumbnail,
                    'url' => Storage::disk('public')->url($thumbnail),
                    'dimensions' => $dimensions
                ];
            }
        }

        return $thumbnails;
    }

    /**
     * Generate video thumbnail from URL using native PHP
     */
    public function generateVideoThumbnail(string $thumbnailUrl, string $videoId): ?string
    {
        try {
            $thumbnailDir = "videos/thumbnails/{$videoId}";
            $thumbnailFilename = "thumbnail_" . time() . ".jpg";
            $thumbnailPath = "{$thumbnailDir}/{$thumbnailFilename}";

            // Ensure directory exists
            $fullThumbnailDir = Storage::disk('public')->path($thumbnailDir);
            if (!is_dir($fullThumbnailDir)) {
                mkdir($fullThumbnailDir, 0755, true);
            }

            // Download image content
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (compatible; Laravel Course System)'
                ]
            ]);

            $imageContent = file_get_contents($thumbnailUrl, false, $context);

            if ($imageContent === false) {

                return null;
            }

            // Create image from string
            $sourceImage = imagecreatefromstring($imageContent);
            if (!$sourceImage) {
                return null;
            }

            // Get source dimensions
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            // Calculate thumbnail dimensions (300x200)
            $thumbWidth = 300;
            $thumbHeight = 200;

            // Calculate aspect ratio fitting
            $aspectRatio = $sourceWidth / $sourceHeight;
            if ($thumbWidth / $thumbHeight > $aspectRatio) {
                $thumbWidth = $thumbHeight * $aspectRatio;
            } else {
                $thumbHeight = $thumbWidth / $aspectRatio;
            }

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled(
                $thumbnail, $sourceImage,
                0, 0, 0, 0,
                $thumbWidth, $thumbHeight,
                $sourceWidth, $sourceHeight
            );

            // Save thumbnail
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
            $success = imagejpeg($thumbnail, $fullThumbnailPath, $this->quality);

            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);

            if ($success) {


                return $thumbnailPath;
            } else {
                return null;
            }

        } catch (\Exception $e) {


            return null;
        }
    }

    /**
     * Delete thumbnail files
     */
    public function deleteThumbnails(string $originalImagePath): bool
    {
        $pathInfo = pathinfo($originalImagePath);
        $thumbnailDir = $pathInfo['dirname'] . '/thumbnails/';

        $deleted = true;

        foreach ($this->thumbnailSizes as $size => $dimensions) {
            $thumbnailPath = $thumbnailDir . $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];

            if (Storage::disk('public')->exists($thumbnailPath)) {
                if (!Storage::disk('public')->delete($thumbnailPath)) {
                    $deleted = false;
                }
            }
        }



        return $deleted;
    }

    /**
     * Get thumbnail URL if exists
     */
    public function getThumbnailUrl(string $imagePath, string $size = 'medium'): ?string
    {
        $pathInfo = pathinfo($imagePath);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' .
            $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->url($thumbnailPath);
        }

        return null;
    }

    /**
     * Generate simple PDF placeholder thumbnail
     */
    public function generatePdfThumbnail(string $pdfPath): string
    {
        // Create a simple PDF icon thumbnail using GD
        $width = 200;
        $height = 250;

        // Create canvas
        $image = imagecreatetruecolor($width, $height);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 220, 53, 69);
        $gray = imagecolorallocate($image, 108, 117, 125);

        // Fill background
        imagefill($image, 0, 0, $white);

        // Draw border
        imagerectangle($image, 5, 5, $width-6, $height-6, $gray);

        // Draw PDF text
        if (function_exists('imagettftext')) {
            // If TTF support is available (better quality)
            $font_size = 16;
            $text = 'PDF';
            $font_color = $red;

            // Center the text
            $text_box = imagettfbbox($font_size, 0, null, $text);
            $text_width = $text_box[4] - $text_box[0];
            $text_height = $text_box[1] - $text_box[5];

            $x = ($width - $text_width) / 2;
            $y = ($height - $text_height) / 2 + $text_height;

            imagestring($image, 5, $x, $y, $text, $font_color);
        } else {
            // Fallback to built-in fonts
            $text = 'PDF';
            $x = ($width - strlen($text) * 15) / 2;
            $y = ($height - 15) / 2;
            imagestring($image, 5, $x, $y, $text, $red);
        }

        // Save thumbnail
        $thumbnailPath = 'defaults/pdf-thumbnail.png';
        $fullPath = Storage::disk('public')->path($thumbnailPath);

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($image, $fullPath);
        imagedestroy($image);



        return $thumbnailPath;
    }

    /**
     * Get available thumbnail sizes
     */
    public function getAvailableSizes(): array
    {
        return $this->thumbnailSizes;
    }

    /**
     * Set thumbnail quality
     */
    public function setQuality(int $quality): void
    {
        $this->quality = max(1, min(100, $quality));
    }

    /**
     * Check if GD extension is available
     */
    public function isGdAvailable(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Get supported image types
     */
    public function getSupportedTypes(): array
    {
        $types = [];

        if (imagetypes() & IMG_JPEG) $types[] = 'jpeg';
        if (imagetypes() & IMG_PNG) $types[] = 'png';
        if (imagetypes() & IMG_GIF) $types[] = 'gif';
        if (imagetypes() & IMG_WEBP) $types[] = 'webp';

        return $types;
    }
}
