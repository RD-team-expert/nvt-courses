<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SendTestCourseEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-course {course_id} {user_email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test course creation email';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService)
    {
        $courseId = $this->argument('course_id');
        $userEmail = $this->argument('user_email');
        
        try {
            $course = Course::findOrFail($courseId);
            
            if ($userEmail) {
                // Check if user exists before trying to fetch
                $user = User::where('email', $userEmail)->first();
                
                if (!$user) {
                    $this->error("User with email {$userEmail} not found.");
                    return Command::FAILURE;
                }
                
                $emailService->sendCourseCreationNotificationToUser($course, $user);
                $this->info("Test email sent to {$user->email} for course: {$course->name}");
            } else {
                // Check if there are any users in the system
                $userCount = User::count();
                
                if ($userCount === 0) {
                    $this->error("No users found in the system.");
                    return Command::FAILURE;
                }
                
                $emailService->sendCourseCreationNotification($course);
                $this->info("Test emails sent to all users for course: {$course->name}");
            }
            
            return Command::SUCCESS;
        } catch (ModelNotFoundException $e) {
            $this->error("Course with ID {$courseId} not found.");
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}