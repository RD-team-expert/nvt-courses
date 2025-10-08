<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->enum('content_type', ['video', 'pdf']);
            $table->foreignId('video_id')->nullable()->constrained('videos')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_path', 500)->nullable()->comment('For uploaded PDFs');
            $table->string('google_drive_pdf_url', 1000)->nullable()->comment('For PDF Google Drive links');
            $table->integer('duration')->nullable()->comment('Video duration in seconds');
            $table->bigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->string('thumbnail_path', 500)->nullable();
            $table->integer('order_number')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['module_id', 'order_number']);
            $table->index(['content_type', 'is_active']);
            $table->index('video_id');
            $table->unique(['module_id', 'order_number'], 'unique_module_content_order');
        });

        // Add check constraint using raw SQL after table creation
        DB::statement('
            ALTER TABLE module_content
            ADD CONSTRAINT valid_content_constraint
            CHECK (
                (content_type = "video" AND video_id IS NOT NULL) OR
                (content_type = "pdf" AND (file_path IS NOT NULL OR google_drive_pdf_url IS NOT NULL))
            )
        ');
    }

    public function down(): void
    {
        // Drop check constraint first (if exists)
        try {
            DB::statement('ALTER TABLE module_content DROP CONSTRAINT valid_content_constraint');
        } catch (\Exception $e) {
            // Ignore if constraint doesn't exist
        }

        Schema::dropIfExists('module_content');
    }
};
