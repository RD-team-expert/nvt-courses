<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Check if the admin user already exists or create a new one
        User::firstOrCreate(
            [ 'email' => 'Harry@pneunited.com' ], // unique field
            [
                'name' => 'Harry',
                'password' => Hash::make('password'), // change to a secure password
                'role' => 'admin', // Ensure your 'users' table has 'role' column
            ]
        );
        
        // Add another admin user - Ahab
        User::firstOrCreate(
            [ 'email' => 'asa@peopleenterprise.org' ], // unique field
            [
                'name' => 'Ahab',
                'password' => Hash::make('Ahab@PNE1'), // secure password
                'role' => 'admin', // admin role
            ]
        );
        
    }
}
