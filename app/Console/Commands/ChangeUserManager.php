<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserDepartmentRole;
use Illuminate\Console\Command;

class ChangeUserManager extends Command
{
    protected $signature = 'manager:change {user_email} {new_manager_email}';
    protected $description = 'Change a user\'s manager';

    public function handle(): int
    {
        $userEmail = $this->argument('user_email');
        $newManagerEmail = $this->argument('new_manager_email');
        
        // Find user
        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error("User not found: {$userEmail}");
            return self::FAILURE;
        }
        
        if (!$user->department) {
            $this->error("User has no department!");
            return self::FAILURE;
        }
        
        // Find new manager
        $newManager = User::where('email', $newManagerEmail)->first();
        if (!$newManager) {
            $this->error("Manager not found: {$newManagerEmail}");
            return self::FAILURE;
        }
        
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Department: {$user->department->name}");
        $this->info("New Manager: {$newManager->name} ({$newManager->email})");
        $this->newLine();
        
        // Remove old manager relationship for this user
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
            'created_by' => auth()->id() ?? 1,
        ]);
        
        $this->info("✅ Manager changed successfully!");
        $this->info("{$user->name}'s manager is now {$newManager->name}");
        
        return self::SUCCESS;
    }
}
