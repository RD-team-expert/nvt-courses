# Design Document: Video & Quiz Tracking Updates

## Overview

This design document outlines the technical approach for implementing the video and quiz tracking updates. The main goals are:

1. Track only **active playback time** (excluding loading, buffering, and pauses)
2. Implement **allowed time window** of Video Duration × 2
3. Enable **progress persistence** for resume functionality
4. Update **attention score calculation** to use active playback time
5. Ensure **quiz attempt rules** are admin-configurable (attempts and time limit)
6. Provide comprehensive **logging and reporting**

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (Vue.js)                         │
├─────────────────────────────────────────────────────────────────┤
│  ContentViewer/Show.vue          │  ModuleQuiz/Take.vue         │
│  - Active playback tracking      │  - Timer countdown           │
│  - Pause/resume detection        │  - Auto-submit on expiry     │
│  - Buffering detection           │  - Attempt tracking          │
│  - Allowed time display          │                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Backend (Laravel)                            │
├─────────────────────────────────────────────────────────────────┤
│  LearningSessionService          │  QuizAttemptService          │
│  - Active time calculation       │  - Attempt validation        │
│  - Session state management      │  - Time limit enforcement    │
│  - Progress persistence          │  - Auto-submit handling      │
├─────────────────────────────────────────────────────────────────┤
│  CourseOnlineReportController                                    │
│  - Updated attention score calculation                           │
│  - Allowed time window validation (Duration × 2)                 │
│  - Active vs total time reporting                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Database                                  │
├─────────────────────────────────────────────────────────────────┤
│  learning_sessions               │  quiz_attempts               │
│  - active_playback_time (NEW)    │  - max_attempts (existing)   │
│  - total_session_time            │  - time_limit_minutes        │
│  - is_within_allowed_time (NEW)  │  - auto_submitted (existing) │
│  - video_events (NEW JSON)       │                              │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Frontend Components

#### Admin/Quizzes/Create.vue & Edit.vue Updates

The Quiz model already has `max_attempts` and `time_limit_minutes` fields, but the Admin UI doesn't expose them. We need to add these fields to the quiz creation/editing forms.

**New Form Fields to Add:**
```typescript
// Add to form state
max_attempts: null,           // Number of attempts allowed (null = unlimited)
time_limit_minutes: null,     // Time limit per attempt in minutes (null = no limit)
```

**New UI Section - "Attempt Settings":**
```vue
<!-- Attempt Settings Card -->
<Card class="mt-6">
    <CardHeader>
        <CardTitle>🎯 Attempt Settings</CardTitle>
    </CardHeader>
    <CardContent>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Maximum Attempts -->
            <div>
                <Label for="max_attempts">Maximum Attempts</Label>
                <Input
                    id="max_attempts"
                    v-model.number="form.max_attempts"
                    type="number"
                    min="1"
                    max="100"
                    :disabled="form.processing"
                    placeholder="Leave empty for unlimited"
                />
                <p class="text-xs text-muted-foreground mt-1">
                    How many times can a user attempt this quiz? Leave empty for unlimited.
                </p>
            </div>

            <!-- Time Limit (move from Deadline Settings) -->
            <div>
                <Label for="time_limit_minutes">Time Limit (minutes)</Label>
                <Input
                    id="time_limit_minutes"
                    v-model.number="form.time_limit_minutes"
                    type="number"
                    min="1"
                    max="1440"
                    :disabled="form.processing"
                    placeholder="Leave empty for no limit"
                />
                <p class="text-xs text-muted-foreground mt-1">
                    How many minutes does a user have to complete each attempt?
                </p>
            </div>
        </div>
    </CardContent>
</Card>
```

**Files to Modify:**
1. `resources/js/pages/Admin/Quizzes/Create.vue` - Add max_attempts field
2. `resources/js/pages/Admin/Quizzes/Edit.vue` - Add max_attempts field
3. `app/Http/Controllers/Admin/QuizController.php` - Ensure max_attempts is saved

#### ContentViewer/Show.vue Updates

**New State Variables:**
```typescript
// Active playback tracking
const activePlaybackTime = ref(0)           // Accumulated active playback seconds
const isActivelyPlaying = ref(false)        // True only when video is actually playing
const lastActiveTimeUpdate = ref(0)         // Timestamp of last active time update
const allowedTimeMinutes = ref(0)           // Video Duration × 2

// Video state tracking
const isBuffering = ref(false)              // True during buffering
const isLoading = ref(false)                // True during initial load

// Event logging
const videoEvents = ref<VideoEvent[]>([])   // Array of pause/resume/rewind events
```

**New Computed Properties:**
```typescript
// Allowed time display (static label, no countdown)
const allowedTimeDisplay = computed(() => {
    const minutes = Math.ceil(allowedTimeMinutes.value)
    return `You are expected to complete this video within ${minutes} minutes`
})

// Check if within allowed time
const isWithinAllowedTime = computed(() => {
    return activePlaybackTime.value <= (allowedTimeMinutes.value * 60)
})

// Remaining allowed time for resumed sessions
const remainingAllowedTime = computed(() => {
    const totalAllowed = allowedTimeMinutes.value * 60
    const used = activePlaybackTime.value
    return Math.max(0, totalAllowed - used)
})
```

**New Methods:**
```typescript
// Track active playback time (only when actually playing)
const updateActivePlaybackTime = () => {
    if (isActivelyPlaying.value && !isBuffering.value && !isLoading.value) {
        const now = Date.now()
        const elapsed = (now - lastActiveTimeUpdate.value) / 1000
        if (elapsed > 0 && elapsed < 2) { // Reasonable increment
            activePlaybackTime.value += elapsed
        }
        lastActiveTimeUpdate.value = now
    }
}

// Log video events
const logVideoEvent = (type: 'pause' | 'resume' | 'rewind', data?: any) => {
    videoEvents.value.push({
        type,
        timestamp: Date.now(),
        position: currentTime.value,
        ...data
    })
}
```

#### ModuleQuiz/Take.vue Updates

The quiz component already has most functionality. Ensure:
- Timer countdown works correctly
- Auto-submit on time expiry
- Remaining attempts display

### 2. Backend Services

#### LearningSessionService Updates

**New Methods:**
```php
/**
 * Update session with active playback time
 */
public function updateActivePlaybackTime(
    int $sessionId,
    int $activePlaybackSeconds,
    array $videoEvents = []
): LearningSession {
    $session = LearningSession::findOrFail($sessionId);
    
    $session->update([
        'active_playback_time' => $activePlaybackSeconds,
        'video_events' => json_encode($videoEvents),
    ]);
    
    return $session->fresh();
}

/**
 * Calculate if session is within allowed time
 */
public function isWithinAllowedTime(LearningSession $session): bool {
    $content = $session->content;
    $videoDurationMinutes = $this->getExpectedDuration($content);
    $allowedTimeMinutes = $videoDurationMinutes * 2;
    
    $activePlaybackMinutes = ($session->active_playback_time ?? 0) / 60;
    
    return $activePlaybackMinutes <= $allowedTimeMinutes;
}

/**
 * End session with active playback data
 */
public function endSessionWithActiveTime(
    int $sessionId,
    int $activePlaybackSeconds,
    float $completionPercentage,
    array $videoEvents = []
): LearningSession {
    $session = LearningSession::findOrFail($sessionId);
    $content = $session->content;
    
    // Calculate if within allowed time
    $videoDurationMinutes = $this->getExpectedDuration($content);
    $allowedTimeMinutes = $videoDurationMinutes * 2;
    $isWithinAllowed = ($activePlaybackSeconds / 60) <= $allowedTimeMinutes;
    
    // Calculate attention score using ACTIVE playback time
    $attentionScore = $this->calculateAttentionScoreWithActiveTime(
        $activePlaybackSeconds,
        $content,
        $completionPercentage,
        $isWithinAllowed
    );
    
    $session->update([
        'session_end' => now(),
        'active_playback_time' => $activePlaybackSeconds,
        'is_within_allowed_time' => $isWithinAllowed,
        'video_events' => json_encode($videoEvents),
        'video_completion_percentage' => $completionPercentage,
        'attention_score' => $attentionScore,
    ]);
    
    return $session->fresh();
}
```

#### CourseOnlineReportController Updates

**Updated Attention Score Calculation:**
```php
/**
 * Calculate attention score using ACTIVE playback time
 * No penalties for pauses/rewinds within allowed time (Duration × 2)
 */
private function calculateSimulatedAttentionScore(
    $sessionStart, 
    $sessionEnd, 
    $calculatedDuration, 
    $contentId = null, 
    $sessionData = null
) {
    $details = [];
    $isSuspicious = false;
    
    // Get video duration
    $videoDurationMinutes = null;
    if ($contentId) {
        $videoDurationSeconds = DB::table('module_content')
            ->where('id', $contentId)
            ->value('duration');
        if ($videoDurationSeconds) {
            $videoDurationMinutes = $videoDurationSeconds / 60;
        }
    }
    
    // Use ACTIVE playback time if available, otherwise fall back to calculated
    $activePlaybackMinutes = $sessionData->active_playback_time 
        ? ($sessionData->active_playback_time / 60) 
        : $calculatedDuration;
    
    // Calculate allowed time (Duration × 2)
    $allowedTimeMinutes = $videoDurationMinutes ? ($videoDurationMinutes * 2) : 90;
    
    $score = 0;
    
    // Check if within allowed time window
    $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;
    
    if ($videoDurationMinutes && $videoDurationMinutes > 0) {
        if ($isWithinAllowedTime) {
            // Within allowed time - good score, no "too long" penalty
            if ($activePlaybackMinutes >= $videoDurationMinutes * 0.8) {
                $score += 30;
                $details[] = 'Good active playback time (+30)';
            } elseif ($activePlaybackMinutes >= $videoDurationMinutes * 0.5) {
                $score += 20;
                $details[] = 'Acceptable active playback time (+20)';
            } else {
                $score += 10;
                $details[] = 'Short active playback time (+10)';
            }
        } else {
            // Exceeded allowed time - apply penalty
            $score += 5;
            $details[] = 'Exceeded allowed time window (+5)';
            $isSuspicious = true;
        }
    }
    
    // Pauses and rewinds do NOT affect score if within allowed time
    if ($sessionData && $isWithinAllowedTime) {
        $pauseCount = $sessionData->pause_count ?? 0;
        $replayCount = $sessionData->video_replay_count ?? 0;
        
        // Pauses are normal behavior - give bonus for engagement
        if ($pauseCount > 0 && $pauseCount <= 20) {
            $score += 10;
            $details[] = 'Normal pause behavior (+10)';
        }
        
        // Replays show attention to detail
        if ($replayCount > 0 && $replayCount <= 10) {
            $score += 10;
            $details[] = 'Replay behavior shows engagement (+10)';
        }
    }
    
    // Video completion bonus
    if ($sessionData && isset($sessionData->video_completion_percentage)) {
        $completionPct = $sessionData->video_completion_percentage;
        if ($completionPct >= 95) {
            $score += 35;
            $details[] = 'Full video completion (+35)';
        } elseif ($completionPct >= 80) {
            $score += 25;
            $details[] = 'High video completion (+25)';
        } elseif ($completionPct >= 60) {
            $score += 15;
            $details[] = 'Moderate video completion (+15)';
        }
    }
    
    // Session completion bonus
    if ($sessionEnd) {
        $score += 5;
        $details[] = 'Session completed (+5)';
    }
    
    // Only apply skip penalty (skipping forward is still suspicious)
    if ($sessionData) {
        $skipCount = $sessionData->video_skip_count ?? 0;
        if ($skipCount >= 1) {
            $score -= 30;
            $isSuspicious = true;
            $details[] = "PENALTY: Skip forward detected (-30)";
        }
    }
    
    $score = max(0, min(100, $score));
    
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

## Data Models

### Database Schema Updates

#### learning_sessions table (add columns)

```sql
ALTER TABLE learning_sessions ADD COLUMN active_playback_time INT DEFAULT 0 
    COMMENT 'Active playback time in seconds (excludes loading, buffering, pauses)';

ALTER TABLE learning_sessions ADD COLUMN is_within_allowed_time BOOLEAN DEFAULT TRUE 
    COMMENT 'Whether session stayed within allowed time (Duration × 2)';

ALTER TABLE learning_sessions ADD COLUMN video_events JSON NULL 
    COMMENT 'JSON array of video events (pause, resume, rewind with timestamps)';
```

#### LearningSession Model Updates

```php
protected $fillable = [
    // ... existing fields ...
    'active_playback_time',      // NEW: Active playback seconds
    'is_within_allowed_time',    // NEW: Boolean flag
    'video_events',              // NEW: JSON events log
];

protected $casts = [
    // ... existing casts ...
    'active_playback_time' => 'integer',
    'is_within_allowed_time' => 'boolean',
    'video_events' => 'array',
];
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Active Playback Time Tracking
*For any* video session, the active playback time counter SHALL only increment when the video is in "playing" state AND not in "buffering" or "loading" state.
**Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

### Property 2: Allowed Time Calculation
*For any* video with duration D minutes, the allowed time window SHALL equal exactly D × 2 minutes.
**Validates: Requirements 2.1**

### Property 3: No Penalty Within Allowed Time
*For any* session where active playback time ≤ (Video Duration × 2), the system SHALL NOT apply "session too long" penalties or reduce attention score due to pauses/rewinds.
**Validates: Requirements 2.3, 2.4, 8.2, 8.3**

### Property 4: Remaining Time Calculation
*For any* resumed session, the remaining allowed time SHALL equal (Video Duration × 2) − (Active playback time already used).
**Validates: Requirements 2.5**

### Property 5: Progress Persistence
*For any* video session that is paused or where user logs out, the current timestamp position SHALL be persisted and restored when the user returns.
**Validates: Requirements 3.1, 3.3, 3.4**

### Property 6: Rewind/Replay Time Exclusion
*For any* rewind or replay action, the rewound/replayed section SHALL NOT be counted as additional active playback time toward the allowed time limit.
**Validates: Requirements 3.2, 3.5**

### Property 7: Post-Completion No Tracking
*For any* video marked as completed, subsequent re-watching SHALL NOT update progress percentage, modify attention score, or apply session time limits.
**Validates: Requirements 4.1, 4.2, 4.3, 4.4**

### Property 8: Quiz Attempt Limit Enforcement
*For any* user who has used all allowed quiz attempts (as configured by admin), the system SHALL block further quiz attempts.
**Validates: Requirements 5.3**

### Property 9: Quiz Auto-Submit on Time Expiry
*For any* quiz with a configured time limit, when the time expires, the system SHALL auto-submit with current answers and block further modifications.
**Validates: Requirements 5.5, 5.6**

### Property 10: Video Event Logging
*For any* video session, all pause, resume, and rewind events SHALL be logged with timestamps and positions.
**Validates: Requirements 6.2, 6.3, 6.4**

### Property 11: Session Time Compliance Logging
*For any* ended session, the system SHALL log whether the user stayed within the allowed time frame (Duration × 2).
**Validates: Requirements 6.5**

### Property 12: Quiz Attempt Logging
*For any* quiz attempt, the system SHALL log start time, end time, total duration, and final score.
**Validates: Requirements 7.1, 7.2, 7.3, 7.4**

### Property 13: Active Time in Score Calculation
*For any* attention score calculation, the system SHALL use active playback time instead of total session time.
**Validates: Requirements 8.1**

### Property 14: Penalty for Exceeding Allowed Time
*For any* session where active playback time exceeds (Video Duration × 2), the system SHALL apply appropriate penalties to the attention score.
**Validates: Requirements 8.4**

## Error Handling

### Video Tracking Errors
- If active playback tracking fails, fall back to total session time
- Log errors but don't block user from watching video
- Display warning if time tracking is unavailable

### Quiz Errors
- If auto-submit fails on time expiry, retry up to 3 times
- If all retries fail, save answers locally and notify user
- Log all auto-submit failures for admin review

### Database Errors
- Use transactions for session updates
- Implement retry logic for transient failures
- Queue failed updates for later processing

## Testing Strategy

### Unit Tests
- Test allowed time calculation (Duration × 2)
- Test remaining time calculation for resumed sessions
- Test attention score calculation with active playback time
- Test quiz attempt limit enforcement

### Property-Based Tests
Using a property-based testing library (e.g., PHPUnit with data providers or Pest):

1. **Active Playback Time Property Test**
   - Generate random video states (playing, paused, buffering, loading)
   - Verify counter only increments during "playing" state
   - **Feature: video-quiz-tracking-updates, Property 1: Active Playback Time Tracking**

2. **Allowed Time Calculation Property Test**
   - Generate random video durations
   - Verify allowed time = duration × 2
   - **Feature: video-quiz-tracking-updates, Property 2: Allowed Time Calculation**

3. **No Penalty Within Allowed Time Property Test**
   - Generate random sessions within allowed time with various pause/rewind counts
   - Verify no "too long" penalty applied
   - **Feature: video-quiz-tracking-updates, Property 3: No Penalty Within Allowed Time**

4. **Quiz Attempt Limit Property Test**
   - Generate random users with various attempt counts
   - Verify blocking when max attempts reached
   - **Feature: video-quiz-tracking-updates, Property 8: Quiz Attempt Limit Enforcement**

5. **Quiz Auto-Submit Property Test**
   - Generate random quiz states at time expiry
   - Verify auto-submit occurs and modifications blocked
   - **Feature: video-quiz-tracking-updates, Property 9: Quiz Auto-Submit on Time Expiry**

### Integration Tests
- Test full video session flow with active time tracking
- Test quiz flow with time limit and auto-submit
- Test progress persistence across sessions
- Test report generation with new metrics
