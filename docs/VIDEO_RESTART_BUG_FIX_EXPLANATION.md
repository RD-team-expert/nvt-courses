# Video Restart Bug - Detailed Explanation & Fix

## 🐛 The Problem

Users reported that when watching course videos, the video would **restart from the beginning** when they returned to continue watching, even though the system was supposed to save their progress and resume from where they left off.

---

## 🔍 Root Cause Analysis

The issue was a **data mismatch** between the backend (Laravel/PHP) and frontend (Vue.js/TypeScript).

### The Mismatch

| Component | What It Sends/Expects | Field Name |
|-----------|----------------------|------------|
| **Backend** | Sends progress data | `progress` with `playback_position` |
| **Frontend** | Expects progress data | `userProgress` with `current_position` |

---

## 📊 How The System Works

### 1. **Data Flow Overview**

```
User Opens Video
    ↓
Backend Controller (ContentViewController.php)
    ↓
Progress Service (gets user's saved position from database)
    ↓
Data Service (prepares data for frontend)
    ↓
Frontend (Show.vue receives data and displays video)
```

---

## 💾 Backend Code Explanation

### Step 1: User Opens Content

**File:** `app/Http/Controllers/User/ContentViewController.php`

```php
public function show(ModuleContent $content)
{
    $user = auth()->user();
    
    // 1. Check if user has access to this content
    $assignment = $this->accessService->verifyAccessOrFail($user, $content);
    
    // 2. Load content relationships (module, course, video)
    $content->load(['module.courseOnline', 'video']);
    
    // 3. GET OR CREATE PROGRESS RECORD
    $progress = $this->progressService->getOrCreateProgress($user, $content);
    // This returns a UserContentProgress model with saved position
    
    // 4. Get video streaming URL if it's a video
    $streamingData = null;
    if ($content->content_type === 'video' && $content->video) {
        $streamingData = $this->videoService->getStreamingUrl($content->video, $content);
    }
    
    // 5. Get navigation (previous/next content)
    $navigation = $this->navigationService->getNavigationWithProgress($content, $user->id);
    
    // 6. BUILD RESPONSE FOR FRONTEND
    $responseData = $this->dataService->buildInertiaResponse(
        $content,
        $content->video,
        $streamingData,
        $progress,  // ← This contains the saved position!
        $navigation
    );
    
    return Inertia::render('User/ContentViewer/Show', $responseData);
}
```

**What happens here:**
- The system loads the user's progress from the database
- The `$progress` object contains `playback_position` (e.g., 125.5 seconds)
- This data is passed to the Data Service to prepare for the frontend

---

### Step 2: Progress Service Gets Saved Position

**File:** `app/Services/ContentView/ContentProgressService.php`

```php
public function getOrCreateProgress(User $user, ModuleContent $content): UserContentProgress
{
    return DB::transaction(function () use ($user, $content) {
        // Try to find existing progress
        $progress = UserContentProgress::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();
        
        if ($progress) {
            // User has watched this before - return saved progress
            // This includes playback_position (e.g., 125.5 seconds)
            return $progress;
        }
        
        // First time watching - create new progress record
        return UserContentProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'content_id' => $content->id,
            ],
            [
                'course_online_id' => $content->module->course_online_id,
                'module_id' => $content->module_id,
                'video_id' => $content->video_id,
                'content_type' => $content->content_type,
                'watch_time' => 0,
                'completion_percentage' => 0,
                'is_completed' => false,
                'playback_position' => 0,  // ← Starts at 0 for new videos
                'task_completed' => false,
            ]
        );
    });
}
```

**What happens here:**
- System checks if user has watched this video before
- If yes: returns saved progress with `playback_position` (e.g., 125.5 seconds)
- If no: creates new record with `playback_position = 0`

---

### Step 3: Data Service Prepares Data (THE BUG WAS HERE!)

**File:** `app/Services/ContentView/ContentDataService.php`

#### ❌ BEFORE THE FIX (BROKEN CODE):

```php
public function prepareProgressData(?UserContentProgress $progress): array
{
    if (!$progress) {
        return [
            'exists' => false,
            'completion_percentage' => 0,
            'playback_position' => 0,  // ← Backend uses this name
            'is_completed' => false,
            'watch_time' => 0,
        ];
    }
    
    return [
        'exists' => true,
        'id' => $progress->id,
        'completion_percentage' => $progress->completion_percentage,
        'playback_position' => $progress->playback_position ?? 0,  // ← Backend sends this
        'is_completed' => $progress->is_completed,
        'watch_time' => $progress->watch_time,
        'last_accessed_at' => $progress->last_accessed_at?->toIso8601String(),
        'completed_at' => $progress->completed_at?->toIso8601String(),
    ];
}

public function buildInertiaResponse(...): array
{
    $progressData = $this->prepareProgressData($progress);
    
    $response = [
        'content' => $contentData,
        'progress' => $progressData,  // ← Backend sends 'progress'
        'navigation' => $navigationData,
        'course' => [...],
        'module' => [...],
    ];
    
    return $response;
}
```

**The Problem:**
1. Backend sends data as `progress` (not `userProgress`)
2. Backend sends `playback_position` (not `current_position`)
3. Backend sends `watch_time` (not `time_spent`)

---

#### ✅ AFTER THE FIX (WORKING CODE):

```php
public function prepareProgressData(?UserContentProgress $progress): array
{
    if (!$progress) {
        return [
            'exists' => false,
            'completion_percentage' => 0,
            'playback_position' => 0,
            'current_position' => 0,  // ✅ FIX: Added for frontend
            'is_completed' => false,
            'watch_time' => 0,
            'time_spent' => 0,  // ✅ FIX: Added for frontend
        ];
    }
    
    $position = $progress->playback_position ?? 0;
    
    return [
        'exists' => true,
        'id' => $progress->id,
        'completion_percentage' => $progress->completion_percentage,
        'playback_position' => $position,  // ← Backend still uses this
        'current_position' => $position,   // ✅ FIX: Frontend uses this
        'is_completed' => $progress->is_completed,
        'watch_time' => $progress->watch_time,
        'time_spent' => $progress->watch_time,  // ✅ FIX: Frontend uses this
        'last_accessed_at' => $progress->last_accessed_at?->toIso8601String(),
        'completed_at' => $progress->completed_at?->toIso8601String(),
    ];
}

public function buildInertiaResponse(...): array
{
    $progressData = $this->prepareProgressData($progress);
    
    $response = [
        'content' => $contentData,
        'progress' => $progressData,      // ← Backend still sends this
        'userProgress' => $progressData,  // ✅ FIX: Frontend expects this
        'navigation' => $navigationData,
        'course' => [...],
        'module' => [...],
    ];
    
    return $response;
}
```

**The Fix:**
1. ✅ Send data as BOTH `progress` AND `userProgress`
2. ✅ Include BOTH `playback_position` AND `current_position` with same value
3. ✅ Include BOTH `watch_time` AND `time_spent` with same value

---

## 🎨 Frontend Code Explanation

### Step 4: Frontend Receives Data

**File:** `resources/js/pages/User/ContentViewer/Show.vue`

#### Frontend Expects This Structure:

```typescript
// TypeScript Interface Definition
interface UserProgress {
    id?: number
    current_position?: number      // ← Frontend expects THIS name
    completion_percentage?: number
    is_completed?: boolean
    time_spent?: number            // ← Frontend expects THIS name
}

// Component Props
const props = defineProps<{
    content: Content
    module?: Module
    course?: Course
    userProgress?: UserProgress    // ← Frontend expects THIS prop name
    navigation?: Navigation
    video?: Video
    pdf?: any
}>()
```

#### ❌ BEFORE THE FIX (BROKEN):

```typescript
// Backend sent: { progress: { playback_position: 125.5 } }
// Frontend expected: { userProgress: { current_position: 125.5 } }

const safeUserProgress = computed(() => props.userProgress || {
    id: 0,
    current_position: 0,  // ← This was ALWAYS 0 because userProgress didn't exist!
    completion_percentage: 0,
    is_completed: false,
    time_spent: 0
})

// Later in the code...
const onLoadedMetadata = () => {
    if (videoElement.value) {
        duration.value = videoElement.value.duration
        // ❌ BUG: This was always 0 because current_position didn't exist!
        videoElement.value.currentTime = safeUserProgress.value.current_position || 0
    }
}
```

**Result:** Video always started at 0 seconds (beginning)

---

#### ✅ AFTER THE FIX (WORKING):

```typescript
// Backend now sends: { 
//   progress: { playback_position: 125.5, current_position: 125.5 },
//   userProgress: { playback_position: 125.5, current_position: 125.5 }
// }

const safeUserProgress = computed(() => props.userProgress || {
    id: 0,
    current_position: 0,
    completion_percentage: 0,
    is_completed: false,
    time_spent: 0
})

// Later in the code...
const onLoadedMetadata = () => {
    if (videoElement.value) {
        duration.value = videoElement.value.duration
        // ✅ FIXED: Now gets the actual saved position (e.g., 125.5 seconds)
        videoElement.value.currentTime = safeUserProgress.value.current_position || 0
    }
}
```

**Result:** Video resumes from saved position (e.g., 125.5 seconds)

---

## 🔄 Complete Data Flow Example

### Scenario: User watches video, leaves, then returns

#### **First Visit (New Video)**

1. **User opens video** → Backend checks database
2. **No progress found** → Creates new record:
   ```php
   UserContentProgress {
       user_id: 123,
       content_id: 456,
       playback_position: 0,  // Starting from beginning
       completion_percentage: 0,
       is_completed: false
   }
   ```

3. **Backend sends to frontend:**
   ```json
   {
       "userProgress": {
           "current_position": 0,
           "playback_position": 0,
           "completion_percentage": 0
       }
   }
   ```

4. **Frontend loads video:**
   ```typescript
   videoElement.value.currentTime = 0  // Starts at beginning
   ```

5. **User watches for 2 minutes (120 seconds)** → Frontend sends progress update:
   ```javascript
   axios.post('/content/456/progress', {
       current_position: 120,
       completion_percentage: 25,
       watch_time: 2
   })
   ```

6. **Backend saves to database:**
   ```php
   UserContentProgress {
       playback_position: 120,  // Saved!
       completion_percentage: 25,
       watch_time: 2
   }
   ```

---

#### **Second Visit (Returning to Video)**

1. **User opens same video** → Backend checks database
2. **Progress found!** → Loads saved record:
   ```php
   UserContentProgress {
       user_id: 123,
       content_id: 456,
       playback_position: 120,  // ← Saved position!
       completion_percentage: 25,
       is_completed: false
   }
   ```

3. **Backend sends to frontend:**
   ```json
   {
       "userProgress": {
           "current_position": 120,      // ✅ Now includes this!
           "playback_position": 120,
           "completion_percentage": 25
       }
   }
   ```

4. **Frontend loads video:**
   ```typescript
   videoElement.value.currentTime = 120  // ✅ Resumes at 2 minutes!
   ```

5. **User continues watching** → Video plays from 2:00 mark

---

## 📝 Database Structure

**Table:** `user_content_progress`

```sql
CREATE TABLE user_content_progress (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    content_id BIGINT,
    course_online_id BIGINT,
    module_id BIGINT,
    video_id BIGINT,
    content_type VARCHAR(50),
    
    -- Progress tracking fields
    playback_position DECIMAL(8,2) DEFAULT 0.00,  -- ← Video position in seconds
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    watch_time INT DEFAULT 0,                      -- Total time spent (minutes)
    
    -- Timestamps
    last_accessed_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY (user_id, content_id)  -- One progress record per user per content
);
```

**Example Data:**

| id | user_id | content_id | playback_position | completion_percentage | watch_time |
|----|---------|------------|-------------------|----------------------|------------|
| 1  | 123     | 456        | 120.50            | 25.00                | 2          |
| 2  | 123     | 457        | 0.00              | 0.00                 | 0          |
| 3  | 124     | 456        | 480.00            | 100.00               | 8          |

---

## 🎯 Summary of The Fix

### What Was Changed:

**File:** `app/Services/ContentView/ContentDataService.php`

1. **Added `current_position` field** (mirrors `playback_position`)
2. **Added `time_spent` field** (mirrors `watch_time`)
3. **Added `userProgress` key** (mirrors `progress`)

### Why It Works Now:

| Before | After |
|--------|-------|
| Backend sends `progress` | Backend sends BOTH `progress` AND `userProgress` |
| Backend sends `playback_position` | Backend sends BOTH `playback_position` AND `current_position` |
| Frontend can't find `userProgress.current_position` | Frontend finds `userProgress.current_position` ✅ |
| Video always starts at 0 | Video resumes from saved position ✅ |

---

## 🧪 Testing The Fix

### Test Case 1: New Video
1. Open a video you've never watched
2. **Expected:** Video starts at 0:00 ✅
3. Watch for 2 minutes
4. Close browser
5. Reopen same video
6. **Expected:** Video resumes at 2:00 ✅

### Test Case 2: Partially Watched Video
1. Open a video you watched 50% of
2. **Expected:** Video resumes at 50% mark ✅
3. Watch to 75%
4. Refresh page
5. **Expected:** Video resumes at 75% ✅

### Test Case 3: Multiple Users
1. User A watches video to 3:00
2. User B watches same video to 5:00
3. User A returns
4. **Expected:** User A's video resumes at 3:00 ✅
5. User B returns
6. **Expected:** User B's video resumes at 5:00 ✅

---

## 🔧 Technical Details

### Why We Keep Both Field Names:

```php
return [
    'playback_position' => $position,  // Backend internal use
    'current_position' => $position,   // Frontend compatibility
    'watch_time' => $progress->watch_time,  // Backend internal use
    'time_spent' => $progress->watch_time,  // Frontend compatibility
];
```

**Reason:** 
- Maintains backward compatibility
- Doesn't break existing code that uses old field names
- Allows gradual migration to standardized naming

---

## 🚀 Future Improvements

1. **Standardize naming** across backend and frontend
2. **Add TypeScript types** for backend responses
3. **Create API documentation** for data contracts
4. **Add automated tests** for progress tracking
5. **Implement progress sync** across multiple devices

---

## 📚 Related Files

- `app/Http/Controllers/User/ContentViewController.php` - Main controller
- `app/Services/ContentView/ContentProgressService.php` - Progress logic
- `app/Services/ContentView/ContentDataService.php` - Data preparation (FIXED HERE)
- `app/Models/UserContentProgress.php` - Database model
- `resources/js/pages/User/ContentViewer/Show.vue` - Frontend component
- `database/migrations/*_create_user_content_progress_table.php` - Database schema

---

## ✅ Conclusion

The bug was caused by a simple naming mismatch between backend and frontend. The backend was sending data with one set of field names (`progress`, `playback_position`), while the frontend expected different names (`userProgress`, `current_position`).

The fix adds both naming conventions to the backend response, ensuring compatibility without breaking existing code. Now videos properly resume from where users left off!
