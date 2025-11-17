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
            // ✅ Make google_drive_url nullable
            $table->string('google_drive_url', 500)->nullable()->change();

            // ✅ Also make streaming_url nullable (if it exists and not nullable already)
            if (Schema::hasColumn('videos', 'streaming_url')) {
                $table->text('streaming_url')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('google_drive_url', 500)->nullable(false)->change();

            if (Schema::hasColumn('videos', 'streaming_url')) {
                $table->text('streaming_url')->nullable(false)->change();
            }
        });
    }
};
