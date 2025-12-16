# Video Tracking Backend Guide

## Overview

This guide documents the backend changes for the video tracking system, focusing on **active playback time tracking** and the **allowed time window** feature. These changes ensure fair and accurate tracking of user engagement by excluding loading, buffering, and pause periods from session duration calculations.

## Key Concepts

### Active Playback Time
**Active playback time** measures only the time when video content is actively playing, excluding:
- Initial loading time
- Buffering periods
- Pause periods
- Time when video is stopped

This provides a more accurate measure of actual content consumption compared to total session time.

### Allowed Time Window
The **allowed time window** is calculated as:
```
Allowed Time = Video Duration × 2
```

This gives users sufficient time to:
- Pause and take notes
- Rewind and review difficult sections
- Deal with interruptions
- Learn at their own pace

Users are only flagged if their **active playback time** exceeds this window.

## Database Schema Changes

### Migration File
Location: `database/migrations/2025_12_12_111419_add_active_playback_tracking_to_learning_sessions_table.php`

### New Columns in `learning_sessions` Table

#### 1. `active_playback_time` (INTEGER)
- **Type**: `INTEGER`
- **Default**: `0`
- **Nullable**: `NO`
- **Description**: Active playback time in seconds (excludes loading, buffering, pauses)
- **Purpose**: Tracks only the time when video is actively playing

**Example Values**:
```php
// User watched a 10-minute video
// - Actual playback: 600 seconds (10 minutes)
// - Paused for notes: 120 seconds (not counted)
// - Buffering: 15 seconds (not counted)
// Result: active_playback_time = 600
```

#### 2. `is_within_allowed_time` (BOOLEAN)
- **Type**: `BOOLEAN`
- **Default**: `TRUE`
- **Nullable**: `NO`
- **Description**: Whether session stayed within allowed time (Duration × 2)
- **Purpose**: Flag sessions that exceed the allowed time window

**Calculation Logic**:
```php
$videoDurationMinutes = $content->video->duration / 60;
$allowedTimeMinutes = $videoDurationMinutes * 2;
$activePlaybackMinutes = $session->active_playback_time / 60;

$isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;
```

**Example**:
```php
// Video duration: 20 minutes
// Allowed time: 40 minutes (20 × 2)
// User's active playback: 35 minutes
// Result: is_within_allowed_time = TRUE

// Video duration: 20 minutes
// Allowed time: 40 minutes (20 × 2)
// User's active playback: 45 minutes
// Result: is_within_allowed_time = FALSE
```

#### 3. `video_events` (JSON)
- **Type**: `JSON`
- **Default**: `NULL`
- **Nullable**: `YES`
- **Description**: JSON array of video events (pause, resume, rewind with timestamps)
- **Purpose**: Detailed logging of user interactions for analysis

**Event Structure**:
```json
[
  {
    "type": "pause",
    "timestamp": 1702345678000,
    "position": 125.5
  },
  {
    "type": "resume",
    "timestamp": 1702345698000,
    "position": 125.5
  },
  {
    "type": "rewind",
    "timestamp": 1702345720000,
    "position": 100.0,
    "from_position": 150.0
  }
]
```

**Event Types**:
- `pause`: User paused the video
- `resume`: User resumed playback
- `rewind`: User jumped backward in the video
- `skip`: User jumped forward in the video (still tracked separately)

## LearningSession Model Updates

### New Fillable Fields
```php
protected $fillable = [
    // ... existing fields ...
    'active_playback_time',      // NEW: Active playback seconds
    'is_within_allowed_time',    // NEW: Boolean flag
    'video_events',              // NEW: JSON events log
];
```

### New Casts
```php
protected $casts = [
    // ... existing casts ...
    'active_playback_time' => 'integer',
    'is_within_allowed_time' => 'boolean',
    'video_events' => 'array',
];
```

## LearningSessionService New Methods

### 1. `updateActivePlaybackTime()`

**Purpose**: Update session with active playback time during video playback.

**Signature**:
```php
public function updateActivePlaybackTime(
    int $sessionId,
    int $activePlaybackSeconds,
    array $videoEvents = []
): LearningSession
```

**Parameters**:
- `$sessionId` (int): The learning session ID
- `$activePlaybackSeconds` (int): Accumulated active playback time in seconds
- `$videoEvents` (array): Array of video events (pause, resume, rewind)

**Returns**: Updated `LearningSession` instance

**Throws**: `Exception` if session is already ended

**Usage Example**:
```php
$sessionService = app(LearningSessionService::class);

// Update active playback time during video playback
$session = $sessionService->updateActivePlaybackTime(
    sessionId: 123,
    activePlaybackSeconds: 450,  // 7.5 minutes of active playback
    videoEvents: [
        ['type' => 'pause', 'timestamp' => 1702345678000, 'position' => 125.5],
        ['type' => 'resume', 'timestamp' => 1702345698000, 'position' => 125.5],
    ]
);
```

**When to Call**:
- During heartbeat updates (every ~10 seconds)
- When session ends
- When user navigates away from video

**Error Handling**:
```php
try {
    $session = $sessionService->updateActivePlaybackTime($sessionId, $activeTime, $events);
} catch (\Exception $e) {
    // Session already ended or not found
    Log::error('Failed to update active playback time', [
        'session_id' => $sessionId,
        'error' => $e->getMessage()
    ]);
}
```

---

### 2. `isWithinAllowedTime()`

**Purpose**: Check if a session's active playback time is within the allowed time window (Duration × 2).

**Signature**:
```php
public function isWithinAllowedTime(LearningSession $session): bool
```

**Parameters**:
- `$session` (LearningSession): The learning session to check

**Returns**: `true` if within allowed time, `false` otherwise

**Calculation Logic**:
```php
// 1. Get video duration in minutes
$videoDurationMinutes = $content->video->duration / 60;

// 2. Calculate allowed time (Duration × 2)
$allowedTimeMinutes = $videoDurationMinutes * 2;

// 3. Convert active playback time to minutes
$activePlaybackMinutes = ($session->active_playback_time ?? 0) / 60;

// 4. Compare
return $activePlaybackMinutes <= $allowedTimeMinutes;
```

**Usage Example**:
```php
$sessionService = app(LearningSessionService::class);
$session = LearningSession::find(123);

if ($sessionService->isWithinAllowedTime($session)) {
    // User is within allowed time - no penalties
    echo "Session is within allowed time window";
} else {
    // User exceeded allowed time - apply penalties
    echo "Session exceeded allowed time window";
}
```

**Edge Cases**:
```php
// Case 1: No active playback time recorded (defaults to 0)
$session->active_playback_time = null;
$result = $sessionService->isWithinAllowedTime($session);
// Result: true (0 minutes is always within allowed time)

// Case 2: Video has no duration
$content->video->duration = null;
// Method uses getExpectedDuration() which returns 0 for invalid content
// Allowed time = 0 × 2 = 0
// Result: false (unless active_playback_time is also 0)

// Case 3: Exactly at the boundary
$videoDuration = 600; // 10 minutes
$allowedTime = 1200; // 20 minutes
$session->active_playback_time = 1200; // exactly 20 minutes
$result = $sessionService->isWithinAllowedTime($session);
// Result: true (uses <= comparison)
```

---

### 3. `calculateAttentionScoreWithActiveTime()`

**Purpose**: Calculate attention score using active playback time instead of total session time, with no penalties for pauses/rewinds within the allowed time window.

**Signature**:
```php
public function calculateAttentionScoreWithActiveTime(
    int $activePlaybackMinutes,
    ModuleContent $content,
    float $completionPercentage,
    bool $isWithinAllowedTime
): int
```

**Parameters**:
- `$activePlaybackMinutes` (int): Active playback time in minutes
- `$content` (ModuleContent): The content being watched
- `$completionPercentage` (float): Video completion percentage (0-100)
- `$isWithinAllowedTime` (bool): Whether session is within allowed time window

**Returns**: Attention score from 0-100

**Scoring Logic**:

#### Base Score
```php
$score = 50; // Everyone starts at 50
```

#### Time Ratio Scoring
```php
$expectedDuration = $this->getExpectedDuration($content);
$timeRatio = $activePlaybackMinutes / $expectedDuration;

if ($isWithinAllowedTime) {
    // Within allowed time - no "too long" penalty
    if ($timeRatio >= 0.8 && $timeRatio <= 2.0) {
        $score += 25; // Good pace, within allowed window
    } elseif ($timeRatio >= 0.5) {
        $score += 15; // Acceptable pace
    } elseif ($timeRatio < 0.3) {
        $score -= 30; // Too fast (suspicious)
    }
} else {
    // Exceeded allowed time window - apply penalty
    $score -= 20; // Penalty for exceeding allowed time
}
```

#### Completion Scoring
```php
if ($completionPercentage >= 90) {
    $score += 20; // Excellent completion
} elseif ($completionPercentage >= 70) {
    $score += 10; // Good completion
} elseif ($completionPercentage < 20) {
    $score -= 25; // Poor completion
}
```

#### Final Score
```php
return max(0, min(100, $score)); // Clamp between 0-100
```

**Usage Example**:
```php
$sessionService = app(LearningSessionService::class);

// Calculate attention score at session end
$attentionScore = $sessionService->calculateAttentionScoreWithActiveTime(
    activePlaybackMinutes: 18,        // 18 minutes of active playback
    content: $moduleContent,          // 15-minute video
    completionPercentage: 95.0,       // Watched 95%
    isWithinAllowedTime: true         // 18 < 30 (15 × 2)
);

// Result: 50 (base) + 25 (good pace) + 20 (excellent completion) = 95
```

**Scoring Examples**:

| Scenario | Video Duration | Active Time | Completion | Within Allowed? | Score Breakdown | Final Score |
|----------|---------------|-------------|------------|-----------------|-----------------|-------------|
| Perfect viewing | 20 min | 22 min | 95% | Yes (22 < 40) | 50 + 25 + 20 = 95 | **95** |
| With pauses | 20 min | 35 min | 90% | Yes (35 < 40) | 50 + 25 + 20 = 95 | **95** |
| Exceeded time | 20 min | 45 min | 85% | No (45 > 40) | 50 - 20 + 10 = 40 | **40** |
| Too fast | 20 min | 5 min | 80% | Yes (5 < 40) | 50 - 30 + 10 = 30 | **30** |
| Incomplete | 20 min | 18 min | 15% | Yes (18 < 40) | 50 + 25 - 25 = 50 | **50** |

---

### 4. Updated `endSession()` Method

**Changes**: Now uses active playback time for score calculation and sets the `is_within_allowed_time` flag.

**Key Updates**:
```php
public function endSession(
    int $sessionId,
    float $finalPosition,
    float $completionPercentage,
    int $finalWatchTimeIncrement = 0,
    int $finalSkipIncrement = 0,
    int $finalSeekIncrement = 0,
    int $finalPauseIncrement = 0
): LearningSession {
    // ... existing code ...

    // NEW: Calculate if within allowed time (Duration × 2)
    $isWithinAllowedTime = $this->isWithinAllowedTime($session);

    // NEW: Use active playback time for score calculation if available
    $durationForScore = $totalDuration;
    if ($session->active_playback_time) {
        // Use active playback time (convert from seconds to minutes)
        $durationForScore = (int) ceil($session->active_playback_time / 60);
    }

    // NEW: Calculate attention score with active time
    $attentionScore = $this->calculateAttentionScoreWithActiveTime(
        $durationForScore,
        $content,
        $completionPercentage,
        $isWithinAllowedTime
    );

    // ... existing code ...

    // NEW: Update session with is_within_allowed_time flag
    $session->update([
        'session_end' => now(),
        'total_duration_minutes' => $totalDuration,
        // ... other fields ...
        'attention_score' => $attentionScore,
        'is_within_allowed_time' => $isWithinAllowedTime,  // NEW
    ]);

    return $session->fresh();
}
```

## Allowed Time Calculation

### Formula
```
Allowed Time (minutes) = Video Duration (minutes) × 2
```

### Examples

#### Example 1: Short Video
```php
Video Duration: 5 minutes
Allowed Time: 5 × 2 = 10 minutes

// User scenarios:
Active Playback: 6 minutes  → Within allowed time ✓
Active Playback: 9 minutes  → Within allowed time ✓
Active Playback: 11 minutes → Exceeded allowed time ✗
```

#### Example 2: Medium Video
```php
Video Duration: 20 minutes
Allowed Time: 20 × 2 = 40 minutes

// User scenarios:
Active Playback: 22 minutes → Within allowed time ✓
Active Playback: 35 minutes → Within allowed time ✓
Active Playback: 45 minutes → Exceeded allowed time ✗
```

#### Example 3: Long Video
```php
Video Duration: 60 minutes
Allowed Time: 60 × 2 = 120 minutes

// User scenarios:
Active Playback: 65 minutes  → Within allowed time ✓
Active Playback: 110 minutes → Within allowed time ✓
Active Playback: 125 minutes → Exceeded allowed time ✗
```

### Why 2x?

The 2x multiplier provides sufficient time for:
1. **Normal viewing**: ~1x duration
2. **Pauses for notes**: Additional time
3. **Rewinding difficult sections**: Review time
4. **Interruptions**: Bathroom breaks, phone calls, etc.
5. **Learning pace variations**: Slower learners get more time

### Rationale

Research shows that effective learning often requires:
- Pausing to take notes
- Rewinding to review complex concepts
- Taking breaks to process information

A 2x multiplier accommodates these behaviors without being overly permissive.

## Integration with Reporting

### CourseOnlineReportController Updates

The attention score calculation in reports now uses active playback time:

```php
private function calculateSimulatedAttentionScore(
    $sessionStart, 
    $sessionEnd, 
    $calculatedDuration, 
    $contentId = null, 
    $sessionData = null
) {
    // Use ACTIVE playback time if available
    $activePlaybackMinutes = $sessionData->active_playback_time 
        ? ($sessionData->active_playback_time / 60) 
        : $calculatedDuration;
    
    // Calculate allowed time (Duration × 2)
    $allowedTimeMinutes = $videoDurationMinutes ? ($videoDurationMinutes * 2) : 90;
    
    // Check if within allowed time window
    $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;
    
    // Apply scoring logic...
    
    return [
        'score' => $score,
        'is_suspicious' => $isSuspicious,
        'details' => $details,
        'is_within_allowed_time' => $isWithinAllowedTime,
        'active_playback_minutes' => $activePlaybackMinutes,
        'allowed_time_minutes' => $allowedTimeMinutes,
    ];
}
```

### Report Fields

Reports now include:
- `active_playback_minutes`: Active playback time
- `total_duration_minutes`: Total session time (including pauses)
- `is_within_allowed_time`: Boolean flag
- `allowed_time_minutes`: Calculated allowed time

This provides transparency and allows administrators to see both metrics.

## Best Practices

### 1. Always Update Active Playback Time
```php
// During heartbeat (every ~10 seconds)
$sessionService->updateActivePlaybackTime(
    $sessionId,
    $activePlaybackSeconds,
    $videoEvents
);
```

### 2. Check Allowed Time Before Penalties
```php
if (!$sessionService->isWithinAllowedTime($session)) {
    // Only apply penalties if exceeded allowed time
    $this->applyTimePenalty($session);
}
```

### 3. Use Active Time for Score Calculation
```php
// Prefer active playback time over total duration
$durationForScore = $session->active_playback_time 
    ? ceil($session->active_playback_time / 60)
    : $session->total_duration_minutes;
```

### 4. Log Video Events for Analysis
```php
$videoEvents = [
    ['type' => 'pause', 'timestamp' => now()->timestamp, 'position' => $currentTime],
    ['type' => 'resume', 'timestamp' => now()->timestamp, 'position' => $currentTime],
];

$sessionService->updateActivePlaybackTime($sessionId, $activeTime, $videoEvents);
```

## Troubleshooting

### Issue: Active playback time is 0
**Cause**: Frontend not sending active playback time updates
**Solution**: Ensure frontend is tracking and sending `active_playback_time` in heartbeat/end session calls

### Issue: is_within_allowed_time always false
**Cause**: Video duration not set or active playback time too high
**Solution**: 
1. Verify video has valid duration in database
2. Check frontend is correctly tracking active playback (not total time)

### Issue: Attention scores too low
**Cause**: Users being penalized for normal pause behavior
**Solution**: Verify `isWithinAllowedTime` is being calculated correctly and passed to score calculation

### Issue: video_events is null
**Cause**: Frontend not sending video events array
**Solution**: Ensure frontend logs pause/resume/rewind events and sends them in session updates

## Migration Guide

### Running the Migration
```bash
php artisan migrate
```

### Rollback (if needed)
```bash
php artisan migrate:rollback --step=1
```

### Verify Migration
```bash
php artisan tinker

# Check if columns exist
Schema::hasColumn('learning_sessions', 'active_playback_time');
Schema::hasColumn('learning_sessions', 'is_within_allowed_time');
Schema::hasColumn('learning_sessions', 'video_events');
```

## Testing

### Unit Test Example
```php
public function test_is_within_allowed_time_calculation()
{
    $video = Video::factory()->create(['duration' => 600]); // 10 minutes
    $content = ModuleContent::factory()->create([
        'content_type' => 'video',
        'video_id' => $video->id
    ]);
    
    $session = LearningSession::factory()->create([
        'content_id' => $content->id,
        'active_playback_time' => 1000, // 16.67 minutes
    ]);
    
    $service = app(LearningSessionService::class);
    
    // 16.67 minutes < 20 minutes (10 × 2)
    $this->assertTrue($service->isWithinAllowedTime($session));
    
    // Update to exceed allowed time
    $session->update(['active_playback_time' => 1500]); // 25 minutes
    
    // 25 minutes > 20 minutes (10 × 2)
    $this->assertFalse($service->isWithinAllowedTime($session));
}
```

## Summary

The backend changes provide:
1. **Fair tracking**: Only active playback time counts
2. **Flexible time window**: 2x video duration allows normal learning behaviors
3. **Detailed logging**: Video events for analysis
4. **Accurate scoring**: Attention scores based on active time
5. **Transparency**: Reports show both active and total time

These changes ensure users are evaluated fairly based on actual engagement, not penalized for normal learning behaviors like pausing and reviewing content.
