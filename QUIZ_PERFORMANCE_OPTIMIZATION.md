# Quiz Performance Calculation - Bug Fix & Optimization Update

## Overview
The `calculateUserQuizPerformance` method has been **fixed** to correctly calculate quiz performance scores.

## Bug Fixed (December 2024)

### The Problem
Users who took quizzes were seeing incorrect quiz performance scores (e.g., showing 64 instead of their actual score).

### Root Cause
The module quiz calculation was counting **ALL course modules** as "assigned quizzes", not just modules that actually have quizzes (`has_quiz = true`).

**Example of the bug:**
- Course has 5 modules, but only 2 modules have quizzes
- User completes both module quizzes with 100% score
- OLD calculation: 2 completed / 5 assigned = 40% completion rate ❌
- NEW calculation: 2 completed / 2 assigned = 100% completion rate ✅

### The Fix
1. **Only count modules with `has_quiz = true`** as assigned quizzes
2. **Only count modules for users who are assigned to the course**
3. **Use best score per module** when calculating average (handles multiple attempts)
4. **Get thresholds from all assigned module quizzes**, not just completed ones

---

## Key Changes

### Before (Buggy)
```php
// Counted ALL modules, not just modules with quizzes
$moduleAssignedCount = DB::table('course_modules')
    ->when($courseId, function ($q) use ($courseId) {
        return $q->where('course_online_id', $courseId);
    })
    ->count();
```

### After (Fixed)
```php
// Only count modules that have quizzes AND user is assigned to the course
$userAssignedCourseIds = DB::table('course_online_assignments')
    ->where('user_id', $userId)
    ->pluck('course_online_id');

$moduleIdsWithQuizzes = DB::table('course_modules')
    ->where('has_quiz', true)  // ✅ Only modules with quizzes
    ->whereIn('course_online_id', $userAssignedCourseIds)  // ✅ Only assigned courses
    ->pluck('id');

$moduleAssignedCount = $moduleIdsWithQuizzes->count();
```

---

## Calculation Formula

### Quiz Performance Score (Weighted)
```
quiz_performance_score = (completion_rate × 0.4) + (passing_rate × 0.4) + (avg_score × 0.2)
```

### Components
- **Completion Rate (40%)**: `completed_quizzes / assigned_quizzes × 100`
- **Passing Rate (40%)**: `passed_quizzes / completed_quizzes × 100`
- **Average Score (20%)**: Average of best scores per quiz

### Example
- User assigned to 2 module quizzes
- User completed both quizzes
- User passed both quizzes with scores of 85% and 90%

```
Completion Rate = 2/2 × 100 = 100%
Passing Rate = 2/2 × 100 = 100%
Average Score = (85 + 90) / 2 = 87.5%

Quiz Performance = (100 × 0.4) + (100 × 0.4) + (87.5 × 0.2)
                 = 40 + 40 + 17.5
                 = 97.5%
```

---

## Module Quiz Counting Logic

### What Counts as "Assigned"
- Modules where `has_quiz = true`
- Modules in courses the user is assigned to

### What Counts as "Completed"
- Unique modules where user has at least one quiz result in `module_quiz_results`

### What Counts as "Passed"
- Unique modules where user has at least one result with `passed = true`

### Average Score Calculation
- Groups results by module
- Takes the **best score** for each module
- Averages the best scores

---

## Return Data Structure

```php
[
    'assigned_quizzes' => 2,      // Total quizzes user should complete
    'completed_quizzes' => 2,     // Total quizzes user has completed
    'passed_quizzes' => 2,        // Total quizzes user has passed
    'completion_rate' => 100.0,   // Percentage completed
    'passing_rate' => 100.0,      // Percentage passed (of completed)
    'avg_quiz_score' => 87.5,     // Average best score
    'quiz_performance_score' => 97.5,  // Weighted final score
    'meets_standards' => true,
    'avg_pass_threshold' => 80,
    'status_label' => 'Strong',
    'regular_quizzes' => [
        'assigned' => 0,
        'completed' => 0,
        'passed' => 0,
        'avg_score' => 0,
    ],
    'module_quizzes' => [
        'assigned' => 2,
        'completed' => 2,
        'passed' => 2,
        'avg_score' => 87.5,
    ],
]
```

---

## Testing Scenarios

✅ **Test 1:** User completes all module quizzes
- Should show 100% completion rate
- Should show correct average score

✅ **Test 2:** User completes some module quizzes
- Should show partial completion rate
- Should only average completed quiz scores

✅ **Test 3:** Course with modules but no quizzes
- Should show 0 assigned quizzes
- Should not affect performance score

✅ **Test 4:** User not assigned to any courses
- Should show 0 for all metrics
- Should not throw errors

---

## Summary

The fix ensures that:
- ✅ Only modules with `has_quiz = true` are counted as assigned
- ✅ Only courses the user is assigned to are considered
- ✅ Best scores per module are used for averaging
- ✅ Quiz performance scores now accurately reflect user performance
