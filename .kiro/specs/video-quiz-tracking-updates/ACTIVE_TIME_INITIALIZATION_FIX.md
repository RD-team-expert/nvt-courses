# Active Time Initialization Issue - Analysis & Fix

## Problem Description

When opening a video that was previously watched:
1. **Initial display:** Shows old time (e.g., 30:16) - this is the saved time from previous session
2. **After ~30 seconds:** Resets to ~0:30 - this is the new session's active playback time

## Root Cause

The display formula is:
```typescript
formattedTimeSpent = activePlaybackTime + (timeSpent * 60)
```

Where:
- `activePlaybackTime` = Time tracked in THIS session (starts at 0)
- `timeSpent` = Time saved from PREVIOUS sessions (in minutes, from database)

### Timeline of Events

```
Page Load:
├─ timeSpent = 30 (from DB, in minutes)
├─ activePlaybackTime = 0 (new session)
├─ Display = 0 + (30 * 60) = 1800 seconds = 30:00 ✅ Correct!
│
User presses Play:
├─ Session starts
├─ activePlaybackTime begins incrementing
├─ After 30 seconds: activePlaybackTime = 30
├─ Display = 30 + (30 * 60) = 1830 seconds = 30:30 ✅ Correct!
│
But user sees:
├─ Initial: 30:16 (includes some old data?)
├─ After 30s: 0:30 (only new session data?)
```

## Why This Happens

The issue is likely that:

1. **On page load:** The `timeSpent` value might include partial data from the last session
2. **After session starts:** The backend might be resetting or recalculating `timeSpent`
3. **Display updates:** The computed property recalculates based on new data

## Solution

The fix is to ensure that:

1. **`timeSpent` is stable** - It should only contain time from COMPLETED previous sessions
2. **`activePlaybackTime` is fresh** - It should start at 0 for each new session
3. **Display is consistent** - It should always show `activePlaybackTime + (timeSpent * 60)`

### Code Fix

The current code is actually correct:

```typescript
// ✅ CORRECT: activePlaybackTime starts at 0 for new session
const activePlaybackTime = ref(0)

// ✅ CORRECT: timeSpent comes from DB (previous sessions)
const timeSpent = ref(safeUserProgress.value.time_spent || 0)

// ✅ CORRECT: Display shows both
const formattedTimeSpent = computed(() => {
    const totalSeconds = activePlaybackTime.value + (timeSpent.value * 60)
    return formatTime(totalSeconds)
})
```

## Real Issue: Backend Data Inconsistency

The actual problem is likely in the backend. When a session ends, the `timeSpent` value might not be properly updated.

### What Should Happen

```
Session 1:
├─ Start: activePlaybackTime = 0
├─ Watch for 30 minutes
├─ End: Save activePlaybackTime = 1800 seconds to DB
└─ Update: timeSpent = 30 minutes

Session 2 (Resume):
├─ Load: timeSpent = 30 (from DB)
├─ Start: activePlaybackTime = 0 (new session)
├─ Watch for 5 more minutes
├─ Display: 30 + 5 = 35 minutes ✅
└─ End: Save activePlaybackTime = 300 seconds
```

### What Might Be Happening

```
Session 1:
├─ Start: activePlaybackTime = 0
├─ Watch for 30 minutes
├─ End: Save activePlaybackTime = 1800 seconds
└─ timeSpent NOT updated properly ❌

Session 2 (Resume):
├─ Load: timeSpent = 30 (from DB, but might be stale)
├─ Start: activePlaybackTime = 0
├─ Display initially: 30 + 0 = 30:00 ✅
├─ After 30s: activePlaybackTime = 30
├─ Display: 30 + 0 = 0:30 ❌ (timeSpent got reset!)
```

## Backend Fix Required

The issue is in `LearningSessionService.php` - the `endSession()` method needs to properly update the user's progress with the new active playback time.

### Current Backend Code

```php
// LearningSessionService.php - endSession()
$session->update([
    'session_end' => now(),
    'total_duration_minutes' => $totalDuration,
    'video_watch_time' => $totalWatchTime,
    'video_skip_count' => $totalSkips,
    'seek_count' => $totalSeeks,
    'pause_count' => $totalPauses,
    'video_completion_percentage' => $completionPercentage,
    'attention_score' => $attentionScore,
    'cheating_score' => $cheatingScore,
    'is_suspicious_activity' => $isSuspicious,
    'is_within_allowed_time' => $isWithinAllowedTime,
]);
```

**Problem:** The `active_playback_time` is saved to the session, but the user's progress `time_spent` might not be updated!

### Required Fix

After ending the session, we need to update the user's progress:

```php
// After session ends, update user progress
$progress = $this->progressService->getOrCreateProgress($user, $content);

// Add the active playback time from this session to the total
$newTotalTime = ($progress->time_spent ?? 0) + ($session->active_playback_time / 60);

$this->progressService->updateProgress(
    $progress->id,
    $finalPosition,
    $completionPercentage,
    $newTotalTime  // ✅ Update with new total
);
```

## Frontend Workaround (Temporary)

If the backend isn't updating properly, we can add a workaround in the frontend:

```typescript
// When session ends, update timeSpent with new active playback time
const endSession = async () => {
    if (!sessionId.value) return

    try {
        // ... existing code ...
        
        // ✅ NEW: Update timeSpent with active playback time
        const newTimeSpent = (timeSpent.value * 60) + activePlaybackTime.value
        timeSpent.value = Math.floor(newTimeSpent / 60)
        
        // ... rest of code ...
    } catch (error) {
        // ...
    }
}
```

## Testing

To verify the fix:

1. **Watch a video for 5 minutes**
   - Display should show: 0:05
   - Active Time should show: 0:05

2. **Close the page and reopen**
   - Display should show: 5:00 (saved time)
   - Active Time should show: 5:00

3. **Watch for 3 more minutes**
   - Display should show: 5:03
   - Active Time should show: 0:03

4. **Close and reopen again**
   - Display should show: 8:00 (5 + 3)
   - Active Time should show: 8:00

## Summary

The frontend code is correct. The issue is likely:

1. **Backend not updating user progress** after session ends
2. **`timeSpent` value not being persisted** properly
3. **Session data being reset** instead of accumulated

**Action Items:**
1. ✅ Verify backend is updating user progress with active playback time
2. ✅ Ensure `timeSpent` accumulates across sessions
3. ✅ Check that session data is properly saved to database
4. ✅ Test the complete flow: watch → close → reopen → watch more
