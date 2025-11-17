<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserLevel;
use Illuminate\Support\Facades\Log;

class L6BusinessOwnerSeeder extends Seeder
{
    public function run()
    {
        // ✅ Use firstOrCreate to prevent duplicates
        $level = UserLevel::firstOrCreate(
            [
                'code' => 'L6', // Check if this exists
            ],
            [
                // If not found, create with these attributes
                'name' => 'Business Owner',
                'hierarchy_level' => 6,
                'description' => 'Business owners and C-suite executives with full organizational authority',
                'can_manage_levels' => ['L1', 'L2', 'L3', 'L4', 'L5'],
            ]
        );

        if ($level->wasRecentlyCreated) {
            Log::info('✅ L6 Business Owner level created successfully');
            $this->command->info('✅ L6 Business Owner level created successfully');
        } else {
            Log::info('ℹ️ L6 Business Owner level already exists');
            $this->command->info('ℹ️ L6 Business Owner level already exists - skipped');
        }
    }
}
