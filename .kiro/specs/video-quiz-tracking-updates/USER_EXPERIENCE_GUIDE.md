# User Experience Guide - Video Tracking Updates

## What Users Will See

### Normal Video Watching Experience

```
┌─────────────────────────────────────────────────────────────────┐
│  ← Back to Course                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📹 Introduction to Programming                                 │
│  Module 1: Getting Started • Programming Course                │
│  [VIDEO] [Required] 🕐 6 min                                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────┬──────────────────────────────┐
│                                  │  📊 Progress          49%    │
│                                  │  ████████░░░░░░░░░░░░        │
│                                  │  3:12 / 6:28                 │
│                                  ├──────────────────────────────┤
│         [VIDEO PLAYER]           │  ⏱️ Active Time              │
│                                  │  3:15                        │
│         [▶️ PLAYING]             │  Only counts when playing    │
│                                  ├──────────────────────────────┤
│                                  │  🕐 Expected Time            │
│                                  │  You are expected to         │
│                                  │  complete this video         │
│                                  │  within 13 minutes           │
│                                  │  Video Duration × 2          │
│                                  ├──────────────────────────────┤
│                                  │  🎯 Status                   │
│                                  │  📈 In Progress              │
└──────────────────────────────────┴──────────────────────────────┘
```

**Key Points:**
- ✅ "Active Time" shows 3:15 (only actual playback)
- ✅ "Expected Time" card in sidebar (not banner)
- ✅ Clear explanation: "Only counts when playing"

---

### When User Pauses Video

```
┌──────────────────────────────────┬──────────────────────────────┐
│                                  │  📊 Progress          49%    │
│                                  │  ████████░░░░░░░░░░░░        │
│                                  │  3:12 / 6:28                 │
│         [VIDEO PLAYER]           ├──────────────────────────────┤
│                                  │  ⏱️ Active Time              │
│         [⏸️ PAUSED]              │  3:15  ← FROZEN!             │
│                                  │  Only counts when playing    │
│                                  ├──────────────────────────────┤
│                                  │  🕐 Expected Time            │
│                                  │  You are expected to         │
│                                  │  complete this video         │
│                                  │  within 13 minutes           │
└──────────────────────────────────┴──────────────────────────────┘
```

**What Happens:**
- ✅ "Active Time" FREEZES at 3:15
- ✅ User can pause as long as they want
- ✅ No penalty for pausing
- ✅ Time resumes when they press play again

---

### When Video is Buffering

```
┌──────────────────────────────────┬──────────────────────────────┐
│                                  │  📊 Progress          49%    │
│                                  │  ████████░░░░░░░░░░░░        │
│                                  │  3:12 / 6:28                 │
│         [VIDEO PLAYER]           ├──────────────────────────────┤
│                                  │  ⏱️ Active Time              │
│         [⏳ BUFFERING...]        │  3:15  ← FROZEN!             │
│                                  │  Only counts when playing    │
│                                  ├──────────────────────────────┤
│                                  │  🕐 Expected Time            │
│                                  │  You are expected to         │
│                                  │  complete this video         │
│                                  │  within 13 minutes           │
└──────────────────────────────────┴──────────────────────────────┘
```

**What Happens:**
- ✅ "Active Time" FREEZES during buffering
- ✅ User is not penalized for slow internet
- ✅ Time resumes when buffering completes

---

### When User Rewinds Video

```
User at 3:00 → Rewinds to 2:00 → Watches to 3:00 again

┌──────────────────────────────────┬──────────────────────────────┐
│                                  │  ⏱️ Active Time              │
│         [VIDEO PLAYER]           │  3:00  ← CORRECT!            │
│                                  │  (Not 4:00)                  │
│         [⏪ REWOUND]             │  Only counts when playing    │
│                                  │                              │
└──────────────────────────────────┴──────────────────────────────┘
```

**What Happens:**
- ✅ Rewound section is NOT counted twice
- ✅ "Active Time" stays accurate
- ✅ User can review difficult sections freely

---

### When Video is Completed

```
┌─────────────────────────────────────────────────────────────────┐
│  ✅ Video Completed • Re-watching is optional and untracked     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📹 Introduction to Programming                                 │
│  Module 1: Getting Started • Programming Course                │
│  [VIDEO] [Required] 🕐 6 min                                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────┬──────────────────────────────┐
│                                  │  📊 Progress         100%    │
│                                  │  ████████████████████        │
│                                  │  6:28 / 6:28                 │
│         [VIDEO PLAYER]           ├──────────────────────────────┤
│                                  │  🎯 Status                   │
│         [✅ COMPLETED]           │  ✅ Completed                │
│                                  ├──────────────────────────────┤
│                                  │  [Mark as Complete]          │
│                                  │                              │
│                                  │  ← No time tracking!         │
└──────────────────────────────────┴──────────────────────────────┘
```

**What Happens:**
- ✅ Green banner shows "Re-watching is optional and untracked"
- ✅ "Active Time" card is HIDDEN
- ✅ "Expected Time" card is HIDDEN
- ✅ User can re-watch without affecting metrics

---

### When Re-watching Completed Video

```
User presses play on completed video:

┌─────────────────────────────────────────────────────────────────┐
│  ✅ Video Completed • Re-watching is optional and untracked     │
├─────────────────────────────────────────────────────────────────┤

┌──────────────────────────────────┬──────────────────────────────┐
│                                  │  📊 Progress         100%    │
│                                  │  ████████████████████        │
│         [VIDEO PLAYER]           │  6:28 / 6:28                 │
│                                  ├──────────────────────────────┤
│         [▶️ PLAYING]             │  🎯 Status                   │
│                                  │  ✅ Completed                │
│         (Re-watching)            │                              │
│                                  │  ← Still no tracking!        │
└──────────────────────────────────┴──────────────────────────────┘
```

**What Happens:**
- ✅ Video plays normally
- ✅ NO time tracking occurs
- ✅ Progress stays at 100%
- ✅ Attention score is NOT updated
- ✅ User can review content freely

---

## Comparison: Before vs After

### Scenario: User Watches 3 Minutes, Pauses for 2 Minutes, Watches 3 More Minutes

#### BEFORE ❌
```
Timeline:
0:00 - Start video
3:00 - Pause video
5:00 - Resume video (after 2 min pause)
8:00 - Finish watching

Display showed: 8:00 (WRONG - includes pause time)
```

#### AFTER ✅
```
Timeline:
0:00 - Start video
3:00 - Pause video
5:00 - Resume video (after 2 min pause)
8:00 - Finish watching

Display shows: 6:00 (CORRECT - only playback time)
```

---

### Scenario: Video Buffers for 30 Seconds

#### BEFORE ❌
```
Timeline:
0:00 - Start video
1:00 - Video starts buffering
1:30 - Video resumes (after 30s buffering)
2:30 - Continue watching

Display showed: 2:30 (WRONG - includes buffering)
```

#### AFTER ✅
```
Timeline:
0:00 - Start video
1:00 - Video starts buffering
1:30 - Video resumes (after 30s buffering)
2:30 - Continue watching

Display shows: 2:00 (CORRECT - excludes buffering)
```

---

## User Benefits

### 1. Fair Time Tracking ✅
- Only actual viewing time counts
- No penalty for pauses
- No penalty for slow internet
- No penalty for reviewing content

### 2. Clear Communication ✅
- "Active Time" label is clear
- "Only counts when playing" subtitle explains behavior
- "Expected Time" shows allowed time window
- Completed videos show "untracked" message

### 3. Flexible Learning ✅
- Pause anytime without worry
- Rewind to review difficult sections
- Take breaks as needed
- Re-watch completed videos freely

### 4. Accurate Metrics ✅
- Attention scores are fair
- Progress tracking is accurate
- Reporting shows real engagement
- No false "too long" flags

---

## Technical Details for Admins

### Allowed Time Calculation

For a 6-minute video:
```
Video Duration: 6 minutes
Allowed Time: 6 × 2 = 12 minutes

User can:
- Watch straight through: 6 minutes ✅
- Pause for 5 minutes: 6 + 0 = 6 minutes ✅
- Rewind 2 minutes: Still ~6 minutes ✅
- Take multiple breaks: Only playback counts ✅
```

### Attention Score Impact

The attention score now uses active playback time:

```php
// Backend calculation
if ($activePlaybackTime <= $allowedTime) {
    // No "too long" penalty
    // Pauses and rewinds don't reduce score
    $score += 25; // Good pace
}
```

### Reporting

Admin reports now show:
- **Active Playback Time** - Time video was actually playing
- **Total Session Time** - Wall-clock time (for reference)
- **Is Within Allowed Time** - Boolean flag
- **Video Events** - Pause/resume/rewind log

---

## Summary

The new video tracking system provides:

✅ **Accurate** - Only counts actual playback time  
✅ **Fair** - No penalties for normal viewing behaviors  
✅ **Clear** - Users understand what's being tracked  
✅ **Flexible** - Users can learn at their own pace  

All changes align with the client's requirements for fair and accurate video tracking!
