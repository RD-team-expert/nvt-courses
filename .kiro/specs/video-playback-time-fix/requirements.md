# Requirements Document

## Introduction

This document specifies requirements for fixing video playback time tracking issues in the content viewer. Currently, the time counter continues incrementing even when the video is paused or loading, which provides inaccurate time tracking. Additionally, there is no systematic way to test frontend functionality to ensure all features are working correctly.

## Glossary

- **Content Viewer**: The Vue component that displays video and PDF content to users
- **Session Elapsed Time**: The total time a user has spent viewing content since the session started
- **Active Playback Time**: The time the video is actually playing (not paused, not buffering, not loading)
- **Time Spent Display**: The UI element showing how long the user has been viewing the content
- **Frontend Testing**: Automated or manual verification that UI components work as expected

## Requirements

### Requirement 1

**User Story:** As a user viewing a video, I want the time counter to only increment when the video is actually playing, so that my viewing time is accurately tracked.

#### Acceptance Criteria

1. WHEN the video is paused THEN the system SHALL stop incrementing the session elapsed time counter
2. WHEN the video is loading or buffering THEN the system SHALL stop incrementing the session elapsed time counter
3. WHEN the video is playing THEN the system SHALL increment the session elapsed time counter at one-second intervals
4. WHEN the video completes THEN the system SHALL stop incrementing the session elapsed time counter
5. WHEN the user switches tabs or minimizes the browser THEN the system SHALL stop incrementing the session elapsed time counter

### Requirement 2

**User Story:** As a developer, I want to verify that the time tracking logic works correctly, so that I can ensure accurate data collection.

#### Acceptance Criteria

1. WHEN the video state changes THEN the system SHALL log the state change to the browser console for debugging
2. WHEN the session elapsed time updates THEN the system SHALL display the current playback state alongside the time
3. WHEN testing the application THEN the developer SHALL be able to see real-time tracking metrics in the UI

### Requirement 3

**User Story:** As a developer, I want to test frontend functionality systematically, so that I can verify all features work correctly before deployment.

#### Acceptance Criteria

1. WHEN the content viewer loads THEN the system SHALL display all required UI elements (video player, controls, progress bar, navigation)
2. WHEN the user interacts with video controls THEN the system SHALL respond appropriately to play, pause, seek, and volume changes
3. WHEN the video reaches completion THEN the system SHALL display the completion indicator and enable navigation to the next content
4. WHEN network errors occur THEN the system SHALL display appropriate error messages to the user
5. WHEN the page loads THEN the system SHALL initialize the session and display the session ID in the debug panel
