<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Status of the subtitle job: null, pending, processing, completed, failed
            $table->string('subtitle_status')->nullable()->default(null)->after('transcode_status');

            // Where the VTT file is saved on disk
            // e.g. "subtitles/1_ar.vtt"
            $table->string('subtitle_vtt_path')->nullable()->default(null)->after('subtitle_status');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['subtitle_status', 'subtitle_vtt_path']);
        });
    }
};