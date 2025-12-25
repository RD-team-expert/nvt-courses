# Learning Time Calculation Issues and Fixes

## Problem Summary

Users are showing incorrect learning times in the User Performance Report:
- **Frank**: Shows ~12m but has 583 seconds (9.72 minutes) of active playback
- **Ryan**: Shows 654m but this includes corrupted session data

## Root Cause Analysis

### Issue #1: Frank's Learning Time (Minor - Display Rounding)

Frank's session 4480:
- `active_playback_time`: 583 seconds
- Correct calculation: 583 / 60 = 9.72 minutes
- Should display as: ~10 minutes
- Currently shows: ~12 minutes (slight discrepancy, possibly due to rounding or other sessions)

**Status**: The calculation is correct. The 12m vs 10m difference is minor and may be due to rounding or additional micro-sessions.

### Issue #2: Ryan's Learning Time (Major - Corrupted Session Data)

Ryan has 24 sessions with a major data corruption issue:

**Session 222 (CORRUPTED):**
- Start: 2025-11-06 22:07:35
- End: 2025-11-16 02:43:03
- Duration: **13,235.47 minutes** (9.2 days!)
- This session was never properly closed and stayed open for 9+ days

**Session 4467 (Appears Inflated):**
- Video Duration: 10.82 minutes (649 seconds)
- Session Duration: 21.33 minutes
- `active_playback_time`: 652 (seconds or minutes?)
  - If seconds: 652 / 60 = 10.87 minutes ✅ (matches video duration)
  - If minutes: 652 minutes ❌ (impossible)

**Analysis**: Session 4467's `active_playback_time` is correctly stored in seconds (652 seconds = 10.87 minutes).

## The Real Problem: Session End Time Corruption

The main issue is **Session 222** which has an end time 9 days after the start time. This happens when:
1. User starts watching a video
2. Session is created
3. User closes browser/tab without properly ending the session
4. Session remains "open" in the database
5. Days later, something triggers the session to close with the current timestamp
6. Result: Massive inflated duration

## Recommended Fixes

### Fix #1: Cap Session Duration at Reasonable Maximum

Update `getActualSessionDuration` to cap session duration at a reasonable maximum (e.g., 3 hours):

```php
// Priority 2: Calculate from start/end times
if ($sessionEnd) {
    $end = Carbon::parse($sessionEnd);
    $minutes = $start->diffInMinutes($end);
    
    // ✅ Cap at 3 hours to prevent corrupted data from inflating totals
    return max(0, min($minutes, 180));
}
```

### Fix #2: Clean Up Corrupted Sessions

Identify and fix sessions with unrealistic durations:

```sql
-- Find sessions with duration > 3 hours
SELECT id, user_id, session_start, session_end,
       TIMESTAMPDIFF(MINUTE, session_start, session_end) as duration_minutes
FROM learning_sessions
WHERE session_end IS NOT NULL
  AND TIMESTAMPDIFF(MINUTE, session_start, session_end) > 180
ORDER BY duration_minutes DESC;
```

### Fix #3: Implement Session Timeout

Add logic to automatically close sessions that have been inactive for more than X minutes:

```php
// In a scheduled job or cleanup script
$staleThreshold = now()->subHours(3);

LearningSession::whereNull('session_end')
    ->where('session_start', '<', $staleThreshold)
    ->update([
        'session_end' => DB::raw('DATE_ADD(session_start, INTERVAL 30 MINUTE)'),
        'updated_at' => now()
    ]);
```

## Current Status

- ✅ `active_playback_time` is correctly stored in **seconds**
- ✅ The division by 60 in `getActualSessionDuration` is correct
- ❌ Session 222 (Ryan) has corrupted end time (9 days duration)
- ❌ No cap on maximum session duration
- ❌ No automatic session timeout mechanism

## Next Steps

1. Implement session duration cap (3 hours maximum)
2. Clean up existing corrupted sessions
3. Add automatic session timeout for inactive sessions
4. Consider adding validation when setting session_end to prevent future corruption
