<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audio_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('audio_id')->constrained('audios')->onDelete('cascade');
            $table->decimal('current_time', 10, 2)->default(0)->comment('Current playback position in seconds');
            $table->integer('total_listened_time')->default(0)->comment('Total time listened in seconds');
            $table->boolean('is_completed')->default(false);
            $table->decimal('completion_percentage', 5, 2)->default(0)->comment('Progress percentage');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            // Composite unique key to prevent duplicate progress records
            $table->unique(['user_id', 'audio_id']);

            // Indexes
            $table->index(['user_id', 'is_completed']);
            $table->index(['audio_id', 'completion_percentage']);
            $table->index('last_accessed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_progress');
    }
};
