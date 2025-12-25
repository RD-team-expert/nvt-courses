<?php

namespace Tests\Feature\AudioAssignment;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 3: User filter correctness**
 * 
 * For any department filter or name search query, all returned users should match 
 * the filter criteria (belong to the department or have names containing the search term).
 * 
 * **Validates: Requirements 2.2, 2.3**
 */
class AudioAssignmentFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_filter_by_department_returns_only_users_in_that_department(): void
    {
        $department1 = Department::factory()->create(['name' => 'Engineering']);
        $department2 = Department::factory()->create(['name' => 'Marketing']);

        // Create users in different departments
        $engineeringUsers = User::factory()->count(3)->create([
            'department_id' => $department1->id,
            'role' => 'user',
        ]);
        
        $marketingUsers = User::factory()->count(2)->create([
            'department_id' => $department2->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/audio-assignments/users?department_id={$department1->id}");

        $response->assertStatus(200);
        
        $returnedUsers = collect($response->json('data'));
        
        // All returned users should be in the Engineering department
        foreach ($returnedUsers as $user) {
            $this->assertEquals($department1->id, $user['department_id']);
        }
        
        // Should have exactly 3 users
        $this->assertCount(3, $returnedUsers);
    }

    public function test_filter_by_name_returns_only_matching_users(): void
    {
        $department = Department::factory()->create();
        
        User::factory()->create(['name' => 'John Smith', 'role' => 'user', 'department_id' => $department->id]);
        User::factory()->create(['name' => 'John Doe', 'role' => 'user', 'department_id' => $department->id]);
        User::factory()->create(['name' => 'Jane Smith', 'role' => 'user', 'department_id' => $department->id]);
        User::factory()->create(['name' => 'Bob Wilson', 'role' => 'user', 'department_id' => $department->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audio-assignments/users?search=John');

        $response->assertStatus(200);
        
        $returnedUsers = collect($response->json('data'));
        
        // All returned users should have "John" in their name
        foreach ($returnedUsers as $user) {
            $this->assertStringContainsStringIgnoringCase('John', $user['name']);
        }
        
        // Should have exactly 2 users (John Smith and John Doe)
        $this->assertCount(2, $returnedUsers);
    }

    public function test_filter_by_email_returns_matching_users(): void
    {
        $department = Department::factory()->create();
        
        User::factory()->create(['email' => 'john@example.com', 'role' => 'user', 'department_id' => $department->id]);
        User::factory()->create(['email' => 'john.doe@company.com', 'role' => 'user', 'department_id' => $department->id]);
        User::factory()->create(['email' => 'jane@example.com', 'role' => 'user', 'department_id' => $department->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audio-assignments/users?search=john');

        $response->assertStatus(200);
        
        $returnedUsers = collect($response->json('data'));
        
        // All returned users should have "john" in their email
        foreach ($returnedUsers as $user) {
            $this->assertTrue(
                str_contains(strtolower($user['name']), 'john') || 
                str_contains(strtolower($user['email']), 'john')
            );
        }
    }

    public function test_combined_department_and_name_filter(): void
    {
        $department = Department::factory()->create(['name' => 'Sales']);
        $otherDepartment = Department::factory()->create(['name' => 'HR']);

        User::factory()->create([
            'name' => 'John Sales',
            'department_id' => $department->id,
            'role' => 'user',
        ]);
        User::factory()->create([
            'name' => 'John HR',
            'department_id' => $otherDepartment->id,
            'role' => 'user',
        ]);
        User::factory()->create([
            'name' => 'Jane Sales',
            'department_id' => $department->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/audio-assignments/users?department_id={$department->id}&search=John");

        $response->assertStatus(200);
        
        $returnedUsers = collect($response->json('data'));
        
        // Should only return John Sales (in Sales department AND name contains John)
        $this->assertCount(1, $returnedUsers);
        $this->assertEquals('John Sales', $returnedUsers->first()['name']);
        $this->assertEquals($department->id, $returnedUsers->first()['department_id']);
    }

    public function test_filter_excludes_admin_users(): void
    {
        $department = Department::factory()->create();
        
        User::factory()->create([
            'name' => 'Admin User',
            'department_id' => $department->id,
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Regular User',
            'department_id' => $department->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/audio-assignments/users?department_id={$department->id}");

        $response->assertStatus(200);
        
        $returnedUsers = collect($response->json('data'));
        
        // Should only return non-admin users
        $this->assertCount(1, $returnedUsers);
        $this->assertEquals('Regular User', $returnedUsers->first()['name']);
    }

    public function test_empty_search_returns_all_non_admin_users(): void
    {
        $department = Department::factory()->create();
        
        User::factory()->count(5)->create([
            'department_id' => $department->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audio-assignments/users');

        $response->assertStatus(200);
        
        $returnedUsers = collect($response->json('data'));
        
        // Should return all 5 non-admin users
        $this->assertCount(5, $returnedUsers);
    }

    public function test_filter_returns_department_info(): void
    {
        $department = Department::factory()->create(['name' => 'Engineering']);
        
        User::factory()->create([
            'name' => 'Test User',
            'department_id' => $department->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audio-assignments/users');

        $response->assertStatus(200);
        
        $user = collect($response->json('data'))->first();
        
        $this->assertEquals($department->id, $user['department_id']);
        $this->assertEquals('Engineering', $user['department_name']);
    }

    /**
     * Property-based test: User filter correctness
     * 
     * For any department filter or name search query, all returned users should match
     * the filter criteria and exclude admin users.
     */
    public function test_property_user_filter_returns_only_matching_users(): void
    {
        // Run property test with 50 iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Reset database for each iteration
            $this->refreshDatabase();
            $this->admin = User::factory()->create(['role' => 'admin']);

            // Create random departments (2-4)
            $departmentCount = rand(2, 4);
            $departments = Department::factory()->count($departmentCount)->create();

            // Create random users in various departments (5-15 users)
            $userCount = rand(5, 15);
            $users = User::factory()->count($userCount)->create([
                'role' => 'user',
            ])->each(function($user) use ($departments) {
                $user->update([
                    'department_id' => $departments->random()->id,
                ]);
            });

            // Create some admin users that should be excluded
            User::factory()->count(rand(1, 3))->create(['role' => 'admin']);

            // Test 1: Filter by random department
            $randomDepartment = $departments->random();
            $response = $this->actingAs($this->admin)
                ->getJson("/api/audio-assignments/users?department_id={$randomDepartment->id}");

            $response->assertStatus(200);
            $returnedUsers = collect($response->json('data'));

            // Property: All returned users must belong to the selected department
            foreach ($returnedUsers as $user) {
                $this->assertEquals($randomDepartment->id, $user['department_id']);
            }

            // Property: No admin users should be returned
            $adminCount = $returnedUsers->filter(fn($u) => 
                User::find($u['id'])->role === 'admin'
            )->count();
            $this->assertEquals(0, $adminCount);

            // Test 2: Filter by random search term (if users exist)
            if ($users->count() > 0) {
                $randomUser = $users->random();
                $searchTerm = substr($randomUser->name, 0, 3); // First 3 chars of name

                $response = $this->actingAs($this->admin)
                    ->getJson("/api/audio-assignments/users?search={$searchTerm}");

                $response->assertStatus(200);
                $returnedUsers = collect($response->json('data'));

                // Property: All returned users must have the search term in name or email
                foreach ($returnedUsers as $user) {
                    $matchesName = str_contains(strtolower($user['name']), strtolower($searchTerm));
                    $matchesEmail = str_contains(strtolower($user['email']), strtolower($searchTerm));
                    $this->assertTrue($matchesName || $matchesEmail);
                }
            }
        }
    }
}
