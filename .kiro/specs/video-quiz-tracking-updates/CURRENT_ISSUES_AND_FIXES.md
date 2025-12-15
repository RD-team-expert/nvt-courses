# Current Issues and Required Fixes

## Date: December 14, 2025

## Issues Identified

### 1. Time Spent Counter Continues During Pause/Buffering ❌

**Problem:** The "Time Spent" display shows `sessionElapsedSeconds` which increments every second regardless of video state.

**Current Code (Frontend - Show.vue line ~1020):**
```typescript
const sessionTimeInterval = setInterval(() => {
    if (sessionStartTime.value) {
        sessionElapsedSeconds.value = Math.floor((Date.now() - sessionStartTime.value) / 1000)
    }
}, 1000)  // Updates every second
```

**Issue:** This counter runs continuously from when the page loads, not just during active playback.

**Backend Issue:** The `total_duration_minutes` in `LearningSessionService` is calculated as:
```php
$currentDurationSeconds = max(0, now()->diffInSeconds($session->session_start));
$currentDuration = (int) ceil($currentDurationSeconds / 60);
```

This calculates wall-clock time from session start, not active playback time.

---

### 2. Allowed Time Display Positioned Incorrectly ❌

**Problem:** The "You are expected to complete this video within X minutes" message appears as a banner above the video player instead of in a sidebar card.

**Current Code (Frontend - Show.vue line ~1178):**
```vue
<!-- ✅ NEW: Allowed Time Display (Task 6.3) -->
<div v-if="allowedTimeDisplay && isVideoReady && !isCompleted" 
     class="px-4 py-3 bg-blue-50 border-b border-blue-100">
    <div class="flex items-center gap-2 text-sm text-blue-700">
        <Clock class="h-4 w-4" />
        <span>{{ allowedTimeDisplay }}</span>
    </div>
</div>
```

**Issue:** This is positioned inside the video card, not in the sidebar with other cards (Progress, Time Spent, Status).

---

### 3. Post-Completion Tracking Not Fully Implemented ❌

**Problem:** While Task 8.1 added checks to skip tracking for completed videos, the implementation has gaps:

**Current Code (Frontend - Show.vue):**
```typescript
// ✅ Task 8.1: Skip tracking for completed videos
if (isCompleted.value && props.content.content_type === 'video') {
    // console.log('⏭️ Skipping session creation - video already completed')
    return
}
```

**Issues:**
1. The `sessionElapsedSeconds` counter still runs for completed videos
2. The "Time Spent" card still displays for completed videos
3. No clear visual indicator that tracking is disabled

---

## Root Cause Analysis

### Time Tracking Architecture

The current implementation has **TWO separate time tracking mechanisms**:

1. **Frontend Display Time** (`sessionElapsedSeconds`):
   - Starts when component mounts
   - Increments every second via `setInterval`
   - Used ONLY for display in "Time Spent" card
   - **Problem:** Runs continuously, not tied to video state

2. **Backend Session Time** (`total_duration_minutes`):
   - Calculated as wall-clock time from `session_start` to `now()`
   - Updated on heartbeat and session end
   - **Problem:** Includes loading, buffering, pauses

3. **Active Playback Time** (`active_playback_time`):
   - NEW field added in Task 6.1
   - Tracked in frontend via `updateActivePlaybackTime()`
   - Only increments when `isActivelyPlaying && !isBuffering && !isVideoLoading`
   - **This is the CORRECT metric but not used for display!**

---

## Required Fixes

### Fix 1: Use Active Playback Time for Display

**Frontend Changes (Show.vue):**

1. **Remove the `sessionElapsedSeconds` interval** (line ~1020):
```typescript
// ❌ REMOVE THIS:
const sessionTimeInterval = setInterval(() => {
    if (sessionStartTime.value) {
        sessionElapsedSeconds.value = Math.floor((Date.now() - sessionStartTime.value) / 1000)
    }
}, 1000)
```

2. **Update `formattedTimeSpent` computed** (line ~1050):
```typescript
// ✅ CHANGE FROM:
const formattedTimeSpent = computed(() => {
    const totalSeconds = (timeSpent.value * 60) + sessionElapsedSeconds.value
    return formatTime(totalSeconds)
})

// ✅ CHANGE TO:
const formattedTimeSpent = computed(() => {
    // Use active playback time (already in seconds) + saved time from DB (in minutes)
    const totalSeconds = activePlaybackTime.value + (timeSpent.value * 60)
    return formatTime(totalSeconds)
})
```

3. **Ensure `updateActivePlaybackTime()` runs every second** (already implemented at line ~1015):
```typescript
const activePlaybackInterval = setInterval(() => {
    if (props.content.content_type === 'video') {
        updateActivePlaybackTime()
    }
}, 1000)  // ✅ Already correct
```

---

### Fix 2: Move Allowed Time Display to Sidebar Card

**Frontend Changes (Show.vue):**

1. **Remove the banner** (line ~1178-1186):
```vue
<!-- ❌ REMOVE THIS ENTIRE SECTION -->
<div v-if="allowedTimeDisplay && isVideoReady && !isCompleted" 
     class="px-4 py-3 bg-blue-50 border-b border-blue-100">
    ...
</div>
```

2. **Add new card in sidebar** (after "Time Spent" card, around line ~1450):
```vue
<!-- Allowed Time Card -->
<Card v-if="content.content_type === 'video' && allowedTimeDisplay && !isCompleted">
    <CardContent class="p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium flex items-center gap-2">
                <Clock class="h-4 w-4 text-amber-500" />
                Expected Time
            </span>
        </div>
        <div class="text-sm text-muted-foreground">
            {{ allowedTimeDisplay }}
        </div>
        <div class="text-xs text-muted-foreground mt-1">
            Video Duration × 2
        </div>
    </CardContent>
</Card>
```

---

### Fix 3: Improve Post-Completion Behavior

**Frontend Changes (Show.vue):**

1. **Stop `activePlaybackInterval` for completed videos** (in `onMounted`, line ~1015):
```typescript
const activePlaybackInterval = setInterval(() => {
    // ✅ ADD CHECK:
    if (props.content.content_type === 'video' && !isCompleted.value) {
        updateActivePlaybackTime()
    }
}, 1000)
```

2. **Hide "Time Spent" card for completed videos** (line ~1430):
```vue
<!-- Time Spent Card -->
<Card v-if="!isCompleted">  <!-- ✅ ADD THIS CONDITION -->
    <CardContent class="p-4">
        ...
    </CardContent>
</Card>
```

3. **Enhance completed video indicator** (line ~1175):
```vue
<div v-if="isCompleted" class="px-4 py-3 bg-green-50 border-b border-green-200">
    <div class="flex items-center gap-2 text-sm text-green-700">
        <CheckCircle class="h-4 w-4" />
        <span class="font-medium">Video Completed</span>
        <span class="text-green-600">• Re-watching is optional and untracked</span>
    </div>
</div>
```

---

### Fix 4: Backend - Ensure Active Playback Time is Primary Metric

**Backend Changes (LearningSessionService.php):**

The backend already has the correct logic in `endSession()` (line ~180):
```php
// Calculate if within allowed time (Duration × 2)
$isWithinAllowedTime = $this->isWithinAllowedTime($session);

// Calculate scores using active playback time if available
$durationForScore = $totalDuration;
if ($session->active_playback_time) {
    // Use active playback time (convert from seconds to minutes)
    $durationForScore = (int) ceil($session->active_playback_time / 60);
}
```

**✅ No backend changes needed** - the logic is already correct!

---

## Summary of Changes

### Frontend (Show.vue)

| Line | Change | Reason |
|------|--------|--------|
| ~1020 | Remove `sessionTimeInterval` | Use active playback time instead |
| ~1050 | Update `formattedTimeSpent` | Display active playback time |
| ~1015 | Add `!isCompleted.value` check | Stop tracking completed videos |
| ~1178 | Remove allowed time banner | Move to sidebar |
| ~1450 | Add "Expected Time" card | Better UX placement |
| ~1430 | Hide "Time Spent" for completed | Clearer post-completion state |

### Backend

**✅ No changes needed** - Active playback time logic is already implemented correctly.

---

## Testing Checklist

After implementing fixes:

- [ ] Play video - "Time Spent" should only increment when video is playing
- [ ] Pause video - "Time Spent" should freeze
- [ ] Video buffering - "Time Spent" should not increment
- [ ] "Expected Time" card appears in sidebar (not above video)
- [ ] Complete video - "Time Spent" card disappears
- [ ] Re-watch completed video - No tracking occurs
- [ ] Completed video shows green banner with "untracked" message

---

## Client Requirements Mapping

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| 1. Loading/buffering must not count | ✅ Implemented | `updateActivePlaybackTime()` checks `!isBuffering && !isVideoLoading` |
| 2. Allowed time = Duration × 2 | ✅ Implemented | `allowedTimeMinutes` computed property |
| 3. Pause/rewind freely | ✅ Implemented | No penalties in `calculateAttentionScoreWithActiveTime()` |
| 4. Static label (no countdown) | ⚠️ Needs repositioning | Move to sidebar card |
| 5. Post-completion no tracking | ⚠️ Partially implemented | Need to stop display counter |
| 6. Display active playback time | ❌ Not implemented | Currently showing wall-clock time |

---

## Next Steps

1. Implement Fix 1 (Use active playback time for display)
2. Implement Fix 2 (Move allowed time to sidebar)
3. Implement Fix 3 (Improve post-completion behavior)
4. Test all scenarios
5. Update documentation
