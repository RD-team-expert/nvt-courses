# Implementation Plan

## Video & Quiz Tracking Updates

- [x] 1. Database Schema Updates





  - [x] 1.1 Create migration for learning_sessions table updates


    - Add `active_playback_time` column (INT, default 0)
    - Add `is_within_allowed_time` column (BOOLEAN, default TRUE)
    - Add `video_events` column (JSON, nullable)
    - _Requirements: 1.5, 6.1, 6.5_
  - [ ]* 1.2 Write property test for database schema
    - **Property 1: Active Playback Time Tracking**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

- [x] 2. Backend - LearningSession Model Updates





  - [x] 2.1 Update LearningSession model with new fields


    - Add `active_playback_time`, `is_within_allowed_time`, `video_events` to fillable
    - Add proper casts for new fields
    - _Requirements: 1.5, 6.1_
  - [ ]* 2.2 Write property test for allowed time calculation
    - **Property 2: Allowed Time Calculation**
    - **Validates: Requirements 2.1**

- [x] 3. Backend - LearningSessionService Updates





  - [x] 3.1 Add method to update active playback time


    - Create `updateActivePlaybackTime()` method
    - Accept active playback seconds and video events array
    - _Requirements: 1.5, 6.2, 6.3, 6.4_
  - [x] 3.2 Add method to check if within allowed time

    - Create `isWithinAllowedTime()` method
    - Calculate allowed time as Video Duration × 2
    - _Requirements: 2.1, 2.3_
  - [x] 3.3 Update endSession method to use active playback time


    - Calculate `is_within_allowed_time` flag
    - Store video events JSON
    - Use active playback time for attention score
    - _Requirements: 1.5, 6.5, 8.1_
  - [ ]* 3.4 Write property test for no penalty within allowed time
    - **Property 3: No Penalty Within Allowed Time**
    - **Validates: Requirements 2.3, 2.4, 8.2, 8.3**
  - [ ]* 3.5 Write property test for remaining time calculation
    - **Property 4: Remaining Time Calculation**
    - **Validates: Requirements 2.5**

- [x] 4. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

- [x] 4.1 Create documentation for database and backend changes


  - Create `docs/VIDEO_TRACKING_BACKEND_GUIDE.md`
  - Document new database columns (active_playback_time, is_within_allowed_time, video_events)
  - Document LearningSessionService new methods
  - Explain allowed time calculation (Duration × 2)
  - _Requirements: Documentation_

- [x] 5. Backend - CourseOnlineReportController Updates





  - [x] 5.1 Update attention score calculation


    - Use active playback time instead of total session time
    - No penalties for pauses/rewinds within allowed time (Duration × 2)
    - Only penalize skip forward behavior
    - _Requirements: 8.1, 8.2, 8.3, 8.4_
  - [x] 5.2 Update reports to show both active and total time


    - Add `active_playback_minutes` to session reports
    - Add `is_within_allowed_time` flag to reports
    - _Requirements: 8.5_
  - [ ]* 5.3 Write property test for active time in score calculation
    - **Property 13: Active Time in Score Calculation**
    - **Validates: Requirements 8.1**
  - [ ]* 5.4 Write property test for penalty when exceeding allowed time
    - **Property 14: Penalty for Exceeding Allowed Time**
    - **Validates: Requirements 8.4**

- [x] 6. Frontend - ContentViewer/Show.vue Updates





  - [x] 6.1 Add active playback time tracking state


    - Add `activePlaybackTime`, `isActivelyPlaying`, `isBuffering` refs
    - Add `allowedTimeMinutes` computed from video duration × 2
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1_


  - [x] 6.2 Implement active playback time counter





    - Only increment when video is playing AND not buffering/loading
    - Pause counter on pause, buffering, loading events


    - Resume counter on play event when ready
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_


  - [x] 6.3 Add static allowed time display label




    - Show "You are expected to complete this video within X minutes"
    - Display near video player (no countdown timer)


    - _Requirements: 2.2_
  - [x] 6.4 Implement video event logging





    - Log pause events with timestamp
    - Log resume events with timestamp
    - Log rewind events with start/end positions
    - _Requirements: 6.2, 6.3, 6.4_
  - [x] 6.5 Update session API calls to send active playback data








    - Send `active_playback_time` in heartbeat
    - Send `video_events` array in session end
    - _Requirements: 1.5, 6.1_
  - [x] 6.6 Write property test for progress persistence





    - **Property 5: Progress Persistence**
    - **Validates: Requirements 3.1, 3.3, 3.4**
  - [x] 6.7 Write property test for rewind/replay time exclusion






    - **Property 6: Rewind/Replay Time Exclusion**
    - **Validates: Requirements 3.2, 3.5**

- [x] 7. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.

- [x] 7.1 Create documentation for frontend video tracking


  - Create `docs/VIDEO_TRACKING_FRONTEND_GUIDE.md`
  - Document active playback time tracking logic
  - Explain how buffering/loading detection works
  - Document video event logging (pause, resume, rewind)
  - Show example of allowed time display
  - _Requirements: Documentation_

- [x] 8. Frontend - Post-Completion Behavior





  - [x] 8.1 Implement post-completion tracking bypass





    - Check if video is already completed before tracking
    - Skip active time tracking for completed videos
    - Skip attention score updates for completed videos
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  - [x] 8.2 Add completed video indicator


    - Show message that re-watching is optional and untracked
    - _Requirements: 4.5_
  - [x] 8.3 Write property test for post-completion no tracking






    - **Property 7: Post-Completion No Tracking**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4**

- [x] 9. Admin Quiz - Add Max Attempts Field





  - [x] 9.1 Update Admin/Quizzes/Create.vue


    - Add "Attempt Settings" card section
    - Add `max_attempts` input field with validation
    - Add helper text explaining the field
    - _Requirements: 5.1_
  - [x] 9.2 Update Admin/Quizzes/Edit.vue


    - Add "Attempt Settings" card section
    - Add `max_attempts` input field with validation
    - Load existing max_attempts value from quiz
    - _Requirements: 5.1_
  - [x] 9.3 Update QuizController to handle max_attempts


    - Ensure `max_attempts` is validated and saved on create
    - Ensure `max_attempts` is validated and saved on update
    - _Requirements: 5.1, 5.2_
  - [ ]* 9.4 Write property test for quiz attempt limit enforcement
    - **Property 8: Quiz Attempt Limit Enforcement**
    - **Validates: Requirements 5.3**

- [ ] 10. Quiz - Time Limit and Auto-Submit
  - [ ] 10.1 Verify quiz time limit functionality
    - Ensure countdown timer displays correctly
    - Ensure time limit is enforced
    - _Requirements: 5.4_
  - [ ] 10.2 Verify auto-submit on time expiry
    - Ensure quiz auto-submits when time runs out
    - Ensure further modifications are blocked
    - _Requirements: 5.5, 5.6_
  - [ ] 10.3 Add remaining attempts display
    - Show user their remaining attempts on quiz page
    - Show time limit information
    - _Requirements: 5.7_
  - [ ]* 10.4 Write property test for quiz auto-submit
    - **Property 9: Quiz Auto-Submit on Time Expiry**
    - **Validates: Requirements 5.5, 5.6**

- [ ] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11.1 Create documentation for quiz updates
  - Create `docs/QUIZ_ATTEMPTS_GUIDE.md`
  - Document how to configure max_attempts in Admin panel
  - Document how to configure time_limit_minutes
  - Explain how attempt limits are enforced
  - Show examples of quiz auto-submit behavior
  - _Requirements: Documentation_

- [ ] 12. Logging and Reporting
  - [ ] 12.1 Implement video event logging storage
    - Store pause/resume/rewind events in video_events JSON
    - Include timestamps and positions
    - _Requirements: 6.2, 6.3, 6.4_
  - [ ] 12.2 Implement session time compliance logging
    - Log `is_within_allowed_time` flag on session end
    - _Requirements: 6.5_
  - [ ] 12.3 Verify quiz attempt logging
    - Ensure start time, end time, duration, score are logged
    - Ensure auto-submit events are logged
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_
  - [ ]* 12.4 Write property test for video event logging
    - **Property 10: Video Event Logging**
    - **Validates: Requirements 6.2, 6.3, 6.4**
  - [ ]* 12.5 Write property test for session time compliance logging
    - **Property 11: Session Time Compliance Logging**
    - **Validates: Requirements 6.5**
  - [ ]* 12.6 Write property test for quiz attempt logging
    - **Property 12: Quiz Attempt Logging**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**

- [ ] 13. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Create final comprehensive documentation
  - Create `docs/VIDEO_QUIZ_TRACKING_COMPLETE_GUIDE.md`
  - Document all changes made in this feature
  - Explain active playback time vs total session time
  - Document allowed time window (Duration × 2)
  - Explain attention score calculation updates
  - Document quiz attempt and time limit configuration
  - Include troubleshooting section
  - Add examples and screenshots
  - _Requirements: Documentation_
