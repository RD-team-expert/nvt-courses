# Requirements Document

## Introduction

This feature integrates the school project's video upload system with an external VPS-based video transcoding service. When administrators upload videos, the system will automatically send them to the VPS for transcoding into multiple quality levels (720p, 480p, 360p). The VPS service handles the heavy processing work using FFmpeg, then notifies the school project when transcoding is complete. This allows users to select video quality based on their internet connection speed.

## Glossary

- **VPS_Transcoding_Service**: The external Laravel-based video transcoding service running on a Virtual Private Server that converts videos to multiple quality levels using FFmpeg
- **School_Project**: The main Laravel application (this project) where administrators upload videos and users watch course content
- **Transcoding**: The process of converting a video file from one resolution/quality to another
- **Callback_URL**: A webhook endpoint that the VPS calls to notify the School_Project when transcoding is complete
- **Project_Key**: A unique identifier for the School_Project in the VPS system to prevent video ID conflicts between different client projects
- **API_Key**: A 64-character authentication token used to secure communication between School_Project and VPS_Transcoding_Service
- **Quality_Variant**: A specific resolution version of a video (e.g., 720p, 480p, 360p)

## Requirements

### Requirement 1

**User Story:** As an administrator, I want uploaded videos to be automatically sent for transcoding, so that users can watch videos in different quality levels.

#### Acceptance Criteria

1. WHEN an administrator uploads a new video THEN the School_Project SHALL send a transcoding request to the VPS_Transcoding_Service containing the video_id, video_url, callback_url, and requested qualities
2. WHEN the transcoding request is sent THEN the School_Project SHALL update the video's transcode_status to 'processing'
3. IF the VPS_Transcoding_Service is unreachable THEN the School_Project SHALL log the error and set transcode_status to 'failed'
4. WHEN sending the transcoding request THEN the School_Project SHALL include the X-API-Key header for authentication
5. WHEN the VPS_Transcoding_Service returns a successful response THEN the School_Project SHALL store the job reference for status tracking

### Requirement 2

**User Story:** As a system, I want to receive transcoding completion notifications from the VPS, so that I can download and store the transcoded video files.

#### Acceptance Criteria

1. WHEN the VPS_Transcoding_Service completes transcoding THEN the School_Project SHALL receive a webhook callback with download URLs for each quality variant
2. WHEN the callback is received THEN the School_Project SHALL verify the project_key matches the configured value
3. WHEN download URLs are received THEN the School_Project SHALL download each quality variant file from the VPS
4. WHEN all quality variants are downloaded THEN the School_Project SHALL update the video's transcode_status to 'completed'
5. IF the callback indicates transcoding failed THEN the School_Project SHALL update the video's transcode_status to 'failed' and log the error message

### Requirement 3

**User Story:** As an administrator, I want to see the transcoding status of videos, so that I know when videos are ready for users to watch in multiple qualities.

#### Acceptance Criteria

1. WHEN displaying the video list THEN the School_Project SHALL show the current transcode_status for each video (pending, processing, completed, failed)
2. WHEN a video's transcode_status is 'processing' THEN the School_Project SHALL display a progress indicator
3. WHEN a video's transcode_status is 'completed' THEN the School_Project SHALL display the available quality variants
4. WHEN a video's transcode_status is 'failed' THEN the School_Project SHALL display an error message and provide a retry option

### Requirement 4

**User Story:** As a user watching course videos, I want to select video quality, so that I can choose a lower quality when my internet connection is slow.

#### Acceptance Criteria

1. WHEN a user opens a video with multiple quality variants THEN the School_Project SHALL display a quality selector showing available options (720p, 480p, 360p)
2. WHEN a user selects a specific quality level THEN the School_Project SHALL switch to streaming the selected quality variant
3. WHEN a user changes quality preference THEN the School_Project SHALL store the preference in browser local storage
4. WHEN a user starts watching any video THEN the School_Project SHALL apply their stored quality preference if available
5. IF only the original quality is available THEN the School_Project SHALL hide the quality selector

### Requirement 5

**User Story:** As a system administrator, I want the VPS communication to be secure, so that unauthorized parties cannot access the transcoding service.

#### Acceptance Criteria

1. WHEN communicating with the VPS_Transcoding_Service THEN the School_Project SHALL include the API_Key in the X-API-Key header
2. WHEN receiving callback requests THEN the School_Project SHALL verify the project_key matches the configured value
3. WHEN downloading transcoded files THEN the School_Project SHALL include the API_Key for authentication
4. WHEN storing VPS credentials THEN the School_Project SHALL use environment variables (TRANSCODING_URL, TRANSCODING_API_KEY, TRANSCODING_PROJECT_KEY)

### Requirement 6

**User Story:** As a system administrator, I want to manage video storage efficiently, so that transcoded videos are organized properly.

#### Acceptance Criteria

1. WHEN storing transcoded video files THEN the School_Project SHALL organize them in the directory structure: videos/transcoded/{video_id}/{quality}.mp4
2. WHEN a video is deleted THEN the School_Project SHALL remove all associated quality variant files
3. WHEN displaying video storage information THEN the School_Project SHALL show total storage used across all quality variants

### Requirement 7

**User Story:** As an administrator, I want to retry failed transcoding jobs, so that temporary failures can be recovered.

#### Acceptance Criteria

1. WHEN a video's transcode_status is 'failed' THEN the School_Project SHALL provide a "Retry Transcoding" button
2. WHEN the retry button is clicked THEN the School_Project SHALL send a new transcoding request to the VPS_Transcoding_Service
3. WHEN retrying transcoding THEN the School_Project SHALL reset the transcode_status to 'pending' before sending the request

