<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\MapEvaluationsToPerformanceLevels;
use App\Models\Evaluation;
use App\Enums\PerformanceLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MapEvaluationsToPerformanceLevelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test directory for export
        Storage::makeDirectory('performance_level_migration');
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        Storage::deleteDirectory('performance_level_migration');
        
        parent::tearDown();
    }

    /**
     * Test that the command maps scores to performance levels correctly.
     */
    public function testCommandMapsScoresToPerformanceLevels()
    {
        // Create test evaluations with different scores
        $evaluations = [
            ['total_score' => 15, 'expected_level' => PerformanceLevel::OUTSTANDING],
            ['total_score' => 12, 'expected_level' => PerformanceLevel::RELIABLE],
            ['total_score' => 8, 'expected_level' => PerformanceLevel::DEVELOPING],
            ['total_score' => 4, 'expected_level' => PerformanceLevel::UNDERPERFORMING],
            ['total_score' => null, 'expected_level' => null], // Should be flagged
        ];

        foreach ($evaluations as $data) {
            Evaluation::create([
                'user_id' => 1,
                'total_score' => $data['total_score'],
                'incentive_amount' => 100.00,
                // Other required fields would go here
            ]);
        }

        // Run the command
        $this->artisan('evaluations:map-performance-levels')
             ->expectsOutput('Mapping evaluations to performance levels')
             ->assertExitCode(0);

        // Check that evaluations with scores were mapped correctly
        $evaluation = Evaluation::where('total_score', 15)->first();
        $this->assertEquals(PerformanceLevel::OUTSTANDING, $evaluation->performance_level);
        
        $evaluation = Evaluation::where('total_score', 12)->first();
        $this->assertEquals(PerformanceLevel::RELIABLE, $evaluation->performance_level);
        
        $evaluation = Evaluation::where('total_score', 8)->first();
        $this->assertEquals(PerformanceLevel::DEVELOPING, $evaluation->performance_level);
        
        $evaluation = Evaluation::where('total_score', 4)->first();
        $this->assertEquals(PerformanceLevel::UNDERPERFORMING, $evaluation->performance_level);

        // Check that evaluation without score was flagged (not updated)
        $evaluation = Evaluation::whereNull('total_score')->first();
        $this->assertNull($evaluation->performance_level);
        
        // Check that flagged records were exported
        $this->assertTrue(Storage::exists('performance_level_migration/flagged_records.csv'));
    }

    /**
     * Test that the dry run option doesn't persist changes.
     */
    public function testDryRunDoesNotPersistChanges()
    {
        // Create a test evaluation
        Evaluation::create([
            'user_id' => 1,
            'total_score' => 14,
            'incentive_amount' => 100.00,
            // Other required fields would go here
        ]);

        // Run the command with --dry-run
        $this->artisan('evaluations:map-performance-levels --dry-run')
             ->expectsOutput('Dry run enabled — no database writes will be performed.')
             ->assertExitCode(0);

        // Check that the evaluation wasn't updated
        $evaluation = Evaluation::where('total_score', 14)->first();
        $this->assertNull($evaluation->performance_level);
    }

    /**
     * Test that the chunk option works as expected.
     */
    public function testChunkOption()
    {
        // Create 10 test evaluations
        for ($i = 0; $i < 10; $i++) {
            Evaluation::create([
                'user_id' => 1,
                'total_score' => 15,
                'incentive_amount' => 100.00,
                // Other required fields would go here
            ]);
        }

        // Run the command with a small chunk size
        $this->artisan('evaluations:map-performance-levels --chunk=5')
             ->expectsOutput('Processed: 10')
             ->assertExitCode(0);

        // Check that all evaluations were updated
        $this->assertEquals(10, Evaluation::where('performance_level', PerformanceLevel::OUTSTANDING)->count());
    }

    /**
     * Test the points range fields are populated correctly.
     */
    public function testPointsRangeFields()
    {
        // Create test evaluation
        Evaluation::create([
            'user_id' => 1,
            'total_score' => 14,
            'incentive_amount' => 100.00,
            // Other required fields would go here
        ]);

        // Run the command
        $this->artisan('evaluations:map-performance-levels')
             ->assertExitCode(0);

        // Check that the evaluation points ranges were set correctly
        $evaluation = Evaluation::where('total_score', 14)->first();
        $this->assertEquals(PerformanceLevel::OUTSTANDING, $evaluation->performance_level);
        $this->assertEquals(13, $evaluation->performance_points_min); // Min for Outstanding
        $this->assertEquals(15, $evaluation->performance_points_max); // Max for Outstanding
    }
}