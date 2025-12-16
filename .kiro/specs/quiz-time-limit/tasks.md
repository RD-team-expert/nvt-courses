# Implementation Plan

- [x] 1. Fix user quiz timer display and functionality


  - Update property name from `quiz.time_limit` to `quiz.time_limit_minutes` in Show.vue
  - Verify timer initializes correctly with time limit value
  - Verify countdown timer displays and updates every second
  - Verify auto-submit triggers when timer reaches zero
  - Test warning colors at 5 minutes and 1 minute thresholds
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5_



- [ ] 2. Verify and update admin quiz index page
  - Check if time limit is displayed in quiz listings
  - Add time limit column if missing
  - Display "No time limit" for quizzes without time limits


  - Format time limit in human-readable format (e.g., "30 min", "2 hours")
  - _Requirements: 1.1, 4.1, 4.2, 4.3_

- [ ] 3. Verify and update admin quiz show page
  - Check if time limit is displayed in quiz details


  - Add time limit display section if missing
  - Show time limit in quiz information card
  - Display "No time limit" when null
  - _Requirements: 1.2, 5.1_



- [ ] 4. Verify admin quiz edit page
  - Confirm time limit field exists and is populated correctly
  - Verify form submission includes time_limit_minutes
  - Test updating time limit value

  - Test removing time limit (setting to null)
  - _Requirements: 1.3, 1.4, 1.5_

- [ ] 5. Add time limit information to user quiz listing
  - Update user quiz index page to show time limit


  - Display time limit badge or indicator
  - Show "No time limit" for unlimited quizzes
  - _Requirements: 5.5_

- [ ] 6. Add time limit confirmation before quiz start
  - Show time limit in quiz details before starting
  - Display warning if quiz has strict time limit
  - Show estimated completion time
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 7. Checkpoint - Test complete time limit flow
  - Ensure all tests pass, ask the user if questions arise.
  - Test admin creating quiz with time limit
  - Test user viewing quiz with time limit
  - Test timer countdown and auto-submit
  - Test quiz completion within time limit
  - Test quiz auto-submit when time expires
  - _Requirements: All_
