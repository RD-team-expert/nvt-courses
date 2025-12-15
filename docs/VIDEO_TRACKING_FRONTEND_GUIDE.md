# Video Tracking Frontend Guide

## Overview

This guide documents the frontend implementation of the video tracking system in the ContentViewer component (`resources/js/pages/User/ContentViewer/Show.vue`). The system tracks **active playback time** (excluding loading, buffering, and pauses) and logs video events for comprehensive user engagement analysis.

## Key Features

1. **Active Playback Time Tracking**: Only counts time when video is actively playing
2. **Buffering/Loading Detection**: Automatically pauses tracking during buffering and loading
3. **Video Event Logging**: Records pause, resume, and rewind events with timestamps
4. **Allowed Time Display**: Shows users the expected completion time (Video Duration × 2)
5. **Progress Persistence**: Saves and restores video position across sessions

## State Variables

### Active Playback Tracking State

```typescript
// Active playback time tracking (Task 6.1)
const activePlaybackTime = ref(0)           // Accumulated active playback seconds
const isActivelyPlaying = ref(false)        // True only when video is actually playing
const isBuffering = ref(false)              // True during buffering
const lastActiveTimeUpdate = ref(0)         // Timestamp of last active time update
```

**Purpose**:
- `activePlaybackTime`: Accumulates only the seconds when video is actively playing
- `isActivelyPlaying`: Flag that controls whether the counter increments
- `isBuffering`: Tracks buffering state to pause the counter
- `lastActiveTimeUpdate`: Used to calculate elapsed time between updates

### Video Event Logging State

```typescript
// Video event logging (Task 6.4)
interface VideoEvent {
    type: 'pause' | 'resume' | 'rewind'
    timestamp: number          // Unix timestamp in milliseconds
    position: number           // Current video position in seconds
    startPosition?: number     // For rewind: position before seeking
    endPosition?: number       // For rewind: position after seeking
}

const videoEvents = ref<VideoEvent[]>([])
```

**Purpose**: Stores all video interaction events during the session for backend analysis.

### Video State Tracking

```typescript
const isPlaying = ref(false)                // User pressed play
const isVideoReady = ref(false)             // Video data loaded and ready
const isVideoLoading = ref(false)           // Initial loading or buffering
const currentTime = ref(0)                  // Current playback position
const duration = ref(0)                     // Total video duration
```

## Computed Properties

### Allowed Time Window

```typescript
// Allowed time window (Task 6.1) - Video Duration × 2
const allowedTimeMinutes = computed(() => {
    if (props.content.content_type === 'video' && duration.value > 0) {
        return (duration.value / 60) * 2  // Duration × 2 in minutes
    }
    return 0
})
```

**Calculation**: `Allowed Time = Video Duration × 2`

**Example**:
- 10-minute video → 20 minutes allowed
- 30-minute video → 60 minutes allowed
- 60-minute video → 120 minutes allowed

### Allowed Time Display

```typescript
// Allowed time display text (Task 6.3)
const allowedTimeDisplay = computed(() => {
    if (props.content.content_type === 'video' && allowedTimeMinutes.value > 0) {
        const minutes = Math.ceil(allowedTimeMinutes.value)
        return `You are expected to complete this video within ${minutes} minutes`
    }
    return ''
})
```

**Display**: Shows a static label (not a countdown timer) informing users of the expected completion time.

## Core Functions

### 1. Active Playback Time Counter

```typescript
// Update active playback time (Task 6.2)
// Only increments when video is playing AND not buffering/loading
const updateActivePlaybackTime = () => {
    if (isActivelyPlaying.value && !isBuffering.value && !isVideoLoading.value) {
        const now = Date.now()
        const elapsed = (now - lastActiveTimeUpdate.value) / 1000
        
        // Only add reasonable increments (between 0 and 2 seconds)
        if (elapsed > 0 && elapsed < 2) {
            activePlaybackTime.value += elapsed
        }
        
        lastActiveTimeUpdate.value = now
    }
}
```

**How It Works**:
1. Checks if video is actively playing (not paused, not buffering, not loading)
2. Calculates elapsed time since last update
3. Only adds reasonable increments (0-2 seconds) to prevent anomalies
4. Updates the timestamp for next calculation

**Called By**: Interval timer (every 1 second)

```typescript
// Set up active playback tracking interval
const activePlaybackInterval = setInterval(() => {
    if (props.content.content_type === 'video') {
        updateActivePlaybackTime()
    }
}, 1000)  // Update every second
```

### 2. Video Event Logging

```typescript
// Log video events (Task 6.4)
const logVideoEvent = (type: 'pause' | 'resume' | 'rewind', data?: any) => {
    const event: VideoEvent = {
        type,
        timestamp: Date.now(),
        position: currentTime.value,
        ...data
    }
    videoEvents.value.push(event)
    // console.log('📹 Video event logged:', event)  // Uncomment for debugging
}
```

**Event Types**:
- **pause**: User paused the video
- **resume**: User resumed playback
- **rewind**: User seeked backward

**Usage Examples**:
```typescript
// Log pause event
logVideoEvent('pause')

// Log resume event
logVideoEvent('resume')

// Log rewind event with positions
logVideoEvent('rewind', {
    startPosition: 90,
    endPosition: 60
})
```

## Video Event Handlers

### Loading Events

#### onLoadStart - Video Loading Started
```typescript
const onLoadStart = () => {
    isVideoLoading.value = true
    isVideoReady.value = false
    isActivelyPlaying.value = false  // ✅ Pause counter during loading
}
```

**Requirement**: 1.1 - Pause counter during loading

#### onLoadedData - Video Data Loaded
```typescript
const onLoadedData = () => {
    isVideoLoading.value = false
    isVideoReady.value = true
    // Don't start tracking until play event
}
```

#### onCanPlay - Video Ready to Play
```typescript
const onCanPlay = async () => {
    isVideoLoading.value = false
    isVideoReady.value = true
    
    // Restore saved position if available
    if (props.userProgress?.current_position && videoElement.value) {
        videoElement.value.currentTime = props.userProgress.current_position
        currentTime.value = props.userProgress.current_position
    }
}
```

### Buffering Events

#### onWaiting - Video Buffering
```typescript
const onWaiting = () => {
    isVideoLoading.value = true
    isBuffering.value = true
    isActivelyPlaying.value = false  // ✅ Pause counter during buffering
}
```

**Requirement**: 1.1 - Pause counter during buffering

#### onPlaying - Video Actually Playing
```typescript
const onPlaying = () => {
    isVideoLoading.value = false
    isBuffering.value = false
    
    // ✅ Resume active playback tracking when video is actually playing
    if (isPlaying.value && isVideoReady.value) {
        isActivelyPlaying.value = true
        lastActiveTimeUpdate.value = Date.now()
    }
}
```

**Requirement**: 1.2 - Resume counter after buffering

### Playback Control Events

#### onPlay - Play Button Pressed
```typescript
const onPlay = () => {
    isPlaying.value = true
    lastCurrentTime.value = currentTime.value

    // ✅ Start active playback tracking (only if ready and not buffering)
    if (isVideoReady.value && !isBuffering.value && !isVideoLoading.value) {
        isActivelyPlaying.value = true
        lastActiveTimeUpdate.value = Date.now()
    }

    logVideoEvent('resume')  // ✅ Log resume event

    if (!sessionId.value) {
        startSession()
    }
}
```

**Requirements**:
- 1.4 - Resume counter when user resumes
- 6.4 - Log resume event

#### onPause - Video Paused
```typescript
const onPause = () => {
    isPlaying.value = false
    isActivelyPlaying.value = false  // ✅ Stop active playback tracking

    logVideoEvent('pause')  // ✅ Log pause event
    pauseCountSinceLastHeartbeat.value++
    totalPauseCount.value++

    updateProgress()
}
```

**Requirements**:
- 1.3 - Pause counter when user pauses
- 6.2 - Log pause event

#### onEnded - Video Ended
```typescript
const onEnded = () => {
    isPlaying.value = false
    isActivelyPlaying.value = false  // ✅ Stop active playback tracking
    
    updateProgress()
    if (progressPercentage.value >= 100) {
        markCompleted()
    }
}
```

### Seeking Events

#### onVideoSeeked - User Seeked in Video
```typescript
let previousSeekPosition = 0

const onVideoSeeked = () => {
    seekCountSinceLastHeartbeat.value++
    totalSeekCount.value++
    
    // Log rewind events (backward seeking)
    const currentPosition = currentTime.value
    if (currentPosition < previousSeekPosition) {
        logVideoEvent('rewind', {
            startPosition: previousSeekPosition,
            endPosition: currentPosition
        })
    }
    previousSeekPosition = currentPosition
}
```

**Requirement**: 6.3 - Log rewind events with start and end positions

**How Rewind Detection Works**:
1. Tracks the previous video position
2. When user seeks, compares new position to previous
3. If new position < previous position → it's a rewind
4. Logs the rewind event with both positions

## Buffering/Loading Detection

### How It Works

The system uses multiple video events to accurately detect buffering and loading states:

```
Video State Flow:

INITIAL LOAD:
loadstart → isVideoLoading = true, isActivelyPlaying = false
    ↓
loadeddata → isVideoLoading = false, isVideoReady = true
    ↓
canplay → Video ready, waiting for user to press play

USER PRESSES PLAY:
play → isPlaying = true
    ↓
playing → isActivelyPlaying = true (if ready and not buffering)
    ↓
ACTIVE PLAYBACK TRACKING STARTS

BUFFERING OCCURS:
waiting → isBuffering = true, isActivelyPlaying = false
    ↓
ACTIVE PLAYBACK TRACKING PAUSED
    ↓
playing → isBuffering = false, isActivelyPlaying = true
    ↓
ACTIVE PLAYBACK TRACKING RESUMES
```

### State Conditions

The active playback counter only increments when ALL conditions are true:

```typescript
if (isActivelyPlaying.value && !isBuffering.value && !isVideoLoading.value) {
    // Increment active playback time
}
```

**Conditions**:
1. `isActivelyPlaying === true`: User pressed play and video is playing
2. `isBuffering === false`: Video is not buffering
3. `isVideoLoading === false`: Video is not in initial load state

## Backend Integration

### Heartbeat Updates

Every 10 minutes, the frontend sends a heartbeat with active playback data:

```typescript
const sendHeartbeat = async () => {
    if (!sessionId.value) return

    try {
        const payload = {
            action: 'heartbeat',
            current_position: currentTime.value,
            active_playback_time: Math.floor(activePlaybackTime.value),  // ✅ Send active time
            video_skip_count: skipCountSinceLastHeartbeat.value,
            video_seek_count: seekCountSinceLastHeartbeat.value,
            pause_count: pauseCountSinceLastHeartbeat.value,
            watch_time: watchTimeSinceLastHeartbeat.value,
        }

        await axios.post(`/content/${props.content.id}/session`, payload)
        
        // Reset incremental counters
        resetIncrementalCounters()
    } catch (error) {
        console.error('Heartbeat failed:', error)
    }
}
```

**Frequency**: Every 10 minutes (600,000 milliseconds)

### Session End

When the session ends, all data including video events is sent:

```typescript
const endSession = async () => {
    if (!sessionId.value) return

    try {
        const payload = {
            action: 'end',
            current_position: currentTime.value,
            completion_percentage: progressPercentage.value,
            active_playback_time: Math.floor(activePlaybackTime.value),  // ✅ Active time
            video_events: videoEvents.value,  // ✅ All logged events
            video_skip_count: totalSkipCount.value,
            video_seek_count: totalSeekCount.value,
            pause_count: totalPauseCount.value,
            watch_time: totalWatchTime.value,
        }

        await axios.post(`/content/${props.content.id}/session`, payload)
        sessionId.value = null
    } catch (error) {
        console.error('Failed to end session:', error)
    }
}
```

### Page Unload Handling

Uses `sendBeacon` for reliable data transmission when user closes tab:

```typescript
const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (sessionId.value) {
        const formData = new FormData()
        formData.append('action', 'end')
        formData.append('current_position', currentTime.value.toString())
        formData.append('completion_percentage', progressPercentage.value.toString())
        formData.append('active_playback_time', Math.floor(activePlaybackTime.value).toString())
        formData.append('video_events', JSON.stringify(videoEvents.value))
        // ... other data
        
        navigator.sendBeacon(`/content/${props.content.id}/session`, formData)
    }
}

window.addEventListener('beforeunload', handleBeforeUnload)
```

**Why sendBeacon?**: Regular AJAX requests may be cancelled when the page unloads. `sendBeacon` is designed for this use case and ensures data is sent reliably.

## Allowed Time Display

### UI Implementation

The allowed time is displayed as a static informational label near the video player:

```vue
<!-- Allowed Time Display (Task 6.3) -->
<Alert v-if="allowedTimeDisplay" class="mb-4">
    <Timer class="h-4 w-4" />
    <AlertDescription>
        {{ allowedTimeDisplay }}
    </AlertDescription>
</Alert>
```

**Example Display**:
```
⏱️ You are expected to complete this video within 40 minutes
```

**Design Decisions**:
- **Static label** (not a countdown timer) to avoid creating pressure
- Shows expected completion time based on video duration × 2
- Only displayed for video content (not PDFs)
- Uses friendly, informative language

### Why Not a Countdown?

A countdown timer could create unnecessary stress and pressure. The static label:
- Informs users of expectations without pressure
- Allows flexible learning pace
- Doesn't distract from content
- Aligns with the goal of fair, flexible tracking

## Progress Persistence

### Saving Progress

Progress is automatically saved in multiple scenarios:

1. **During Playback** (via heartbeat every 10 minutes):
```typescript
const sendHeartbeat = async () => {
    const payload = {
        action: 'heartbeat',
        current_position: currentTime.value,  // ✅ Current video position
        // ... other data
    }
    await axios.post(`/content/${props.content.id}/session`, payload)
}
```

2. **On Pause**:
```typescript
const onPause = () => {
    updateProgress()  // Saves current position
}
```

3. **On Page Unload**:
```typescript
const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    // Saves position via sendBeacon
}
```

### Restoring Progress

When user returns to a video, the saved position is restored:

```typescript
const onCanPlay = async () => {
    isVideoLoading.value = false
    isVideoReady.value = true
    
    // ✅ Restore saved position if available
    if (props.userProgress?.current_position && videoElement.value) {
        videoElement.value.currentTime = props.userProgress.current_position
        currentTime.value = props.userProgress.current_position
    }
}
```

**Requirements Satisfied**:
- 3.1 - Save timestamp on pause
- 3.3 - Persist position on logout
- 3.4 - Resume from saved position

## Example Event Flow

### Scenario 1: Normal Viewing with Pause

```
1. User clicks play
   → onPlay() fires
   → isActivelyPlaying = true
   → logVideoEvent('resume')
   → Active playback counter starts

2. Video plays for 30 seconds
   → updateActivePlaybackTime() increments every second
   → activePlaybackTime = 30

3. User pauses at 30s
   → onPause() fires
   → isActivelyPlaying = false
   → logVideoEvent('pause')
   → Active playback counter stops

4. User waits 10 seconds (paused)
   → activePlaybackTime stays at 30 (not incrementing)

5. User resumes
   → onPlay() fires
   → isActivelyPlaying = true
   → logVideoEvent('resume')
   → Active playback counter resumes

6. Video plays for 20 more seconds
   → activePlaybackTime = 50

Final result: 50 seconds of active playback (30 + 20)
```

### Scenario 2: Buffering During Playback

```
1. Video playing normally
   → isActivelyPlaying = true
   → activePlaybackTime incrementing

2. Network slowdown causes buffering
   → onWaiting() fires
   → isBuffering = true
   → isActivelyPlaying = false
   → Active playback counter pauses

3. Buffering for 5 seconds
   → activePlaybackTime does NOT increment

4. Buffering complete
   → onPlaying() fires
   → isBuffering = false
   → isActivelyPlaying = true
   → Active playback counter resumes

Result: Buffering time excluded from active playback
```

### Scenario 3: Rewind to Review

```
1. User watches to 60 seconds
   → activePlaybackTime = 60

2. User seeks back to 30 seconds
   → onVideoSeeked() fires
   → Detects: 30 < 60 (backward seek)
   → logVideoEvent('rewind', {
       startPosition: 60,
       endPosition: 30
     })

3. User watches from 30s to 70s (40 seconds)
   → activePlaybackTime = 100 (60 + 40)

Result: Rewound section (30-60) NOT counted twice
```

## Testing and Debugging

### Enable Console Logging

Uncomment the console.log in `logVideoEvent`:

```typescript
const logVideoEvent = (type: 'pause' | 'resume' | 'rewind', data?: any) => {
    const event: VideoEvent = {
        type,
        timestamp: Date.now(),
        position: currentTime.value,
        ...data
    }
    videoEvents.value.push(event)
    console.log('📹 Video event logged:', event)  // ✅ Uncomment for debugging
}
```

### Manual Testing Checklist

- [ ] **Active Playback Tracking**
  - [ ] Counter increments during normal playback
  - [ ] Counter pauses during initial loading
  - [ ] Counter pauses during buffering
  - [ ] Counter pauses when user pauses
  - [ ] Counter resumes when user resumes

- [ ] **Event Logging**
  - [ ] Pause events logged with correct timestamp and position
  - [ ] Resume events logged with correct timestamp and position
  - [ ] Rewind events logged with start and end positions
  - [ ] Events sent to backend on session end

- [ ] **Allowed Time Display**
  - [ ] Label shows correct calculation (duration × 2)
  - [ ] Label only shows for video content
  - [ ] Label displays in user-friendly format

- [ ] **Progress Persistence**
  - [ ] Position saved on pause
  - [ ] Position saved on page unload
  - [ ] Position restored when returning to video

### Browser DevTools Inspection

1. **Check State Variables**:
```javascript
// In Vue DevTools
activePlaybackTime.value  // Should increment only when playing
isActivelyPlaying.value   // Should be true only when actually playing
isBuffering.value         // Should be true during buffering
videoEvents.value         // Should contain logged events
```

2. **Monitor Network Requests**:
- Check POST requests to `/content/{id}/session`
- Verify `active_playback_time` is included in payload
- Verify `video_events` array is included in session end

3. **Console Logs**:
```
📹 Video event logged: {type: 'resume', timestamp: 1702345678901, position: 0}
📹 Video event logged: {type: 'pause', timestamp: 1702345708901, position: 30}
📹 Video event logged: {type: 'rewind', timestamp: 1702345738901, position: 15, startPosition: 30, endPosition: 15}
```

## Performance Considerations

### Memory Usage

- **Video Events Array**: Grows with user interactions. For typical sessions (< 100 events), memory impact is negligible.
- **Active Playback Counter**: Single number, minimal memory footprint.

### CPU Usage

- **Update Interval**: Runs every 1 second, very lightweight operation.
- **Event Handlers**: Fire only on user interactions, minimal overhead.

### Network Usage

- **Heartbeat**: Every 10 minutes, small payload (~500 bytes).
- **Session End**: Single request with all data (~1-5 KB depending on event count).

## Troubleshooting

### Issue: Active playback time not incrementing

**Possible Causes**:
1. Video not in "playing" state
2. Buffering or loading state active
3. Interval not set up

**Debug Steps**:
```typescript
// Check state
console.log('isActivelyPlaying:', isActivelyPlaying.value)
console.log('isBuffering:', isBuffering.value)
console.log('isVideoLoading:', isVideoLoading.value)
console.log('isPlaying:', isPlaying.value)
console.log('isVideoReady:', isVideoReady.value)
```

### Issue: Events not being logged

**Possible Causes**:
1. Event handlers not attached to video element
2. `logVideoEvent` function not being called

**Debug Steps**:
```typescript
// Add console.log in event handlers
const onPause = () => {
    console.log('onPause fired')
    logVideoEvent('pause')
}
```

### Issue: Progress not persisting

**Possible Causes**:
1. Session not being created
2. Backend not saving position
3. `sendBeacon` failing on page unload

**Debug Steps**:
```typescript
// Check session ID
console.log('sessionId:', sessionId.value)

// Check network tab for POST requests
// Verify current_position is in payload
```

## Related Documentation

- [Video Tracking Backend Guide](./VIDEO_TRACKING_BACKEND_GUIDE.md) - Backend implementation details
- [Active Playback Tracking Implementation](./ACTIVE_PLAYBACK_TRACKING_IMPLEMENTATION.md) - Detailed implementation notes
- [Video Event Logging Guide](./VIDEO_EVENT_LOGGING_GUIDE.md) - Event logging specifics
- [Requirements Document](../.kiro/specs/video-quiz-tracking-updates/requirements.md) - Feature requirements
- [Design Document](../.kiro/specs/video-quiz-tracking-updates/design.md) - Technical design

## Summary

The frontend video tracking system provides:

1. **Accurate Time Tracking**: Only counts active playback time, excluding loading, buffering, and pauses
2. **Comprehensive Event Logging**: Records all user interactions for analysis
3. **User-Friendly Display**: Shows expected completion time without creating pressure
4. **Reliable Data Transmission**: Uses `sendBeacon` for page unload scenarios
5. **Progress Persistence**: Saves and restores video position across sessions

This implementation ensures fair and accurate tracking of user engagement while providing a smooth, non-intrusive learning experience.
