<?php
// database/migrations/2025_04_05_000000_create_drive_key_tracker_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_key_tracker', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->unique(); // e.g., DRIVE_API_KEY_1
            $table->integer('active_users')->default(0);
            $table->integer('max_users')->default(10);
            $table->boolean('is_active')->default(true); // 🆕 Can disable broken keys
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // 🚀 PERFORMANCE BOOST: Add indexes for faster queries
            $table->index('active_users'); // Speed up sorting by active_users
            $table->index(['active_users', 'is_active']); // Composite index for finding available keys
        });

        // Insert your 7 keys
        $keys = [
            'DRIVE_API_KEY_1',
            'DRIVE_API_KEY_2',
            'DRIVE_API_KEY_3',
            'DRIVE_API_KEY_4',
            'DRIVE_API_KEY_5',
            'DRIVE_API_KEY_6',
            'DRIVE_API_KEY_7',
        ];

        foreach ($keys as $key) {
            DB::table('drive_key_tracker')->insert([
                'key_name' => $key,
                'active_users' => 0,
                'max_users' => 10,
                'is_active' => true, // 🆕 All keys start active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_key_tracker');
    }
};
