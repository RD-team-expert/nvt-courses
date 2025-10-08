<?php
// Migration: enhance_videos_table_for_courses

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Add new columns for course integration (only the ones you don't have)
            $table->integer('order_number')->default(0)->after('video_category_id');
            $table->boolean('is_required')->default(true)->after('order_number');

            // Add indexes for new columns
            $table->index('order_number');
            $table->index(['is_active', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['videos_order_number_index']);
            $table->dropIndex(['videos_is_active_is_required_index']);

            $table->dropColumn([
                'order_number',
                'is_required'
            ]);
        });
    }
};
