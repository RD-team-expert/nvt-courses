<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add tier assignment (nullable - manual assignment)
            $table->foreignId('user_level_tier_id')->nullable()->constrained('user_level_tiers')->onDelete('set null')->after('user_level_id');

            // Keep user_level_id - users still belong to levels
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_level_tier_id');
        });
    }
};
