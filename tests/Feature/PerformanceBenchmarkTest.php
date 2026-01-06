<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\CourseOnlineAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $courses = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'user']);
        
        // Create realistic data volume
        $this->createTestData();
    }

    /**
     * Create test data with realistic volume
     */
    protected function createTestData(): void
    {
        // Create 10 courses with varying modules and content
        for ($c = 0; $c < 10; $c++) {
            $course = CourseOnline::factory()->create([
                'name' => "Performance Test Course {$c}",
                'is_active' => true,
            ]);
            
            $this->courses[] = $course;
            
            // Create 3-5 modules per course
            $moduleCount = rand(3, 5);
            for ($m = 0; $m < $moduleCount; $m++) {
                $module = CourseModule::factory()->create([
                    'course_online_id' => $course->id,
                    'name' => "Module {$m}",
                    'order_number' => $m + 1,
                    'is_required' => true,
                ]);
                
                // Create 2-4 content items per module
                $contentCount = rand(2, 4);
                for ($content = 0; $content < $contentCount; $content++) {
                    ModuleContent::factory()->create([
                        'module_id' => $module->id,
                        'title' => "Content {$content}",
                        'content_type' => ['video', 'pdf', 'text'][rand(0, 2)],
                        'order_number' => $content + 1,
                        'is_required' => true,
                    ]);
                }
            }
            
            // Assign 70% of courses to user
            if (rand(1, 10) <= 7) {
                CourseOnlineAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'course_online_id' => $course->id,
                    'status' => ['assigned', 'in_progress', 'completed'][rand(0, 2)],
                    'assigned_by' => $this->user->id,
                ]);
            }
        }
    }

    /**
     * Benchmark courses index page performance
     */
    public function test_courses_index_performance(): void
    {
        $this->actingAs($this->user);
        
        $iterations = 5;
        $times = [];
        $queryCounts = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            DB::enableQueryLog();
            
            $start = microtime(true);
            $response = $this->get(route('courses-online.index'));
            $end = microtime(true);
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            $times[] = ($end - $start) * 1000; // Convert to ms
            $queryCounts[] = count($queries);
            
            $response->assertStatus(200);
        }
        
        $avgTime = array_sum($times) / count($times);
        $avgQueries = array_sum($queryCounts) / count($queryCounts);
        
        echo "\n=== Courses Index Performance ===\n";
        echo "Average Response Time: " . round($avgTime, 2) . "ms\n";
        echo "Average Query Count: " . round($avgQueries, 1) . "\n";
        echo "Min/Max Time: " . round(min($times), 2) . "ms / " . round(max($times), 2) . "ms\n";
        
        // Performance assertions
        $this->assertLessThan(500, $avgTime, "Average response time too high: {$avgTime}ms");
        $this->assertLessThan(40, $avgQueries, "Too many queries: {$avgQueries}");
    }

    /**
     * Benchmark course show page performance
     */
    public function test_course_show_performance(): void
    {
        $this->actingAs($this->user);
        
        // Get a course with assignment
        $course = $this->courses[0];
        
        $iterations = 5;
        $times = [];
        $queryCounts = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            DB::enableQueryLog();
            
            $start = microtime(true);
            $response = $this->get(route('courses-online.show', $course));
            $end = microtime(true);
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            $times[] = ($end - $start) * 1000;
            $queryCounts[] = count($queries);
        }
        
        $avgTime = array_sum($times) / count($times);
        $avgQueries = array_sum($queryCounts) / count($queryCounts);
        
        echo "\n=== Course Show Performance ===\n";
        echo "Average Response Time: " . round($avgTime, 2) . "ms\n";
        echo "Average Query Count: " . round($avgQueries, 1) . "\n";
        
        // Performance assertions
        $this->assertLessThan(400, $avgTime, "Average response time too high: {$avgTime}ms");
        $this->assertLessThan(35, $avgQueries, "Too many queries: {$avgQueries}");
    }

    /**
     * Test query count scales linearly with data
     */
    public function test_query_count_scaling(): void
    {
        $this->actingAs($this->user);
        
        // Measure queries with current data
        DB::enableQueryLog();
        $this->get(route('courses-online.index'));
        $baseQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        
        // Add 5 more courses
        for ($i = 0; $i < 5; $i++) {
            $course = CourseOnline::factory()->create(['is_active' => true]);
            
            for ($m = 0; $m < 3; $m++) {
                $module = CourseModule::factory()->create([
                    'course_online_id' => $course->id,
                    'order_number' => $m + 1,
                ]);
                
                for ($c = 0; $c < 3; $c++) {
                    ModuleContent::factory()->create([
                        'module_id' => $module->id,
                        'order_number' => $c + 1,
                    ]);
                }
            }
            
            CourseOnlineAssignment::factory()->create([
                'user_id' => $this->user->id,
                'course_online_id' => $course->id,
                'assigned_by' => $this->user->id,
            ]);
        }
        
        // Measure queries after adding data
        DB::enableQueryLog();
        $this->get(route('courses-online.index'));
        $newQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        
        $increase = $newQueries - $baseQueries;
        
        echo "\n=== Query Scaling Test ===\n";
        echo "Base queries: {$baseQueries}\n";
        echo "After adding 5 courses: {$newQueries}\n";
        echo "Increase: {$increase}\n";
        
        // With proper eager loading, query count should not increase linearly with data
        // N+1 would cause significant increase, eager loading keeps it bounded
        $this->assertLessThan(20, $increase, "Query count increased too much - possible N+1");
    }

    /**
     * Benchmark API endpoint performance
     */
    public function test_api_performance(): void
    {
        $this->actingAs($this->user);
        
        $course = $this->courses[0];
        
        $iterations = 5;
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $response = $this->getJson("/api/courses-online/{$course->id}/user-progress");
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000;
            $response->assertStatus(200);
        }
        
        $avgTime = array_sum($times) / count($times);
        
        echo "\n=== API Performance ===\n";
        echo "Average Response Time: " . round($avgTime, 2) . "ms\n";
        
        // API should be fast
        $this->assertLessThan(200, $avgTime, "API response too slow: {$avgTime}ms");
    }

    /**
     * Test memory usage during heavy load
     */
    public function test_memory_usage(): void
    {
        $this->actingAs($this->user);
        
        $startMemory = memory_get_usage(true);
        
        // Make multiple requests
        for ($i = 0; $i < 10; $i++) {
            $this->get(route('courses-online.index'));
        }
        
        $endMemory = memory_get_usage(true);
        $memoryIncrease = ($endMemory - $startMemory) / 1024 / 1024; // MB
        
        echo "\n=== Memory Usage ===\n";
        echo "Start: " . round($startMemory / 1024 / 1024, 2) . "MB\n";
        echo "End: " . round($endMemory / 1024 / 1024, 2) . "MB\n";
        echo "Increase: " . round($memoryIncrease, 2) . "MB\n";
        
        // Memory should not increase significantly
        $this->assertLessThan(50, $memoryIncrease, "Memory usage increased too much");
    }
}
