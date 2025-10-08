<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_level_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_level_id')->constrained('user_levels')->onDelete('cascade');
            $table->string('tier_name'); // "Tier 1", "Tier 2", "Tier 3"
            $table->integer('tier_order'); // 1, 2, 3
            $table->text('description')->nullable();
            $table->timestamps();

            // Ensure unique tier order within each level
            $table->unique(['user_level_id', 'tier_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_level_tiers');
    }
};
