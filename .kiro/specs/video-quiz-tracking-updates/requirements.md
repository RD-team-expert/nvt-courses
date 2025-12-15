# Requirements Document

## Introduction

This document specifies the requirements for updating the video and quiz tracking system. The updates focus on improving session duration accuracy by measuring only active playback time (excluding loading/buffering), implementing a flexible allowed time window (2x video duration), enabling progress persistence for resume functionality, and enforcing quiz attempt rules with configurable time limits. The goal is to provide fair and accurate tracking that doesn't penalize users for normal viewing behaviors like pausing, rewinding, or resuming.

## Glossary

- **Active Playback Time**: The actual time during which video content is actively playing, excluding loading, buffering, and pause periods
- **Allowed Time Window**: The maximum time permitted for completing a video, calculated as Video Duration × 2
- **Session Duration**: The total time tracked for a learning session
- **Buffering**: The period when video playback is paused while waiting for data to load
- **Loading Time**: The initial period when video data is being fetched before playback can begin
- **Progress Persistence**: The system's ability to save and restore a user's viewing position
- **Attention Score**: A metric (0-100) indicating user engagement during content viewing
- **Quiz Attempt**: A single instance of a user taking a quiz
- **Time Limit**: The maximum duration allowed for completing a quiz
- **Auto-Submit**: Automatic submission of quiz answers when time expires

## Requirements

### Requirement 1: Active Playback Time Tracking

**User Story:** As a user, I want my session duration to only count actual video playback time, so that loading delays and buffering don't unfairly affect my learning metrics.

#### Acceptance Criteria

1. WHEN the video is loading or buffering THEN the System SHALL pause the active playback time counter
2. WHEN the video resumes playing after buffering THEN the System SHALL resume the active playback time counter
3. WHEN the user pauses the video THEN the System SHALL pause the active playback time counter
4. WHEN the user resumes the video after pausing THEN the System SHALL resume the active playback time counter
5. WHEN calculating session duration THEN the System SHALL use only the accumulated active playback time

### Requirement 2: Extended Allowed Time Window

**User Story:** As a user, I want sufficient time to watch videos at my own pace, so that I can pause, rewind, and review content without being flagged for taking too long.

#### Acceptance Criteria

1. WHEN a video is loaded THEN the System SHALL calculate the allowed time as Video Duration × 2
2. WHEN displaying the allowed time THEN the System SHALL show a static label near the video player stating "You are expected to complete this video within {calculated_time} minutes"
3. WHEN the user's active playback time is within the allowed time window THEN the System SHALL NOT flag the session as "too long"
4. WHEN the user pauses or rewinds within the allowed time window THEN the System SHALL NOT negatively impact the attention score
5. WHEN calculating remaining allowed time for a resumed session THEN the System SHALL compute (Video Duration × 2) − (Active playback time already used)

### Requirement 3: Pause, Rewind, and Resume Behavior

**User Story:** As a user, I want to freely pause and rewind videos without penalty, so that I can learn at my own pace and review difficult sections.

#### Acceptance Criteria

1. WHEN the user pauses the video THEN the System SHALL save the current timestamp position
2. WHEN the user rewinds the video THEN the System SHALL NOT count the rewound section as additional active playback time
3. WHEN the user logs out during video playback THEN the System SHALL persist the current timestamp position to the database
4. WHEN the user returns to a partially watched video THEN the System SHALL resume playback from the saved timestamp position
5. WHEN the user replays a section of video THEN the System SHALL NOT count replayed sections toward the allowed time limit

### Requirement 4: Post-Completion Behavior

**User Story:** As a user, I want to re-watch completed videos for review without affecting my progress metrics, so that I can refresh my knowledge freely.

#### Acceptance Criteria

1. WHEN a video is marked as completed THEN the System SHALL stop tracking active playback time for that video
2. WHEN a user re-watches a completed video THEN the System SHALL NOT update the progress percentage
3. WHEN a user re-watches a completed video THEN the System SHALL NOT modify the attention score
4. WHEN a user re-watches a completed video THEN the System SHALL NOT apply session time limits
5. WHEN displaying a completed video THEN the System SHALL indicate that re-watching is optional and untracked

### Requirement 5: Quiz Attempt Rules (Admin Configurable)

**User Story:** As an administrator, I want to configure quiz attempt limits and time limits, so that I can control how users take quizzes based on course requirements.

#### Acceptance Criteria

1. WHEN configuring a quiz THEN the System SHALL allow administrators to set the maximum number of attempts allowed per user
2. WHEN configuring a quiz THEN the System SHALL allow administrators to set a fixed time limit in minutes
3. WHEN a user has used all allowed attempts THEN the System SHALL block further quiz attempts for that user
4. WHEN a quiz has a time limit configured THEN the System SHALL display a countdown timer during the attempt
5. WHEN the quiz time limit expires THEN the System SHALL auto-submit the quiz with current answers
6. WHEN the quiz time limit expires THEN the System SHALL block further answer modifications
7. WHEN displaying quiz information THEN the System SHALL show the user their remaining attempts and the time limit

### Requirement 6: Reporting and Logging for Videos

**User Story:** As an administrator, I want comprehensive video session logs, so that I can analyze user engagement and identify potential issues.

#### Acceptance Criteria

1. WHEN a video session occurs THEN the System SHALL log the active playback time separately from total session time
2. WHEN a user pauses the video THEN the System SHALL log the pause event with timestamp
3. WHEN a user rewinds the video THEN the System SHALL log the rewind event with start and end positions
4. WHEN a user resumes the video THEN the System SHALL log the resume event with timestamp
5. WHEN a session ends THEN the System SHALL log whether the user stayed within the allowed time frame (Video Duration × 2)

### Requirement 7: Reporting and Logging for Quizzes

**User Story:** As an administrator, I want detailed quiz attempt logs, so that I can review user performance and ensure fair assessment.

#### Acceptance Criteria

1. WHEN a quiz attempt starts THEN the System SHALL log the start time
2. WHEN a quiz attempt ends THEN the System SHALL log the end time
3. WHEN a quiz attempt is submitted THEN the System SHALL log the total duration (end time - start time)
4. WHEN a quiz is graded THEN the System SHALL log the final score
5. WHEN a quiz is auto-submitted due to time expiry THEN the System SHALL log the auto-submit event

### Requirement 8: Session Score Calculation Update

**User Story:** As an administrator, I want the attention score calculation to account for the new allowed time window, so that users are fairly evaluated based on the updated rules.

#### Acceptance Criteria

1. WHEN calculating attention score THEN the System SHALL use active playback time instead of total session time
2. WHEN the active playback time is within (Video Duration × 2) THEN the System SHALL NOT apply "session too long" penalties
3. WHEN the user pauses or rewinds within allowed limits THEN the System SHALL NOT reduce the attention score
4. WHEN the active playback time exceeds (Video Duration × 2) THEN the System SHALL apply appropriate penalties to the attention score
5. WHEN generating reports THEN the System SHALL display both active playback time and total session time for transparency
