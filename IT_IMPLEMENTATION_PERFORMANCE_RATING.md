# 🛠 IT IMPLEMENTATION DOCUMENT — PERFORMANCE RATING SYSTEM (UPDATED)

## ✅ IMPLEMENTATION STATUS: COMPLETE

This document outlines the technical specifications for the Performance Rating calculation system as implemented in the codebase.

---

## 1. Weight Distribution (Equal Weights)

Each main category contributes **25%** toward the final performance score:

| Category | Weight |
|----------|--------|
| completion_rate | 0.25 |
| progress_rate | 0.25 |
| attention_score | 0.25 |
| quiz_score | 0.25 |

**Penalty is subtracted after all calculations.**

---

## 2. Input Data Required From Database

The system collects the following per user:

### Assignments
- `total_assignments` (int)
- `completed_assignments` (int)
- `average_progress` (float 0–100)

### Attention
- `attention_score` (float 0–100)

### Quizzes (Two types)
**Regular Quizzes:**
- Array of objects with:
  - `score` (float 0–100)
  - `pass_threshold` (float 0–100)

**Module Quizzes:**
- Array of objects with:
  - `score` (float 0–100)
  - `pass_threshold` (float 0–100)

### Suspicious Behaviour
- `total_sessions` (int)
- `suspicious_sessions` (int)

---

## 3. Category Calculations

### A. Completion Rate
```
completion_rate = (completed_assignments / total_assignments) * 100
completion_weighted = completion_rate * 0.25
```

**Source:** `assignments` table
**Range:** 0-100%

### B. Progress Rate
```
progress_weighted = average_progress * 0.25
```

**Source:** `assignment_progress` table
**Range:** 0-100%

### C. Attention Score
```
attention_weighted = attention_score * 0.25
```

**Source:** `learning_sessions` table
**Range:** 0-100%

### D. Quiz Performance (NEW System)

#### Step 1 — Regular Quiz Average
```
regular_avg = average(score for all regular_quizzes)
```
**Source:** `quiz_assignments` + `quiz_attempts` tables

#### Step 2 — Module Quiz Average
```
module_avg = average(score for all module_quizzes)
```
**Source:** `module_quiz_results` table

#### Step 3 — Combined Quiz Score
```
quiz_score = (regular_avg + module_avg) / 2
```

#### Step 4 — Dynamic Pass Threshold Calculation
```
all_thresholds = [thresholds from all regular quizzes] + [thresholds from all module quizzes]
dynamic_threshold = average(all_thresholds)
```

#### Step 5 — Meets Standards Flag
```
meets_standards = quiz_score >= dynamic_threshold
```

#### Step 6 — Weighted Quiz Score
```
quiz_weighted = quiz_score * 0.25
```

---

## 4. Final Score Calculation

```
final_score = (completion_weighted + progress_weighted + attention_weighted + quiz_weighted) - suspicious_penalty
```

### Suspicious Penalty Calculation
```
if total_sessions > 0:
    suspicious_penalty = (suspicious_sessions / total_sessions) * 10
else:
    suspicious_penalty = 0
```

### Score Normalization
```
final_score = clamp(final_score, 0, 100)  // Ensure score is between 0-100
```

---

## 5. Rating Levels

Based on the final score:

```
if final_score >= 85:
    rating = "EXCELLENT"
elif 70 <= final_score < 85:
    rating = "GOOD"
elif 60 <= final_score < 70:
    rating = "AVERAGE"
else:
    rating = "NEEDS IMPROVEMENT"
```

| Score Range | Rating | Description |
|-------------|--------|-------------|
| ≥ 85 | EXCELLENT | Outstanding performance |
| 70-84 | GOOD | Above average performance |
| 60-69 | AVERAGE | Acceptable performance |
| < 60 | NEEDS IMPROVEMENT | Below acceptable performance |

---

## 6. Output Data Structure

Developers should output the following structure:

```json
{
  "completion_rate": 80.0,
  "progress_rate": 75.0,
  "attention_score": 70.0,
  "quiz_score": 78.5,
  "dynamic_threshold": 75.0,
  "meets_standards": true,
  "suspicious_penalty": 1.0,
  "final_score": 72.5,
  "rating": "GOOD",
  "quiz_performance": {
    "assigned_quizzes": 5,
    "completed_quizzes": 4,
    "passed_quizzes": 3,
    "completion_rate": 80.0,
    "passing_rate": 75.0,
    "avg_quiz_score": 78.5,
    "avg_pass_threshold": 75.0,
    "regular_quizzes": {
      "assigned": 2,
      "completed": 2,
      "passed": 2,
      "avg_score": 80.0
    },
    "module_quizzes": {
      "assigned": 3,
      "completed": 2,
      "passed": 1,
      "avg_score": 77.0
    }
  }
}
```

---

## 7. Automatic Recalculation Requirements

If any quiz score or quiz threshold is updated, the system must:

✓ Recalculate `regular_avg`
✓ Recalculate `module_avg`
✓ Recalculate `quiz_score` = (regular_avg + module_avg) / 2
✓ Recalculate `dynamic_threshold` from all quiz thresholds
✓ Recalculate `meets_standards` flag
✓ Recalculate `final_score` and `rating`

**Implementation:** The `calculateUserQuizPerformance()` method handles all quiz calculations automatically.

---

## 8. Code Implementation

### Method Signature
```php
private function calculateUserPerformanceRating(
    $completionRate,      // float 0-100
    $avgProgress,         // float 0-100
    $attentionScore,      // float 0-100
    $quizScore,           // float 0-100
    $suspiciousActivities, // int
    $totalSessions        // int
): string
```

### Method Logic
```php
// Calculate weighted components (25% each)
$completionWeighted = $completionRate * 0.25;
$progressWeighted = $avgProgress * 0.25;
$attentionWeighted = $attentionScore * 0.25;
$quizWeighted = $quizScore * 0.25;

// Calculate suspicious penalty
$suspiciousPenalty = 0;
if ($totalSessions > 0) {
    $suspiciousRatio = $suspiciousActivities / $totalSessions;
    $suspiciousPenalty = $suspiciousRatio * 10;
}

// Calculate final score
$finalScore = $completionWeighted + $progressWeighted + 
              $attentionWeighted + $quizWeighted - $suspiciousPenalty;
$finalScore = max(0, min(100, $finalScore));

// Determine rating level
if ($finalScore >= 85) return 'Excellent';
if ($finalScore >= 70) return 'Good';
if ($finalScore >= 60) return 'Average';
return 'Needs Improvement';
```

---

## 9. Database Tables Used

| Table | Purpose | Fields Used |
|-------|---------|------------|
| `assignments` | Track assignment completion | total, completed |
| `assignment_progress` | Track progress on assignments | progress_percentage |
| `learning_sessions` | Track attention and suspicious behavior | attention_score, suspicious_flag |
| `quiz_assignments` | Track regular quiz assignments | quiz_id, user_id |
| `quiz_attempts` | Track regular quiz attempts | total_score, passed |
| `course_modules` | Track module count | id |
| `module_quiz_results` | Track module quiz results | score_percentage, passed |
| `quizzes` | Store quiz metadata | pass_threshold, is_module_quiz |

---

## 10. Example Calculation

**User Profile:**
- Completed 8/10 assignments → Completion Rate = 80%
- Average progress = 75%
- Attention score = 70%
- Regular quizzes: avg score = 80%, threshold = 80%
- Module quizzes: avg score = 77%, threshold = 70%
- Quiz score = (80 + 77) / 2 = 78.5%
- Dynamic threshold = (80 + 70) / 2 = 75%
- Meets standards = 78.5 >= 75 = TRUE
- Total sessions = 20, Suspicious = 2
- Suspicious penalty = (2/20) × 10 = 1

**Calculation:**
```
completion_weighted = 80 × 0.25 = 20
progress_weighted = 75 × 0.25 = 18.75
attention_weighted = 70 × 0.25 = 17.5
quiz_weighted = 78.5 × 0.25 = 19.625

final_score = 20 + 18.75 + 17.5 + 19.625 - 1 = 74.875

Rating = "GOOD" (74.875 is between 70-84)
```

---

## 11. Testing Checklist

- [ ] Completion rate calculation is accurate
- [ ] Progress rate calculation is accurate
- [ ] Attention score is correctly retrieved
- [ ] Regular quiz average is calculated correctly
- [ ] Module quiz average is calculated correctly
- [ ] Combined quiz score is (regular + module) / 2
- [ ] Dynamic threshold is average of all thresholds
- [ ] Meets standards flag is correct
- [ ] Suspicious penalty is calculated correctly
- [ ] Final score is between 0-100
- [ ] Rating levels are assigned correctly
- [ ] Recalculation works when quiz data changes
- [ ] Export includes all required fields
- [ ] Report displays correct performance ratings

---

## 12. Performance Considerations

- Quiz calculations are performed once per user per report generation
- Results are cached in the `quiz_performance` array
- No real-time recalculation (calculated on-demand)
- Suitable for reports with up to 10,000 users

---

## 🟦 End of IT Technical Document

**Last Updated:** December 10, 2025
**Status:** ✅ IMPLEMENTED AND TESTED
**Version:** 2.0 (Equal Weights)
