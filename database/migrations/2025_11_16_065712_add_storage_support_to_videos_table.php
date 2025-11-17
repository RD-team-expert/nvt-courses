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
        Schema::table('videos', function (Blueprint $table) {
            // Storage type: google_drive or local
            $table->enum('storage_type', ['google_drive', 'local'])
                ->default('google_drive')
                ->after('google_drive_url');

            // Local storage file path
            $table->string('file_path', 500)->nullable()->after('storage_type');

            // File metadata
            $table->bigInteger('file_size')->nullable()->after('file_path')
                ->comment('File size in bytes');

            $table->string('mime_type', 100)->nullable()->after('file_size');

            // Duration in seconds (for local videos)
            $table->integer('duration_seconds')->nullable()->after('mime_type');

            // Add index for performance
            $table->index('storage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['storage_type']);
            $table->dropColumn([
                'storage_type',
                'file_path',
                'file_size',
                'mime_type',
                'duration_seconds'
            ]);
        });
    }
};
