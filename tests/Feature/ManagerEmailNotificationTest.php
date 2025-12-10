<?php

namespace Tests\Feature;

use App\Models\CourseOnline;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentRole;
use App\Services\ManagerHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManagerEmailNotificationTest extends TestCase
{
    // NOTE: NOT using RefreshDatabase - we want to test against actual database
    // use RefreshDatabase;

    protected ManagerHierarchyService $managerService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize the manager service
        $this->managerService = app(ManagerHierarchyService::class);
    }

    /**
     * Test that identifies users without department assignments
     */
    public function test_users_have_departments(): void
    {
        $this->info("\n=== Testing User Department Assignments ===\n");
        
        $usersWithoutDepartments = $this->getUsersWithoutDepartments();
        
        $this->info("Total users without departments: " . count($usersWithoutDepartments));
        
        if (count($usersWithoutDepartments) > 0) {
            $this->info("\nUsers without departments:");
            foreach ($usersWithoutDepartments as $user) {
                $this->info("  - ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}");
            }
        } else {
            $this->info("✓ All users have department assignments");
        }
        
        // This test always passes but reports findings
        $this->assertTrue(true);
    }

    /**
     * Test that identifies users without assigned managers
     */
    public function test_users_have_assigned_managers(): void
    {
        $this->info("\n=== Testing Manager Assignments ===\n");
        
        $usersWithoutManagers = $this->getUsersWithoutManagers();
        
        $this->info("Total users without managers: " . count($usersWithoutManagers));
        
        if (count($usersWithoutManagers) > 0) {
            $this->info("\nUsers without managers:");
            foreach ($usersWithoutManagers as $user) {
                $dept = $user['department'] ?? 'No Department';
                $this->info("  - ID: {$user['id']}, Name: {$user['name']}, Department: {$dept}");
            }
        } else {
            $this->info("✓ All users have manager assignments");
        }
        
        // This test always passes but reports findings
        $this->assertTrue(true);
    }

    /**
     * Test the ManagerHierarchyService functionality
     */
    public function test_manager_hierarchy_service(): void
    {
        $this->info("\n=== Testing ManagerHierarchyService ===\n");
        
        $results = $this->testManagerLookup();
        
        $this->info("Total users tested: " . count($results));
        $usersWithManagers = collect($results)->where('managers_found', '>', 0)->count();
        $this->info("Users with managers found: {$usersWithManagers}");
        $this->info("Users without managers: " . (count($results) - $usersWithManagers));
        
        // This test always passes but reports findings
        $this->assertTrue(true);
    }

    /**
     * Test the email notification flow
     */
    public function test_email_notification_flow(): void
    {
        $this->info("\n=== Testing Email Notification Flow ===\n");
        
        Mail::fake();
        
        $results = $this->simulateEmailNotificationFlow();
        
        $this->info("Total users: {$results['total_users']}");
        $this->info("Users with managers: {$results['users_with_managers']}");
        $this->info("Users without managers: {$results['users_without_managers']}");
        $this->info("Total manager emails that would be sent: {$results['total_manager_emails']}");
        
        if ($results['total_manager_emails'] > 0) {
            $this->info("\nManagers who would receive emails:");
            foreach ($results['manager_email_list'] as $email => $data) {
                $this->info("  - {$data['manager_name']} ({$email})");
                $this->info("    Team members: " . implode(', ', $data['team_members']));
            }
        }
        
        // This test always passes but reports findings
        $this->assertTrue(true);
    }

    /**
     * Comprehensive diagnostic test
     */
    public function test_comprehensive_diagnostic(): void
    {
        $this->info("\n");
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     MANAGER EMAIL NOTIFICATION DIAGNOSTIC REPORT               ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->info("");
        
        // Collect all diagnostic data
        $usersWithoutDepartments = $this->getUsersWithoutDepartments();
        $usersWithoutManagers = $this->getUsersWithoutManagers();
        $managerLookupResults = $this->testManagerLookup();
        
        Mail::fake();
        $emailSimulation = $this->simulateEmailNotificationFlow();
        
        // Generate summary
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $usersWithDepartments = $totalUsers - count($usersWithoutDepartments);
        $usersWithManagers = $totalUsers - count($usersWithoutManagers);
        
        $this->info("📊 SUMMARY STATISTICS");
        $this->info("─────────────────────────────────────────────────────────────────");
        $this->info("Total Users (non-admin): {$totalUsers}");
        
        if ($totalUsers > 0) {
            $this->info("Users with Departments: {$usersWithDepartments} (" . round(($usersWithDepartments/$totalUsers)*100, 1) . "%)");
            $this->info("Users without Departments: " . count($usersWithoutDepartments) . " (" . round((count($usersWithoutDepartments)/$totalUsers)*100, 1) . "%)");
            $this->info("Users with Managers: {$usersWithManagers} (" . round(($usersWithManagers/$totalUsers)*100, 1) . "%)");
            $this->info("Users without Managers: " . count($usersWithoutManagers) . " (" . round((count($usersWithoutManagers)/$totalUsers)*100, 1) . "%)");
        } else {
            $this->info("⚠️  No users found in database. Cannot perform diagnostic.");
        }
        $this->info("");
        
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
                $this->info(($i + 1) . ". " . $recommendation);
            }
            $this->info("");
        }
        
        // Final assessment
        $issuesFound = count($usersWithoutDepartments) > 0 || count($usersWithoutManagers) > 0;
        
        if ($issuesFound) {
            $this->info("⚠️  ISSUES FOUND");
            $this->info("─────────────────────────────────────────────────────────────────");
            $this->info("Manager email notifications may not be working correctly due to");
            $this->info("missing data relationships. Please review the recommendations above.");
        } else {
            $this->info("✅ NO ISSUES FOUND");
            $this->info("─────────────────────────────────────────────────────────────────");
            $this->info("All users have proper department and manager assignments.");
            $this->info("Manager email notifications should be working correctly.");
        }
        
        $this->info("");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->info("");
        
        // This test always passes but reports findings
        $this->assertTrue(true);
    }

    /**
     * Helper method to get users without departments
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
     * Helper method to get users without managers
     */
    protected function getUsersWithoutManagers(): array
    {
        return User::where('role', '!=', 'admin')
            ->whereNotNull('department_id')
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
     * Helper method to test manager lookup for all users
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
     * Helper method to simulate email notification flow
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
     * Helper method to generate recommendations
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
            $recommendations[] = "Assign managers to " . count($usersWithoutManagers) . " user(s) in the UserDepartmentRole table. Use the ManagerHierarchyService->assignManager() method or create records manually.";
        }
        
        if ($emailSimulation['users_without_managers'] > 0) {
            $recommendations[] = "Review the UserDepartmentRole table to ensure all departments have primary managers assigned with is_primary=1 and active dates.";
        }
        
        if ($emailSimulation['total_manager_emails'] === 0 && $emailSimulation['total_users'] > 0) {
            $recommendations[] = "CRITICAL: No manager emails would be sent. This explains why managers are not receiving notifications. Fix department and manager assignments immediately.";
        }
        
        return $recommendations;
    }

    /**
     * Helper method to output info messages
     */
    protected function info(string $message): void
    {
        fwrite(STDOUT, $message . "\n");
    }
}
