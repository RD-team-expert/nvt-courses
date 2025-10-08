<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incentives', function (Blueprint $table) {
            // Add Level and Tier foreign keys
            $table->foreignId('user_level_id')->nullable()->constrained('user_levels')->cascadeOnDelete()->after('id');
            $table->foreignId('user_level_tier_id')->nullable()->constrained('user_level_tiers')->cascadeOnDelete()->after('user_level_id');

            // Keep existing columns: min_score, max_score, incentive_amount, evaluation_config_id
        });
    }

    public function down(): void
    {
        Schema::table('incentives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_level_tier_id');
            $table->dropConstrainedForeignId('user_level_id');
        });
    }
};
