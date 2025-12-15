# Requirements Document

## Introduction

This feature fixes and completes the time limit functionality for quizzes. The backend and admin interface already support setting time limits, but the user-facing quiz pages do not display the countdown timer or enforce the time limit. This spec will implement the missing user-side functionality to show a countdown timer during quiz attempts and auto-submit when time expires.

## Glossary

- **Quiz System**: The application component that manages quiz creation, assignment, and completion
- **Time Limit**: A duration in minutes that restricts how long a user has to complete a quiz attempt
- **Auto-Submit**: The automatic submission of a quiz when the time limit expires
- **Quiz Attempt**: A single instance of a user taking a quiz
- **Admin Interface**: The administrative pages where quizzes are created and managed
- **User Interface**: The pages where users view and take quizzes

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to verify that time limit settings are properly displayed across all admin pages, so that I can manage timed quizzes effectively.

#### Acceptance Criteria

1. WHEN an administrator views the quiz index page THEN the system SHALL display time limit information for each quiz
2. WHEN an administrator views quiz details THEN the system SHALL show the time limit if one is set
3. WHEN an administrator edits a quiz THEN the system SHALL display the current time limit value in the form
4. WHEN an administrator creates a quiz THEN the system SHALL provide an input field for time limit in minutes
5. WHEN an administrator saves a quiz with a time limit THEN the system SHALL validate and store the time_limit_minutes value

### Requirement 2

**User Story:** As a user, I want to see how much time I have remaining during a quiz, so that I can pace myself appropriately.

#### Acceptance Criteria

1. WHEN a user starts a quiz with a time limit THEN the system SHALL display a countdown timer
2. WHEN the countdown timer is running THEN the system SHALL update the display every second
3. WHEN the time remaining is less than 5 minutes THEN the system SHALL display the timer in a warning color
4. WHEN the time remaining is less than 1 minute THEN the system SHALL display the timer in an urgent color
5. WHEN the timer reaches zero THEN the system SHALL automatically submit the quiz

### Requirement 3

**User Story:** As a user, I want the quiz to auto-submit when time expires, so that I don't lose my progress if I forget to submit manually.

#### Acceptance Criteria

1. WHEN a quiz time limit expires THEN the system SHALL automatically submit the user's current answers
2. WHEN a quiz is auto-submitted THEN the system SHALL save all answered questions
3. WHEN a quiz is auto-submitted THEN the system SHALL calculate the score based on submitted answers
4. WHEN a quiz is auto-submitted THEN the system SHALL mark the attempt as completed
5. WHEN a quiz is auto-submitted THEN the system SHALL display a notification that time expired and the quiz was submitted

### Requirement 4

**User Story:** As a user, I want to be prevented from continuing a quiz after the time limit expires, so that the assessment is fair and consistent.

#### Acceptance Criteria

1. WHEN a quiz time limit expires THEN the system SHALL disable all answer inputs
2. WHEN a quiz time limit expires THEN the system SHALL prevent the user from changing any answers
3. WHEN a quiz time limit expires THEN the system SHALL automatically trigger the submit action
4. WHEN a user attempts to submit after time expires THEN the system SHALL accept the submission with current answers
5. WHEN a quiz is auto-submitted due to time expiry THEN the system SHALL record the submission time accurately

### Requirement 5

**User Story:** As a user, I want to see the time limit before starting a quiz, so that I can prepare appropriately.

#### Acceptance Criteria

1. WHEN a user views a quiz details page THEN the system SHALL display the time limit if one is set
2. WHEN a user is about to start a quiz attempt THEN the system SHALL show a confirmation with the time limit
3. WHEN a quiz has a time limit THEN the system SHALL display an estimated completion time
4. WHEN a user views their quiz history THEN the system SHALL show how much time they used for each attempt
5. WHEN a user views available quizzes THEN the system SHALL indicate which quizzes have time limits
