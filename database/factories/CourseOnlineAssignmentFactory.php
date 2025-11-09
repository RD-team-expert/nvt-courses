<?php

namespace Database\Factories;

use App\Models\CourseOnlineAssignment;
use App\Models\User;
use App\Models\CourseOnline;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseOnlineAssignmentFactory extends Factory
{
    protected $model = CourseOnlineAssignment::class;

    public function definition(): array
    {
        return [
            'course_online_id' => CourseOnline::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'started_at' => null,
            'completed_at' => null,
            'status' => 'assigned', // ✅ FIXED: Use 'assigned' instead of 'active'
            'progress_percentage' => 0,
            'current_module_id' => null,
            'notification_sent' => false,
            'deadline' => null,
            'is_overdue' => false,
            'deadline_notification_sent_at' => null,
        ];
    }

    public function inProgress()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress', // ✅ Correct ENUM value
            'started_at' => now()->subDays(5),
            'progress_percentage' => fake()->numberBetween(10, 90),
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed', // ✅ Correct ENUM value
            'started_at' => now()->subDays(10),
            'completed_at' => now()->subDays(1),
            'progress_percentage' => 100,
        ]);
    }
}
