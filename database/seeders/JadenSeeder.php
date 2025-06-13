<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class JadenSeeder extends Seeder
{
    public function run()
    {
        // Check if the admin user already exists or create a new one
        User::firstOrCreate(
            [ 'email' => 'Jaden@pneunited.com' ], // unique field
            [
                'name' => 'Jaden',
                'password' => Hash::make('password'), // change to a secure password
                'role' => 'admin', // Ensure your 'users' table has 'role' column
            ]
        );
        
      
       
        
    }
}
