# Monthly KPI Dashboard - Calculation Guide

This document explains how each metric in the Monthly Training KPI Report is calculated.

---

## 📊 Section 1: Training Delivery Overview

### Courses Delivered
- **Source:** `courses` table
- **Calculation:** Count of courses created in the selected month/year
- **SQL:** `SELECT COUNT(*) FROM courses WHERE created_at BETWEEN start_date AND end_date`

### Total Enrolled
- **Source:** `course_registrations` table
- **Calculation:** Count of all course registrations in the period
- **SQL:** `SELECT COUNT(*) FROM course_registrations WHERE registered_at BETWEEN start_date AND end_date`

### Active Participants
- **Source:** `course_registrations` table
- **Calculation:** Distinct count of users with active registrations
- **SQL:** `SELECT COUNT(DISTINCT user_id) FROM course_registrations WHERE registered_at BETWEEN start_date AND end_date`

### Completion Rate
- **Source:** `course_registrations` + `course_completions` tables
- **Calculation:** (Completed courses / Total registrations) × 100
- **Formula:** `(COUNT(course_completions) / COUNT(course_registrations)) × 100`

---

## 💻 Section 2: Online Course Analytics Overview

### Online Courses Delivered
- **Source:** `course_online` table
- **Calculation:** Count of active online courses
- **SQL:** `SELECT COUNT(*) FROM course_online WHERE is_active = true`

### Online Enrollments
- **Source:** `course_online_assignments` table
- **Calculation:** Total assignments created
- **SQL:** `SELECT COUNT(*) FROM course_online_assignments`

### Online Completed
- **Source:** `course_online_assignments` table
- **Calculation:** Count of completed assignments
- **SQL:** `SELECT COUNT(*) FROM course_online_assignments WHERE status = 'completed'`

### Online Completion Rate
- **Source:** `course_online_assignments` table
- **Calculation:** (Completed / Total Enrollments) × 100
- **Formula:** `(Completed assignments / Total assignments) × 100`

### Active Online Learners
- **Source:** `course_online_assignments` table
- **Calculation:** Distinct users with in_progress or completed status
- **SQL:** `SELECT COUNT(DISTINCT user_id) FROM course_online_assignments WHERE status IN ('in_progress', 'completed')`

---

## 🎥 Section 3: Video Engagement Metrics

### Videos Watched
- **Source:** `user_content_progress` table
- **Calculation:** Count of unique video content items accessed
- **SQL:** `SELECT COUNT(DISTINCT content_id) FROM user_content_progress WHERE content_type = 'video'`

### Average Video Completion
- **Source:** `user_content_progress` table
- **Calculation:** Average of completion_percentage for all video progress records
- **SQL:** `SELECT AVG(completion_percentage) FROM user_content_progress WHERE content_type = 'video'`
- **Example:** If 3 videos have 100%, 95%, and 90% completion → Average = 95%

### Total Watch Time
- **Source:** `user_content_progress` table
- **Calculation:** Sum of watch_time (in seconds) converted to hours
- **SQL:** `SELECT SUM(watch_time) / 3600 FROM user_content_progress WHERE content_type = 'video'`
- **Note:** watch_time is stored in seconds, divided by 3600 to get hours

### Video Replays
- **Source:** `learning_sessions` table
- **Calculation:** Sum of video_replay_count across all sessions
- **SQL:** `SELECT SUM(video_replay_count) FROM learning_sessions`

---

## 📚 Section 4: Online Module Progress

### Total Modules
- **Source:** `course_modules` table
- **Calculation:** Count of all modules in online courses
- **SQL:** `SELECT COUNT(*) FROM course_modules WHERE course_online_id IS NOT NULL`

### Completed Modules
- **Source:** `user_content_progress` table
- **Calculation:** Count of completed content items
- **SQL:** `SELECT COUNT(*) FROM user_content_progress WHERE is_completed = true`

### Average Modules Per User
- **Source:** `user_content_progress` table
- **Calculation:** Average number of completed modules per user
- **SQL:** 
```sql
SELECT AVG(modules_per_user) FROM (
    SELECT user_id, COUNT(DISTINCT content_id) as modules_per_user
    FROM user_content_progress
    WHERE is_completed = true
    GROUP BY user_id
) as user_stats
```

### Module Completion Rate
- **Source:** `user_content_progress` table
- **Calculation:** (Unique completed content / Total modules) × 100
- **Formula:** `(COUNT(DISTINCT completed content_id) / Total modules) × 100`

---

## ⏱️ Section 5: Learning Session Analytics

### Total Sessions
- **Source:** `learning_sessions` table
- **Calculation:** Count of all learning sessions
- **SQL:** `SELECT COUNT(*) FROM learning_sessions`

### Average Session Duration
- **Source:** `learning_sessions` table
- **Calculation:** Average of total_duration_minutes for sessions with duration > 0
- **SQL:** `SELECT AVG(total_duration_minutes) FROM learning_sessions WHERE total_duration_minutes > 0`
- **Fallback:** If no duration data, uses video_watch_time (seconds) / 60

### Attention Score
- **Source:** `learning_sessions` table
- **Calculation:** Average attention_score for sessions with score > 0
- **SQL:** `SELECT AVG(attention_score) FROM learning_sessions WHERE attention_score > 0`
- **Range:** 0-100 (higher is better)
- **Factors:**
  - Video completion percentage (50% weight)
  - Session pace vs expected duration (30% weight)
  - Engagement activities like pauses and replays (20% weight)

### Suspicious Activity Count
- **Source:** `learning_sessions` table
- **Calculation:** Count of sessions flagged as suspicious
- **SQL:** `SELECT COUNT(*) FROM learning_sessions WHERE is_suspicious_activity = true`
- **Triggers:**
  - High skip count (>5 skips)
  - Excessive seeking (>20 seeks)
  - Very fast completion (<50% of expected time)
  - Low video completion but high progress claim

### Total Learning Hours
- **Source:** `learning_sessions` table
- **Calculation:** Sum of total_duration_minutes converted to hours
- **SQL:** `SELECT SUM(total_duration_minutes) / 60 FROM learning_sessions`
- **Fallback:** If no duration data, uses SUM(video_watch_time) / 3600

---

## 📈 Section 6: Learning Outcomes

### Quiz Pass Rate
- **Source:** `quiz_attempts` table
- **Calculation:** (Passed attempts / Total attempts) × 100
- **SQL:** `SELECT (COUNT(*) WHERE passed = true / COUNT(*)) × 100 FROM quiz_attempts`

### Quiz Fail Rate
- **Source:** `quiz_attempts` table
- **Calculation:** 100 - Pass Rate
- **Formula:** `100 - Quiz Pass Rate`

### Average Quiz Score
- **Source:** `quiz_attempts` table
- **Calculation:** Average of total_score across all attempts
- **SQL:** `SELECT AVG(total_score) FROM quiz_attempts`

### Improvement Rate
- **Source:** `quiz_attempts` table
- **Calculation:** Percentage of users who improved on retakes
- **Logic:**
  1. Find all retakes (attempt_number > 1)
  2. Compare with first attempt score
  3. Count how many improved
  4. Calculate percentage

---

## ⭐ Section 7: Course Quality & Feedback

### Average Rating
- **Source:** `course_completions` table
- **Calculation:** Average of rating field
- **SQL:** `SELECT AVG(rating) FROM course_completions WHERE rating IS NOT NULL`
- **Range:** 1-5 stars

### Total Feedback Count
- **Source:** `course_completions` table
- **Calculation:** Count of records with feedback text
- **SQL:** `SELECT COUNT(*) FROM course_completions WHERE feedback IS NOT NULL`

### Feedback Sentiment
- **Source:** `course_completions` table
- **Calculation:** Based on rating values
- **Logic:**
  - **Positive:** rating >= 4 (4-5 stars)
  - **Neutral:** rating = 3 (3 stars)
  - **Negative:** rating <= 2 (1-2 stars)
- **Formula:** `(Count in category / Total ratings) × 100`

---

## 🏆 Section 8: Performance Analysis

### Top Performing Courses
- **Source:** `courses` + `course_registrations` + `course_completions` tables
- **Ranking Criteria:**
  1. Average rating (higher is better)
  2. Completion rate (higher is better)
- **SQL:**
```sql
SELECT 
    courses.name,
    AVG(course_completions.rating) as avg_rating,
    (COUNT(course_completions) / COUNT(course_registrations) × 100) as completion_rate
FROM courses
LEFT JOIN course_registrations ON courses.id = course_registrations.course_id
LEFT JOIN course_completions ON courses.id = course_completions.course_id
GROUP BY courses.id
ORDER BY avg_rating DESC, completion_rate DESC
LIMIT 5
```

### Courses Needing Improvement
- **Source:** Same as above
- **Criteria:**
  - Completion rate < 70% OR
  - Average rating < 3.5
- **Issues Identified:**
  - "Very low completion rate" if < 50%
  - "Low completion rate" if < 70%
  - "Poor ratings" if < 2.5
  - "Below average ratings" if < 3.5

### Top Performing Users
- **Source:** `users` + `course_completions` tables
- **Ranking Criteria:**
  1. Number of courses completed
  2. Average rating given
- **Performance Score Calculation:**
  - Rating >= 4: 90 points
  - Rating >= 3: 70 points
  - Rating < 3: 50 points

### Users Needing Support
- **Source:** `users` + `course_registrations` + `course_completions` tables
- **Criteria:**
  - High number of incomplete courses
  - Low performance scores
- **Calculation:** `courses_registered - courses_completed`

---

## 🏆 Section 9: Online Course Top Performers

### Top Online Courses
- **Source:** `course_online` + `course_online_assignments` tables
- **Ranking:** By completion rate
- **SQL:**
```sql
SELECT 
    course_online.name,
    COUNT(assignments) as total_enrolled,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    (completed / total_enrolled × 100) as completion_rate
FROM course_online
LEFT JOIN course_online_assignments ON course_online.id = assignments.course_online_id
GROUP BY course_online.id
ORDER BY completion_rate DESC
LIMIT 5
```

### Top Online Learners
- **Source:** `users` + `course_online_assignments` tables
- **Ranking Criteria:**
  1. Number of courses completed
  2. Average progress percentage
- **SQL:**
```sql
SELECT 
    users.name,
    COUNT(assignments) as courses_enrolled,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as courses_completed,
    AVG(progress_percentage) as avg_progress
FROM users
JOIN course_online_assignments ON users.id = assignments.user_id
GROUP BY users.id
ORDER BY courses_completed DESC, avg_progress DESC
LIMIT 5
```

---

## 📊 Section 10: Monthly Engagement Trend

### Current Month Engagement
- **Calculation:** Weighted score based on:
  - Attendance rate (40% weight)
  - Completion rate (40% weight)
  - Inverse of dropout rate (20% weight)
- **Formula:** `(Attendance × 0.4) + (Completion × 0.4) + ((100 - Dropout) × 0.2)`

### Trend Direction
- **Calculation:** Compare current month vs previous month
- **Logic:**
  - **Up:** Current > Previous
  - **Down:** Current < Previous
  - **Stable:** Current = Previous

### Trend Percentage
- **Formula:** `((Current - Previous) / Previous) × 100`
- **Example:** If previous = 70% and current = 80%, trend = +14.29%

---

## 🔄 Data Refresh & Caching

- **Real-time Data:** Most metrics are calculated in real-time from the database
- **No Caching:** Data is fetched fresh on each page load
- **Date Filtering:** When a specific month/year is selected, data is filtered by date ranges
- **Fallback Logic:** If no data exists for the selected period, all-time data is shown

---

## 📝 Notes for Clients

1. **Data Accuracy:** All metrics are calculated directly from your database tables
2. **Date Ranges:** Metrics can be filtered by month, year, department, or course
3. **Real-time Updates:** Data reflects the current state of your database
4. **Zero Values:** If you see 0, it means no data exists for that metric in the selected period
5. **Percentage Calculations:** All percentages are rounded to 2 decimal places
6. **Time Conversions:** 
   - Watch time is stored in seconds, displayed in hours
   - Session duration is stored in minutes, displayed in minutes or hours

---

## 🛠️ Technical Details

### Database Tables Used
- `courses` - Traditional course information
- `course_online` - Online course information
- `course_registrations` - Course enrollments
- `course_online_assignments` - Online course assignments
- `course_completions` - Course completion records
- `user_content_progress` - Video and content progress tracking
- `learning_sessions` - Detailed session tracking with engagement metrics
- `quiz_attempts` - Quiz attempt records
- `users` - User information
- `departments` - Department information

### Key Columns
- **user_content_progress:**
  - `content_type` - 'video' or 'pdf'
  - `watch_time` - Time spent in seconds
  - `completion_percentage` - 0-100
  - `is_completed` - Boolean flag

- **learning_sessions:**
  - `total_duration_minutes` - Session length in minutes
  - `video_watch_time` - Video watch time in seconds
  - `attention_score` - 0-100 engagement score
  - `is_suspicious_activity` - Boolean flag for cheating detection
  - `video_replay_count` - Number of video replays

---

**Last Updated:** December 2024  
**Version:** 1.0
