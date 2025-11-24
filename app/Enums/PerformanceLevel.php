<?php

namespace App\Enums;

/**
 * Performance Level Enum
 * 
 * Defines the 4-tier performance rating system for evaluations.
 * Replaces the previous monetary incentive_amount field.
 * 
 * Usage:
 *   PerformanceLevel::getAll()                           // Get all levels
 *   PerformanceLevel::getById(1)                         // Get specific level
 *   PerformanceLevel::getLabelByLevel(1)                 // Get label: "Outstanding"
 *   PerformanceLevel::getRangeByLevel(1)                 // Get points range: "13-15"
 *   PerformanceLevel::getLevelByScore(14)                // Get level from score: 1
 *   PerformanceLevel::getDescriptionByLevel(1)           // Get full description
 *   PerformanceLevel::getColorByLevel(1)                 // Get color code: "green"
 */
class PerformanceLevel
{
    // Level constants
    const OUTSTANDING = 1;
    const RELIABLE = 2;
    const DEVELOPING = 3;
    const UNDERPERFORMING = 4;

    /**
     * Get all performance levels with their metadata
     * 
     * @return array
     */
    public static function getAll(): array
    {
        return [
            self::OUTSTANDING => self::getById(self::OUTSTANDING),
            self::RELIABLE => self::getById(self::RELIABLE),
            self::DEVELOPING => self::getById(self::DEVELOPING),
            self::UNDERPERFORMING => self::getById(self::UNDERPERFORMING),
        ];
    }

    /**
     * Get performance level details by level ID
     * 
     * @param int $level
     * @return array|null
     */
    public static function getById(int $level): ?array
    {
        $levels = [
            self::OUTSTANDING => [
                'level' => self::OUTSTANDING,
                'label' => trans('performance_levels.outstanding.label'),
                'description' => trans('performance_levels.outstanding.description'),
                'min_score' => 13,
                'max_score' => 15,
                'color' => 'green',
                'badge_class' => 'badge-success',
            ],
            self::RELIABLE => [
                'level' => self::RELIABLE,
                'label' => trans('performance_levels.reliable.label'),
                'description' => trans('performance_levels.reliable.description'),
                'min_score' => 10,
                'max_score' => 12,
                'color' => 'blue',
                'badge_class' => 'badge-info',
            ],
            self::DEVELOPING => [
                'level' => self::DEVELOPING,
                'label' => trans('performance_levels.developing.label'),
                'description' => trans('performance_levels.developing.description'),
                'min_score' => 7,
                'max_score' => 9,
                'color' => 'yellow',
                'badge_class' => 'badge-warning',
            ],
            self::UNDERPERFORMING => [
                'level' => self::UNDERPERFORMING,
                'label' => trans('performance_levels.underperforming.label'),
                'description' => trans('performance_levels.underperforming.description'),
                'min_score' => 0,
                'max_score' => 6,
                'color' => 'red',
                'badge_class' => 'badge-danger',
            ],
        ];

        return $levels[$level] ?? null;
    }

    /**
     * Get all valid level IDs
     * 
     * @return array
     */
    public static function getAllIds(): array
    {
        return [self::OUTSTANDING, self::RELIABLE, self::DEVELOPING, self::UNDERPERFORMING];
    }

    /**
     * Get label string for a performance level
     * 
     * @param int $level
     * @return string|null
     */
    public static function getLabelByLevel(int $level): ?string
    {
        $levelData = self::getById($level);
        return $levelData['label'] ?? null;
    }

    /**
     * Get points range string (e.g., "13-15")
     * 
     * @param int $level
     * @return string|null
     */
    public static function getRangeByLevel(int $level): ?string
    {
        $levelData = self::getById($level);
        if (!$levelData) {
            return null;
        }

        if ($level === self::UNDERPERFORMING) {
            return "Below {$levelData['min_score']}";
        }

        return "{$levelData['min_score']}-{$levelData['max_score']}";
    }

    /**
     * Get full range object {min, max} for a level
     * 
     * @param int $level
     * @return array|null
     */
    public static function getScoreRangeByLevel(int $level): ?array
    {
        $levelData = self::getById($level);
        if (!$levelData) {
            return null;
        }

        return [
            'min' => $levelData['min_score'],
            'max' => $levelData['max_score'],
        ];
    }

    /**
     * Get description string for a performance level
     * 
     * @param int $level
     * @return string|null
     */
    public static function getDescriptionByLevel(int $level): ?string
    {
        $levelData = self::getById($level);
        return $levelData['description'] ?? null;
    }

    /**
     * Get color code for a performance level
     * 
     * @param int $level
     * @return string|null
     */
    public static function getColorByLevel(int $level): ?string
    {
        $levelData = self::getById($level);
        return $levelData['color'] ?? null;
    }

    /**
     * Get CSS badge class for a performance level
     * 
     * @param int $level
     * @return string|null
     */
    public static function getBadgeClassByLevel(int $level): ?string
    {
        $levelData = self::getById($level);
        return $levelData['badge_class'] ?? null;
    }

    /**
     * Map a score to a performance level
     * 
     * @param int $score
     * @return int|null
     */
    public static function getLevelByScore(int $score): ?int
    {
        if ($score >= 13 && $score <= 15) {
            return self::OUTSTANDING;
        } elseif ($score >= 10 && $score <= 12) {
            return self::RELIABLE;
        } elseif ($score >= 7 && $score <= 9) {
            return self::DEVELOPING;
        } elseif ($score < 7) {
            return self::UNDERPERFORMING;
        }

        return null;
    }

    /**
     * Check if a level is valid
     * 
     * @param mixed $level
     * @return bool
     */
    public static function isValid($level): bool
    {
        return in_array($level, self::getAllIds(), true);
    }

    /**
     * Get array for dropdown/select options
     * Format: [1 => 'Outstanding (13-15)', 2 => 'Reliable (10-12)', ...]
     * 
     * @return array
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::getAll() as $level) {
            $options[$level['level']] = $level['label'] . ' (' . self::getRangeByLevel($level['level']) . ')';
        }
        return $options;
    }

    /**
     * Get levels for frontend Vue component
     * Returns array with all metadata for each level
     * 
     * @return array
     */
    public static function getForFrontend(): array
    {
        return array_values(self::getAll());
    }
}
