# Requirements Document

## Introduction

This document outlines the requirements for restructuring the NVT Courses Learning Management System (LMS) from its current monolithic architecture to a modular, domain-driven structure. The system is a Laravel 11 + Vue 3 + Inertia.js application that manages:

- **Traditional Courses**: In-person training with attendance tracking via clocking system
- **Online Courses**: Self-paced learning with video/PDF content, progress tracking, and learning sessions
- **Quiz System**: Standalone quizzes and module-level quizzes with time limits
- **Audio System**: Audio content with assignments and progress tracking
- **Video System**: Video library with streaming, bookmarks, and progress
- **Attendance System**: Clock in/out for traditional courses
- **Evaluation System**: Employee performance evaluations
- **User Management**: Users, departments, levels, manager hierarchies
- **Reporting System**: Multiple reports for analytics and compliance
- **Notification System**: Email notifications for various events

The restructuring aims to improve maintainability, reduce code duplication, establish clear boundaries between domains, and make the codebase easier to understand and extend.

## Glossary

- **LMS**: Learning Management System - the application being restructured
- **Module**: A self-contained domain package containing models, controllers, services, and views for a specific business domain
- **Domain**: A distinct business area (e.g., Courses, Quizzes, Users)
- **Traditional Course**: In-person training course with physical attendance
- **Online Course**: Self-paced digital course with video/PDF content
- **CourseOnline**: The model representing online courses (current naming)
- **Learning Session**: A tracked period of user engagement with online course content
- **Clocking**: Clock in/out records for traditional course attendance
- **Assignment**: The relationship between a user and a course/quiz/audio they are assigned to
- **Progress**: Tracking of user completion percentage for content
- **Evaluation**: Performance assessment of employees
- **Manager Hierarchy**: The organizational structure defining who manages whom

## Requirements

### Requirement 1: Project Analysis and Documentation

**User Story:** As a developer, I want comprehensive documentation of the current system architecture, so that I can understand all components before restructuring.

#### Acceptance Criteria

1. THE System Analyzer SHALL produce a complete inventory of all 45 Eloquent models with their relationships and responsibilities
2. THE System Analyzer SHALL document all 60+ controllers with their routes, methods, and dependencies
3. THE System Analyzer SHALL catalog all 30+ services with their purposes and inter-dependencies
4. THE System Analyzer SHALL map all 100+ Vue pages with their data requirements and component dependencies
5. THE System Analyzer SHALL identify all 15 mail classes and their trigger events
6. THE System Analyzer SHALL document all 12 events and 6 listeners with their relationships
7. THE System Analyzer SHALL create a dependency graph showing relationships between all components

### Requirement 2: Domain Identification and Boundaries

**User Story:** As a developer, I want clear domain boundaries defined, so that I can organize code into logical modules.

#### Acceptance Criteria

1. THE Domain Analyzer SHALL identify distinct business domains from the existing codebase
2. THE Domain Analyzer SHALL define clear boundaries between domains with minimal cross-domain dependencies
3. THE Domain Analyzer SHALL document shared kernel components that multiple domains depend on
4. THE Domain Analyzer SHALL identify anti-corruption layers needed between domains
5. THE Domain Analyzer SHALL produce a domain map showing all identified domains and their relationships

### Requirement 3: User Management Module Structure

**User Story:** As a developer, I want the User Management domain restructured into a cohesive module, so that all user-related functionality is organized together.

#### Acceptance Criteria

1. THE User Module SHALL contain User, Department, UserLevel, UserLevelTier, and UserDepartmentRole models
2. THE User Module SHALL contain all user-related controllers (UserController, UserDepartmentRoleController, OrganizationalController)
3. THE User Module SHALL contain ManagerHierarchyService and related user services
4. THE User Module SHALL contain all user-related Vue pages under a unified structure
5. THE User Module SHALL expose clear interfaces for other modules to interact with user data

### Requirement 4: Traditional Course Module Structure

**User Story:** As a developer, I want the Traditional Course domain restructured into a cohesive module, so that in-person training functionality is organized together.

#### Acceptance Criteria

1. THE Traditional Course Module SHALL contain Course, CourseAvailability, CourseRegistration, CourseCompletion, and CourseAssignment models
2. THE Traditional Course Module SHALL contain CourseController, CourseAssignmentController, and related admin controllers
3. THE Traditional Course Module SHALL contain CourseService and related services
4. THE Traditional Course Module SHALL contain all traditional course Vue pages
5. THE Traditional Course Module SHALL integrate with the Attendance Module for clocking functionality

### Requirement 5: Online Course Module Structure

**User Story:** As a developer, I want the Online Course domain restructured into a cohesive module, so that self-paced learning functionality is organized together.

#### Acceptance Criteria

1. THE Online Course Module SHALL contain CourseOnline, CourseModule, ModuleContent, CourseOnlineAssignment, and UserContentProgress models
2. THE Online Course Module SHALL contain CourseOnlineController, CourseModuleController, ModuleContentController, and ContentViewController
3. THE Online Course Module SHALL contain CourseProgressService, CourseAnalyticsService, and SessionAnalyticsService
4. THE Online Course Module SHALL contain LearningSession and related analytics models
5. THE Online Course Module SHALL contain all online course Vue pages including content viewer

### Requirement 6: Quiz Module Structure

**User Story:** As a developer, I want the Quiz domain restructured into a cohesive module, so that all quiz functionality is organized together.

#### Acceptance Criteria

1. THE Quiz Module SHALL contain Quiz, QuizQuestion, QuizAnswer, QuizAttempt, QuizAssignment, and ModuleQuizResult models
2. THE Quiz Module SHALL contain QuizController (admin and user), QuizAssignmentController, and ModuleQuizController
3. THE Quiz Module SHALL support both standalone quizzes and module-level quizzes
4. THE Quiz Module SHALL contain all quiz-related Vue pages for both admin and user interfaces
5. THE Quiz Module SHALL expose interfaces for integration with Course modules

### Requirement 7: Audio Module Structure

**User Story:** As a developer, I want the Audio domain restructured into a cohesive module, so that audio content functionality is organized together.

#### Acceptance Criteria

1. THE Audio Module SHALL contain Audio, AudioCategory, AudioProgress, and AudioAssignment models
2. THE Audio Module SHALL contain AudioController and AudioAssignmentController
3. THE Audio Module SHALL contain audio streaming and progress tracking functionality
4. THE Audio Module SHALL contain all audio-related Vue pages
5. THE Audio Module SHALL support both Google Cloud and local storage for audio files

### Requirement 8: Video Module Structure

**User Story:** As a developer, I want the Video domain restructured into a cohesive module, so that video content functionality is organized together.

#### Acceptance Criteria

1. THE Video Module SHALL contain Video, VideoCategory, VideoProgress, VideoBookmark, and VideoQuality models
2. THE Video Module SHALL contain VideoController, VideoStreamController, and VideoCategoryController
3. THE Video Module SHALL contain VideoStorageService, VpsTranscodingService, and GoogleDriveService
4. THE Video Module SHALL contain all video-related Vue pages
5. THE Video Module SHALL support multiple storage backends (Google Drive, VPS, Local)

### Requirement 9: Attendance Module Structure

**User Story:** As a developer, I want the Attendance domain restructured into a cohesive module, so that clock in/out functionality is organized together.

#### Acceptance Criteria

1. THE Attendance Module SHALL contain Clocking model and related attendance tracking
2. THE Attendance Module SHALL contain ClockingController and AttendanceController
3. THE Attendance Module SHALL contain AttendanceService and ClockingService
4. THE Attendance Module SHALL contain all attendance-related Vue pages
5. THE Attendance Module SHALL integrate with Traditional Course Module for course-specific attendance

### Requirement 10: Evaluation Module Structure

**User Story:** As a developer, I want the Evaluation domain restructured into a cohesive module, so that performance evaluation functionality is organized together.

#### Acceptance Criteria

1. THE Evaluation Module SHALL contain Evaluation, EvaluationConfig, EvaluationType, EvaluationHistory, and Incentive models
2. THE Evaluation Module SHALL contain EvaluationController, UserEvaluationController, and OnlineCourseEvaluationController
3. THE Evaluation Module SHALL contain EvaluationEmailService and related services
4. THE Evaluation Module SHALL contain all evaluation-related Vue pages
5. THE Evaluation Module SHALL integrate with User Module for employee data

### Requirement 11: Notification Module Structure

**User Story:** As a developer, I want the Notification domain restructured into a cohesive module, so that all email and notification functionality is organized together.

#### Acceptance Criteria

1. THE Notification Module SHALL contain NotificationTemplate model and all Mail classes
2. THE Notification Module SHALL contain NotificationService and EmailService
3. THE Notification Module SHALL contain all Events and Listeners related to notifications
4. THE Notification Module SHALL provide a unified interface for sending notifications from any module
5. THE Notification Module SHALL support email templates for all notification types

### Requirement 12: Reporting Module Structure

**User Story:** As a developer, I want the Reporting domain restructured into a cohesive module, so that all analytics and reporting functionality is organized together.

#### Acceptance Criteria

1. THE Reporting Module SHALL contain CourseAnalytics model and related analytics models
2. THE Reporting Module SHALL contain ReportController, CourseOnlineReportController, and UserCourseProgressReportController
3. THE Reporting Module SHALL contain ReportService, ExportAnalyticsService, CsvExportService, and ExcelExportService
4. THE Reporting Module SHALL contain all report-related Vue pages
5. THE Reporting Module SHALL provide interfaces for other modules to contribute report data

### Requirement 13: Shared Kernel Components

**User Story:** As a developer, I want shared components identified and organized, so that common functionality is reusable across modules.

#### Acceptance Criteria

1. THE Shared Kernel SHALL contain base controller, model, and service classes
2. THE Shared Kernel SHALL contain common UI components used across modules
3. THE Shared Kernel SHALL contain utility services (FileUploadService, ThumbnailService)
4. THE Shared Kernel SHALL contain common middleware and request classes
5. THE Shared Kernel SHALL contain shared TypeScript types and interfaces

### Requirement 14: Code Quality Improvements

**User Story:** As a developer, I want code quality issues identified and documented, so that they can be addressed during restructuring.

#### Acceptance Criteria

1. THE Code Analyzer SHALL identify duplicate code patterns across controllers and services
2. THE Code Analyzer SHALL identify inconsistent naming conventions (e.g., CourseOnline vs OnlineCourse)
3. THE Code Analyzer SHALL identify missing or incomplete error handling
4. THE Code Analyzer SHALL identify N+1 query problems and inefficient database queries
5. THE Code Analyzer SHALL identify unused code and dead routes

### Requirement 15: Migration Strategy

**User Story:** As a developer, I want a clear migration strategy, so that I can restructure the project incrementally without breaking functionality.

#### Acceptance Criteria

1. THE Migration Plan SHALL define the order of module extraction based on dependencies
2. THE Migration Plan SHALL include rollback procedures for each migration step
3. THE Migration Plan SHALL define testing requirements for each migration phase
4. THE Migration Plan SHALL maintain backward compatibility during transition
5. THE Migration Plan SHALL include database migration strategy if schema changes are needed

### Requirement 19: Production Data Preservation (CRITICAL)

**User Story:** As a system administrator, I want all existing production data preserved during restructuring, so that no user data, course progress, or historical records are lost.

#### Acceptance Criteria

1. THE Restructuring Process SHALL NOT delete, modify, or corrupt any existing production data
2. WHEN database schema changes are required THEN THE Migration SHALL use additive-only changes (new columns, new tables) without removing existing columns or tables
3. THE Restructuring Process SHALL maintain all existing table relationships and foreign key constraints
4. WHEN renaming tables or columns is necessary THEN THE Migration SHALL create new structures and copy data rather than using destructive rename operations
5. THE Migration Process SHALL include data verification steps to confirm data integrity after each migration
6. THE Restructuring Process SHALL create database backups before any schema modification
7. WHEN new module structures require data reorganization THEN THE System SHALL use database views or query modifications rather than moving data between tables
8. THE Migration Process SHALL be reversible without data loss for at least 30 days after deployment
9. THE System SHALL maintain audit logs of all data-related changes during restructuring
10. IF any migration step fails THEN THE System SHALL automatically rollback to the previous state without data loss

### Requirement 16: Frontend Architecture Restructure

**User Story:** As a developer, I want the Vue.js frontend restructured to match the modular backend, so that frontend and backend are aligned.

#### Acceptance Criteria

1. THE Frontend Restructure SHALL organize Vue pages by domain module
2. THE Frontend Restructure SHALL create shared component library for common UI elements
3. THE Frontend Restructure SHALL establish consistent state management patterns
4. THE Frontend Restructure SHALL define TypeScript interfaces matching backend data structures
5. THE Frontend Restructure SHALL improve component reusability across modules

### Requirement 17: API and Route Organization

**User Story:** As a developer, I want routes organized by module, so that API endpoints are logically grouped and discoverable.

#### Acceptance Criteria

1. THE Route Restructure SHALL organize routes into module-specific route files
2. THE Route Restructure SHALL establish consistent URL naming conventions
3. THE Route Restructure SHALL document all API endpoints with their purposes
4. THE Route Restructure SHALL identify and consolidate duplicate routes
5. THE Route Restructure SHALL maintain backward compatibility for existing URLs

### Requirement 18: Testing Infrastructure

**User Story:** As a developer, I want testing infrastructure established for each module, so that changes can be verified automatically.

#### Acceptance Criteria

1. THE Testing Infrastructure SHALL include unit tests for each module's services
2. THE Testing Infrastructure SHALL include feature tests for each module's controllers
3. THE Testing Infrastructure SHALL include integration tests for cross-module interactions
4. THE Testing Infrastructure SHALL establish test factories for all models
5. THE Testing Infrastructure SHALL achieve minimum 70% code coverage for critical paths

### Requirement 20: Laravel 12 Upgrade

**User Story:** As a developer, I want the system upgraded to Laravel 12, so that I can benefit from the latest framework features, security updates, and performance improvements.

#### Acceptance Criteria

1. THE Upgrade Process SHALL migrate from Laravel 11 to Laravel 12 following official upgrade guide
2. THE Upgrade Process SHALL update all composer dependencies to Laravel 12 compatible versions
3. THE Upgrade Process SHALL update PHP version requirement if needed by Laravel 12
4. THE Upgrade Process SHALL review and update all deprecated features and methods
5. THE Upgrade Process SHALL update configuration files to match Laravel 12 structure
6. THE Upgrade Process SHALL verify all Eloquent models work with Laravel 12 changes
7. THE Upgrade Process SHALL update all middleware to Laravel 12 patterns
8. THE Upgrade Process SHALL verify Inertia.js compatibility with Laravel 12
9. THE Upgrade Process SHALL update all service providers to Laravel 12 conventions
10. THE Upgrade Process SHALL run full test suite after upgrade to verify functionality
