# Video Event Logging Implementation Guide

## Overview

This document describes the video event logging implementation for the ContentViewer component. The system tracks user interactions with video content including pause, resume, and rewind events.

## Implementation Details

### Data Structure

#### VideoEvent Interface
```typescript
interface VideoEvent {
    type: 'pause' | 'resume' | 'rewind'
    timestamp: number          // Unix timestamp in milliseconds
    position: number           // Current video position in seconds
    startPosition?: number     // For rewind: position before seeking
    endPosition?: number       // For rewind: position after seeking
}
```

### Event Types

#### 1. Pause Events
**Triggered when:** User pauses the video
**Data captured:**
- `type`: 'pause'
- `timestamp`: Time when pause occurred
- `position`: Video position when paused

**Implementation:**
```typescript
const onPause = () => {
    isPlaying.value = false
    isActivelyPlaying.value = false
    
    // Log pause event
    logVideoEvent('pause')
    
    // Track pause count
    pauseCountSinceLastHeartbeat.value++
    totalPauseCount.value++
    
    updateProgress()
}
```

#### 2. Resume Events
**Triggered when:** User resumes video playback
**Data captured:**
- `type`: 'resume'
- `timestamp`: Time when playback resumed
- `position`: Video position when resumed

**Implementation:**
```typescript
const onPlay = () => {
    isPlaying.value = true
    lastCurrentTime.value = currentTime.value
    
    // Start active playback tracking
    if (isVideoReady.value && !isBuffering.value && !isVideoLoading.value) {
        isActivelyPlaying.value = true
        lastActiveTimeUpdate.value = Date.now()
    }
    
    // Log resume event
    logVideoEvent('resume')
    
    if (!sessionId.value) {
        startSession()
    }
}
```

#### 3. Rewind Events
**Triggered when:** User seeks backward in the video
**Data captured:**
- `type`: 'rewind'
- `timestamp`: Time when rewind occurred
- `position`: Current video position after seeking
- `startPosition`: Position before seeking
- `endPosition`: Position after seeking

**Implementation:**
```typescript
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

### Position Tracking

The system tracks the previous video position to detect rewind events:

```typescript
// Track position in onTimeUpdate (throttled to 1 second)
let lastTimeUpdate = 0
const onTimeUpdate = () => {
    if (videoElement.value) {
        const now = Date.now()
        if (now - lastTimeUpdate >= 1000) {
            currentTime.value = videoElement.value.currentTime
            previousSeekPosition = currentTime.value  // Track for rewind detection
            lastTimeUpdate = now
        }
    }
}
```

### Event Logging Function

The central logging function that creates and stores events:

```typescript
const logVideoEvent = (type: 'pause' | 'resume' | 'rewind', data?: any) => {
    const event: VideoEvent = {
        type,
        timestamp: Date.now(),
        position: currentTime.value,
        ...data
    }
    videoEvents.value.push(event)
}
```

### Data Transmission

Video events are sent to the backend in three scenarios:

#### 1. Session End (Normal)
```typescript
const endSession = async () => {
    const payload = {
        action: 'end',
        current_position: currentTime.value,
        active_playback_time: Math.floor(activePlaybackTime.value),
        video_events: videoEvents.value,  // Send all logged events
        // ... other data
    }
    
    await axios.post(`/content/${props.content.id}/session`, payload)
}
```

#### 2. Page Unload (beforeunload)
```typescript
const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (sessionId.value) {
        const formData = new FormData()
        formData.append('action', 'end')
        formData.append('active_playback_time', Math.floor(activePlaybackTime.value).toString())
        formData.append('video_events', JSON.stringify(videoEvents.value))
        // ... other data
        
        navigator.sendBeacon(`/content/${props.content.id}/session`, formData)
    }
}
```

#### 3. Page Hide (pagehide)
```typescript
const handlePageHide = () => {
    if (sessionId.value) {
        const formData = new FormData()
        formData.append('action', 'end')
        formData.append('active_playback_time', Math.floor(activePlaybackTime.value).toString())
        formData.append('video_events', JSON.stringify(videoEvents.value))
        // ... other data
        
        navigator.sendBeacon(`/content/${props.content.id}/session`, formData)
    }
}
```

## Event Flow Examples

### Example 1: Normal Viewing Session
```
1. User starts video → resume event logged
2. User pauses at 30s → pause event logged
3. User resumes at 30s → resume event logged
4. User finishes video → session ends, all events sent to backend
```

### Example 2: Rewind Behavior
```
1. User watches to 60s
2. User seeks back to 30s → rewind event logged with:
   - startPosition: 60
   - endPosition: 30
3. User continues watching
```

### Example 3: Multiple Interactions
```
1. resume event at 0s
2. pause event at 45s
3. resume event at 45s
4. rewind event from 90s to 60s
5. pause event at 75s
6. resume event at 75s
7. Session ends → all 6 events sent to backend
```

## Backend Integration

The video events are stored in the `learning_sessions` table in the `video_events` JSON column:

```sql
ALTER TABLE learning_sessions ADD COLUMN video_events JSON NULL 
    COMMENT 'JSON array of video events (pause, resume, rewind with timestamps)';
```

### Example JSON Structure
```json
[
  {
    "type": "resume",
    "timestamp": 1702345678901,
    "position": 0
  },
  {
    "type": "pause",
    "timestamp": 1702345708901,
    "position": 30
  },
  {
    "type": "resume",
    "timestamp": 1702345718901,
    "position": 30
  },
  {
    "type": "rewind",
    "timestamp": 1702345778901,
    "position": 60,
    "startPosition": 90,
    "endPosition": 60
  }
]
```

## Requirements Validation

This implementation satisfies the following requirements:

- **Requirement 6.2**: ✅ WHEN a user pauses the video THEN the System SHALL log the pause event with timestamp
- **Requirement 6.3**: ✅ WHEN a user rewinds the video THEN the System SHALL log the rewind event with start and end positions
- **Requirement 6.4**: ✅ WHEN a user resumes the video THEN the System SHALL log the resume event with timestamp

## Testing

### Manual Testing Steps

1. **Test Pause Event:**
   - Open a video in the ContentViewer
   - Play the video
   - Pause the video
   - Check browser console for "📹 Video event logged: {type: 'pause', ...}"

2. **Test Resume Event:**
   - With video paused, click play
   - Check console for "📹 Video event logged: {type: 'resume', ...}"

3. **Test Rewind Event:**
   - Play video to at least 30 seconds
   - Seek backward using the timeline
   - Check console for "📹 Video event logged: {type: 'rewind', startPosition: X, endPosition: Y}"

4. **Test Data Transmission:**
   - Complete a video session
   - Check network tab for POST to `/content/{id}/session`
   - Verify `video_events` array is included in payload

### Browser Console Debugging

Enable console logging by uncommenting the log statement in `logVideoEvent`:

```typescript
const logVideoEvent = (type: 'pause' | 'resume' | 'rewind', data?: any) => {
    const event: VideoEvent = {
        type,
        timestamp: Date.now(),
        position: currentTime.value,
        ...data
    }
    videoEvents.value.push(event)
    console.log('📹 Video event logged:', event)  // Uncomment for debugging
}
```

## Performance Considerations

1. **Memory Usage**: Events are stored in memory during the session. For long sessions with many interactions, this could accumulate. Consider implementing a maximum event limit if needed.

2. **Network Transmission**: Events are only sent when the session ends, minimizing network overhead.

3. **Position Tracking**: The `onTimeUpdate` event is throttled to once per second to reduce CPU usage while still maintaining accurate position tracking for rewind detection.

## Future Enhancements

1. **Forward Skip Detection**: Currently only rewind (backward seeking) is logged. Could add forward skip detection.

2. **Event Aggregation**: For sessions with many events, could aggregate similar consecutive events (e.g., multiple small rewinds).

3. **Real-time Transmission**: Could send events in batches during the session rather than only at the end.

4. **Additional Event Types**: Could add events for:
   - Speed changes
   - Quality changes
   - Fullscreen toggles
   - Volume changes

## Related Documentation

- [Active Playback Tracking Implementation](./ACTIVE_PLAYBACK_TRACKING_IMPLEMENTATION.md)
- [Video Tracking Backend Guide](./VIDEO_TRACKING_BACKEND_GUIDE.md)
- [Requirements Document](../.kiro/specs/video-quiz-tracking-updates/requirements.md)
- [Design Document](../.kiro/specs/video-quiz-tracking-updates/design.md)
