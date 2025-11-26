<?php

namespace Tests\Unit;

use App\Models\Incentive;
use App\Enums\PerformanceLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncentivePerformanceLevelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an incentive can be created with a performance level.
     */
    public function testCreateIncentiveWithPerformanceLevel()
    {
        $incentive = Incentive::create([
            'min_score' => 13,
            'max_score' => 15,
            'performance_level' => PerformanceLevel::OUTSTANDING,
        ]);

        $this->assertEquals(PerformanceLevel::OUTSTANDING, $incentive->performance_level);
        $this->assertEquals(13, $incentive->min_score);
        $this->assertEquals(15, $incentive->max_score);
    }

    /**
     * Test that performance_level is properly cast to integer.
     */
    public function testPerformanceLevelIsProperlyCast()
    {
        $incentive = new Incentive([
            'performance_level' => '2', // String input
        ]);

        $this->assertIsInt($incentive->performance_level);
        $this->assertEquals(2, $incentive->performance_level);
    }

    /**
     * Test that we can update an existing incentive to add a performance level.
     */
    public function testUpdateIncentiveToAddPerformanceLevel()
    {
        // Create incentive with only monetary amount
        $incentive = Incentive::create([
            'min_score' => 10,
            'max_score' => 12,
            'incentive_amount' => 500.00,
        ]);

        // Update to add performance level
        $incentive->performance_level = PerformanceLevel::RELIABLE;
        $incentive->save();

        // Refresh from database
        $incentive->refresh();

        // Assert both values exist during transition period
        $this->assertEquals(PerformanceLevel::RELIABLE, $incentive->performance_level);
        $this->assertEquals(500.00, $incentive->incentive_amount);
    }

    /**
     * Test the relationship between score ranges and performance levels.
     */
    public function testScoreRangeMatchesPerformanceLevel()
    {
        // Create incentives for each performance level
        $incentives = [
            [
                'min_score' => 13,
                'max_score' => 15,
                'performance_level' => PerformanceLevel::OUTSTANDING,
            ],
            [
                'min_score' => 10, 
                'max_score' => 12,
                'performance_level' => PerformanceLevel::RELIABLE,
            ],
            [
                'min_score' => 7,
                'max_score' => 9,
                'performance_level' => PerformanceLevel::DEVELOPING,
            ],
            [
                'min_score' => 0,
                'max_score' => 6, 
                'performance_level' => PerformanceLevel::UNDERPERFORMING,
            ],
        ];

        foreach ($incentives as $data) {
            $incentive = Incentive::create($data);
            
            // Check that the score ranges match PerformanceLevel definition
            $expectedRange = PerformanceLevel::getScoreRangeByLevel($data['performance_level']);
            $this->assertEquals($expectedRange['min'], $incentive->min_score);
            $this->assertEquals($expectedRange['max'], $incentive->max_score);
        }
    }
}