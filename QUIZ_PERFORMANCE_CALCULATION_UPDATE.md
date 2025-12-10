# Quiz Performance Calculation Update

## Overview
Updated the `calculateUserQuizPerformance` method to include **BOTH regular quizzes AND module quizzes** in the performance calculation, with automatic averaging.

## What Changed

### Before
- Only included regular quizzes (assigned via `quiz_assignments` table)
- Module quizzes were completely ignored in the report
- Hardcoded passing threshold of 80%

### After
- Includes **regular quizzes** (via `quiz_assignments` table)
- Includes **module quizzes** (via `module_quiz_results` table)
- Calculates **average pass threshold** from ALL assigned quizzes
- Provides breakdown of both quiz types for transparency

## Calculation Logic

### Regular Quizzes
- Source: `quiz_assignments` + `quiz_attempts` tables
- Tracks: Assigned, Completed, Passed, Average Score

### Module Quizzes
- Source: `course_modules` + `module_quiz_results` tables
- Tracks: Assigned (module count), Completed, Passed, Average Score

### Combined Metrics
```
Total Assigned = Regular Assigned + Module Assigned
Total Completed = Regular Completed + Module Completed
Total Passed = Regular Passed + Module Passed

Completion Rate = (Total Completed / Total Assigned) × 100
Passing Rate = (Total Passed / Total Completed) × 100
Combined Avg Score = (Regular Avg + Module Avg) / 2

Quiz Performance Score = 
  (Completion Rate × 0.40) + 
  (Passing Rate × 0.40) + 
  (Combined Avg Score × 0.20)

Meets Standards = 
  Completion ≥ 90% AND 
  Passing ≥ avgPassThreshold AND 
  Combined Avg Score ≥ avgPassThreshold
```

### Dynamic Pass Threshold
- Calculated from average of ALL assigned quiz thresholds
- Includes both regular and module quiz pass_threshold values
- Defaults to 80% if no quizzes assigned
- **Automatically adjusts** when quiz thresholds change

## Return Data Structure

```php
[
    // Combined totals
    'assigned_quizzes' => int,
    'completed_quizzes' => int,
    'passed_quizzes' => int,
    'completion_rate' => float,
    'passing_rate' => float,
    'avg_quiz_score' => float,
    'quiz_performance_score' => float,
    'meets_standards' => bool,
    'avg_pass_threshold' => float,
    'status_label' => string,
    
    // Breakdown for transparency
    'regular_quizzes' => [
        'assigned' => int,
        'completed' => int,
        'passed' => int,
        'avg_score' => float,
    ],
    'module_quizzes' => [
        'assigned' => int,
        'completed' => int,
        'passed' => int,
        'avg_score' => float,
    ],
]
```

## Example Scenario

**User has:**
- 2 regular quizzes (thresholds: 80%, 75%)
- 3 module quizzes (thresholds: 70%, 70%, 80%)

**Calculations:**
- Average Pass Threshold = (80 + 75 + 70 + 70 + 80) / 5 = **75%**
- If user completed 4/5 quizzes with 3 passed and avg score 78%:
  - Completion Rate = 80%
  - Passing Rate = 75%
  - Combined Avg Score = 78%
  - Quiz Performance Score = (80 × 0.4) + (75 × 0.4) + (78 × 0.2) = **77.6%**
  - Meets Standards = 80% ≥ 90%? **NO** (fails completion threshold)

## Benefits

✅ **Complete Picture**: Reports now show ALL quiz performance, not just regular quizzes
✅ **Fair Assessment**: Combines module and regular quiz performance equally
✅ **Dynamic Thresholds**: Automatically adjusts when quiz settings change
✅ **Transparent Breakdown**: Shows separate metrics for each quiz type
✅ **Accurate Reporting**: Users see their true overall quiz performance
