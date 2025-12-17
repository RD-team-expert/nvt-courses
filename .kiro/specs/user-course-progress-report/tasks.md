# Implementation Plan

- [x] 1. Set up project structure and core interfaces
  - Create UserCourseProgressReportController in app/Http/Controllers/Admin
  - Create CourseProgressService in app/Services
  - Create LearningScoreCalculator in app/Services
  - Create ExcelExportService in app/Services (or extend existing)
  - Add routes for report page and export endpoint
  - _Requirements: 1.1, 3.1_

- [x] 2. Fix Traditional Course Progress Calculation
  - [x] 2.1 Update getTraditionalCourseData() to calculate progress from Clocking model
    - Query Clocking table to count completed sessions (where clock_out IS NOT NULL)
    - Get total sessions from CourseAvailability.sessions
    - Calculate progress as (attended_sessions / total_sessions) × 100
    - _Requirements: 1.7, 5.1, 5.2_

  - [x] 2.2 Update deadline source for traditional courses
    - Use CourseAvailability.end_date as the deadline for traditional courses
    - Update calculateDaysOverdue() to use end_date for traditional courses
    - _Requirements: 1.5_

  - [ ]* 2.3 Write property test for traditional course progress
    - **Property 4c: Traditional course progress calculation**
    - **Validates: Requirements 1.7**

- [x] 3. Fix Days Overdue Calculation
  - [x] 3.1 Update calculateDaysOverdue() for traditional courses
    - Use CourseAvailability.end_date minus current date
    - Return null if no deadline or if completed
    - _Requirements: 1.5_

  - [x] 3.2 Update calculateDaysOverdue() for online courses
    - Use CourseOnline.deadline or CourseOnlineAssignment.deadline
    - Prioritize assignment deadline if set, otherwise use course deadline
    - _Requirements: 1.6_

  - [ ]* 3.3 Write property tests for days overdue
    - **Property 4: Days overdue for traditional courses**
    - **Property 4b: Days overdue for online courses**
    - **Validates: Requirements 1.5, 1.6**

- [x] 4. Fix Completion Status Display
  - [x] 4.1 Update determineCompletionStatus() to show "Not Started" instead of "Overdue"
    - Change status label from "Overdue" to "Not Started" for non-completed assignments
    - Keep "Completed", "In Progress" as is
    - _Requirements: 3.3_

  - [ ]* 4.2 Write property test for completion status
    - **Property 11: Non-completed sheet shows "Not Started"**
    - **Validates: Requirements 3.3**

- [x] 5. Fix Compliance Status for Completed Assignments
  - [x] 5.1 Update calculateComplianceStatus() to consider score band
    - For completed assignments with score band "Needs Attention" (score < 70), return "Non-Compliant"
    - For completed assignments with "Excellent" or "Good" score band, return "Compliant"
    - Keep existing logic for incomplete assignments (At Risk, Non-Compliant based on deadline)
    - _Requirements: 4.2, 4.3_

  - [ ]* 5.2 Write property tests for compliance status
    - **Property 13: Compliance for completed with good scores**
    - **Property 13b: Non-Compliant for completed with Needs Attention**
    - **Validates: Requirements 4.2, 4.3**

- [x] 6. Update Excel Export Service
  - [x] 6.1 Update Completed Courses sheet to include traditional courses
    - Ensure both traditional and online courses with status "completed" are included
    - Add "Start Course" column before "Completion Date"
    - Keep "Completion Date" column for completed sheet
    - _Requirements: 3.2, 3.4_

  - [x] 6.2 Update Non-Completed Courses sheet columns
    - Add "Start Course" column
    - Remove "Completion Date" column from non-completed sheet
    - Ensure completion status shows "Not Started" instead of "Overdue"
    - _Requirements: 3.3, 3.5_

  - [ ]* 6.3 Write property tests for export sheets
    - **Property 10: Completed sheet includes both course types**
    - **Property 11b: Non-completed sheet without Completion Date**
    - **Property 11c: Completed sheet with both Start Course and Completion Date**
    - **Validates: Requirements 3.2, 3.4, 3.5**

- [x] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Update Controller to Pass Correct Data
  - [x] 8.1 Update index() method
    - Ensure started_date is populated for both course types
    - For traditional: use responded_at from CourseAssignment
    - For online: use started_at from CourseOnlineAssignment
    - _Requirements: 1.2_

  - [x] 8.2 Update export() method
    - Pass started_date to Excel export service
    - Ensure compliance status uses updated logic with score band
    - _Requirements: 3.4, 3.5_

- [x] 9. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 10. Write integration tests
  - Test traditional course progress calculation from Clocking
  - Test days overdue calculation for both course types
  - Test compliance status with score band consideration
  - Test Excel export with correct columns for each sheet
  - _Integration testing requirement_

- [ ] 11. Manual Testing and Verification
  - [ ] 11.1 Verify traditional course progress shows correctly
    - Check that progress = (clocking sessions / total sessions) × 100
    - Verify days overdue uses CourseAvailability.end_date
    - _Requirements: 1.5, 1.7_

  - [ ] 11.2 Verify Excel export
    - Completed sheet has both traditional and online courses
    - Completed sheet has Start Course and Completion Date columns
    - Non-completed sheet has Start Course but NO Completion Date column
    - Non-completed shows "Not Started" instead of "Overdue"
    - _Requirements: 3.2, 3.3, 3.4, 3.5_

  - [ ] 11.3 Verify compliance status
    - Completed with "Needs Attention" shows "Non-Compliant"
    - Completed with "Excellent" or "Good" shows "Compliant"
    - _Requirements: 4.2, 4.3_
