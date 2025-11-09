<?php

namespace Database\Factories;

use App\Models\ModuleContent;
use App\Models\CourseModule;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleContentFactory extends Factory
{
    protected $model = ModuleContent::class;

    public function definition(): array
    {
        return [
            'module_id' => CourseModule::factory(),
            'content_type' => 'pdf',
            'video_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'file_path' => 'test/path/document.pdf', // ✅ PDFs MUST have file_path
            'google_drive_pdf_url' => null,
            'duration' => null,
            'file_size' => 1024000, // 1MB
            'pdf_page_count' => $this->faker->numberBetween(10, 100),
            'thumbnail_path' => null,
            'order_number' => $this->faker->unique()->numberBetween(1, 10000),
            'is_required' => true,
            'is_active' => true,
        ];
    }

    /**
     * State: Video content type with video relationship
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'video',
            'video_id' => Video::factory(), // Auto-create video
            'file_path' => null, // ✅ Videos don't have file_path
            'google_drive_pdf_url' => null,
            'duration' => $this->faker->numberBetween(300, 3600),
            'pdf_page_count' => null,
            'file_size' => 5120000, // 5MB
        ]);
    }

    /**
     * State: Video content with specific video
     */
    public function withVideo(Video $video): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'video',
            'video_id' => $video->id,
            'file_path' => null,
            'google_drive_pdf_url' => null,
            'duration' => $video->duration ?? $this->faker->numberBetween(300, 3600),
            'pdf_page_count' => null,
            'file_size' => 5120000,
        ]);
    }

    /**
     * State: PDF content with Google Drive URL
     */
    public function pdfWithDriveUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'pdf',
            'video_id' => null,
            'google_drive_pdf_url' => 'https://drive.google.com/file/d/' . $this->faker->uuid() . '/view',
            'file_path' => null, // When using Drive URL, no local file_path
        ]);
    }

    /**
     * State: Inactive content
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * State: Optional content
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }
}
