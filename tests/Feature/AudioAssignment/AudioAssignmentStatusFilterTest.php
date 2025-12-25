<?php

namespace Tests\Feature\AudioAssignment;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 10: Assignment status filter correctness**
 * 
 * For any status filter on assignments, all returned assignments should have the matching status value.
 * 
 * **Validates: Requirements 6.2**
 */
class AudioAssignmentStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Audio $audio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->audio = Audio::factory()->create(['created_by' => $this->admin->id]);
    }

    public function test_scope_pending_returns_only_assigned_status(): void
    {
        // Create assignments with different statuses
        AudioAssignment::factory()->count(3)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
        ]);
        AudioAssignment::factory()->count(2)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);
        AudioAssignment::factory()->count(1)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        $pendingAssignments = AudioAssignment::pending()->get();
        
        // All returned assignments should have status 'assigned'
        foreach ($pendingAssignments as $assignment) {
            $this->assertEquals('assigned', $assignment->status);
        }
        
        $this->assertCount(3, $pendingAssignments);
    }

    public function test_scope_in_progress_returns_only_in_progress_status(): void
    {
        AudioAssignment::factory()->count(2)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
        ]);
        AudioAssignment::factory()->count(4)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);
        AudioAssignment::factory()->count(1)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        $inProgressAssignments = AudioAssignment::inProgress()->get();
        
        foreach ($inProgressAssignments as $assignment) {
            $this->assertEquals('in_progress', $assignment->status);
        }
        
        $this->assertCount(4, $inProgressAssignments);
    }

    public function test_scope_completed_returns_only_completed_status(): void
    {
        AudioAssignment::factory()->count(2)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
        ]);
        AudioAssignment::factory()->count(1)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);
        AudioAssignment::factory()->count(5)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        $completedAssignments = AudioAssignment::completed()->get();
        
        foreach ($completedAssignments as $assignment) {
            $this->assertEquals('completed', $assignment->status);
        }
        
        $this->assertCount(5, $completedAssignments);
    }

    public function test_status_filter_query_returns_correct_results(): void
    {
        AudioAssignment::factory()->count(2)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
        ]);
        AudioAssignment::factory()->count(3)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);
        AudioAssignment::factory()->count(1)->create([
            'audio_id' => $this->audio->id,
            'assigned_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        // Test filtering by status using where clause (as used in controller)
        $assignedResults = AudioAssignment::where('status', 'assigned')->get();
        $inProgressResults = AudioAssignment::where('status', 'in_progress')->get();
        $completedResults = AudioAssignment::where('status', 'completed')->get();
        
        $this->assertCount(2, $assignedResults);
        $this->assertCount(3, $inProgressResults);
        $this->assertCount(1, $completedResults);
        
        // Verify all results have correct status
        foreach ($assignedResults as $assignment) {
            $this->assertEquals('assigned', $assignment->status);
        }
        foreach ($inProgressResults as $assignment) {
            $this->assertEquals('in_progress', $assignment->status);
        }
        foreach ($completedResults as $assignment) {
            $this->assertEquals('completed', $assignment->status);
        }
    }

    public function test_status_filter_property_for_all_statuses(): void
    {
        $statuses = ['assigned', 'in_progress', 'completed'];
        
        // Create assignments for each status
        foreach ($statuses as $status) {
            AudioAssignment::factory()->count(rand(2, 5))->create([
                'audio_id' => $this->audio->id,
                'assigned_by' => $this->admin->id,
                'status' => $status,
            ]);
        }

        // Property test: for any status filter, all returned assignments should have that status
        foreach ($statuses as $filterStatus) {
            $filteredAssignments = AudioAssignment::where('status', $filterStatus)->get();
            
            foreach ($filteredAssignments as $assignment) {
                $this->assertEquals(
                    $filterStatus, 
                    $assignment->status,
                    "Expected status '{$filterStatus}' but got '{$assignment->status}'"
                );
            }
            
            // Verify count is greater than 0
            $this->assertGreaterThan(0, $filteredAssignments->count());
        }
    }

    /**
     * Property-based test: Assignment status filter correctness
     * 
     * For any status filter on assignments, all returned assignments should have
     * the matching status value across many random scenarios.
     */
    public function test_property_status_filter_always_returns_correct_status(): void
    {
        $statuses = ['assigned', 'in_progress', 'completed'];
        
        // Run property test with 100 iterations
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up all assignments from previous iteration
            AudioAssignment::query()->delete();

            // Create random number of assignments for each status (1-10 per status)
            $assignmentCounts = [];
            foreach ($statuses as $status) {
                $count = rand(1, 10);
                $assignmentCounts[$status] = $count;
                
                AudioAssignment::factory()->count($count)->create([
                    'audio_id' => $this->audio->id,
                    'assigned_by' => $this->admin->id,
                    'status' => $status,
                ]);
            }

            // Property: For each status filter, all returned assignments must have that exact status
            foreach ($statuses as $filterStatus) {
                $filteredAssignments = AudioAssignment::where('status', $filterStatus)->get();
                
                // Property 1: Count must match what we created
                $this->assertEquals(
                    $assignmentCounts[$filterStatus],
                    $filteredAssignments->count(),
                    "Iteration {$iteration}: Expected {$assignmentCounts[$filterStatus]} assignments with status '{$filterStatus}' but got {$filteredAssignments->count()}"
                );
                
                // Property 2: Every assignment must have the filtered status
                foreach ($filteredAssignments as $assignment) {
                    $this->assertEquals(
                        $filterStatus,
                        $assignment->status,
                        "Iteration {$iteration}: Expected status '{$filterStatus}' but got '{$assignment->status}'"
                    );
                }
                
                // Property 3: No assignment should have a different status
                $wrongStatusCount = $filteredAssignments->filter(function($assignment) use ($filterStatus) {
                    return $assignment->status !== $filterStatus;
                })->count();
                
                $this->assertEquals(0, $wrongStatusCount, "Iteration {$iteration}: Found assignments with wrong status in filtered results");
            }
        }
    }
}
