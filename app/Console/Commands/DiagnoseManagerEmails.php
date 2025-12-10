<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ManagerHierarchyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DiagnoseManagerEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:manager-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose why managers are not receiving course assignment email notifications';

    protected ManagerHierarchyService $managerService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->managerService = app(ManagerHierarchyService::class);
        
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     MANAGER EMAIL NOTIFICATION DIAGNOSTIC REPORT               ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        // Collect all diagnostic data
        $usersWithoutDepartments = $this->getUsersWithoutDepartments();
        $usersWithoutManagers = $this->getUsersWithoutManagers();
        $managerLookupResults = $this->testManagerLookup();
        $emailSimulation = $this->simulateEmailNotificationFlow();
        
        // Generate summary
        $totalUsers = User::where('role', '!=', 'admin')->count();
        
        if ($totalUsers === 0) {
            $this->warn("⚠️  No users found in database. Cannot perform diagnostic.");
            return self::SUCCESS;
        }
        
        $usersWithDepartments = $totalUsers - count($usersWithoutDepartments);
        $usersWithManagers = $totalUsers - count($usersWithoutManagers);
        
        $this->info("📊 SUMMARY STATISTICS");
        $this->info("─────────────────────────────────────────────────────────────────");
        $this->info("Total Users (non-admin): {$totalUsers}");
        $this->info("Users with Departments: {$usersWithDepartments} (" . round(($usersWithDepartments/$totalUsers)*100, 1) . "%)");
        $this->info("Users without Departments: " . count($usersWithoutDepartments) . " (" . round((count($usersWithoutDepartments)/$totalUsers)*100, 1) . "%)");
        $this->info("Users with Managers: {$usersWithManagers} (" . round(($usersWithManagers/$totalUsers)*100, 1) . "%)");
        $this->info("Users without Managers: " . count($usersWithoutManagers) . " (" . round((count($usersWithoutManagers)/$totalUsers)*100, 1) . "%)");
        $this->newLine();
        
        // Show detailed findings
        if (count($usersWithoutDepartments) > 0) {
            $this->warn("⚠️  USERS WITHOUT DEPARTMENTS (" . count($usersWithoutDepartments) . ")");
            $this->info("─────────────────────────────────────────────────────────────────");
            
            $headers = ['ID', 'Name', 'Email'];
            $rows = collect($usersWithoutDepartments)->take(20)->map(fn($user) => [
                $user['id'],
                $user['name'],
                $user['email'],
            ])->toArray();
            
            $this->table($headers, $rows);
            
            if (count($usersWithoutDepartments) > 20) {
                $this->info("... and " . (count($usersWithoutDepartments) - 20) . " more");
            }
            $this->newLine();
        }
        
        if (count($usersWithoutManagers) > 0) {
            $this->warn("⚠️  USERS WITHOUT MANAGERS (" . count($usersWithoutManagers) . ")");
            $this->info("─────────────────────────────────────────────────────────────────");
            
            $headers = ['ID', 'Name', 'Department', 'Email'];
            $rows = collect($usersWithoutManagers)->take(20)->map(fn($user) => [
                $user['id'],
                $user['name'],
                $user['department'] ?? 'N/A',
                $user['email'],
            ])->toArray();
            
            $this->table($headers, $rows);
            
            if (count($usersWithoutManagers) > 20) {
                $this->info("... and " . (count($usersWithoutManagers) - 20) . " more");
            }
            $this->newLine();
        }
        
        // Show email simulation results
        $this->info("📧 EMAIL NOTIFICATION SIMULATION");
        $this->info("─────────────────────────────────────────────────────────────────");
        $this->info("Total users: {$emailSimulation['total_users']}");
        $this->info("Users with managers: {$emailSimulation['users_with_managers']}");
        $this->info("Users without managers: {$emailSimulation['users_without_managers']}");
        $this->info("Total manager emails that would be sent: {$emailSimulation['total_manager_emails']}");
        $this->newLine();
        
        if ($emailSimulation['total_manager_emails'] > 0) {
            $this->info("Managers who would receive emails:");
            
            $headers = ['Manager Name', 'Email', 'Team Members', 'Count'];
            $rows = collect($emailSimulation['manager_email_list'])->map(function($data, $email) {
                return [
                    $data['manager_name'],
                    $email,
                    implode(', ', array_slice($data['team_members'], 0, 3)) . 
                        (count($data['team_members']) > 3 ? '...' : ''),
                    $data['team_member_count'],
                ];
            })->values()->toArray();
            
            $this->table($headers, $rows);
            $this->newLine();
        }
        
        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $usersWithoutDepartments,
            $usersWithoutManagers,
            $emailSimulation
        );
        
        if (count($recommendations) > 0) {
            $this->info("💡 RECOMMENDATIONS");
            $this->info("─────────────────────────────────────────────────────────────────");
            foreach ($recommendations as $i => $recommendation) {
                $this->line(($i + 1) . ". " . $recommendation);
            }
            $this->newLine();
        }
        
        // Final assessment
        $issuesFound = count($usersWithoutDepartments) > 0 || count($usersWithoutManagers) > 0;
        
        if ($issuesFound) {
            $this->error("⚠️  ISSUES FOUND");
            $this->info("─────────────────────────────────────────────────────────────────");
            $this->line("Manager email notifications may not be working correctly due to");
            $this->line("missing data relationships. Please review the recommendations above.");
        } else {
            $this->info("✅ NO ISSUES FOUND");
            $this->info("─────────────────────────────────────────────────────────────────");
            $this->line("All users have proper department and manager assignments.");
            $this->line("Manager email notifications should be working correctly.");
        }
        
        $this->newLine();
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        return self::SUCCESS;
    }

    /**
     * Get users without departments
     */
    protected function getUsersWithoutDepartments(): array
    {
        return User::whereNull('department_id')
            ->where('role', '!=', 'admin')
            ->get(['id', 'name', 'email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->toArray();
    }

    /**
     * Get users without managers
     */
    protected function getUsersWithoutManagers(): array
    {
        return User::where('role', '!=', 'admin')
            ->whereNotNull('department_id')
            ->with('department')
            ->get()
            ->filter(function($user) {
                $managers = $this->managerService->getDirectManagersForUser($user->id);
                return empty($managers);
            })
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department?->name,
                'department_id' => $user->department_id,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Test manager lookup for all users
     */
    protected function testManagerLookup(): array
    {
        $users = User::where('role', '!=', 'admin')
            ->whereNotNull('department_id')
            ->with(['department'])
            ->get();
        
        $results = [];
        
        foreach ($users as $user) {
            $managers = $this->managerService->getDirectManagersForUser($user->id);
            
            $results[$user->id] = [
                'user_name' => $user->name,
                'department' => $user->department?->name ?? 'Unknown',
                'managers_found' => count($managers),
                'managers' => collect($managers)->map(fn($m) => [
                    'name' => $m['manager']->name,
                    'email' => $m['manager']->email,
                    'level' => $m['level'],
                ])->toArray(),
            ];
        }
        
        return $results;
    }

    /**
     * Simulate email notification flow
     */
    protected function simulateEmailNotificationFlow(): array
    {
        $users = User::where('role', '!=', 'admin')
            ->with(['department'])
            ->get();
        
        $usersByManager = [];
        $usersWithManagers = 0;
        $usersWithoutManagers = 0;
        
        foreach ($users as $user) {
            if (!$user->department) {
                $usersWithoutManagers++;
                continue;
            }
            
            $managers = $this->managerService->getDirectManagersForUser($user->id);
            
            if (empty($managers)) {
                $usersWithoutManagers++;
                continue;
            }
            
            $usersWithManagers++;
            
            foreach ($managers as $managerData) {
                $manager = $managerData['manager'];
                $managerId = $manager->id;
                
                if (!isset($usersByManager[$managerId])) {
                    $usersByManager[$managerId] = [
                        'manager' => $manager,
                        'manager_name' => $manager->name,
                        'manager_email' => $manager->email,
                        'team_members' => [],
                    ];
                }
                
                $usersByManager[$managerId]['team_members'][] = $user->name;
            }
        }
        
        return [
            'total_users' => $users->count(),
            'users_with_managers' => $usersWithManagers,
            'users_without_managers' => $usersWithoutManagers,
            'total_manager_emails' => count($usersByManager),
            'manager_email_list' => collect($usersByManager)->mapWithKeys(function($data, $managerId) {
                return [
                    $data['manager_email'] => [
                        'manager_name' => $data['manager_name'],
                        'team_members' => $data['team_members'],
                        'team_member_count' => count($data['team_members']),
                    ]
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate recommendations
     */
    protected function generateRecommendations(
        array $usersWithoutDepartments,
        array $usersWithoutManagers,
        array $emailSimulation
    ): array {
        $recommendations = [];
        
        if (count($usersWithoutDepartments) > 0) {
            $recommendations[] = "Assign departments to " . count($usersWithoutDepartments) . " user(s) who are missing department assignments. Users without departments cannot have managers assigned.";
        }
        
        if (count($usersWithoutManagers) > 0) {
            $recommendations[] = "Assign managers to " . count($usersWithoutManagers) . " user(s) in the UserDepartmentRole table. Use the ManagerHierarchyService->assignManager() method or create records manually with is_primary=1.";
        }
        
        if ($emailSimulation['users_without_managers'] > 0) {
            $recommendations[] = "Review the UserDepartmentRole table to ensure all departments have primary managers assigned with is_primary=1 and active dates (end_date is NULL or in the future).";
        }
        
        if ($emailSimulation['total_manager_emails'] === 0 && $emailSimulation['total_users'] > 0) {
            $recommendations[] = "CRITICAL: No manager emails would be sent. This explains why managers are not receiving notifications. Fix department and manager assignments immediately.";
        }
        
        if (count($recommendations) === 0 && $emailSimulation['total_manager_emails'] > 0) {
            $recommendations[] = "Data looks good! If managers still aren't receiving emails, check your mail configuration and logs for delivery issues.";
        }
        
        return $recommendations;
    }
}
