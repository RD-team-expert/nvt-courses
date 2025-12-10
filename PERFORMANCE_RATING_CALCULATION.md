# Performance Rating Calculation

## Overview
The **Performance** column in the User Performance Report is calculated using a weighted scoring system that combines multiple factors.

## Calculation Formula

```
Performance Score = (Completion Rate × 0.3) + (Avg Progress × 0.3) + (Attention Score × 0.3) - Suspicious Penalty
```

### Components

#### 1. **Completion Rate** (30% weight)
- **What it measures**: Percentage of assignments completed by the user
- **Formula**: `(Completed Assignments / Total Assignments) × 100`
- **Range**: 0-100%
- **Contribution**: Up to 30 points

#### 2. **Average Progress** (30% weight)
- **What it measures**: Average progress percentage across all assignments
- **Formula**: Average of all assignment progress percentages
- **Range**: 0-100%
- **Contribution**: Up to 30 points

#### 3. **Attention Score** (30% weight)
- **What it measures**: User's focus/attention during learning sessions
- **Base Score**: Calculated from learning session data
- **Quiz Boost**: +25% of quiz performance score is added to attention score
  - Formula: `Attention Score + (Quiz Performance Score × 0.25)`
- **Range**: 0-100%
- **Contribution**: Up to 30 points

#### 4. **Suspicious Activities Penalty** (Deduction)
- **What it measures**: Penalty for suspicious learning behavior
- **Formula**: `(Suspicious Sessions / Total Sessions) × 10`
- **Impact**: Deducts points from the total score
- **Example**: If 50% of sessions are suspicious, deduct 5 points

### Final Score Calculation

```
Raw Score = (Completion Rate × 0.3) + (Avg Progress × 0.3) + (Attention Score × 0.3) - Suspicious Penalty
Final Score = Clamp(Raw Score, 0, 100)  // Ensure score is between 0-100
```

## Performance Rating Levels

Based on the final score:

| Score Range | Rating | Description |
|-------------|--------|-------------|
| ≥ 85 | **Excellent** | Outstanding performance |
| 70-84 | **Good** | Above average performance |
| 60-69 | **Average** | Acceptable performance |
| < 60 | **Needs Improvement** | Below acceptable performance |

## Example Calculation

**User Profile:**
- Completed 8 out of 10 assignments → Completion Rate = 80%
- Average progress across assignments = 75%
- Attention score = 70%
- Quiz performance score = 80% → Quiz boost = 80 × 0.25 = 20
- Adjusted attention score = 70 + 20 = 90%
- Total sessions = 20, Suspicious sessions = 2
- Suspicious ratio = 2/20 = 0.1 → Penalty = 0.1 × 10 = 1 point

**Calculation:**
```
Score = (80 × 0.3) + (75 × 0.3) + (90 × 0.3) - 1
Score = 24 + 22.5 + 27 - 1
Score = 72.5

Rating = "Good" (72.5 is between 70-84)
```

## Quiz Integration

The quiz performance directly impacts the Performance rating through the **Attention Score**:

- **Quiz Performance Score** is calculated from:
  - Completion Rate: 40%
  - Passing Rate: 40%
  - Average Score: 20%

- **Quiz Boost Effect**: 25% of the quiz performance score is added to the attention score
  - This means a user with excellent quiz performance (100%) gets +25 points to their attention score
  - This can significantly improve their overall performance rating

## Key Factors Affecting Performance Rating

### Positive Factors
✅ High completion rate (completing assignments)
✅ High progress on assignments
✅ Good attention/focus during learning
✅ Passing quizzes with high scores
✅ Clean learning sessions (no suspicious activity)

### Negative Factors
❌ Low completion rate
❌ Low progress on assignments
❌ Poor attention/focus
❌ Failing quizzes or low quiz scores
❌ Suspicious learning behavior (rapid clicks, unusual patterns)

## Data Sources

| Component | Source Table | Field |
|-----------|--------------|-------|
| Completion Rate | assignments | status |
| Avg Progress | assignment_progress | progress_percentage |
| Attention Score | learning_sessions | attention_score |
| Quiz Performance | quiz_attempts + module_quiz_results | total_score, passed |
| Suspicious Sessions | learning_sessions | suspicious_flag |

## Notes

- The performance rating is **recalculated dynamically** each time the report is viewed
- Quiz performance now includes **BOTH regular quizzes AND module quizzes**
- The quiz boost (25% weight) helps users with strong quiz performance improve their overall rating
- Suspicious activity detection helps identify potential cheating or unusual behavior patterns
