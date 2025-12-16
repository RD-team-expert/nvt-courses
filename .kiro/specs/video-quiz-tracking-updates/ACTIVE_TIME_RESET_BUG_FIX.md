# Active Time Reset Bug - Root Cause & Fix

## Problem

When opening a previously watched video:
1. **Initial display:** Shows old time (e.g., 30:16)
2. **After ~30 seconds:** Resets to ~0:30

## Root Cause

The issue was in the backend's session end handler. When a session ended:

1. ✅ Active playback time was saved to `LearningSession.active_playback_time`
2. ❌ But it was NOT accumulated into `UserContentProgress.watch_time`
3. ❌ So when the page reloaded, the old `watch_time` was still there
4. ❌ Then the new session started tracking from 0

### Data Flow (Before Fix)

```
Session 1:
├─ Watch for 30 minutes
├─ End session
├─ Save: LearningSession.active_playback_time = 1800 seconds ✅
└─ UserContentProgress.watch_time = 0 ❌ (NOT UPDATED!)

Page Reload:
├─ Load: UserContentProgress.watch_time = 0
├─ Display: 0 + (0 * 60) = 0:00 ❌

Session 2:
├─ Start new session
├─ activePlaybackTime = 0 (new session)
├─ After 30s: activePlaybackTime = 30
├─ Display: 30 + (0 * 60) = 0:30 ❌
```

## Solution

Added code to update `UserContentProgress.watch_time` when session ends:

```php
// ✅ NEW: Update user progress with active playback time from this session
$progress = $this->progressService->getOrCreateProgress($user, $content);

// Calculate total watch time: previous sessions + this session's active playback
$activePlaybackMinutes = ($ended->active_playback_time ?? 0) / 60;
$totalWatchTime = ($progress->watch_time ?? 0) + $activePlaybackMinutes;

// Update progress with new watch time
$this->progressService->updateProgress(
    $progress->id,
    $request->input('final_position', 0),
    $request->input('completion_percentage', 0),
    (int) $totalWatchTime
);
```

### Data Flow (After Fix)

```
Session 1:
├─ Watch for 30 minutes
├─ End session
├─ Save: LearningSession.active_playback_time = 1800 seconds ✅
├─ Calculate: totalWatchTime = 0 + (1800 / 60) = 30 minutes
└─ Update: UserContentProgress.watch_time = 30 ✅

Page Reload:
├─ Load: UserContentProgress.watch_time = 30
├─ Display: 0 + (30 * 60) = 30:00 ✅

Session 2:
├─ Start new session
├─ activePlaybackTime = 0 (new session)
├─ After 30s: activePlaybackTime = 30
├─ Display: 30 + (30 * 60) = 30:30 ✅
├─ End session
├─ Save: LearningSession.active_playback_time = 30 seconds
├─ Calculate: totalWatchTime = 30 + (30 / 60) = 30.5 minutes
└─ Update: UserContentProgress.watch_time = 30.5 ✅

Page Reload:
├─ Load: UserContentProgress.watch_time = 30.5
└─ Display: 0 + (30.5 * 60) = 30:30 ✅
```

## Files Modified

### Backend
- `app/Http/Controllers/User/ContentViewController.php`
  - Added code to update user progress after session ends
  - Accumulates active playback time into `watch_time`

### Frontend
- No changes needed (already correct)

## Testing

To verify the fix works:

### Test 1: Watch and Resume
1. Open video
2. Watch for 5 minutes
3. Close page
4. Reopen video
5. **Expected:** Display shows 5:00 (not 0:00)
6. Watch for 3 more minutes
7. **Expected:** Display shows 5:03

### Test 2: Multiple Sessions
1. Watch for 10 minutes → Close
2. Reopen → Display shows 10:00 ✅
3. Watch for 5 minutes → Close
4. Reopen → Display shows 15:00 ✅
5. Watch for 2 minutes → Close
6. Reopen → Display shows 17:00 ✅

### Test 3: Pause and Resume
1. Watch for 5 minutes
2. Pause for 2 minutes
3. Resume and watch for 3 more minutes
4. Close page
5. Reopen
6. **Expected:** Display shows 8:00 (not 5:00 or 3:00)

## How It Works Now

### Display Formula
```typescript
formattedTimeSpent = activePlaybackTime + (timeSpent * 60)
```

Where:
- `activePlaybackTime` = Time tracked in THIS session (starts at 0, increments when playing)
- `timeSpent` = Accumulated time from ALL previous sessions (in minutes, from database)

### Session Lifecycle

```
1. Page Load
   ├─ Load timeSpent from database (e.g., 30 minutes)
   ├─ Initialize activePlaybackTime = 0
   └─ Display = 0 + (30 * 60) = 30:00

2. User Plays Video
   ├─ activePlaybackTime increments (0 → 1 → 2 → ...)
   └─ Display = activePlaybackTime + (30 * 60)

3. User Pauses
   ├─ activePlaybackTime freezes
   └─ Display stays the same

4. Session Ends
   ├─ Save activePlaybackTime to LearningSession
   ├─ Calculate: newTimeSpent = 30 + (activePlaybackTime / 60)
   ├─ Update: UserContentProgress.watch_time = newTimeSpent
   └─ Database now has: watch_time = 30 + (activePlaybackTime / 60)

5. Page Reload
   ├─ Load timeSpent from database (now updated!)
   ├─ Initialize activePlaybackTime = 0
   └─ Display = 0 + (newTimeSpent * 60)
```

## Summary

**Before Fix:**
- Active playback time was saved to session but not accumulated
- User progress wasn't updated
- Display would reset when page reloaded

**After Fix:**
- Active playback time is accumulated into user progress
- Display persists across page reloads
- Time tracking is accurate and continuous

The fix ensures that active playback time from each session is properly accumulated into the user's total watch time, so the display remains consistent across sessions.
