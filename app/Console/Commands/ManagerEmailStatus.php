<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ManagerHierarchyService;
use Illuminate\Console\Command;

class ManagerEmailStatus extends Command
{
    protected $signature = 'manager:email-status';
    protected $description = 'Show complete status of manager email system';

    public function handle(): int
    {
        $managerService = app(ManagerHierarchyService::class);
        
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║           MANAGER EMAIL SYSTEM STATUS                          ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        // Check configuration
        $this->info("📧 EMAIL CONFIGURATION:");
        $this->line("  Mail Driver: " . config('mail.default'));
        $this->line("  Mail Host: " . config('mail.mailers.smtp.host'));
        $this->line("  Mail Port: " . config('mail.mailers.smtp.port'));
        $this->line("  From Address: " . config('mail.from.address'));
        $this->newLine();
        
        // Check Mailhog
        $mailhogRunning = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 1);
        if ($mailhogRunning) {
            $this->info("  ✅ Mailhog is RUNNING on port 1025");
            $this->line("  🌐 View emails at: http://127.0.0.1:8025");
            fclose($mailhogRunning);
        } else {
            $this->error("  ❌ Mailhog is NOT running on port 1025");
        }
        $this->newLine();
        
        // User statistics
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $usersWithDept = User::where('role', '!=', 'admin')->whereNotNull('department_id')->count();
        $usersWithManagers = 0;
        
        $users = User::where('role', '!=', 'admin')->with('department')->get();
        foreach ($users as $user) {
            if ($user->department) {
                $managers = $managerService->getDirectManagersForUser($user->id);
                if (!empty($managers)) {
                    $usersWithManagers++;
                }
            }
        }
        
        $this->info("👥 USER STATISTICS:");
        $this->line("  Total Users: {$totalUsers}");
        $this->line("  Users with Department: {$usersWithDept} (" . round(($usersWithDept/$totalUsers)*100, 1) . "%)");
        $this->line("  Users with Managers: {$usersWithManagers} (" . round(($usersWithManagers/$totalUsers)*100, 1) . "%)");
        $this->newLine();
        
        $usersWithoutManagers = $totalUsers - $usersWithManagers;
        if ($usersWithoutManagers > 0) {
            $this->warn("  ⚠️  {$usersWithoutManagers} users will NOT trigger manager emails");
            $this->line("  💡 Run: php artisan managers:assign --bulk");
        } else {
            $this->info("  ✅ All users have managers assigned!");
        }
        $this->newLine();
        
        // Test instructions
        $this->info("🧪 TESTING:");
        $this->line("  Test manager email: php artisan test:real-manager-email {user_id}");
        $this->line("  View all managers: php artisan list:user-managers");
        $this->line("  Diagnose issues: php artisan diagnose:manager-emails");
        $this->newLine();
        
        // How it works
        $this->info("📋 HOW IT WORKS:");
        $this->line("  1. Admin assigns course to user");
        $this->line("  2. CourseOnlineAssigned event is triggered");
        $this->line("  3. SendCourseOnlineAssignmentNotifications listener runs");
        $this->line("  4. Email sent to user");
        $this->line("  5. Email sent to user's manager(s)");
        $this->line("  6. Emails appear in Mailhog (local) or sent via SMTP (production)");
        $this->newLine();
        
        return self::SUCCESS;
    }
}
