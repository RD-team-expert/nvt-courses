# Implementation Plan

- [x] 1. Set up project structure and core interfaces



  - Create UserCourseProgressReportController in app/Http/Controllers/Admin
  - Create CourseProgressService in app/Services
  - Create LearningScoreCalculator in app/Services
  - Create ExcelExportService in app/Services (or extend existing)
  - Add routes for report page and export endpoint
  - _Requirements: 1.1, 3.1_






- [ ] 2. Implement LearningScoreCalculator service
  - [ ] 2.1 Implement learning score calculation method
    - Create calculate() method with weighted formula: (completion_rate × 0.25) + (progress × 0.25) + (attention × 0.25) + (quiz × 0.25) - suspicious_penalty
    - Ensure score is clamped to 0-100 range
    - Handle missing or null input values with defaults
    - _Requirements: 1.3_



  - [ ]* 2.2 Write property test for learning score formula
    - **Property 2: Learning score calculation formula**
    - **Validates: Requirements 1.3**

  - [ ] 2.3 Implement attention score retrieval
    - Create getAttentionScore() method
    - Reuse simulated attention calculation from CourseOnlineReportController
    - Return 65 as default for users with no learning sessions


    - _Requirements: 5.3, 6.4_

  - [ ]* 2.4 Write property test for attention score consistency
    - **Property 17: Attention score calculation consistency**
    - **Validates: Requirements 5.3**

  - [ ] 2.5 Implement quiz score aggregation
    - Create getQuizScore() method





    - Aggregate regular quiz attempts from quiz_attempts table
    - Aggregate module quiz results from module_quiz_results table
    - Calculate weighted average of both quiz types
    - Return 0 for users with no quiz attempts
    - _Requirements: 5.4, 6.3_


  - [ ]* 2.6 Write property test for quiz score aggregation
    - **Property 18: Quiz score aggregation**
    - **Validates: Requirements 5.4**

- [ ] 3. Implement CourseProgressService for data aggregation
  - [ ] 3.1 Implement traditional course data retrieval
    - Query CourseAssignment and CourseRegistration tables
    - Join with User and Department tables
    - Map to standardized progress data structure
    - Set course_type to "traditional"
    - _Requirements: 5.1, 5.5_


  - [ ] 3.2 Implement online course data retrieval
    - Query CourseOnlineAssignment table
    - Join with User, Department, and CourseOnline tables
    - Retrieve learning session data for attention scores
    - Retrieve user content progress data
    - Map to standardized progress data structure
    - Set course_type to "online"
    - _Requirements: 5.2, 5.5_

  - [ ]* 3.3 Write property test for course type identification
    - **Property 19: Course type identification**
    - **Validates: Requirements 5.5**

  - [ ] 3.4 Implement getProgressData() method
    - Combine traditional and online course data using UNION
    - Apply filters (department, course_type, date_range, status, user, course)


    - Return paginated collection of progress records
    - _Requirements: 1.1, 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ]* 3.5 Write property tests for filter logic
    - **Property 5: Department filter**
    - **Property 6: Course type filter**
    - **Property 7: Date range filter**
    - **Property 8: Status filter**
    - **Property 9: Multiple filters AND logic**


    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

  - [ ] 3.6 Implement score band determination
    - Create determineScoreBand() method
    - Return "Excellent" for scores ≥ 85
    - Return "Good" for scores ≥ 70 and < 85
    - Return "Needs Attention" for scores < 70
    - _Requirements: 1.4_

  - [ ]* 3.7 Write property test for score band classification
    - **Property 3: Score band classification**
    - **Validates: Requirements 1.4**

  - [ ] 3.8 Implement compliance status calculation
    - Create calculateComplianceStatus() method


    - Return "Compliant" for completed assignments or no deadline



    - Return "At Risk" for incomplete assignments 1-7 days before deadline
    - Return "Non-Compliant" for incomplete assignments past deadline
    - Consider both deadline and progress percentage
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 3.9 Write property tests for compliance status
    - **Property 13: Compliance status for completed assignments**
    - **Property 14: At Risk compliance status**
    - **Property 15: Non-Compliant status for overdue**
    - **Property 16: Compliance considers deadline and progress**
    - **Validates: Requirements 4.2, 4.3, 4.4, 4.5**


  - [ ] 3.10 Implement days overdue calculation
    - Create calculateDaysOverdue() method
    - Calculate difference between current date and deadline
    - Return null for assignments with no deadline
    - Return null for completed assignments
    - _Requirements: 1.5, 6.2_



  - [ ]* 3.11 Write property test for days overdue calculation
    - **Property 4: Days overdue calculation**





    - **Validates: Requirements 1.5**

- [ ] 4. Implement UserCourseProgressReportController
  - [ ] 4.1 Implement index() method
    - Validate and sanitize filter inputs
    - Call CourseProgressService to get progress data
    - Calculate learning scores for each assignment using LearningScoreCalculator
    - Determine score bands and compliance status
    - Format data for display
    - Get filter options (departments, courses, users)


    - Return Inertia response with paginated data
    - _Requirements: 1.1, 1.2_

  - [ ]* 4.2 Write property test for display fields
    - **Property 1: Display contains all required fields**
    - **Validates: Requirements 1.2**


  - [ ] 4.3 Implement error handling for data retrieval
    - Handle database query failures gracefully
    - Use default values for missing relationships (department = "N/A")
    - Log errors for debugging
    - Return empty result set with error message on failure
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ] 4.4 Add middleware for admin-only access
    - Ensure only users with admin role can access the report
    - Return 403 Forbidden for non-admin users
    - _Security requirement_

- [ ] 5. Implement Excel export functionality
  - [ ] 5.1 Implement export() method in controller
    - Validate and sanitize filter inputs
    - Retrieve all matching progress data (no pagination)
    - Separate data into completed and non-completed collections
    - Call ExcelExportService to generate file
    - Return binary file response with timestamped filename
    - _Requirements: 3.1, 3.5_


  - [ ]* 5.2 Write property test for filename format
    - **Property 12: Export filename format**
    - **Validates: Requirements 3.5**






  - [ ] 5.3 Implement exportCourseProgress() in ExcelExportService
    - Create new Spreadsheet instance
    - Create "Completed Courses (KPI)" worksheet
    - Create "Non-Completed Courses (KPI)" worksheet
    - Call formatWorksheet() for each sheet

    - Save and return file
    - _Requirements: 3.1_

  - [ ] 5.4 Implement formatWorksheet() method
    - Add column headers in first row: Employee Name, Department, Course type, Course Name, Completion Status, DaysOverdue, progress%, Completion Date, Overall Learning Score (0-100), Score Band, Compliance Status
    - Populate data rows

    - Format numeric values as numbers (not text)
    - Format dates as "MM/DD/YYYY"
    - Format percentages with "%" symbol
    - Apply UTF-8 encoding
    - Auto-size columns for readability
    - _Requirements: 3.4, 7.1, 7.2, 7.3, 7.4, 7.5_


  - [ ]* 5.5 Write property tests for export data segregation
    - **Property 10: Completed sheet segregation**
    - **Property 11: Non-completed sheet segregation**




    - **Validates: Requirements 3.2, 3.3**

  - [ ]* 5.6 Write property tests for export formatting
    - **Property 20: Numeric export formatting**
    - **Property 21: Date export formatting**
    - **Property 22: Percentage export formatting**
    - **Property 23: UTF-8 encoding in export**

    - **Validates: Requirements 7.1, 7.2, 7.3, 7.5**

  - [ ] 5.7 Implement error handling for export
    - Handle Excel generation failures
    - Return 500 error with user-friendly message

    - Generate empty Excel with headers if no data matches filters
    - Log export operations for audit trail
    - _Error handling requirement_

- [x] 6. Create Inertia page component for UI


  - [ ] 6.1 Create UserCourseProgressReport.tsx/vue component
    - Display paginated table with all required columns
    - Implement filter controls (department, course type, date range, status, user, course)
    - Add export button
    - Show loading states during data fetch
    - Display error messages when data retrieval fails
    - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 2.4, 3.1_

  - [ ] 6.2 Implement filter interactions
    - Connect filter controls to backend API
    - Apply filters on change
    - Clear filters functionality
    - Persist filters in URL query parameters
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ] 6.3 Implement export button functionality
    - Trigger export endpoint with current filters
    - Show loading indicator during export
    - Handle download response
    - Display success/error notifications
    - _Requirements: 3.1_

  - [ ] 6.4 Style the report page
    - Match existing admin report page styling
    - Ensure responsive design for mobile/tablet
    - Add tooltips for score bands and compliance status
    - Highlight overdue assignments
    - _UI/UX requirement_

- [ ] 7. Add database optimizations
  - [ ] 7.1 Add indexes for performance
    - Add index on course_assignments(user_id, status)
    - Add index on course_online_assignments(user_id, status)
    - Add index on users(department_id)
    - Add index on course_assignments(assigned_at)
    - Add index on course_online_assignments(assigned_at)
    - _Performance requirement_

  - [ ] 7.2 Implement eager loading
    - Eager load user, department, course relationships
    - Eager load learning sessions for online courses
    - Eager load quiz attempts and module quiz results
    - _Performance requirement_

  - [ ] 7.3 Implement caching strategy
    - Cache learning scores for 15 minutes
    - Cache attention scores for 30 minutes
    - Cache quiz scores for 1 hour
    - Cache filter options for 1 hour
    - Invalidate caches on data changes
    - _Performance requirement_

- [ ] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Add validation and security measures
  - [ ] 9.1 Implement input validation
    - Validate filter inputs (dates, IDs, status values)
    - Ensure date_from <= date_to
    - Ensure numeric IDs are positive integers
    - Return validation errors with clear messages
    - _Validation requirement_

  - [ ] 9.2 Implement security measures
    - Add rate limiting on export endpoint (max 10 per hour per user)
    - Prevent SQL injection through parameterized queries
    - Limit export file size to prevent DoS
    - Sanitize all user inputs
    - _Security requirement_

  - [ ] 9.3 Add audit logging
    - Log all export operations with user ID and timestamp
    - Log filter usage for analytics
    - Log errors for debugging
    - _Audit requirement_

- [ ]* 10. Write integration tests
  - Test end-to-end data flow from database to report display
  - Test filter combinations work correctly together
  - Test export generates valid Excel files
  - Test data consistency between traditional and online courses
  - Test performance with large datasets (1000+ assignments)
  - _Integration testing requirement_

- [ ]* 11. Write unit tests for edge cases
  - Test handling of users with no department (should show "N/A")
  - Test handling of assignments with no deadline (should show "N/A" for days overdue)
  - Test handling of users with no quiz attempts (should use 0 for quiz score)
  - Test handling of users with no learning sessions (should use 65 for attention score)
  - Test learning score calculation with missing components (should use 0 and continue)
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 12. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 13. Perform manual testing
  - Test UI/UX of the report page
  - Test filter interactions and responsiveness
  - Test Excel file opens correctly in Microsoft Excel and Google Sheets
  - Verify data accuracy by comparing with source tables
  - Test edge cases (users with no courses, courses with no users)
  - _Manual testing requirement_

- [ ] 14. Documentation and deployment
  - [ ] 14.1 Update API documentation
    - Document new endpoints (index, export)
    - Document filter parameters
    - Document response formats
    - _Documentation requirement_

  - [ ] 14.2 Create user guide
    - Document how to use filters
    - Document how to interpret learning scores and score bands
    - Document how to export data
    - Document compliance status meanings
    - _Documentation requirement_

  - [ ] 14.3 Deploy to staging
    - Run migrations if needed
    - Deploy code to staging environment
    - Test in staging with production-like data
    - _Deployment requirement_

  - [ ] 14.4 Deploy to production
    - Deploy code to production
    - Monitor for errors
    - Verify functionality with real users
    - _Deployment requirement_
