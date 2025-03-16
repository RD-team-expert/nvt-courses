<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CourseCompletionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get some users and courses
        $users = User::where('role', '!=', 'admin')->take(5)->get();
        $courses = Course::take(3)->get();
        
        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command->info('No users or courses found. Please seed users and courses first.');
            return;
        }

        foreach ($users as $user) {
            foreach ($courses as $course) {
                // Check if registration exists, create if not
                $registration = CourseRegistration::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => $course->id],
                    [
                        'status' => 'completed',
                        'created_at' => Carbon::now()->subDays(rand(30, 60))
                    ]
                );

                // Create completion record
                CourseCompletion::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'completed_at' => Carbon::now()->subDays(rand(1, 20)),
                    'rating' => rand(3, 5),
                    'feedback' => $this->getRandomFeedback()
                ]);
            }
        }
    }

    /**
     * Get random feedback text
     *
     * @return string
     */
    private function getRandomFeedback()
    {
        $feedback = [
            'Great course! I learned a lot.',
            'The content was well-structured and easy to follow.',
            'Excellent materials and presentation.',
            'Very informative and practical.',
            'I enjoyed the hands-on exercises.',
            'The instructor was knowledgeable and engaging.',
            'This course exceeded my expectations.',
            'I would recommend this course to others.',
            'The pace was perfect for me.',
            'Very comprehensive coverage of the topic.'
        ];

        return $feedback[array_rand($feedback)];
    }
}