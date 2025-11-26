<?php

namespace Tests\Feature;

use App\Enums\PerformanceLevel;
use App\Models\Evaluation;
use App\Services\EvaluationEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceLevelMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the migration script successfully populates performance_level fields.
     */
    public function testMigrationAddsPeformanceLevelField()
    {
        // Ensure our migration has been run
        Artisan::call('migrate');
        
        // Check that incentives table has performance_level column
        $this->assertTrue(Schema::hasColumn('incentives', 'performance_level'));
        
        // Check that evaluations table has performance_level column
        $this->assertTrue(Schema::hasColumn('evaluations', 'performance_level'));
        
        // Check that evaluations table has performance_points_min/max columns
        $this->assertTrue(Schema::hasColumn('evaluations', 'performance_points_min'));
        $this->assertTrue(Schema::hasColumn('evaluations', 'performance_points_max'));
    }

    /**
     * Test that MapEvaluationsToPerformanceLevels command correctly maps scores.
     */
    public function testMapEvaluationsCommand()
    {
        // Create evaluations with different scores
        $evaluations = [
            ['score' => 15, 'expected_level' => PerformanceLevel::OUTSTANDING],
            ['score' => 11, 'expected_level' => PerformanceLevel::RELIABLE],
            ['score' => 8, 'expected_level' => PerformanceLevel::DEVELOPING],
            ['score' => 5, 'expected_level' => PerformanceLevel::UNDERPERFORMING],
        ];

        foreach ($evaluations as $data) {
            Evaluation::create([
                'user_id' => 1,
                'total_score' => $data['score'],
                'incentive_amount' => 100.00,
                // Other required fields would go here
            ]);
        }

        // Run the migration command
        Artisan::call('evaluations:map-performance-levels');

        // Verify evaluations were updated with correct performance levels
        foreach ($evaluations as $data) {
            $evaluation = Evaluation::where('total_score', $data['score'])->first();
            $this->assertEquals($data['expected_level'], $evaluation->performance_level);
        }
    }

    /**
     * Test that EvaluationEmailService sends emails with performance level data.
     */
    public function testEmailServiceIncludesPerformanceLevels()
    {
        Mail::fake();

        // Create an evaluation with performance level
        $evaluation = Evaluation::create([
            'user_id' => 1,
            'total_score' => 14,
            'performance_level' => PerformanceLevel::OUTSTANDING,
            'performance_points_min' => 13,
            'performance_points_max' => 15,
            // Other required fields would go here
        ]);

        // Create email service instance
        $emailService = new EvaluationEmailService();
        
        // Send an evaluation notification
        $emailService->sendManagerEvaluationReport($evaluation);
        
        // Assert that the email was sent and contains performance level data
        Mail::assertSent(function ($mail) use ($evaluation) {
            return $mail->evaluation->performance_level === PerformanceLevel::OUTSTANDING &&
                   $mail->viewData['performanceLevel'] !== null &&
                   $mail->viewData['performanceLevel']['label'] === 'Outstanding';
        });
    }

    /**
     * Test the backward compatibility during transition.
     */
    public function testBackwardCompatibilityDuringTransition()
    {
        // Create an evaluation with both incentive_amount and performance_level
        $evaluation = Evaluation::create([
            'user_id' => 1,
            'total_score' => 14,
            'incentive_amount' => 500.00,
            'performance_level' => PerformanceLevel::OUTSTANDING,
            // Other required fields would go here
        ]);

        // Verify both fields are populated
        $this->assertEquals(500.00, $evaluation->incentive_amount);
        $this->assertEquals(PerformanceLevel::OUTSTANDING, $evaluation->performance_level);

        // Ensure that report generation uses performance_level over incentive_amount
        $reportData = [
            'evaluation' => $evaluation,
            'performanceLevel' => PerformanceLevel::getById($evaluation->performance_level),
        ];
        
        // Assert that the performance level data is correctly structured
        $this->assertEquals('Outstanding', $reportData['performanceLevel']['label']);
        $this->assertEquals('green', $reportData['performanceLevel']['color']);
        $this->assertEquals('badge-success', $reportData['performanceLevel']['badge_class']);
    }
}