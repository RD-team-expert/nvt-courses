<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseAvailability;
use Carbon\Carbon;

class CourseAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            // Create 2-3 availability ranges for each course
            $startDate = Carbon::now()->addDays(10);

            CourseAvailability::create([
                'course_id' => $course->id,
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays(7),
                'capacity' => 25,
                'status' => 'active'
            ]);

            CourseAvailability::create([
                'course_id' => $course->id,
                'start_date' => $startDate->copy()->addDays(30),
                'end_date' => $startDate->copy()->addDays(37),
                'capacity' => 30,
                'status' => 'active'
            ]);

            CourseAvailability::create([
                'course_id' => $course->id,
                'start_date' => $startDate->copy()->addDays(60),
                'end_date' => $startDate->copy()->addDays(67),
                'capacity' => 20,
                'status' => 'active'
            ]);
        }
    }
}
