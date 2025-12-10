<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ManagerHierarchyService;
use Illuminate\Console\Command;

class ListAllUserManagers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:user-managers 
                            {--export= : Export to CSV file}
                            {--department= : Filter by department name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users with their managers and email addresses';

    protected ManagerHierarchyService $managerService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->managerService = app(ManagerHierarchyService::class);
        
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     ALL USERS WITH THEIR MANAGERS AND EMAIL ADDRESSES         ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        // Get users
        $query = User::where('role', '!=', 'admin')->with(['department']);
        
        if ($department = $this->option('department')) {
            $query->whereHas('department', function($q) use ($department) {
                $q->where('name', 'like', "%{$department}%");
            });
        }
        
        $users = $query->orderBy('department_id')->orderBy('name')->get();
        
        $this->info("Total users: {$users->count()}");
        $this->newLine();
        
        $data = [];
        $usersWithManagers = 0;
        $usersWithoutManagers = 0;
        $usersWithoutDepartment = 0;
        
        foreach ($users as $user) {
            $department = $user->department?->name ?? 'NO DEPARTMENT';
            
            if (!$user->department) {
                $usersWithoutDepartment++;
                $data[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'department' => $department,
                    'manager_name' => '❌ NO DEPARTMENT',
                    'manager_email' => '-',
                    'status' => 'FAILED',
                ];
                continue;
            }
            
            $managers = $this->managerService->getDirectManagersForUser($user->id);
            
            if (empty($managers)) {
                $usersWithoutManagers++;
                $data[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'department' => $department,
                    'manager_name' => '❌ NO MANAGER',
                    'manager_email' => '-',
                    'status' => 'FAILED',
                ];
            } else {
                $usersWithManagers++;
                foreach ($managers as $managerData) {
                    $manager = $managerData['manager'];
                    $data[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'department' => $department,
                        'manager_name' => '✓ ' . $manager->name,
                        'manager_email' => $manager->email,
                        'status' => 'OK',
                    ];
                }
            }
        }
        
        // Display table
        $this->table(
            ['User ID', 'User Name', 'User Email', 'Department', 'Manager Name', 'Manager Email', 'Status'],
            collect($data)->map(fn($row) => [
                $row['user_id'],
                $row['user_name'],
                $row['user_email'],
                $row['department'],
                $row['manager_name'],
                $row['manager_email'],
                $row['status'],
            ])->toArray()
        );
        
        // Summary
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║  SUMMARY                                                       ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        $this->info("Total Users: {$users->count()}");
        $this->info("✅ Users with managers: {$usersWithManagers} (" . round(($usersWithManagers/$users->count())*100, 1) . "%)");
        $this->error("❌ Users without managers: {$usersWithoutManagers} (" . round(($usersWithoutManagers/$users->count())*100, 1) . "%)");
        $this->error("❌ Users without department: {$usersWithoutDepartment}");
        $this->newLine();
        
        if ($usersWithoutManagers > 0 || $usersWithoutDepartment > 0) {
            $this->warn("⚠️  Manager emails will NOT be sent for users marked as FAILED");
            $this->info("💡 Fix: Run 'php artisan managers:assign --bulk' to assign managers");
        } else {
            $this->info("🎉 All users have managers! Manager emails will be sent correctly.");
        }
        
        $this->newLine();
        
        // Export to CSV if requested
        if ($exportFile = $this->option('export')) {
            $this->exportToCsv($data, $exportFile);
        }
        
        return self::SUCCESS;
    }

    /**
     * Export data to CSV file
     */
    protected function exportToCsv(array $data, string $filename): void
    {
        $filepath = storage_path('app/' . $filename);
        
        $fp = fopen($filepath, 'w');
        
        // Write header
        fputcsv($fp, ['User ID', 'User Name', 'User Email', 'Department', 'Manager Name', 'Manager Email', 'Status']);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($fp, [
                $row['user_id'],
                $row['user_name'],
                $row['user_email'],
                $row['department'],
                $row['manager_name'],
                $row['manager_email'],
                $row['status'],
            ]);
        }
        
        fclose($fp);
        
        $this->info("✅ Exported to: {$filepath}");
    }
}
