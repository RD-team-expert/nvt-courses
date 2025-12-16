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
        // Create video_qualities table
        Schema::create('video_qualities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('quality'); // '720p', '480p', '360p'
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();
            
            $table->unique(['video_id', 'quality']);
        });

        // Add transcode_status to videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->enum('transcode_status', ['pending', 'processing', 'completed', 'failed', 'skipped'])
                  ->default('pending')
                  ->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_qualities');
        
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('transcode_status');
        });
    }
};
