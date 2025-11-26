<?php

namespace Tests\Unit;

use App\Enums\PerformanceLevel;
use PHPUnit\Framework\TestCase;

class PerformanceLevelTest extends TestCase
{
    /**
     * Test that getAll returns all performance levels.
     */
    public function testGetAll()
    {
        $levels = PerformanceLevel::getAll();
        $this->assertCount(4, $levels);
        $this->assertArrayHasKey(PerformanceLevel::OUTSTANDING, $levels);
        $this->assertArrayHasKey(PerformanceLevel::RELIABLE, $levels);
        $this->assertArrayHasKey(PerformanceLevel::DEVELOPING, $levels);
        $this->assertArrayHasKey(PerformanceLevel::UNDERPERFORMING, $levels);
    }

    /**
     * Test that getById returns the correct level data.
     */
    public function testGetById()
    {
        $level = PerformanceLevel::getById(PerformanceLevel::OUTSTANDING);
        $this->assertEquals(PerformanceLevel::OUTSTANDING, $level['level']);
        $this->assertEquals('green', $level['color']);
        $this->assertEquals(13, $level['min_score']);
        $this->assertEquals(15, $level['max_score']);
    }

    /**
     * Test that getLabelByLevel returns the correct label.
     */
    public function testGetLabelByLevel()
    {
        // Mock the trans function if it's not available in the test environment
        if (!function_exists('trans')) {
            function trans($key) {
                $translations = [
                    'performance_levels.outstanding.label' => 'Outstanding',
                    'performance_levels.reliable.label' => 'Reliable',
                    'performance_levels.developing.label' => 'Developing',
                    'performance_levels.underperforming.label' => 'Underperforming',
                ];
                return $translations[$key] ?? $key;
            }
        }

        $this->assertEquals('Outstanding', PerformanceLevel::getLabelByLevel(PerformanceLevel::OUTSTANDING));
        $this->assertEquals('Reliable', PerformanceLevel::getLabelByLevel(PerformanceLevel::RELIABLE));
        $this->assertEquals('Developing', PerformanceLevel::getLabelByLevel(PerformanceLevel::DEVELOPING));
        $this->assertEquals('Underperforming', PerformanceLevel::getLabelByLevel(PerformanceLevel::UNDERPERFORMING));
    }

    /**
     * Test that getRangeByLevel returns the correct score range.
     */
    public function testGetRangeByLevel()
    {
        $this->assertEquals('13-15', PerformanceLevel::getRangeByLevel(PerformanceLevel::OUTSTANDING));
        $this->assertEquals('10-12', PerformanceLevel::getRangeByLevel(PerformanceLevel::RELIABLE));
        $this->assertEquals('7-9', PerformanceLevel::getRangeByLevel(PerformanceLevel::DEVELOPING));
        $this->assertEquals('Below 0', PerformanceLevel::getRangeByLevel(PerformanceLevel::UNDERPERFORMING));
    }

    /**
     * Test that getLevelByScore maps scores to the correct performance levels.
     */
    public function testGetLevelByScore()
    {
        // Outstanding (13-15)
        $this->assertEquals(PerformanceLevel::OUTSTANDING, PerformanceLevel::getLevelByScore(13));
        $this->assertEquals(PerformanceLevel::OUTSTANDING, PerformanceLevel::getLevelByScore(14));
        $this->assertEquals(PerformanceLevel::OUTSTANDING, PerformanceLevel::getLevelByScore(15));
        
        // Reliable (10-12)
        $this->assertEquals(PerformanceLevel::RELIABLE, PerformanceLevel::getLevelByScore(10));
        $this->assertEquals(PerformanceLevel::RELIABLE, PerformanceLevel::getLevelByScore(11));
        $this->assertEquals(PerformanceLevel::RELIABLE, PerformanceLevel::getLevelByScore(12));
        
        // Developing (7-9)
        $this->assertEquals(PerformanceLevel::DEVELOPING, PerformanceLevel::getLevelByScore(7));
        $this->assertEquals(PerformanceLevel::DEVELOPING, PerformanceLevel::getLevelByScore(8));
        $this->assertEquals(PerformanceLevel::DEVELOPING, PerformanceLevel::getLevelByScore(9));
        
        // Underperforming (0-6)
        $this->assertEquals(PerformanceLevel::UNDERPERFORMING, PerformanceLevel::getLevelByScore(0));
        $this->assertEquals(PerformanceLevel::UNDERPERFORMING, PerformanceLevel::getLevelByScore(3));
        $this->assertEquals(PerformanceLevel::UNDERPERFORMING, PerformanceLevel::getLevelByScore(6));
    }

    /**
     * Test that isValid correctly validates performance level IDs.
     */
    public function testIsValid()
    {
        $this->assertTrue(PerformanceLevel::isValid(PerformanceLevel::OUTSTANDING));
        $this->assertTrue(PerformanceLevel::isValid(PerformanceLevel::RELIABLE));
        $this->assertTrue(PerformanceLevel::isValid(PerformanceLevel::DEVELOPING));
        $this->assertTrue(PerformanceLevel::isValid(PerformanceLevel::UNDERPERFORMING));
        $this->assertFalse(PerformanceLevel::isValid(5));
        $this->assertFalse(PerformanceLevel::isValid('invalid'));
        $this->assertFalse(PerformanceLevel::isValid(null));
    }

    /**
     * Test that getSelectOptions returns formatted options for dropdowns.
     */
    public function testGetSelectOptions()
    {
        $options = PerformanceLevel::getSelectOptions();
        $this->assertCount(4, $options);
        $this->assertArrayHasKey(PerformanceLevel::OUTSTANDING, $options);
        $this->assertStringContainsString('Outstanding', $options[PerformanceLevel::OUTSTANDING]);
        $this->assertStringContainsString('13-15', $options[PerformanceLevel::OUTSTANDING]);
    }

    /**
     * Test that getForFrontend returns an indexed array for Vue.
     */
    public function testGetForFrontend()
    {
        $levels = PerformanceLevel::getForFrontend();
        $this->assertCount(4, $levels);
        $this->assertIsArray($levels[0]);
        $this->assertArrayHasKey('level', $levels[0]);
        $this->assertArrayHasKey('label', $levels[0]);
        $this->assertArrayHasKey('color', $levels[0]);
    }
}