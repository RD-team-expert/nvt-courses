<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->decimal('timestamp', 10, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'video_id']);
            $table->index(['video_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_bookmarks');
    }
};
