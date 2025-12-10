<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use App\Models\ModuleContent;
use App\Models\CourseModule;
use App\Models\CourseOnline;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for ContentViewController
 * 
 * Tests session management and progress tracking endpoints:
 * - Session start/heartbeat/end
 * - Progress updates
 * - Content completion
 */
class ContentViewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ModuleContent $content;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create course structure
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create([
            'course_online_id' => $course->id,
        ]);
        
        // Create video
        $video = Video::factory()->create([
            'storage_type' => 'local',
            'duration' => 300, // 5 minutes
        ]);
        
        // Create content
        $this->content = ModuleContent::factory()->create([
            'course_module_id' => $module->id,
            'video_id' => $video->id,
            'content_type' => 'video',
        ]);
    }

    /**
     * Test: Unauthenticated user cannot access session endpoint
     */
    public function test_unauthenticated_user_cannot_manage_session(): void
    {
        $response = $this->postJson(route('content.session', $this->content), [
            'action' => 'start',
            'position' => 0,
        ]);
        
        // JSON requests get 401, regular requests get redirect
        $this->assertTrue(in_array($response->status(), [401, 302, 403]));
    }

    /**
     * Test: User can start a learning session
     */
    public function test_user_can_start_session(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'start',
                'position' => 0,
                'api_key_id' => null,
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'session_id']);
        
        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('session_id'));
    }

    /**
     * Test: User can send heartbeat
     */
    public function test_user_can_send_heartbeat(): void
    {
        // First start a session
        $startResponse = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'start',
                'position' => 0,
            ]);
        
        $startResponse->assertStatus(200);
        
        // Send heartbeat
        $response = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'heartbeat',
                'current_position' => 60,
                'skip_count' => 1,
                'seek_count' => 2,
                'pause_count' => 0,
                'watch_time' => 60,
            ]);
        
        $response->assertStatus(200);
    }

    /**
     * Test: User can end session
     */
    public function test_user_can_end_session(): void
    {
        // First start a session
        $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'start',
                'position' => 0,
            ]);
        
        // End session
        $response = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'end',
                'current_position' => 120,
                'skip_count' => 0,
                'seek_count' => 1,
                'pause_count' => 1,
                'watch_time' => 120,
            ]);
        
        $response->assertStatus(200);
    }

    /**
     * Test: User can update progress
     */
    public function test_user_can_update_progress(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('content.progress', $this->content), [
                'current_position' => 150,
                'completion_percentage' => 50,
                'watch_time' => 3,
            ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'completion_percentage',
            ]);
    }

    /**
     * Test: Progress completion triggers at 100%
     */
    public function test_progress_completion_at_100_percent(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('content.progress', $this->content), [
                'current_position' => 300,
                'completion_percentage' => 100,
                'watch_time' => 5,
            ]);
        
        $response->assertStatus(200);
        $this->assertTrue($response->json('is_completed') || $response->json('success'));
    }

    /**
     * Test: User can mark content as complete
     */
    public function test_user_can_mark_content_complete(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('content.complete', $this->content));
        
        // Should succeed
        $response->assertStatus(200);
    }

    /**
     * Test: Multiple rapid progress updates don't fail
     */
    public function test_rapid_progress_updates_work(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson(route('content.progress', $this->content), [
                    'current_position' => $i * 30,
                    'completion_percentage' => $i * 10,
                    'watch_time' => 1,
                ]);
            
            $response->assertStatus(200);
        }
    }

    /**
     * Test: Invalid action returns error
     */
    public function test_invalid_session_action_returns_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'invalid_action',
                'position' => 0,
            ]);
        
        // Should return validation error or bad request
        $this->assertTrue(in_array($response->status(), [400, 422, 500]));
    }

    /**
     * Test: Session lifecycle works end-to-end
     */
    public function test_full_session_lifecycle(): void
    {
        // 1. Start
        $start = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'start',
                'position' => 0,
            ]);
        $start->assertStatus(200);
        
        // 2. Multiple heartbeats
        for ($i = 1; $i <= 5; $i++) {
            $heartbeat = $this->actingAs($this->user)
                ->postJson(route('content.session', $this->content), [
                    'action' => 'heartbeat',
                    'current_position' => $i * 60,
                    'skip_count' => 0,
                    'seek_count' => 0,
                    'pause_count' => 0,
                    'watch_time' => 60,
                ]);
            $heartbeat->assertStatus(200);
        }
        
        // 3. End
        $end = $this->actingAs($this->user)
            ->postJson(route('content.session', $this->content), [
                'action' => 'end',
                'current_position' => 300,
            ]);
        $end->assertStatus(200);
    }
}
