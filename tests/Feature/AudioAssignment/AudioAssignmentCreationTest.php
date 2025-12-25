<?php

namespace Tests\Feature\AudioAssignment;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Feature: audio-assignment-system, Property 2: Assignment creation completeness**
 * 
 * For any assignment creation request with N selected users, exactly N AudioAssignment 
 * records should be created, each with assigned_by, assigned_at, and status='assigned' properly set.
 * 
 * **Validates: Requirements 2.4, 2.5**
 */
class AudioAssignmentCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Audio $audio;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for assigning
        $this->admin = User::factory()->create();
        
        // Create audio
        $this->audio = Audio::factory()->create([
            'created_by' => $this->admin->id,
            'is_active' => true,
        ]);
    }

    public function test_creates_exactly_n_assignments_for_n_selected_users(): void
    {
        // Property test: for any number of users (1-10), exactly that many assignments should be created
        $userCounts = [1, 2, 3, 5, 10];
        
        foreach ($userCounts as $count) {
            // Create N users
            $users = User::factory()->count($count)->create();
            $userIds = $users->pluck('id')->toArray();
            
            $beforeCount = AudioAssignment::count();
            
            // Create assignments for all users
            foreach ($userIds as $userId) {
                AudioAssignment::create([
                    'audio_id' => $this->audio->id,
                    'user_id' => $userId,
                    'assigned_by' => $this->admin->id,
                    'assigned_at' => now(),
                    'status' => 'assigned',
                ]);
            }
            
            $afterCount = AudioAssignment::count();
            
            // Verify exactly N new assignments were created
            $this->assertEquals($count, $afterCount - $beforeCount);
        }
    }

    public function test_sets_assigned_by_correctly_for_all_assignments(): void
    {
        $users = User::factory()->count(5)->create();
        
        foreach ($users as $user) {
            AudioAssignment::create([
                'audio_id' => $this->audio->id,
                'user_id' => $user->id,
                'assigned_by' => $this->admin->id,
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);
        }
        
        $assignments = AudioAssignment::where('audio_id', $this->audio->id)->get();
        
        // All assignments should have the correct assigned_by
        foreach ($assignments as $assignment) {
            $this->assertEquals($this->admin->id, $assignment->assigned_by);
        }
    }

    public function test_sets_assigned_at_timestamp_for_all_assignments(): void
    {
        $users = User::factory()->count(3)->create();
        $assignedAt = now();
        
        foreach ($users as $user) {
            AudioAssignment::create([
                'audio_id' => $this->audio->id,
                'user_id' => $user->id,
                'assigned_by' => $this->admin->id,
                'assigned_at' => $assignedAt,
                'status' => 'assigned',
            ]);
        }
        
        $assignments = AudioAssignment::where('audio_id', $this->audio->id)->get();
        
        // All assignments should have assigned_at set
        foreach ($assignments as $assignment) {
            $this->assertNotNull($assignment->assigned_at);
        }
    }

    public function test_sets_initial_status_as_assigned_for_all_new_assignments(): void
    {
        $users = User::factory()->count(5)->create();
        
        foreach ($users as $user) {
            AudioAssignment::create([
                'audio_id' => $this->audio->id,
                'user_id' => $user->id,
                'assigned_by' => $this->admin->id,
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);
        }
        
        $assignments = AudioAssignment::where('audio_id', $this->audio->id)->get();
        
        // All assignments should have status 'assigned'
        foreach ($assignments as $assignment) {
            $this->assertEquals('assigned', $assignment->status);
        }
    }

    public function test_enforces_unique_constraint_on_audio_id_and_user_id(): void
    {
        $user = User::factory()->create();
        
        // Create first assignment
        AudioAssignment::create([
            'audio_id' => $this->audio->id,
            'user_id' => $user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);
        
        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AudioAssignment::create([
            'audio_id' => $this->audio->id,
            'user_id' => $user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);
    }

    public function test_initializes_progress_percentage_to_zero_for_new_assignments(): void
    {
        $user = User::factory()->create();
        
        $assignment = AudioAssignment::create([
            'audio_id' => $this->audio->id,
            'user_id' => $user->id,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);
        
        $this->assertEquals(0.0, (float) $assignment->progress_percentage);
    }
}
