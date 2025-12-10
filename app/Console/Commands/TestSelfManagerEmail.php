<?php

namespace App\Console\Commands;

use App\Events\CourseOnlineAssigned;
use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Console\Command;

class TestSelfManagerEmail extends Command
{
    protected $signature = 'test:self-manager-email';
    protected $description = 'Test that self-managers do not receive emails about themselves';

    public function handle(): int
    {
        $this->info("Testing self-manager email filtering...");
        $this->newLine();
        
        // Find users who are their own managers
        $managerService = app(\App\Services\ManagerHierarchyService::class);
        
        $selfManagedUsers = User::where('role', '!=', 'admin')
            ->with('department')
            ->get()
            ->filter(function($user) use ($managerService) {
                if (!$user->department) return false;
                
                $managers = $managerService->getDirectManagersForUser($user->id);
                
                foreach ($managers as $managerData) {
                    if ($managerData['manager']->id === $user->id) {
                        return true;
                    }
                }
                return false;
            });
        
        if ($selfManagedUsers->isEmpty()) {
            $this->warn("No self-managed users found in database");
            return self::SUCCESS;
        }
        
        $this->info("Found " . $selfManagedUsers->count() . " self-managed users:");
        foreach ($selfManagedUsers as $user) {
            $this->line("  - {$user->name} ({$user->email}) - {$user->department->name}");
        }
        $this->newLine();
        
        // Test with first self-managed user
        $testUser = $selfManagedUsers->first();
        $course = CourseOnline::first();
        
        if (!$course) {
            $this->error("No courses found!");
            return self::FAILURE;
        }
        
        $this->info("Testing with user: {$testUser->name}");
        $this->info("Course: {$course->name}");
        $this->newLine();
        
        $admin = User::where('role', 'admin')->first();
        
        $this->info("Triggering CourseOnlineAssigned event...");
        event(new CourseOnlineAssigned(
            $course,
            $testUser,
            route('login'),
            $admin,
            []
        ));
        
        $this->newLine();
        $this->info("✅ Event triggered!");
        $this->info("📋 Check logs - should see 'Skipping self-manager notification'");
        $this->info("📧 Check Mailhog - should see user email but NO manager email");
        $this->newLine();
        
        return self::SUCCESS;
    }
}
