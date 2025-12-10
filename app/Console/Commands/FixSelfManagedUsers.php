<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserDepartmentRole;
use App\Services\ManagerHierarchyService;
use Illuminate\Console\Command;

class FixSelfManagedUsers extends Command
{
    protected $signature = 'manager:fix-self-managed';
    protected $description = 'Interactive tool to fix self-managed users';

    public function handle(): int
    {
        $managerService = app(ManagerHierarchyService::class);
        
        $this->info("Finding self-managed users...");
        $this->newLine();
        
        // Find self-managed users
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
            $this->info("✅ No self-managed users found!");
            return self::SUCCESS;
        }
        
        $this->warn("Found {$selfManagedUsers->count()} self-managed users:");
        $this->newLine();
        
        foreach ($selfManagedUsers as $user) {
            $this->line("  {$user->id}. {$user->name} ({$user->email}) - {$user->department->name}");
        }
        
        $this->newLine();
        $this->info("You can fix these users one by one using:");
        $this->line("  php artisan manager:change {user_email} {new_manager_email}");
        $this->newLine();
        
        $this->info("Example:");
        $this->line("  php artisan manager:change joey@pnefoods.com alen@pnefoods.com");
        $this->newLine();
        
        if ($this->confirm('Do you want to fix them interactively now?', false)) {
            foreach ($selfManagedUsers as $user) {
                $this->newLine();
                $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("User: {$user->name}");
                $this->info("Email: {$user->email}");
                $this->info("Department: {$user->department->name}");
                $this->newLine();
                
                if (!$this->confirm('Fix this user?', true)) {
                    $this->line("Skipped.");
                    continue;
                }
                
                $newManagerEmail = $this->ask('Enter new manager email');
                
                $newManager = User::where('email', $newManagerEmail)->first();
                if (!$newManager) {
                    $this->error("Manager not found: {$newManagerEmail}");
                    continue;
                }
                
                // Remove old manager relationship
                UserDepartmentRole::where('department_id', $user->department_id)
                    ->where('is_primary', 1)
                    ->delete();
                
                // Add new manager relationship
                UserDepartmentRole::create([
                    'user_id' => $newManager->id,
                    'department_id' => $user->department_id,
                    'user_level_id' => $newManager->user_level_id,
                    'is_primary' => 1,
                    'start_date' => now(),
                    'end_date' => null,
                    'created_by' => 1,
                ]);
                
                $this->info("✅ {$user->name}'s manager is now {$newManager->name}");
            }
        }
        
        return self::SUCCESS;
    }
}
