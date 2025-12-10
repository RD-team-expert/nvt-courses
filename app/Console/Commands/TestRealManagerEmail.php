<?php

namespace App\Console\Commands;

use App\Events\CourseOnlineAssigned;
use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Console\Command;

class TestRealManagerEmail extends Command
{
    protected $signature = 'test:real-manager-email {user_id}';
    protected $description = 'Test actual manager email by triggering CourseOnlineAssigned event';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $user = User::with('department')->find($userId);
        
        if (!$user) {
            $this->error("User not found!");
            return self::FAILURE;
        }
        
        $course = CourseOnline::first();
        if (!$course) {
            $this->error("No courses found!");
            return self::FAILURE;
        }
        
        $this->info("Testing email for:");
        $this->line("User: {$user->name} ({$user->email})");
        $this->line("Department: " . ($user->department?->name ?? 'NO DEPARTMENT'));
        $this->line("Course: {$course->name}");
        $this->newLine();
        
        if (!$user->department) {
            $this->error("User has no department - manager email will NOT be sent");
            return self::FAILURE;
        }
        
        $this->info("Triggering CourseOnlineAssigned event...");
        
        // Trigger the actual event
        $admin = User::where('role', 'admin')->first();
        
        event(new CourseOnlineAssigned(
            $course,
            $user,
            route('login'),
            $admin,
            []
        ));
        
        $this->newLine();
        $this->info("✅ Event triggered!");
        $this->info("📧 Check Mailhog at http://127.0.0.1:8025 to see emails");
        $this->newLine();
        
        return self::SUCCESS;
    }
}
