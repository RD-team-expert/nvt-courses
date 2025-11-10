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
        Schema::table('learning_sessions', function (Blueprint $table) {
            // Add api_key_id column after content_id
            $table->unsignedInteger('api_key_id')->nullable()->after('content_id');
            
            // Optional: Add foreign key constraint if you have a drive_key_tracker table
            // $table->foreign('api_key_id')->references('id')->on('drive_key_tracker')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            // Remove foreign key first if you added it
            // $table->dropForeign(['api_key_id']);
            
            // Remove the column
            $table->dropColumn('api_key_id');
        });
    }
};
