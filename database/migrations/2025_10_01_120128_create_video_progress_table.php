<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->decimal('current_time', 10, 2)->default(0);
            $table->integer('total_watched_time')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->decimal('playback_speed', 3, 2)->default(1.0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
            $table->index(['user_id', 'is_completed']);
            $table->index(['video_id', 'completion_percentage']);
            $table->index('last_accessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};
