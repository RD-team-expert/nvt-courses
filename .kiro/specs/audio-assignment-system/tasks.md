# Implementation Plan

- [x] 1. Database schema and model setup

  - [x] 1.1 Create migration for audio_assignments table


    - Create migration with audio_id, user_id, assigned_by, assigned_at, started_at, completed_at, status, progress_percentage, notification_sent columns
    - Add unique constraint on audio_id + user_id
    - Add indexes for user_id, status, and assigned_by
    - _Requirements: 2.4, 2.5_
  - [x] 1.2 Create migration to add storage_type and local_path to audios table


    - Add storage_type enum column (google_drive, local) with default 'google_drive'
    - Add local_path nullable string column


    - _Requirements: 1.1, 1.2, 1.3_
  - [x] 1.3 Create AudioAssignment model with relationships


    - Define fillable fields, casts, and relationships (audio, user, assignedBy)


    - Implement markAsStarted(), updateProgress(), markAsCompleted() methods


    - Add scopes for pending, inProgress, completed
    - _Requirements: 2.4, 2.5_
  - [x] 1.4 Write property test for assignment creation completeness


    - **Property 2: Assignment creation completeness**

    - **Validates: Requirements 2.4, 2.5**








  - [ ] 1.5 Update Audio model with storage_type support
    - Add storage_type and local_path to fillable


    - Add getPlaybackUrl() method that returns correct URL based on storage_type
    - Add relationship to assignments
    - _Requirements: 1.2, 1.3, 5.1_








- [x] 2. Checkpoint - Ensure all tests pass


  - Ensure all tests pass, ask the user if questions arise.



- [ ] 3. Event and notification system
  - [ ] 3.1 Create AudioAssigned event
    - Define event with audio, user, assignedBy, loginLink, metadata properties


    - Implement ShouldBroadcast if needed


    - _Requirements: 3.1, 4.1_


  - [x] 3.2 Create AudioAssignmentNotification mail class


    - Include audio name, description, duration in email

    - Generate and include tokenized login link


    - Create blade template for email
    - _Requirements: 3.1, 3.2, 3.3_
  - [x] 3.3 Write property test for user notification email content


    - **Property 4: User notification email content**
    - **Validates: Requirements 3.1, 3.2, 3.3**
  - [x] 3.4 Create AudioAssignmentManagerNotification mail class



    - Include team member name and audio details








    - Create blade template for manager email





    - _Requirements: 4.2, 4.3_










  - [x] 3.5 Create SendAudioAssignmentNotifications listener


    - Send user notification email










    - Lookup manager using ManagerHierarchyService
    - Skip manager notification for self-managed users





    - Send manager notification if manager exists





    - _Requirements: 3.1, 4.1, 4.2, 4.4_
  - [ ] 3.6 Write property test for manager notification correctness
    - **Property 6: Manager notification correctness**





    - **Validates: Requirements 4.1, 4.2, 4.3**
  - [x] 3.7 Write property test for self-manager notification skip


    - **Property 7: Self-manager notification skip**


    - **Validates: Requirements 4.4**
  - [ ] 3.8 Register event and listener in EventServiceProvider
    - Map AudioAssigned event to SendAudioAssignmentNotifications listener

    - _Requirements: 3.1, 4.1_

- [ ] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Admin assignment controller and API



  - [ ] 5.1 Create AudioAssignmentController with index, create, store methods
    - index: List all assignments with pagination






    - create: Show assignment form with audio list
    - store: Create assignments for selected users, dispatch AudioAssigned event
    - _Requirements: 2.1, 2.4, 6.1_
  - [ ] 5.2 Implement user filter API endpoint
    - Filter users by department_id
    - Filter users by name search
    - Return paginated user list with department info
    - _Requirements: 2.2, 2.3_
  - [ ] 5.3 Write property test for user filter correctness
    - **Property 3: User filter correctness**
    - **Validates: Requirements 2.2, 2.3**
  - [ ] 5.4 Implement assignment status filter
    - Filter assignments by status (assigned, in_progress, completed)
    - _Requirements: 6.2_
  - [ ] 5.5 Write property test for assignment status filter correctness
    - **Property 10: Assignment status filter correctness**
    - **Validates: Requirements 6.2**
  - [ ] 5.6 Add routes for audio assignment endpoints
    - Register admin routes for assignment CRUD
    - Register API route for user filter
    - _Requirements: 2.1, 2.2, 2.3_

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Audio storage and playback
  - [x] 7.1 Update AudioController to support dual storage


    - Modify store method to handle storage_type selection
    - Save to local storage when selected
    - Save Google Drive URL when selected
    - _Requirements: 1.1, 1.2, 1.3_
  - [x] 7.2 Write property test for local storage file persistence



    - **Property 1: Local storage file persistence**


    - **Validates: Requirements 1.2**
  - [ ] 7.3 Implement audio streaming endpoint for local files
    - Create route for streaming local audio files


    - Validate user has assignment or is admin
    - Stream file with proper headers
    - _Requirements: 5.3_
  - [ ] 7.4 Write property test for storage type player selection
    - **Property 8: Storage type player selection**
    - **Validates: Requirements 5.1**
  - [ ] 7.5 Implement progress tracking for audio playback
    - Create endpoint to update listening progress
    - Update AudioAssignment progress_percentage
    - Mark as in_progress when started, completed when 100%
    - _Requirements: 5.4_
  - [x] 7.6 Write property test for progress tracking persistence

    - **Property 9: Progress tracking persistence**
    - **Validates: Requirements 5.4**

- [x] 8. Checkpoint - Ensure all tests pass



  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Login link authentication



  - [x] 9.1 Implement login token generation for audio assignments


    - Generate unique token when assignment is created
    - Store token with expiration in users table (reuse existing login_token)
    - _Requirements: 3.3_
  - [x] 9.2 Implement login link authentication endpoint


    - Validate token and authenticate user
    - Redirect to audio player page
    - Handle expired/invalid tokens
    - _Requirements: 3.4_
  - [x] 9.3 Write property test for login link authentication round-trip


    - **Property 5: Login link authentication round-trip**
    - **Validates: Requirements 3.4**

- [ ] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.



- [x] 11. Frontend components



  - [x] 11.1 Create admin audio assignment page (Vue component)

    - Display audio selection dropdown
    - Display user list with department/name filters
    - Multi-select users functionality
    - Submit button to create assignments
    - _Requirements: 2.1, 2.2, 2.3_


  - [ ] 11.2 Create admin assignment list page (Vue component)
    - Display assignments table with user, audio, status, date
    - Status filter dropdown


    - Pagination
    - _Requirements: 6.1, 6.2_


  - [ ] 11.3 Update audio creation form for storage type selection
    - Add storage type radio buttons (Google Drive / Local)
    - Show file upload for local, URL input for Google Drive
    - _Requirements: 1.1_
  - [ ] 11.4 Update audio player to handle both storage types
    - Detect storage type from audio data
    - Load correct source URL
    - Track progress on playback
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 12. Email templates




  - [x] 12.1 Create user assignment email template

    - Display audio name, description, duration
    - Include styled login link button
    - Responsive design
    - _Requirements: 3.2, 3.3_
  - [x] 12.2 Create manager notification email template

    - Display team member name
    - Display audio details
    - Display assignment information
    - _Requirements: 4.3_

- [ ] 13. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
