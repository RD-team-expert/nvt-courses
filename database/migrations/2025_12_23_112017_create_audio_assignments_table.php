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
        Schema::create('audio_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_id')->constrained('audios')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed'])->default('assigned');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();

            // Unique constraint: one assignment per user per audio
            $table->unique(['audio_id', 'user_id']);
            
            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_assignments');
    }
};
