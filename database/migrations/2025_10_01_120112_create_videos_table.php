<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('google_drive_url', 500);
            $table->integer('duration')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('video_category_id')->nullable()->constrained('video_categories')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
            $table->index(['video_category_id', 'is_active']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
