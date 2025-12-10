<?php

namespace App\Console\Commands;

use App\Models\CourseOnline;
use App\Models\User;
use App\Services\ManagerHierarchyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\CourseOnlineAssignmentManagerNotification;

class TestManagerEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:manager-emails 
                            {--user-ids=* : User IDs to test (comma-separated)}
                            {--department= : Department name to test all users in that department}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test manager email notifications for specific users or departments';

    protected ManagerHierarchyService $managerService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->managerService = app(ManagerHierarchyService::class);
        
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║           MANAGER EMAIL NOTIFICATION TEST                      ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        // Get users to test
        $users = $this->getUsersToTest();
        
        if ($users->isEmpty()) {
            $this->error("No users found to test.");
            return self::FAILURE;
        }
        
        $this->info("Testing {$users->count()} user(s):");
        $this->newLine();
        
        // Get a sample course for testing
        $course = CourseOnline::first();
        if (!$course) {
            $this->error("No courses found in database. Please create a course first.");
            return self::FAILURE;
        }
        
        $this->info("Using course: {$course->name}");
        $this->newLine();
        
        // Test each user
        $results = [];
        $totalManagerEmails = 0;
        
        foreach ($users as $user) {
            $this->info("─────────────────────────────────────────────────────────────────");
            $this->info("Testing User: {$user->name} (ID: {$user->id})");
            $this->info("Department: " . ($user->department?->name ?? 'NO DEPARTMENT'));
            $this->newLine();
            
            if (!$user->department) {
                $this->error("  ✗ User has no department - CANNOT send manager email");
                $results[] = [
                    'user' => $user->name,
                    'status' => 'FAILED',
                    'reason' => 'No department',
                    'managers' => 0,
                ];
                continue;
            }
            
            // Get managers
            $managers = $this->managerService->getDirectManagersForUser($user->id);
            
            if (empty($managers)) {
                $this->error("  ✗ User has no managers assigned - CANNOT send manager email");
                $results[] = [
                    'user' => $user->name,
                    'status' => 'FAILED',
                    'reason' => 'No managers assigned',
                    'managers' => 0,
                ];
                continue;
            }
            
            $this->info("  ✓ Found " . count($managers) . " manager(s):");
            
            foreach ($managers as $managerData) {
                $manager = $managerData['manager'];
                $this->line("    - {$manager->name} ({$manager->email})");
                $this->line("      Level: {$managerData['level']}");
                $this->line("      Relationship: {$managerData['relationship']}");
                
                // Test creating the email
                try {
                    $notification = new CourseOnlineAssignmentManagerNotification(
                        $course,
                        collect([$user]),
                        auth()->user() ?? $user,
                        $manager,
                        [
                            'relationship' => $managerData['relationship'],
                            'level' => $managerData['level'],
                        ]
                    );
                    
                    $this->info("      ✓ Email object created successfully");
                    $totalManagerEmails++;
                    
                } catch (\Exception $e) {
                    $this->error("      ✗ Failed to create email: " . $e->getMessage());
                }
            }
            
            $results[] = [
                'user' => $user->name,
                'status' => 'SUCCESS',
                'reason' => 'Managers found',
                'managers' => count($managers),
            ];
            
            $this->newLine();
        }
        
        // Summary
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║  TEST SUMMARY                                                  ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝");
        $this->newLine();
        
        $this->table(
            ['User', 'Status', 'Reason', 'Managers'],
            collect($results)->map(fn($r) => [
                $r['user'],
                $r['status'],
                $r['reason'],
                $r['managers'],
            ])->toArray()
        );
        
        $this->newLine();
        $this->info("Total manager emails that would be sent: {$totalManagerEmails}");
        $this->newLine();
        
        $successCount = collect($results)->where('status', 'SUCCESS')->count();
        $failCount = collect($results)->where('status', 'FAILED')->count();
        
        if ($failCount > 0) {
            $this->warn("⚠️  {$failCount} user(s) failed - managers will NOT receive emails for these users");
            $this->info("💡 Fix: Assign departments and managers to failed users");
        }
        
        if ($successCount > 0) {
            $this->info("✅ {$successCount} user(s) passed - managers WILL receive emails");
        }
        
        $this->newLine();
        
        return self::SUCCESS;
    }

    /**
     * Get users to test based on options
     */
    protected function getUsersToTest()
    {
        $userIds = $this->option('user-ids');
        $department = $this->option('department');
        
        if (!empty($userIds)) {
            // Test specific user IDs
            return User::whereIn('id', $userIds)
                ->with(['department'])
                ->get();
        }
        
        if ($department) {
            // Test all users in a department
            return User::whereHas('department', function($q) use ($department) {
                $q->where('name', 'like', "%{$department}%");
            })
            ->with(['department'])
            ->get();
        }
        
        // Interactive mode - ask user
        $this->info("Select test mode:");
        $mode = $this->choice(
            'How would you like to test?',
            [
                'Test specific user IDs',
                'Test all users in a department',
                'Test a random sample of users',
            ],
            0
        );
        
        if ($mode === 'Test specific user IDs') {
            $ids = $this->ask('Enter user IDs (comma-separated)');
            $userIds = array_map('trim', explode(',', $ids));
            return User::whereIn('id', $userIds)->with(['department'])->get();
        }
        
        if ($mode === 'Test all users in a department') {
            $deptName = $this->ask('Enter department name (partial match OK)');
            return User::whereHas('department', function($q) use ($deptName) {
                $q->where('name', 'like', "%{$deptName}%");
            })
            ->with(['department'])
            ->get();
        }
        
        // Random sample
        return User::where('role', '!=', 'admin')
            ->with(['department'])
            ->inRandomOrder()
            ->limit(5)
            ->get();
    }
}
