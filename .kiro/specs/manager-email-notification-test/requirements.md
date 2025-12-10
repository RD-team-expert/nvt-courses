# Requirements Document

## Introduction

This feature addresses a critical issue in the course assignment system where managers are not receiving email notifications when their team members are assigned to online courses. The system should verify that all necessary data relationships exist (users have departments, managers are properly assigned) and that email notifications are successfully sent to managers when courses are assigned to their team members.

## Glossary

- **System**: The Laravel-based learning management system
- **Manager**: A user with management responsibilities (typically L2 level) assigned to oversee team members through the UserDepartmentRole table
- **Team Member**: A user (typically L1 level) who reports to a manager
- **Course Assignment**: The process of assigning an online course to one or more users
- **Manager Notification**: An email sent to a manager informing them that their team member(s) have been assigned to a course
- **UserDepartmentRole**: The database table that defines manager-employee relationships within departments
- **ManagerHierarchyService**: The service class responsible for retrieving manager relationships

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want to verify that users have proper department assignments, so that the manager notification system can function correctly.

#### Acceptance Criteria

1. WHEN the system checks user data THEN the System SHALL identify all users without department assignments
2. WHEN a user is assigned to a course THEN the System SHALL verify the user has a valid department_id before attempting manager lookup
3. WHEN generating a test report THEN the System SHALL list all users missing department assignments with their IDs and names

### Requirement 2

**User Story:** As a system administrator, I want to verify that managers are properly assigned to team members, so that notifications reach the correct recipients.

#### Acceptance Criteria

1. WHEN the system checks manager assignments THEN the System SHALL identify all users without assigned managers in the UserDepartmentRole table
2. WHEN looking up managers THEN the System SHALL query UserDepartmentRole for active manager relationships where is_primary is true
3. WHEN generating a test report THEN the System SHALL list all users without managers including their department information

### Requirement 3

**User Story:** As a system administrator, I want to test the ManagerHierarchyService, so that I can verify it correctly retrieves manager relationships.

#### Acceptance Criteria

1. WHEN the ManagerHierarchyService retrieves managers for a user THEN the System SHALL return all active primary managers from the user's department
2. WHEN a user has no department THEN the System SHALL return an empty array of managers
3. WHEN a user has a department but no assigned managers THEN the System SHALL return an empty array of managers
4. WHEN generating a test report THEN the System SHALL show the manager lookup results for each user including manager names and email addresses

### Requirement 4

**User Story:** As a system administrator, I want to test the email notification flow, so that I can verify managers receive course assignment notifications.

#### Acceptance Criteria

1. WHEN a course is assigned to users THEN the System SHALL send manager notifications to all identified managers
2. WHEN the CourseOnlineAssignmentManagerNotification is created THEN the System SHALL include the course details, team member list, and manager information
3. WHEN generating a test report THEN the System SHALL log which managers should receive notifications and any errors encountered
4. WHEN testing email delivery THEN the System SHALL verify the Mail facade is called with correct manager email addresses

### Requirement 5

**User Story:** As a system administrator, I want a comprehensive diagnostic test, so that I can identify exactly why managers are not receiving emails.

#### Acceptance Criteria

1. WHEN the diagnostic test runs THEN the System SHALL check all data prerequisites including user departments and manager assignments
2. WHEN the diagnostic test runs THEN the System SHALL simulate the course assignment flow without sending actual emails
3. WHEN the diagnostic test completes THEN the System SHALL generate a detailed report showing success and failure counts
4. WHEN issues are found THEN the System SHALL provide actionable recommendations for fixing the data
5. WHEN the test completes THEN the System SHALL output results in a readable format showing which users would receive notifications and which would not
