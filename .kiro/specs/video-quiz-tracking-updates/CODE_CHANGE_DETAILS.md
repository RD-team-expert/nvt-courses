# Code Change Details - Active Time Reset Bug Fix

## File Modified
`app/Http/Controllers/User/ContentViewController.php`

## Location
In the `manageSession()` method, case `'end':`

## Before (Lines 155-170)

```php
$ended = $this->sessionService->endSession(
    $session->id,
    $request->input('final_position', $request->input('current_position', 0)),
    $request->input('completion_percentage', 0),
    $request->input('final_watch_time', $request->input('watch_time', 0)),
    $request->input('final_skip', $request->input('skip_count', 0)),
    $request->input('final_seek', $request->input('seek_count', 0)),
    $request->input('final_pause', $request->input('pause_count', 0))
);



return response()->json([
    'success' => true,
    'attention_score' => $ended->attention_score,
    'cheating_score' => $ended->cheating_score,
    'is_suspicious' => $ended->is_suspicious_activity,
]);
```

## After (Lines 155-180)

```php
$ended = $this->sessionService->endSession(
    $session->id,
    $request->input('final_position', $request->input('current_position', 0)),
    $request->input('completion_percentage', 0),
    $request->input('final_watch_time', $request->input('watch_time', 0)),
    $request->input('final_skip', $request->input('skip_count', 0)),
    $request->input('final_seek', $request->input('seek_count', 0)),
    $request->input('final_pause', $request->input('pause_count', 0))
);

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

return response()->json([
    'success' => true,
    'attention_score' => $ended->attention_score,
    'cheating_score' => $ended->cheating_score,
    'is_suspicious' => $ended->is_suspicious_activity,
]);
```

## What Was Added

### Lines 165-167: Get User Progress
```php
$progress = $this->progressService->getOrCreateProgress($user, $content);
```
- Retrieves the user's progress record for this content
- Creates one if it doesn't exist

### Lines 169-171: Calculate Total Watch Time
```php
$activePlaybackMinutes = ($ended->active_playback_time ?? 0) / 60;
$totalWatchTime = ($progress->watch_time ?? 0) + $activePlaybackMinutes;
```
- Converts active playback time from seconds to minutes
- Adds it to the existing watch time from previous sessions
- Uses null coalescing to handle missing values

### Lines 173-179: Update Progress
```php
$this->progressService->updateProgress(
    $progress->id,
    $request->input('final_position', 0),
    $request->input('completion_percentage', 0),
    (int) $totalWatchTime
);
```
- Updates the user's progress with the new total watch time
- Passes the final position and completion percentage
- Casts watch time to integer

## Why This Fixes the Bug

### Before
1. Session ends
2. Active playback time saved to `LearningSession.active_playback_time`
3. User progress NOT updated
4. Page reload shows old data
5. New session starts from 0

### After
1. Session ends
2. Active playback time saved to `LearningSession.active_playback_time`
3. **NEW:** Active playback time accumulated into `UserContentProgress.watch_time`
4. Page reload shows updated data
5. New session continues from correct time

## Data Flow Example

### Scenario: Watch 30 minutes, close, reopen, watch 5 more minutes

#### Before Fix ❌
```
Session 1:
├─ activePlaybackTime = 1800 seconds (30 minutes)
├─ LearningSession.active_playback_time = 1800 ✅
└─ UserContentProgress.watch_time = 0 ❌

Page Reload:
├─ Display = 0 + (0 * 60) = 0:00 ❌

Session 2:
├─ activePlaybackTime = 300 seconds (5 minutes)
├─ Display = 300 + (0 * 60) = 5:00 ❌
└─ LearningSession.active_playback_time = 300 ✅
```

#### After Fix ✅
```
Session 1:
├─ activePlaybackTime = 1800 seconds (30 minutes)
├─ LearningSession.active_playback_time = 1800 ✅
├─ Calculate: totalWatchTime = 0 + (1800 / 60) = 30
└─ UserContentProgress.watch_time = 30 ✅

Page Reload:
├─ Display = 0 + (30 * 60) = 30:00 ✅

Session 2:
├─ activePlaybackTime = 300 seconds (5 minutes)
├─ Display = 300 + (30 * 60) = 35:00 ✅
├─ LearningSession.active_playback_time = 300 ✅
├─ Calculate: totalWatchTime = 30 + (300 / 60) = 35
└─ UserContentProgress.watch_time = 35 ✅
```

## Testing the Fix

### Manual Test
1. Open browser DevTools (F12)
2. Go to Network tab
3. Watch video for 5 minutes
4. Close page
5. Reopen page
6. Check Network tab for POST to `/content/{id}/session` with `action=end`
7. Verify response includes the updated watch time

### Automated Test
```php
// Test that watch time is accumulated
$user = User::factory()->create();
$content = ModuleContent::factory()->create();

// First session: 30 minutes
$session1 = LearningSession::create([
    'user_id' => $user->id,
    'content_id' => $content->id,
    'active_playback_time' => 1800, // 30 minutes
]);

// Simulate session end
$this->post("/content/{$content->id}/session", [
    'action' => 'end',
    'active_playback_time' => 1800,
    'completion_percentage' => 50,
]);

// Check progress was updated
$progress = UserContentProgress::where('user_id', $user->id)
    ->where('content_id', $content->id)
    ->first();

$this->assertEquals(30, $progress->watch_time); // ✅ Should be 30 minutes
```

## Verification

After applying this fix, verify:

1. ✅ No syntax errors in the file
2. ✅ Active playback time is accumulated on session end
3. ✅ Display persists across page reloads
4. ✅ Multiple sessions accumulate correctly
5. ✅ Pauses don't affect the total time

All checks should pass!
