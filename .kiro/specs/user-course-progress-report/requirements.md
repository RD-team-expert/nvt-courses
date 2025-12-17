# Requirements Document

## Introduction

This document specifies the requirements for a User Course Progress Report feature that allows administrators to view and export comprehensive data about user progress across all course types (traditional and online courses). The system SHALL provide filtering capabilities and Excel export functionality with separate sheets for completed and non-completed courses.

## Glossary

- **System**: The User Course Progress Report feature
- **Administrator**: A user with admin role who can access reports
- **User**: An employee enrolled in courses
- **Traditional Course**: A course managed through the Course model with CourseRegistration tracking and Clocking model for attendance
- **Online Course**: A course managed through the CourseOnline model with CourseOnlineAssignment tracking
- **Learning Score**: A calculated score (0-100) based on progress percentage, attention score, quiz performance, and completion rate
- **Score Band**: A categorical rating (Excellent/Good/Needs Attention) derived from the Learning Score
- **Compliance Status**: A status indicator (Compliant/At Risk/Non-Compliant) based on deadline adherence, progress, and score band
- **Traditional Course Deadline**: The end_date field from CourseAvailability model
- **Online Course Deadline**: The deadline field from CourseOnline model or CourseOnlineAssignment
- **Days Overdue**: The number of days past the course deadline (end_date for traditional, deadline for online)
- **Excel Export**: An Excel file (.xlsx) with multiple worksheets
- **Completion Status**: The current state of a course assignment (Completed/In Progress/Not Started)
- **Traditional Course Progress**: Calculated as (attended sessions / total sessions) × 100, where attended sessions come from Clocking model and total sessions from CourseAvailability.sessions

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to view a comprehensive report of all user course progress, so that I can monitor learning outcomes across the organization.

#### Acceptance Criteria

1. WHEN an administrator accesses the report page THEN the System SHALL display a paginated list of all course assignments for both traditional and online courses
2. WHEN displaying course assignments THEN the System SHALL show employee name, department, course type, course name, completion status, days overdue, progress percentage, start course date, completion date (for completed only), learning score, score band, and compliance status
3. WHEN calculating learning score THEN the System SHALL use the formula: (completion_rate × 0.25) + (progress_percentage × 0.25) + (attention_score × 0.25) + (quiz_score × 0.25) - suspicious_penalty
4. WHEN determining score band THEN the System SHALL classify scores as Excellent (≥85), Good (≥70 and <85), or Needs Attention (<70)
5. WHEN calculating days overdue for traditional courses THEN the System SHALL compute the difference between current date and CourseAvailability.end_date
6. WHEN calculating days overdue for online courses THEN the System SHALL compute the difference between current date and CourseOnline.deadline (or CourseOnlineAssignment.deadline if set)
7. WHEN calculating progress for traditional courses THEN the System SHALL compute (attended sessions from Clocking / total sessions from CourseAvailability.sessions) × 100

### Requirement 2

**User Story:** As an administrator, I want to filter the course progress report by multiple criteria, so that I can focus on specific segments of data.

#### Acceptance Criteria

1. WHEN the administrator selects a department filter THEN the System SHALL display only assignments for users in that department
2. WHEN the administrator selects a course type filter THEN the System SHALL display only assignments matching that course type (traditional or online)
3. WHEN the administrator selects a date range filter THEN the System SHALL display only assignments within the specified date range
4. WHEN the administrator selects a completion status filter THEN the System SHALL display only assignments matching that status
5. WHEN multiple filters are applied THEN the System SHALL apply all filters using AND logic

### Requirement 3

**User Story:** As an administrator, I want to export course progress data to Excel with separate sheets for completed and non-completed courses, so that I can perform offline analysis and share reports with stakeholders.

#### Acceptance Criteria

1. WHEN the administrator clicks the export button THEN the System SHALL generate an Excel file with two worksheets named "Completed Courses (KPI)" and "Non-Completed Courses (KPI)"
2. WHEN generating the completed courses sheet THEN the System SHALL include assignments with status "completed" for both traditional and online courses
3. WHEN generating the non-completed courses sheet THEN the System SHALL include assignments with status "in_progress", "assigned", or "not_started" and display "Not Started" instead of "Overdue" for completion status
4. WHEN populating the completed courses worksheet THEN the System SHALL include: Employee Name, Department, Course type, Course Name, Completion Status, DaysOverdue, progress%, Start Course, Completion Date, Overall Learning Score (0-100), Score Band, and Compliance Status
5. WHEN populating the non-completed courses worksheet THEN the System SHALL include: Employee Name, Department, Course type, Course Name, Completion Status, DaysOverdue, progress%, Start Course, Overall Learning Score (0-100), Score Band, and Compliance Status (without Completion Date column)
6. WHEN the export is complete THEN the System SHALL download the file with a timestamped filename in format "user_course_progress_YYYY-MM-DD_HH-mm-ss.xlsx"

### Requirement 4

**User Story:** As an administrator, I want the system to calculate compliance status based on deadlines, progress, and score band, so that I can identify at-risk learners.

#### Acceptance Criteria

1. WHEN an assignment has no deadline THEN the System SHALL set compliance status to "Compliant"
2. WHEN an assignment is completed and score band is "Excellent" or "Good" THEN the System SHALL set compliance status to "Compliant"
3. WHEN an assignment is completed and score band is "Needs Attention" THEN the System SHALL set compliance status to "Non-Compliant"
4. WHEN an assignment is incomplete and within 7 days of deadline THEN the System SHALL set compliance status to "At Risk"
5. WHEN an assignment is incomplete and past the deadline THEN the System SHALL set compliance status to "Non-Compliant"
6. WHEN calculating compliance status THEN the System SHALL consider deadline, progress percentage, and score band for completed assignments

### Requirement 5

**User Story:** As an administrator, I want the system to aggregate data from both traditional and online courses, so that I have a unified view of all learning activities.

#### Acceptance Criteria

1. WHEN retrieving traditional course data THEN the System SHALL query CourseAssignment, CourseAvailability, and Clocking tables
2. WHEN calculating traditional course progress THEN the System SHALL count completed clocking sessions (clock_in and clock_out) divided by CourseAvailability.sessions
3. WHEN retrieving online course data THEN the System SHALL query CourseOnlineAssignment, LearningSession, and UserContentProgress tables
4. WHEN calculating attention scores for online courses THEN the System SHALL use the simulated attention calculation from CourseOnlineReportController
5. WHEN calculating quiz scores THEN the System SHALL aggregate both regular quiz attempts and module quiz results
6. WHEN displaying course type THEN the System SHALL clearly indicate whether each assignment is "traditional" or "online"
7. WHEN including traditional courses in completed report THEN the System SHALL include all traditional course assignments with status "completed"

### Requirement 6

**User Story:** As an administrator, I want the report to handle missing or incomplete data gracefully, so that the system remains functional even with data gaps.

#### Acceptance Criteria

1. WHEN a user has no department assigned THEN the System SHALL display "N/A" for the department field
2. WHEN an assignment has no deadline THEN the System SHALL display "N/A" for days overdue
3. WHEN a user has no quiz attempts THEN the System SHALL use 0 for quiz score component
4. WHEN a user has no learning sessions THEN the System SHALL use 65 as default attention score
5. WHEN calculating learning score with missing components THEN the System SHALL use 0 for missing values and continue calculation

### Requirement 7

**User Story:** As an administrator, I want the Excel export to maintain proper formatting and data types, so that the exported data is immediately usable in spreadsheet applications.

#### Acceptance Criteria

1. WHEN exporting numeric values THEN the System SHALL format them as numbers (not text)
2. WHEN exporting dates THEN the System SHALL format them in "MM/DD/YYYY" format
3. WHEN exporting percentage values THEN the System SHALL include the "%" symbol
4. WHEN generating the Excel file THEN the System SHALL include column headers in the first row of each sheet
5. WHEN generating the Excel file THEN the System SHALL apply UTF-8 encoding to handle special characters correctly
