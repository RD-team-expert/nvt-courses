# Implementation Plan

## VPS Video Transcoding Integration

- [x] 1. Database Setup

  - [x] 1.1 Create migration for video_qualities table and transcode_status field


    - Create `video_qualities` table with: id, video_id, quality, file_path, file_size, timestamps
    - Add `transcode_status` enum field to `videos` table
    - Add unique constraint on (video_id, quality)
    - _Requirements: 6.1_


  - [x] 1.2 Run migration

    - Execute `php artisan migrate`


    - _Requirements: 6.1_

- [ ] 2. Create VideoQuality Model
  - [ ] 2.1 Create VideoQuality model
    - Create `app/Models/VideoQuality.php`
    - Define fillable fields: video_id, quality, file_path, file_size
    - Add `video()` relationship to Video model
    - Add `getFormattedFileSizeAttribute()` accessor


    - Add `deleteFile()` method


    - _Requirements: 6.1, 6.2_

  - [ ]* 2.2 Write property test for file path structure
    - **Property 5: File storage path structure**

    - **Validates: Requirements 6.1**

- [x] 3. Update Video Model

  - [ ] 3.1 Add transcode_status to Video model fillable
    - Add `transcode_status` to $fillable array
    - _Requirements: 3.1_
  - [ ] 3.2 Add qualities() relationship
    - Add HasMany relationship to VideoQuality
    - _Requirements: 3.3, 4.2_
  - [ ] 3.3 Add helper methods
    - Add `hasMultipleQualities()`, `getAvailableQualities()`, `getQualityPath()`

    - Add `isTranscoding()`, `isTranscodeComplete()`, `isTranscodeFailed()`


    - _Requirements: 3.1, 4.2_


  - [ ] 3.4 Update deleteStoredFile() to cascade delete quality files
    - Delete all quality variant files when video is deleted
    - Delete quality directory
    - _Requirements: 6.2_
  - [ ]* 3.5 Write property test for cascade deletion
    - **Property 6: Video deletion cascades to quality files**
    - **Validates: Requirements 6.2**



- [ ] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 5. Create VPS API Services
  - [ ] 5.1 Add transcoding configuration to config/services.php
    - Add `transcoding` array with url, api_key, project_key
    - _Requirements: 5.4_

  - [x] 5.2 Create VpsApiClient service


    - Create `app/Services/VpsApiClient.php`
    - Implement `sendTranscodeRequest()` method
    - Implement `downloadFile()` method
    - Implement `getProjectKey()` method
    - _Requirements: 1.1, 1.4, 5.1, 5.3_


  - [ ]* 5.3 Write property test for request fields
    - **Property 1: Transcoding request contains required fields**
    - **Validates: Requirements 1.1, 1.4**
  - [x] 5.4 Create VpsTranscodingService


    - Create `app/Services/VpsTranscodingService.php`


    - Implement `requestTranscoding()` method

    - Implement `handleCallback()` method
    - Implement `downloadAndStoreQuality()` method

    - Implement `retryTranscoding()` method
    - _Requirements: 1.1, 1.2, 2.3, 2.4, 7.2, 7.3_


  - [ ]* 5.5 Write property test for status transitions
    - **Property 2: Status transitions are valid**
    - **Validates: Requirements 1.2, 2.4, 2.5, 7.3**

- [ ] 6. Create Callback Controller
  - [ ] 6.1 Create TranscodeCallbackController
    - Create `app/Http/Controllers/TranscodeCallbackController.php`
    - Implement `handle()` method with project_key verification
    - _Requirements: 2.1, 2.2, 5.2_
  - [ ]* 6.2 Write property test for project_key verification
    - **Property 3: Callback project_key verification**
    - **Validates: Requirements 2.2, 5.2**
  - [ ] 6.3 Add callback route
    - Add POST `/api/transcode/callback` route to routes/web.php
    - _Requirements: 2.1_

- [ ] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Integrate with VideoController
  - [ ] 8.1 Update VideoController::store() to trigger transcoding
    - After creating local video, call VpsTranscodingService::requestTranscoding()
    - _Requirements: 1.1, 1.2_
  - [ ] 8.2 Add retryTranscode() method to VideoController
    - Implement retry functionality for failed transcoding
    - _Requirements: 7.1, 7.2, 7.3_
  - [ ] 8.3 Add retry route
    - Add POST `/admin/videos/{video}/retry-transcode` route
    - _Requirements: 7.1_

- [x] 9. Update VideoStreamController for Quality Selection





  - [x] 9.1 Add quality parameter to stream() method


    - Accept optional quality parameter
    - Use Video::getQualityPath() to get correct file
    - _Requirements: 4.2_
  - [ ]* 9.2 Write property test for quality selection
    - **Property 7: Quality selection returns correct file**
    - **Validates: Requirements 4.2**
  - [x] 9.3 Add quality stream route


    - Add GET `/video/stream/{video}/{quality?}` route
    - _Requirements: 4.2_

- [x] 10. Update Admin Video Views





  - [x] 10.1 Update Admin/Video/Index.vue to show transcode_status


    - Display status badge (pending, processing, completed, failed)
    - Show available qualities for completed videos
    - _Requirements: 3.1, 3.2, 3.3_


  - [ ] 10.2 Update Admin/Video/Show.vue to show transcoding details
    - Display transcode_status with appropriate styling
    - Show list of available quality variants with file sizes
    - Add "Retry Transcoding" button for failed status
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 7.1_
  - [ ]* 10.3 Write property test for completed videos exposing qualities
    - **Property 8: Completed videos expose available qualities**
    - **Validates: Requirements 3.1, 3.3**

- [x] 11. Update User ContentViewer for Quality Selection




  - [x] 11.1 Update ContentViewer/Show.vue to include quality selector


    - Add quality dropdown/selector component
    - Load available qualities from video props
    - _Requirements: 4.1, 5.1_


  - [ ] 11.2 Implement quality switching logic
    - Save quality preference to localStorage
    - Switch video source when quality changes


    - Maintain playback position during switch
    - _Requirements: 4.2, 4.3, 4.4_
  - [ ] 11.3 Handle single quality videos
    - Hide quality selector when only original quality available
    - _Requirements: 4.5_

- [x] 12. Update Backend to Pass Quality Data to Frontend






  - [x] 12.1 Update ContentViewController to include available qualities

    - Add available_qualities array to video data passed to frontend
    - _Requirements: 3.3, 4.1_
  - [x] 12.2 Update VideoController responses to include transcode info


    - Include transcode_status and available_qualities in API responses
    - _Requirements: 3.1, 3.3_

- [ ] 13. Final Checkpoint - Ensure all tests pass




  - Ensure all tests pass, ask the user if questions arise.

