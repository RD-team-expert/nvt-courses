# Design Document

## Overview

This design addresses the implementation gap between the backend time limit functionality and the frontend user experience. The backend already stores and validates `time_limit_minutes`, and the admin interface allows setting this value. However, the user-facing quiz pages have a mismatch in the data property name, causing the timer not to display or function correctly.

The primary issue is that the backend passes `time_limit_minutes` but the frontend expects `time_limit`. Additionally, we need to ensure all admin pages properly display time limit information.

## Architecture

### Current State

**Backend (Working)**:
- Database field: `time_limit_minutes` (integer, nullable)
- Model: Quiz model has `time_limit_minutes` in fillable and casts
- Controllers: Admin QuizController validates and stores `time_limit_minutes`
- API: QuizController passes `time_limit_minutes` to frontend

**Frontend (Partially Working)**:
- Admin Create/Edit pages: Have input field for `time_limit_minutes` ✓
- User Quiz Show page: Has timer logic but expects `quiz.time_limit` instead of `quiz.time_limit_minutes` ✗

### Target State

All components will use consistent naming (`time_limit_minutes`) and properly display/enforce time limits.

## Components and Interfaces

### Backend Components (No Changes Needed)

The backend is already correctly implemented:

1. **Quiz Model** (`app/Models/Quiz.php`)
   - Field: `time_limit_minutes`
   - Validation: 1-1440 minutes
   - Already in fillable array and casts

2. **Admin QuizController** (`app/Http/Controllers/Admin/QuizController.php`)
   - Already validates and stores `time_limit_minutes`
   - Already passes to admin views

3. **User QuizController** (`app/Http/Controllers/QuizController.php`)
   - Already passes `time_limit_minutes` to user views (line 180)

### Frontend Components (Need Updates)

1. **User Quiz Show Page** (`resources/js/pages/Quizzes/Show.vue`)
   - **Issue**: Expects `quiz.time_limit` but receives `quiz.time_limit_minutes`
   - **Fix**: Update all references from `quiz.time_limit` to `quiz.time_limit_minutes`
   - **Lines to update**: 59, 68, 69, 237, 238

2. **Admin Quiz Index Page** (`resources/js/pages/Admin/Quizzes/Index.vue`)
   - **Check**: Verify time limit is displayed in quiz listings
   - **Add**: Time limit column if missing

3. **Admin Quiz Show Page** (`resources/js/pages/Admin/Quizzes/Show.vue`)
   - **Check**: Verify time limit is displayed in quiz details
   - **Add**: Time limit display if missing

4. **Admin Quiz Edit Page** (`resources/js/pages/Admin/Quizzes/Edit.vue`)
   - **Check**: Verify time limit field exists and is populated
   - **Verify**: Form submission includes `time_limit_minutes`

## Data Models

### Quiz Model Fields (Existing)

```php
'time_limit_minutes' => 'nullable|integer|min:1|max:1440'
```

- **Type**: Integer (nullable)
- **Range**: 1-1440 minutes (1 minute to 24 hours)
- **Default**: null (no time limit)
- **Storage**: Database column `time_limit_minutes`

### Frontend Data Structure

```javascript
quiz: {
    id: number,
    title: string,
    description: string,
    time_limit_minutes: number | null,  // Key field
    pass_threshold: number,
    total_points: number,
    // ... other fields
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Time limit data consistency
*For any* quiz with a time limit set, the value passed from backend to frontend should match the database value exactly
**Validates: Requirements 1.5**

### Property 2: Timer countdown accuracy
*For any* quiz with a time limit, the countdown timer should decrease by 1 second every second until reaching zero
**Validates: Requirements 2.2**

### Property 3: Auto-submit on expiry
*For any* quiz attempt where the time limit expires, the system should automatically submit the current answers without user interaction
**Validates: Requirements 3.1**

### Property 4: Time limit display consistency
*For any* quiz with a time limit, all admin pages (index, show, edit) should display the same time limit value
**Validates: Requirements 1.1, 1.2, 1.3**

### Property 5: Timer warning thresholds
*For any* quiz timer, when time remaining is less than 5 minutes, the display should change to warning color, and when less than 1 minute, to urgent color
**Validates: Requirements 2.3, 2.4**

## Error Handling

### Frontend Error Scenarios

1. **Missing time_limit_minutes in props**
   - **Handling**: Treat as no time limit (null)
   - **Display**: Show "No time limit" in UI
   - **Behavior**: No timer displayed, no auto-submit

2. **Invalid time_limit_minutes value (negative, zero, or > 1440)**
   - **Handling**: Backend validation prevents this
   - **Fallback**: If somehow received, treat as no time limit

3. **Timer reaches zero during submission**
   - **Handling**: Allow submission to complete
   - **Behavior**: Don't trigger auto-submit if manual submit is in progress

4. **Network error during auto-submit**
   - **Handling**: Retry submission once
   - **Display**: Show error message to user
   - **Fallback**: Allow manual retry

5. **User navigates away during timed quiz**
   - **Handling**: Clear timer interval on component unmount
   - **Behavior**: Timer stops, no auto-submit triggered

### Backend Error Scenarios

1. **Invalid time_limit_minutes in request**
   - **Handling**: Laravel validation returns 422 error
   - **Message**: "The time limit minutes must be between 1 and 1440."

2. **Quiz attempt after time expired**
   - **Handling**: Check attempt start time + time limit
   - **Behavior**: Accept submission if within grace period (30 seconds)

## Testing Strategy

### Unit Tests

1. **Test time limit display in admin pages**
   - Create quiz with time limit
   - Verify displayed in index, show, edit pages
   - Verify "No time limit" shown when null

2. **Test timer initialization**
   - Mount quiz component with time limit
   - Verify timer starts at correct value (minutes * 60)
   - Verify timer interval is created

3. **Test timer countdown**
   - Mock setInterval
   - Verify timer decrements by 1 each second
   - Verify timer stops at zero

4. **Test auto-submit trigger**
   - Set timer to 1 second
   - Wait for expiry
   - Verify submitAttempt is called

5. **Test timer cleanup**
   - Mount component with timer
   - Unmount component
   - Verify interval is cleared

### Integration Tests

1. **End-to-end timed quiz flow**
   - Admin creates quiz with 2-minute time limit
   - User starts quiz
   - Verify timer displays and counts down
   - Wait for expiry
   - Verify auto-submit occurs
   - Verify attempt is recorded

2. **Timer warning colors**
   - Start quiz with 6-minute time limit
   - Fast-forward to 4 minutes remaining
   - Verify warning color (orange)
   - Fast-forward to 30 seconds remaining
   - Verify urgent color (red)

3. **Manual submit before expiry**
   - Start quiz with time limit
   - Answer questions
   - Submit manually before time expires
   - Verify submission succeeds
   - Verify timer is cleared

### Property-Based Tests

Property-based tests will be implemented using a JavaScript testing library (e.g., fast-check for JavaScript/TypeScript).

1. **Property Test: Timer accuracy**
   - Generate random time limits (1-1440 minutes)
   - Start timer
   - Verify countdown matches expected value at random intervals
   - **Validates: Property 2**

2. **Property Test: Data consistency**
   - Generate random quiz data with various time limits
   - Pass through backend to frontend
   - Verify time_limit_minutes matches at all points
   - **Validates: Property 1**

## Implementation Notes

### Key Changes Required

1. **User Quiz Show Page** (`resources/js/pages/Quizzes/Show.vue`)
   ```javascript
   // Change from:
   timeRemaining = ref(props.quiz.time_limit ? props.quiz.time_limit * 60 : 0)
   
   // To:
   timeRemaining = ref(props.quiz.time_limit_minutes ? props.quiz.time_limit_minutes * 60 : 0)
   ```

2. **Timer Display Logic**
   ```javascript
   // Change from:
   if (props.quiz.time_limit && timeRemaining.value > 0)
   
   // To:
   if (props.quiz.time_limit_minutes && timeRemaining.value > 0)
   ```

3. **UI Display**
   ```vue
   <!-- Change from: -->
   <p class="font-semibold">{{ quiz.time_limit ? `${quiz.time_limit} min` : 'No limit' }}</p>
   
   <!-- To: -->
   <p class="font-semibold">{{ quiz.time_limit_minutes ? `${quiz.time_limit_minutes} min` : 'No limit' }}</p>
   ```

### Admin Pages Verification

Need to verify these pages properly display time_limit_minutes:

1. **Index.vue**: Add time limit column to quiz table
2. **Show.vue**: Display time limit in quiz details
3. **Edit.vue**: Verify form field is populated (already exists in Create.vue)

### Auto-Submit Implementation

The auto-submit logic already exists in Show.vue (lines 70-73):
```javascript
if (timeRemaining.value <= 0) {
    clearInterval(timerInterval.value)
    submitAttempt() // Auto-submit when time runs out
}
```

This should work correctly once the property name is fixed.

### Timer Warning Colors

The warning color logic already exists (lines 237-240):
```javascript
:class="[
    'text-lg font-bold',
    timeRemaining < 300 ? 'text-destructive' : 'text-foreground'
]"
```

This implements the 5-minute warning threshold. The 1-minute urgent color is also implemented in the Clock icon class (line 234).

## Performance Considerations

1. **Timer Interval**: Uses setInterval with 1-second updates
   - Minimal performance impact
   - Cleared on component unmount to prevent memory leaks

2. **Auto-Submit**: Triggers standard form submission
   - No additional overhead
   - Uses existing submission logic

3. **Progress Bar**: Updates every second
   - Lightweight calculation
   - No DOM manipulation issues

## Security Considerations

1. **Client-Side Timer**: Not trusted for enforcement
   - Backend must validate attempt duration
   - Check `started_at` timestamp + time_limit_minutes
   - Accept submissions within grace period

2. **Time Manipulation**: Users could manipulate client timer
   - Backend validation prevents cheating
   - Server-side time tracking is authoritative

3. **Auto-Submit**: Prevents data loss
   - Saves current answers even if incomplete
   - Better UX than losing all progress
