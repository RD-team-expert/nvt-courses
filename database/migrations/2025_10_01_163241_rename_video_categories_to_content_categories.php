<?php
// Migration: rename_video_categories_to_content_categories

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename the table
        Schema::rename('video_categories', 'content_categories');

        // Update the foreign key reference in videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('video_category_id', 'content_category_id');
        });
    }

    public function down(): void
    {
        // Revert foreign key name in videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('content_category_id', 'video_category_id');
        });

        // Rename table back
        Schema::rename('content_categories', 'video_categories');
    }
};
