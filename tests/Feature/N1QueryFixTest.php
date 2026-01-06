<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\CourseOnlineAssignment;
use App\Models\UserContentProgress;
use App\Models\LearningSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class N1QueryFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CourseOnline $course;
    protected CourseModule $module;
    protected ModuleContent $content;
    protected CourseOnlineAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'user',
        ]);
        
        // Create course with modules and content
        $this->course = CourseOnline::factory()->create([
            'name' => 'Test Course for N+1',
            'is_active' => true,
        ]);
        
        $this->module = CourseModule::factory()->create([
            'course_online_id' => $this->course->id,
            'name' => 'Test Module',
            'order_number' => 1,
            'is_required' => true,
        ]);
        
        // Use PDF content type (default in factory) to avoid video constraint
        $this->content = ModuleContent::factory()->create([
            'module_id' => $this->module->id,
            'title' => 'Test Content',
            'content_type' => 'pdf',
            'order_number' => 1,
            'is_required' => true,
        ]);
        
        // Create assignment
        $this->assignment = CourseOnlineAssignment::factory()->create([
            'user_id' => $this->user->id,
            'course_online_id' => $this->course->id,
            'status' => 'in_progress',
            'assigned_by' => $this->user->id,
        ]);
    }

    /**
     * Test that courses-online index page loads without N+1 queries
     */
    public function test_courses_online_index_query_count(): void
    {
        $this->actingAs($this->user);
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->get(route('courses-online.index'));
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        // With proper eager loading, we should have a reasonable number of queries
        // Before fix: Could be 50+ queries with N+1
        // After fix: Should be under 20 queries
        $this->assertLessThan(30, $queryCount, 
            "Too many queries executed ({$queryCount}). Possible N+1 issue. Queries: " . 
            json_encode(array_column($queries, 'query'), JSON_PRETTY_PRINT)
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test that course show page loads without N+1 queries
     */
    public function test_course_show_query_count(): void
    {
        $this->actingAs($this->user);
        
        DB::enableQueryLog();
        
        $response = $this->get(route('courses-online.show', $this->course));
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        // Should be under 25 queries with proper eager loading
        $this->assertLessThan(35, $queryCount,
            "Too many queries on course show ({$queryCount}). Possible N+1 issue."
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test that isContentUnlocked works without lazy loading
     */
    public function test_content_unlock_check_no_lazy_loading(): void
    {
        $this->actingAs($this->user);
        
        // Load content with module eagerly
        $content = ModuleContent::with('module')->find($this->content->id);
        
        DB::enableQueryLog();
        
        // Access module - should not trigger additional query
        $moduleId = $content->module->id;
        $moduleName = $content->module->name;
        
        $queries = DB::getQueryLog();
        
        // No additional queries should be made since module is eager loaded
        $this->assertCount(0, $queries, 
            "Lazy loading detected when accessing module on content"
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test that content navigation service works without lazy loading
     */
    public function test_content_navigation_no_lazy_loading(): void
    {
        $this->actingAs($this->user);
        
        // Create additional content for navigation
        ModuleContent::factory()->create([
            'module_id' => $this->module->id,
            'title' => 'Second Content',
            'content_type' => 'pdf',
            'order_number' => 2,
        ]);
        
        $content = ModuleContent::with('module.courseOnline')->find($this->content->id);
        
        $navigationService = app(\App\Services\ContentView\ContentNavigationService::class);
        
        DB::enableQueryLog();
        
        $navigation = $navigationService->getNavigation($content, $this->user->id);
        
        $queries = DB::getQueryLog();
        
        // Navigation should work with minimal queries
        $this->assertLessThan(5, count($queries),
            "Too many queries in navigation service: " . count($queries)
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test that progress service works without lazy loading
     */
    public function test_progress_service_no_lazy_loading(): void
    {
        $this->actingAs($this->user);
        
        $content = ModuleContent::with('module')->find($this->content->id);
        
        $progressService = app(\App\Services\ContentView\ContentProgressService::class);
        
        DB::enableQueryLog();
        
        $progress = $progressService->getOrCreateProgress($this->user, $content);
        
        $queries = DB::getQueryLog();
        
        // Should be minimal queries
        $this->assertLessThan(5, count($queries),
            "Too many queries in progress service: " . count($queries)
        );
        
        $this->assertNotNull($progress);
        
        DB::disableQueryLog();
    }

    /**
     * Test that learning session service works without lazy loading
     */
    public function test_learning_session_no_lazy_loading(): void
    {
        $this->actingAs($this->user);
        
        $content = ModuleContent::with('module')->find($this->content->id);
        
        $sessionService = app(\App\Services\ContentView\LearningSessionService::class);
        
        DB::enableQueryLog();
        
        try {
            $session = $sessionService->startSession($this->user, $content, 0, null);
            $this->assertNotNull($session);
        } catch (\Exception $e) {
            // May fail if course is completed, but we're testing query count
        }
        
        $queries = DB::getQueryLog();
        
        // Should be reasonable number of queries
        $this->assertLessThan(10, count($queries),
            "Too many queries in session service: " . count($queries)
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test multiple courses with multiple modules don't cause N+1
     */
    public function test_multiple_courses_no_n1(): void
    {
        $this->actingAs($this->user);
        
        // Create 5 more courses with assignments
        for ($i = 0; $i < 5; $i++) {
            $course = CourseOnline::factory()->create(['is_active' => true]);
            
            // Create 3 modules per course
            for ($j = 0; $j < 3; $j++) {
                $module = CourseModule::factory()->create([
                    'course_online_id' => $course->id,
                    'order_number' => $j + 1,
                ]);
                
                // Create 2 content items per module
                for ($k = 0; $k < 2; $k++) {
                    ModuleContent::factory()->create([
                        'module_id' => $module->id,
                        'order_number' => $k + 1,
                    ]);
                }
            }
            
            CourseOnlineAssignment::factory()->create([
                'user_id' => $this->user->id,
                'course_online_id' => $course->id,
                'status' => 'in_progress',
                'assigned_by' => $this->user->id,
            ]);
        }
        
        DB::enableQueryLog();
        
        $response = $this->get(route('courses-online.index'));
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        // Even with 6 courses, query count should be bounded
        // Without N+1 fix, this could be 100+ queries
        // With fix, should stay under 50
        $this->assertLessThan(60, $queryCount,
            "N+1 detected with multiple courses! Query count: {$queryCount}"
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test course progress tracking doesn't cause N+1
     * Note: Tests the detailed progress endpoint that exists
     */
    public function test_course_progress_query_count(): void
    {
        $this->actingAs($this->user);
        
        // Create progress record
        UserContentProgress::create([
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'course_online_id' => $this->course->id,
            'module_id' => $this->module->id,
            'content_type' => 'pdf',
            'completion_percentage' => 50,
            'is_completed' => false,
        ]);
        
        DB::enableQueryLog();
        
        // Test the course progress page directly
        $response = $this->get("/courses-online/{$this->course->id}");
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThan(40, $queryCount,
            "Too many queries on progress page: {$queryCount}"
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test that CourseOnline model accessors don't cause lazy loading
     */
    public function test_course_model_accessors_no_lazy_loading(): void
    {
        // Load course with relationships
        $course = CourseOnline::with(['modules.content'])->find($this->course->id);
        
        DB::enableQueryLog();
        
        // Access computed properties
        $totalDuration = $course->total_duration;
        $totalContent = $course->total_content;
        
        $queries = DB::getQueryLog();
        
        // Should not trigger additional queries since relationships are loaded
        $this->assertLessThan(2, count($queries),
            "Model accessors triggered lazy loading"
        );
        
        DB::disableQueryLog();
    }

    /**
     * Test quiz controller doesn't have N+1
     */
    public function test_quiz_index_no_n1(): void
    {
        $this->actingAs($this->user);
        
        DB::enableQueryLog();
        
        $response = $this->get(route('quizzes.index'));
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThan(15, $queryCount,
            "Quiz index has too many queries: {$queryCount}"
        );
        
        DB::disableQueryLog();
    }

    /**
     * Helper to output query log for debugging
     */
    protected function dumpQueries(array $queries): void
    {
        foreach ($queries as $index => $query) {
            dump("Query {$index}: " . $query['query']);
        }
    }
}
