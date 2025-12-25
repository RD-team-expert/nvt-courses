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
        Schema::table('audios', function (Blueprint $table) {
            $table->enum('storage_type', ['google_drive', 'local'])->default('google_drive')->after('google_cloud_url');
            $table->string('local_path')->nullable()->after('storage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->dropColumn(['storage_type', 'local_path']);
        });
    }
};
