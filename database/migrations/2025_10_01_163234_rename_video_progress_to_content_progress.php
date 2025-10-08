<?php
// Migration: rename_video_progress_to_content_progress

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, rename the table
        Schema::rename('video_progress', 'content_progress');

        // Then add new columns to renamed table
        Schema::table('content_progress', function (Blueprint $table) {
            $table->foreignId('course_online_id')->nullable()->after('video_id')->constrained('course_online')->onDelete('cascade');
            $table->foreignId('module_id')->nullable()->after('course_online_id')->constrained('course_modules')->onDelete('cascade');
            $table->enum('content_type', ['video', 'pdf'])->default('video')->after('module_id');
            $table->integer('pdf_pages_viewed')->default(0)->after('completion_percentage');
            $table->boolean('task_completed')->default(false)->after('last_accessed_at');

            // Add indexes for new columns
            $table->index(['course_online_id', 'user_id']);
            $table->index(['module_id', 'user_id']);
            $table->index('content_type');
        });
    }

    public function down(): void
    {
        Schema::table('content_progress', function (Blueprint $table) {
            $table->dropIndex(['content_progress_course_online_id_user_id_index']);
            $table->dropIndex(['content_progress_module_id_user_id_index']);
            $table->dropIndex(['content_progress_content_type_index']);

            $table->dropForeign(['course_online_id']);
            $table->dropForeign(['module_id']);

            $table->dropColumn([
                'course_online_id',
                'module_id',
                'content_type',
                'pdf_pages_viewed',
                'task_completed'
            ]);
        });

        Schema::rename('content_progress', 'video_progress');
    }
};
