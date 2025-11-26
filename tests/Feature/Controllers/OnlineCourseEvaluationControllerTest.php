<?php

namespace Tests\Feature\Controllers;

use App\Enums\PerformanceLevel;
use App\Models\Evaluation;
use App\Models\User;
use App\Models\CourseOnline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineCourseEvaluationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // Create online course
        $this->course = CourseOnline::factory()->create();
    }

    /**
     * Test that the evaluation form view has performance level data.
     */
    public function testIndexViewHasPerformanceLevels()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.online-course-evaluations.create', $this->course->id));

        $response->assertStatus(200);
        $response->assertViewHas('performanceLevels');
        
        // Check that all performance levels are passed to the view
        $performanceLevels = $response->viewData('performanceLevels');
        $this->assertCount(4, $performanceLevels);
        
        // Check that performance level data has expected structure
        $this->assertArrayHasKey('level', $performanceLevels[0]);
        $this->assertArrayHasKey('label', $performanceLevels[0]);
        $this->assertArrayHasKey('color', $performanceLevels[0]);
    }

    /**
     * Test that evaluations are stored with the correct performance level.
     */
    public function testStoreEvaluationWithPerformanceLevel()
    {
        // Create a user to evaluate
        $user = User::factory()->create();
        
        // Submit an evaluation with a score
        $response = $this->actingAs($this->admin)
            ->post(route('admin.online-course-evaluations.store'), [
                'course_id' => $this->course->id,
                'user_id' => $user->id,
                'total_score' => 14,
                'comments' => 'Great performance',
            ]);

        $response->assertStatus(302); // Redirect after successful store
        
        // Check that evaluation was created with correct performance level
        $evaluation = Evaluation::where('user_id', $user->id)
            ->where('course_id', $this->course->id)
            ->first();
        
        $this->assertNotNull($evaluation);
        $this->assertEquals(14, $evaluation->total_score);
        $this->assertEquals(PerformanceLevel::OUTSTANDING, $evaluation->performance_level);
    }

    /**
     * Test that the system correctly maps scores to performance levels.
     */
    public function testScoresAreMappedToCorrectPerformanceLevels()
    {
        $user = User::factory()->create();
        
        // Test different score levels
        $testCases = [
            ['score' => 15, 'expected_level' => PerformanceLevel::OUTSTANDING],
            ['score' => 12, 'expected_level' => PerformanceLevel::RELIABLE],
            ['score' => 9, 'expected_level' => PerformanceLevel::DEVELOPING],
            ['score' => 6, 'expected_level' => PerformanceLevel::UNDERPERFORMING],
        ];
        
        foreach ($testCases as $testCase) {
            // Create evaluation
            $response = $this->actingAs($this->admin)
                ->post(route('admin.online-course-evaluations.store'), [
                    'course_id' => $this->course->id,
                    'user_id' => $user->id,
                    'total_score' => $testCase['score'],
                    'comments' => "Score test: {$testCase['score']}",
                ]);
                
            $response->assertStatus(302);
            
            // Check saved performance level
            $evaluation = Evaluation::where('user_id', $user->id)
                ->where('total_score', $testCase['score'])
                ->first();
                
            $this->assertNotNull($evaluation);
            $this->assertEquals($testCase['expected_level'], $evaluation->performance_level);
        }
    }

    /**
     * Test that performance level data is included in the evaluation show view.
     */
    public function testShowViewContainsPerformanceLevelData()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create an evaluation with a performance level
        $evaluation = Evaluation::create([
            'user_id' => $user->id,
            'course_id' => $this->course->id,
            'total_score' => 14,
            'performance_level' => PerformanceLevel::OUTSTANDING,
            'comments' => 'Excellent work',
            // Other required fields would go here
        ]);
        
        // View the evaluation
        $response = $this->actingAs($this->admin)
            ->get(route('admin.online-course-evaluations.show', $evaluation->id));
            
        $response->assertStatus(200);
        $response->assertViewHas('evaluation');
        
        // Check that the view has performance level data
        $viewData = $response->viewData('evaluation');
        $this->assertEquals(PerformanceLevel::OUTSTANDING, $viewData->performance_level);
        
        // Check for performance level label and color in view
        $response->assertSee('Outstanding'); // Performance level label
        $response->assertSee('badge-success'); // Badge class for Outstanding
    }
}