<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Department;
use App\Services\ManagerHierarchyService;
use Illuminate\Console\Command;

class AssignManagersToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'managers:assign 
                            {--user-id= : Specific user ID to assign a manager to}
                            {--manager-id= : Manager user ID to assign}
                            {--department-id= : Department ID (optional, uses user\'s department)}
                            {--role-type=direct_manager : Role type (direct_manager, supervisor, etc.)}
                            {--bulk : Assign managers in bulk mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign managers to users who don\'t have manager assignments';

    protected ManagerHierarchyService $managerService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->managerService = app(ManagerHierarchyService::class);
        
        if ($this->option('bulk')) {
            return $this->handleBulkAssignment();
        }
        
        return $this->handleSingleAssignment();
    }

    /**
     * Handle single user manager assignment
     */
    protected function handleSingleAssignment(): int
    {
        $userId = $this->option('user-id');
        $managerId = $this->option('manager-id');
        
        if (!$userId || !$managerId) {
            $this->error('Both --user-id and --manager-id are required for single assignment.');
            $this->info('Usage: php artisan managers:assign --user-id=123 --manager-id=456');
            return self::FAILURE;
        }
        
        $user = User::find($userId);
        $manager = User::find($managerId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return self::FAILURE;
        }
        
        if (!$manager) {
            $this->error("Manager with ID {$managerId} not found.");
            return self::FAILURE;
        }
        
        if (!$user->department_id) {
            $this->error("User {$user->name} does not have a department assigned.");
            $this->info("Please assign a department first: UPDATE users SET department_id = ? WHERE id = {$userId}");
            return self::FAILURE;
        }
        
        try {
            $departmentId = $this->option('department-id') ?? $user->department_id;
            $roleType = $this->option('role-type');
            
            $this->managerService->assignManager(
                $userId,
                $managerId,
                $roleType,
                $departmentId,
                $managerId // Use manager's ID as created_by
            );
            
            $this->info("✅ Successfully assigned {$manager->name} as manager to {$user->name}");
            $this->info("   Department: {$user->department->name}");
            $this->info("   Role Type: {$roleType}");
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Failed to assign manager: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Handle bulk manager assignment
     */
    protected function handleBulkAssignment(): int
    {
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║           BULK MANAGER ASSIGNMENT WIZARD                       ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        // Get users without managers
        $usersWithoutManagers = User::where('role', '!=', 'admin')
            ->whereNotNull('department_id')
            ->with('department')
            ->get()
            ->filter(function($user) {
                $managers = $this->managerService->getDirectManagersForUser($user->id);
                return empty($managers);
            });
        
        if ($usersWithoutManagers->isEmpty()) {
            $this->info("✅ All users already have managers assigned!");
            return self::SUCCESS;
        }
        
        $this->info("Found {$usersWithoutManagers->count()} users without managers.");
        $this->newLine();
        
        // Group by department
        $byDepartment = $usersWithoutManagers->groupBy('department_id');
        
        $this->info("Users grouped by department:");
        foreach ($byDepartment as $deptId => $users) {
            $deptName = $users->first()->department->name ?? 'Unknown';
            $this->info("  - {$deptName}: {$users->count()} users");
        }
        $this->newLine();
        
        if (!$this->confirm('Do you want to assign managers department by department?')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }
        
        $totalAssigned = 0;
        
        foreach ($byDepartment as $deptId => $users) {
            $deptName = $users->first()->department->name ?? 'Unknown';
            
            $this->newLine();
            $this->info("═══════════════════════════════════════════════════════════════");
            $this->info("Department: {$deptName} ({$users->count()} users)");
            $this->info("═══════════════════════════════════════════════════════════════");
            
            // Show users in this department
            $this->table(
                ['ID', 'Name', 'Email'],
                $users->map(fn($u) => [$u->id, $u->name, $u->email])->toArray()
            );
            
            // Get available managers
            $availableManagers = User::where('department_id', $deptId)
                ->whereHas('userLevel', function($q) {
                    $q->where('hierarchy_level', '>', 1); // L2 and above
                })
                ->get();
            
            if ($availableManagers->isEmpty()) {
                $this->warn("No managers found in this department. Skipping...");
                continue;
            }
            
            $this->info("\nAvailable managers in this department:");
            $this->table(
                ['ID', 'Name', 'Email', 'Level'],
                $availableManagers->map(fn($m) => [
                    $m->id,
                    $m->name,
                    $m->email,
                    $m->userLevel->name ?? 'Unknown'
                ])->toArray()
            );
            
            $managerId = $this->ask("Enter manager ID to assign to all users in {$deptName} (or 'skip' to skip)");
            
            if (strtolower($managerId) === 'skip') {
                $this->info("Skipped {$deptName}");
                continue;
            }
            
            $manager = User::find($managerId);
            if (!$manager) {
                $this->error("Invalid manager ID. Skipping department.");
                continue;
            }
            
            // Filter out the manager from the list of users (can't be their own manager)
            $usersToAssign = $users->filter(fn($u) => $u->id !== $manager->id);
            
            if ($usersToAssign->isEmpty()) {
                $this->warn("No users to assign (manager cannot be assigned to themselves)");
                continue;
            }
            
            if (!$this->confirm("Assign {$manager->name} as manager to {$usersToAssign->count()} users in {$deptName}?")) {
                continue;
            }
            
            $roleType = $this->choice(
                'Select role type',
                ['direct_manager', 'supervisor', 'project_manager'],
                0
            );
            
            $assigned = 0;
            $skipped = 0;
            
            foreach ($usersToAssign as $user) {
                try {
                    $this->managerService->assignManager(
                        $user->id,
                        $manager->id,
                        $roleType,
                        $deptId,
                        $manager->id // Use manager's ID as created_by
                    );
                    $this->info("  ✓ Assigned to {$user->name}");
                    $assigned++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed for {$user->name}: " . $e->getMessage());
                }
            }
            
            // Note if manager was skipped
            if ($users->count() !== $usersToAssign->count()) {
                $skipped = $users->count() - $usersToAssign->count();
                $this->info("  ⊘ Skipped {$manager->name} (cannot be their own manager)");
            }
            
            $this->info("\n✅ Assigned {$assigned} users in {$deptName} to {$manager->name}");
            $totalAssigned += $assigned;
        }
        
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║  BULK ASSIGNMENT COMPLETE                                      ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->info("Total users assigned: {$totalAssigned}");
        $this->newLine();
        
        $this->info("Run 'php artisan diagnose:manager-emails' to verify the changes.");
        
        return self::SUCCESS;
    }
}
