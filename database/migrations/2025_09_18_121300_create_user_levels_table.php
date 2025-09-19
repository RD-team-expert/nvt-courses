<?php
// database/migrations/2025_09_18_120002_create_user_levels_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // L1, L2, L3, L4
            $table->string('name', 100); // Employee, Direct Manager, etc.
            $table->tinyInteger('hierarchy_level'); // 1, 2, 3, 4
            $table->text('description')->nullable();
            $table->json('can_manage_levels')->nullable(); // Which levels this level can manage
            $table->timestamps();

            // Indexes
            $table->index('hierarchy_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};
