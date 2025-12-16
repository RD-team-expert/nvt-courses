# Active Playback Time API Integration Guide

## Overview

This document describes how active playback time and video events are transmitted from the frontend to the backend through session API calls.

## Implementation Summary

Task 6.5 has been implemented to send active playback data in both heartbeat and session end API calls.

## Frontend Implementation

### Data Being Tracked

The frontend tracks the following data:

1. **Active Playback Time** (`activePlaybackTime`): Accumulated seconds of actual video playback (excluding loading, buffering, and pauses)
2. **Video Events** (`videoEvents`): Array of pause, resume, and rewind events with timestamps

### API Calls

#### 1. Heartbeat Call (Every ~10 minutes)

**Endpoint**: `POST /content/{content_id}/session`

**Payload**:
```javascript
{
    action: 'heartbeat',
    current_position: currentTime.value,
    skip_count: skipCountSinceLastHeartbeat.value,
    seek_count: seekCountSinceLastHeartbeat.value,
    pause_count: pauseCountSinceLastHeartbeat.value,
    watch_time: Math.floor(watchTimeSinceLastHeartbeat.value),
    active_playback_time: Math.floor(activePlaybackTime.value)  // ✅ NEW
}
```

**Purpose**: Periodically update the server with current active playback time to prevent data loss if the session ends unexpectedly.

#### 2. End Session Call (Normal Session End)

**Endpoint**: `POST /content/{content_id}/session`

**Payload**:
```javascript
{
    action: 'end',
    current_position: currentTime.value,
    skip_count: skipCountSinceLastHeartbeat.value,
    seek_count: seekCountSinceLastHeartbeat.value,
    pause_count: pauseCountSinceLastHeartbeat.value,
    watch_time: Math.floor(watchTimeSinceLastHeartbeat.value),
    active_playback_time: Math.floor(activePlaybackTime.value),  // ✅ NEW
    video_events: videoEvents.value  // ✅ NEW
}
```

**Purpose**: Send final active playback time and complete video event log when user explicitly ends the session.

#### 3. BeforeUnload/PageHide (Browser Close/Navigation)

**Endpoint**: `POST /content/{content_id}/session` (via `navigator.sendBeacon`)

**Payload** (FormData):
```javascript
formData.append('action', 'end')
formData.append('final_position', currentTime.value.toString())
formData.append('completion_percentage', progressPercentage.value.toString())
formData.append('final_watch_time', watchTimeSinceLastHeartbeat.value.toString())
formData.append('final_skip', skipCountSinceLastHeartbeat.value.toString())
formData.append('final_seek', seekCountSinceLastHeartbeat.value.toString())
formData.append('final_pause', pauseCountSinceLastHeartbeat.value.toString())
formData.append('active_playback_time', Math.floor(activePlaybackTime.value).toString())  // ✅ NEW
formData.append('video_events', JSON.stringify(videoEvents.value))  // ✅ NEW
```

**Purpose**: Ensure data is saved even when user closes browser or navigates away using the reliable `sendBeacon` API.

## Backend Implementation

### Controller: ContentViewController

**File**: `app/Http/Controllers/User/ContentViewController.php`

#### Heartbeat Handler

```php
case 'heartbeat':
    $session = $this->sessionService->getActiveSession($user->id, $content->id);

    if (!$session) {
        return response()->json(['success' => false, 'message' => 'No active session'], 404);
    }

    // ✅ NEW: Update active playback time if provided
    if ($request->has('active_playback_time')) {
        $this->sessionService->updateActivePlaybackTime(
            $session->id,
            $request->input('active_playback_time', 0),
            [] // Video events are only sent on session end
        );
    }

    $updated = $this->sessionService->updateHeartbeat(
        $session->id,
        $request->input('current_position', 0),
        $request->input('watch_time', 0),
        $request->input('skip_count', 0),
        $request->input('seek_count', 0),
        $request->input('pause_count', 0)
    );

    return response()->json([
        'success' => true,
        'duration_minutes' => $updated->total_duration_minutes,
        'total_watch_time' => $updated->video_watch_time,
    ]);
```

**Key Changes**:
- Checks if `active_playback_time` is present in the request
- Calls `updateActivePlaybackTime()` service method to persist the data
- Video events are not sent during heartbeat (only on session end)

#### End Session Handler

```php
case 'end':
    $session = $this->sessionService->getActiveSession($user->id, $content->id);

    if (!$session) {
        return response()->json(['success' => false, 'message' => 'No active session'], 404);
    }

    // ✅ NEW: Update active playback time and video events before ending
    if ($request->has('active_playback_time')) {
        $videoEvents = $request->input('video_events', []);
        
        // Handle video_events as JSON string (from FormData) or array
        if (is_string($videoEvents)) {
            $videoEvents = json_decode($videoEvents, true) ?? [];
        }
        
        $this->sessionService->updateActivePlaybackTime(
            $session->id,
            $request->input('active_playback_time', 0),
            $videoEvents
        );
    }

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

**Key Changes**:
- Checks if `active_playback_time` is present in the request
- Retrieves `video_events` from request
- Handles `video_events` as either JSON string (from FormData/sendBeacon) or array (from regular POST)
- Calls `updateActivePlaybackTime()` with both active time and video events
- Supports fallback parameter names for compatibility with sendBeacon calls

### Service: LearningSessionService

**File**: `app/Services/ContentView/LearningSessionService.php`

#### updateActivePlaybackTime Method

```php
/**
 * Update session with active playback time
 * 
 * @param int $sessionId
 * @param int $activePlaybackSeconds Active playback time in seconds
 * @param array $videoEvents Array of video events (pause, resume, rewind)
 * @return LearningSession
 */
public function updateActivePlaybackTime(
    int $sessionId,
    int $activePlaybackSeconds,
    array $videoEvents = []
): LearningSession {
    $session = LearningSession::findOrFail($sessionId);

    if ($session->session_end) {
        throw new \Exception('Cannot update ended session');
    }

    $session->update([
        'active_playback_time' => $activePlaybackSeconds,
        'video_events' => $videoEvents,
    ]);

    return $session->fresh();
}
```

**Purpose**: Persists active playback time and video events to the database.

## Database Schema

### learning_sessions Table

New columns added:

```sql
active_playback_time INT DEFAULT 0 
    COMMENT 'Active playback time in seconds (excludes loading, buffering, pauses)'

is_within_allowed_time BOOLEAN DEFAULT TRUE 
    COMMENT 'Whether session stayed within allowed time (Duration × 2)'

video_events JSON NULL 
    COMMENT 'JSON array of video events (pause, resume, rewind with timestamps)'
```

## Video Events Structure

Video events are stored as a JSON array with the following structure:

```json
[
    {
        "type": "pause",
        "timestamp": 1702345678901,
        "position": 125.5
    },
    {
        "type": "resume",
        "timestamp": 1702345698901,
        "position": 125.5
    },
    {
        "type": "rewind",
        "timestamp": 1702345718901,
        "position": 100.0,
        "startPosition": 150.0,
        "endPosition": 100.0
    }
]
```

**Event Types**:
- `pause`: User paused the video
- `resume`: User resumed playback
- `rewind`: User seeked backward in the video

**Fields**:
- `type`: Event type (pause/resume/rewind)
- `timestamp`: Unix timestamp in milliseconds
- `position`: Current video position in seconds
- `startPosition`: (rewind only) Position before seeking
- `endPosition`: (rewind only) Position after seeking

## Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    Frontend (Vue.js)                             │
│  - Tracks activePlaybackTime (seconds)                           │
│  - Logs videoEvents (pause/resume/rewind)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              API Call (Heartbeat or End Session)                 │
│  POST /content/{id}/session                                      │
│  {                                                               │
│    action: 'heartbeat' | 'end',                                  │
│    active_playback_time: 245,  // seconds                        │
│    video_events: [...]          // only on 'end'                 │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│         ContentViewController::manageSession()                   │
│  - Validates request                                             │
│  - Calls LearningSessionService                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│    LearningSessionService::updateActivePlaybackTime()            │
│  - Updates learning_sessions.active_playback_time                │
│  - Updates learning_sessions.video_events (JSON)                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Database (MySQL)                              │
│  learning_sessions table:                                        │
│  - active_playback_time: 245                                     │
│  - video_events: [{"type":"pause",...}]                          │
│  - is_within_allowed_time: true                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Testing

### Manual Testing Steps

1. **Start a video session**:
   - Open a video content page
   - Verify session starts when video loads

2. **Test heartbeat with active playback time**:
   - Play video for 30+ seconds
   - Check browser network tab for heartbeat call
   - Verify `active_playback_time` is included in payload
   - Check database: `learning_sessions.active_playback_time` should be updated

3. **Test video events logging**:
   - Pause the video → Check `videoEvents` array in browser console
   - Resume the video → Check for resume event
   - Rewind the video → Check for rewind event with positions

4. **Test normal session end**:
   - Play video and perform various actions (pause, rewind, etc.)
   - Navigate away or close the content
   - Check network tab for end session call
   - Verify both `active_playback_time` and `video_events` are sent
   - Check database: both fields should be populated

5. **Test sendBeacon (browser close)**:
   - Play video for a while
   - Close the browser tab
   - Check database: session should be ended with active playback time and events

### Database Verification

```sql
-- Check latest session with active playback data
SELECT 
    id,
    user_id,
    content_id,
    active_playback_time,
    total_duration_minutes,
    is_within_allowed_time,
    video_events,
    session_start,
    session_end
FROM learning_sessions
WHERE active_playback_time > 0
ORDER BY id DESC
LIMIT 5;

-- Check video events structure
SELECT 
    id,
    JSON_PRETTY(video_events) as formatted_events
FROM learning_sessions
WHERE video_events IS NOT NULL
ORDER BY id DESC
LIMIT 1;
```

## Troubleshooting

### Issue: active_playback_time is always 0

**Possible Causes**:
1. Frontend not tracking active playback time correctly
2. Video not entering "playing" state
3. Buffering/loading detection not working

**Solution**:
- Check browser console for `activePlaybackTime` value
- Verify `isActivelyPlaying` is true when video plays
- Check `isBuffering` and `isVideoLoading` states

### Issue: video_events is empty

**Possible Causes**:
1. User didn't pause/resume/rewind during session
2. Event logging not triggered

**Solution**:
- Perform pause/resume/rewind actions
- Check browser console for logged events
- Verify `logVideoEvent()` function is being called

### Issue: Data not persisted to database

**Possible Causes**:
1. API call failing
2. Backend not receiving parameters
3. Database column missing

**Solution**:
- Check browser network tab for API response
- Check Laravel logs: `storage/logs/laravel.log`
- Verify migration has been run: `php artisan migrate:status`

## Requirements Validation

This implementation satisfies the following requirements:

- **Requirement 1.5**: Active playback time is sent in session updates
- **Requirement 6.1**: Active playback time is logged separately from total session time
- **Requirement 6.2**: Pause events are logged with timestamps
- **Requirement 6.3**: Rewind events are logged with start/end positions
- **Requirement 6.4**: Resume events are logged with timestamps

## Related Documentation

- [Active Playback Tracking Implementation](./ACTIVE_PLAYBACK_TRACKING_IMPLEMENTATION.md)
- [Video Tracking Backend Guide](./VIDEO_TRACKING_BACKEND_GUIDE.md)
- [Video Event Logging Guide](./VIDEO_EVENT_LOGGING_GUIDE.md)
