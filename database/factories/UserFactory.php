<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'user',
            'status' => 'active',
            'is_admin' => 0,
            'department_id' => null,
            'user_level_id' => null,
            'employee_code' => null,
            'hire_date' => null,
            'phone' => fake()->phoneNumber(),
            'login_token' => null,
            'login_token_expires_at' => null,
            'user_level_tier_id' => null,
        ];
    }

    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_admin' => 1,
        ]);
    }
}
