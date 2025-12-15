# Active Playback Time Counter Implementation

## Task 6.2: Implement Active Playback Time Counter

### Overview
This document describes the implementation of the active playback time counter for video tracking, which only counts time when the video is actively playing (excluding loading, buffering, and pause periods).

### Requirements
- **1.1**: WHEN the video is loading or buffering THEN the System SHALL pause the active playback time counter
- **1.2**: WHEN the video resumes playing after buffering THEN the System SHALL resume the active playback time counter
- **1.3**: WHEN the user pauses the video THEN the System SHALL pause the active playback time counter
- **1.4**: WHEN the user resumes the video after pausing THEN the System SHALL resume the active playback time counter
- **1.5**: WHEN calculating session duration THEN the System SHALL use only the accumulated active playback time

### Implementation Details

#### State Variables (from Task 6.1)
```typescript
const activePlaybackTime = ref(0)           // Accumulated active playback seconds
const isActivelyPlaying = ref(false)        // True only when video is actually playing
const isBuffering = ref(false)              // True during buffering
const lastActiveTimeUpdate = ref(0)         // Timestamp of last active time update
```

#### Core Function
```typescript
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

#### Event Handlers

##### 1. onLoadStart - Video Loading Started
**Requirement: 1.1**
```typescript
const onLoadStart = () => {
    isVideoLoading.value = true
    isVideoReady.value = false
    isActivelyPlaying.value = false  // ✅ Pause counter during loading
}
```

##### 2. onLoadedData - Video Data Loaded
```typescript
const onLoadedData = () => {
    isVideoLoading.value = false
    isVideoReady.value = true
    // Don't start tracking until play event
}
```

##### 3. onCanPlay - Video Ready to Play
```typescript
const onCanPlay = async () => {
    isVideoLoading.value = false
    isVideoReady.value = true
    // Video is ready, but don't start active tracking until play event
}
```

##### 4. onWaiting - Video Buffering
**Requirement: 1.1**
```typescript
const onWaiting = () => {
    isVideoLoading.value = true
    isBuffering.value = true
    isActivelyPlaying.value = false  // ✅ Pause counter during buffering
}
```

##### 5. onPlaying - Video Actually Playing
**Requirement: 1.2**
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

##### 6. onPlay - Play Button Pressed
**Requirement: 1.4**
```typescript
const onPlay = () => {
    isPlaying.value = true
    lastCurrentTime.value = currentTime.value

    // ✅ Start active playback tracking (only if ready and not buffering)
    if (isVideoReady.value && !isBuffering.value && !isVideoLoading.value) {
        isActivelyPlaying.value = true
        lastActiveTimeUpdate.value = Date.now()
    }

    logVideoEvent('resume')

    if (!sessionId.value) {
        startSession()
    }
}
```

##### 7. onPause - Video Paused
**Requirement: 1.3**
```typescript
const onPause = () => {
    isPlaying.value = false
    isActivelyPlaying.value = false  // ✅ Stop active playback tracking

    logVideoEvent('pause')
    pauseCountSinceLastHeartbeat.value++
    totalPauseCount.value++

    updateProgress()
}
```

##### 8. onEnded - Video Ended
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

#### Tracking Interval
The `updateActivePlaybackTime()` function is called every second via an interval:

```typescript
const activePlaybackInterval = setInterval(() => {
    if (props.content.content_type === 'video') {
        updateActivePlaybackTime()
    }
}, 1000)  // Update every second
```

#### Cleanup
The interval is properly cleaned up when the component unmounts:

```typescript
onUnmounted(() => {
    clearInterval(activePlaybackInterval)
    // ... other cleanup
})
```

### State Transitions

```
Video States and Active Playback Tracking:

LOADING → isActivelyPlaying = false
    ↓
READY → isActivelyPlaying = false (waiting for play)
    ↓
PLAY PRESSED → isActivelyPlaying = true (if ready and not buffering)
    ↓
PLAYING → isActivelyPlaying = true (confirmed playing)
    ↓
BUFFERING → isActivelyPlaying = false (pause during buffer)
    ↓
PLAYING → isActivelyPlaying = true (resume after buffer)
    ↓
PAUSED → isActivelyPlaying = false (user paused)
    ↓
PLAY PRESSED → isActivelyPlaying = true (resume)
    ↓
ENDED → isActivelyPlaying = false (video finished)
```

### Integration with Backend

The active playback time is sent to the backend in two places:

1. **Heartbeat** (every 10 minutes):
```typescript
const payload = {
    action: 'heartbeat',
    active_playback_time: Math.floor(activePlaybackTime.value),
    // ... other data
}
```

2. **Session End**:
```typescript
const payload = {
    action: 'end',
    active_playback_time: Math.floor(activePlaybackTime.value),
    video_events: videoEvents.value,
    // ... other data
}
```

### Testing Scenarios

To verify the implementation works correctly, test these scenarios:

1. **Normal Playback**: Active time should increment normally
2. **Pause/Resume**: Active time should stop during pause, resume on play
3. **Buffering**: Active time should stop during buffering, resume when playing
4. **Loading**: Active time should not increment during initial load
5. **Seeking**: Active time should continue tracking after seek (unless paused)
6. **Multiple Pause/Resume Cycles**: Active time should accurately track only playing time
7. **Video End**: Active time should stop incrementing when video ends

### Verification Checklist

- [x] State variables defined (Task 6.1)
- [x] `updateActivePlaybackTime()` function implemented
- [x] Tracking interval set up (1 second)
- [x] `onLoadStart` pauses tracking during loading
- [x] `onWaiting` pauses tracking during buffering
- [x] `onPlaying` resumes tracking when actually playing
- [x] `onPlay` starts tracking when play button pressed (with conditions)
- [x] `onPause` stops tracking when paused
- [x] `onEnded` stops tracking when video ends
- [x] Active playback time sent in heartbeat
- [x] Active playback time sent in session end
- [x] Interval cleanup in `onUnmounted`
- [x] No TypeScript/linting errors

### Status
✅ **COMPLETE** - All requirements for Task 6.2 have been implemented.

The active playback time counter now:
- Only increments when video is actively playing
- Pauses during loading, buffering, and user pauses
- Resumes when video starts playing again
- Is properly integrated with the backend API
- Has proper cleanup on component unmount
