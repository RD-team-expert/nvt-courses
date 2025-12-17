# Design Document

## Overview

The User Course Progress Report feature provides administrators with a comprehensive view of all user course progress across both traditional and online courses. The system aggregates data from multiple sources, calculates learning scores using a weighted formula, and provides Excel export functionality with separate sheets for completed and non-completed courses.

The feature integrates with existing course management systems and reuses calculation logic from CourseOnlineReportController to ensure consistency across reports.

## Architecture

### High-Level Architecture

```
┌─────────────────┐
│   Admin User    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│  UserCourseProgressReportController     │
│  - index()                              │
│  - export()                             │
└────────┬────────────────────────────────┘
         │
         ├──────────────────┬──────────────────┐
         ▼                  ▼                  ▼
┌──────────────────┐ ┌──────────────┐ ┌──────────────────┐
│ CourseProgress   │ │ ExcelExport  │ │ LearningScore    │
│ Service          │ │ Service      │ │ Calculator       │
└────────┬─────────┘ └──────────────┘ └──────────────────┘
         │
         ├────────────┬────────────┬────────────┐
         ▼            ▼            ▼            ▼
┌──────────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐
│ Course       │ │ Course   │ │ Learning │ │ Quiz         │
│ Assignment   │ │ Online   │ │ Session  │ │ Attempts     │
│              │ │ Assign.  │ │          │ │              │
└──────────────┘ └──────────┘ └──────────┘ └──────────────┘
```

### Component Responsibilities

1. **UserCourseProgressReportController**: Handles HTTP requests, applies filters, coordinates data retrieval and export
2. **CourseProgressService**: Aggregates data from traditional and online courses, calculates metrics
3. **ExcelExportService**: Generates Excel files with multiple worksheets
4. **LearningScoreCalculator**: Calculates learning scores using the weighted formula
5. **Models**: Provide data access to course assignments, learning sessions, and quiz attempts

## Components and Interfaces

### UserCourseProgressReportController

```php
class UserCourseProgressReportController extends Controller
{
    public function index(Request $request): Response
    // Displays the course progress report with filters
    // Parameters: course_id, status, date_from, date_to, user_id, department_id, course_type
    // Returns: Inertia response with paginated assignments and filter options
    
    public function export(Request $request): BinaryFileResponse
    // Exports course progress data to Excel
    // Parameters: same as index()
    // Returns: Excel file download with 2 sheets
}
```

### CourseProgressService

```php
class CourseProgressService
{
    public function getProgressData(array $filters): Collection
    // Retrieves and aggregates progress data from all sources
    // Returns: Collection of course progress records
    
    public function calculateLearningScore(array $data): float
    // Calculates learning score using weighted formula
    // Returns: Score between 0-100
    
    public function determineScoreBand(float $score): string
    // Maps score to band (Excellent/Good/Needs Attention)
    // Returns: Score band string
    
    public function calculateComplianceStatus(
        ?Carbon $deadline,
        string $status,
        float $progress,
        float $learningScore
    ): string
    // Determines compliance status based on deadline, progress, and score band
    // For completed: "Non-Compliant" if score < 70 (Needs Attention), else "Compliant"
    // For incomplete: "Non-Compliant" if past deadline, "At Risk" if within 7 days
    // Returns: Compliant/At Risk/Non-Compliant
    
    public function calculateDaysOverdue(?Carbon $deadline, string $status): ?int
    // Calculates days past deadline for incomplete assignments
    // For traditional: uses CourseAvailability.end_date
    // For online: uses CourseOnline.deadline or CourseOnlineAssignment.deadline
    // Returns: Number of days or null
    
    public function calculateTraditionalProgress(int $userId, int $courseId, int $totalSessions): float
    // Calculates progress for traditional courses from Clocking records
    // Returns: (attended_sessions / total_sessions) * 100
}
```

### ExcelExportService

```php
class ExcelExportService
{
    public function exportCourseProgress(
        Collection $completedData,
        Collection $nonCompletedData
    ): BinaryFileResponse
    // Generates Excel file with 2 sheets
    // Returns: Excel file download
    
    private function formatWorksheet(
        Worksheet $sheet,
        Collection $data,
        string $sheetName
    ): void
    // Formats a worksheet with headers and data
    // Applies proper data types and formatting
}
```

### LearningScoreCalculator

```php
class LearningScoreCalculator
{
    public function calculate(
        float $completionRate,
        float $progressPercentage,
        float $attentionScore,
        float $quizScore,
        int $suspiciousActivities,
        int $totalSessions
    ): float
    // Calculates learning score using formula:
    // (completion_rate × 0.25) + (progress × 0.25) + 
    // (attention × 0.25) + (quiz × 0.25) - suspicious_penalty
    // Returns: Score between 0-100
    
    public function getAttentionScore(int $userId, int $courseId): float
    // Retrieves or calculates attention score for online courses
    // Uses simulated attention calculation from CourseOnlineReportController
    // Returns: Attention score or 65 as default
    
    public function getQuizScore(int $userId, ?int $courseId): float
    // Aggregates quiz performance from regular and module quizzes
    // Returns: Quiz score between 0-100
}
```

## Data Models

### Course Progress Data Structure

```php
[
    'id' => int,                          // Assignment ID
    'user_id' => int,
    'user_name' => string,
    'employee_code' => string|null,
    'department' => string,               // Department name or 'N/A'
    'course_type' => string,              // 'traditional' or 'online'
    'course_id' => int,
    'course_name' => string,
    'completion_status' => string,        // 'Completed', 'In Progress', 'Not Started'
    'days_overdue' => int|null,           // Days past deadline or null
    'progress_percentage' => float,       // 0-100 (for traditional: attended_sessions/total_sessions * 100)
    'started_date' => string|null,        // 'MM/DD/YYYY' or null - Start Course date
    'completion_date' => string|null,     // 'MM/DD/YYYY' or null (only for completed)
    'learning_score' => float,            // 0-100
    'score_band' => string,               // 'Excellent', 'Good', 'Needs Attention'
    'compliance_status' => string,        // 'Compliant', 'At Risk', 'Non-Compliant'
    'assigned_at' => Carbon,
    'deadline' => Carbon|null,            // CourseAvailability.end_date for traditional, CourseOnline.deadline for online
    
    // Calculation components (not displayed but used for calculation)
    'completion_rate' => float,
    'attention_score' => float,
    'quiz_score' => float,
    'suspicious_activities' => int,
    'total_sessions' => int,              // From CourseAvailability.sessions for traditional
    'attended_sessions' => int,           // From Clocking count for traditional
]
```

### Filter Structure

```php
[
    'department_id' => int|null,
    'course_type' => string|null,         // 'traditional', 'online', or null
    'date_from' => string|null,           // 'YYYY-MM-DD'
    'date_to' => string|null,             // 'YYYY-MM-DD'
    'status' => string|null,              // 'completed', 'in_progress', 'overdue'
    'user_id' => int|null,
    'course_id' => int|null,
]
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Display contains all required fields
*For any* course assignment record, when rendered in the report, the output should contain all required fields: employee name, department, course type, course name, completion status, days overdue, progress percentage, completion date, learning score, score band, and compliance status.
**Validates: Requirements 1.2**

### Property 2: Learning score calculation formula
*For any* set of input values (completion_rate, progress_percentage, attention_score, quiz_score, suspicious_activities, total_sessions), the calculated learning score should equal: (completion_rate × 0.25) + (progress_percentage × 0.25) + (attention_score × 0.25) + (quiz_score × 0.25) - (suspicious_activities / total_sessions × 10), clamped to 0-100.
**Validates: Requirements 1.3**

### Property 3: Score band classification
*For any* learning score value, the score band should be "Excellent" if score ≥ 85, "Good" if score ≥ 70 and < 85, or "Needs Attention" if score < 70.
**Validates: Requirements 1.4**

### Property 4: Days overdue calculation for traditional courses
*For any* incomplete traditional course assignment with a deadline, the days overdue should equal the difference in days between the current date and CourseAvailability.end_date.
**Validates: Requirements 1.5**

### Property 4b: Days overdue calculation for online courses
*For any* incomplete online course assignment with a deadline, the days overdue should equal the difference in days between the current date and CourseOnline.deadline (or CourseOnlineAssignment.deadline if set).
**Validates: Requirements 1.6**

### Property 4c: Traditional course progress calculation
*For any* traditional course assignment, the progress percentage should equal (count of Clocking records with clock_out / CourseAvailability.sessions) × 100.
**Validates: Requirements 1.7**

### Property 5: Department filter
*For any* department filter selection, all returned assignments should belong to users in that department.
**Validates: Requirements 2.1**

### Property 6: Course type filter
*For any* course type filter selection, all returned assignments should match that course type (traditional or online).
**Validates: Requirements 2.2**

### Property 7: Date range filter
*For any* date range filter (date_from, date_to), all returned assignments should have assigned_at dates within that range (inclusive).
**Validates: Requirements 2.3**

### Property 8: Status filter
*For any* completion status filter, all returned assignments should have that completion status.
**Validates: Requirements 2.4**

### Property 9: Multiple filters AND logic
*For any* combination of filters, the returned assignments should satisfy ALL applied filters simultaneously.
**Validates: Requirements 2.5**

### Property 10: Completed sheet segregation
*For any* export operation, the "Completed Courses (KPI)" sheet should contain only assignments with status "completed" for both traditional and online courses.
**Validates: Requirements 3.2**

### Property 11: Non-completed sheet segregation
*For any* export operation, the "Non-Completed Courses (KPI)" sheet should contain only assignments with status "in_progress", "assigned", or "not_started", and completion status should display "Not Started" instead of "Overdue".
**Validates: Requirements 3.3**

### Property 11b: Non-completed sheet columns
*For any* non-completed courses export, the worksheet should NOT include a "Completion Date" column but should include a "Start Course" column.
**Validates: Requirements 3.5**

### Property 11c: Completed sheet columns
*For any* completed courses export, the worksheet should include both "Start Course" and "Completion Date" columns.
**Validates: Requirements 3.4**

### Property 12: Export filename format
*For any* export operation, the generated filename should match the pattern "user_course_progress_YYYY-MM-DD_HH-mm-ss.xlsx" where YYYY-MM-DD_HH-mm-ss represents the export timestamp.
**Validates: Requirements 3.5**

### Property 13: Compliance status for completed assignments with good scores
*For any* assignment with status "completed" and score band "Excellent" or "Good", the compliance status should be "Compliant".
**Validates: Requirements 4.2**

### Property 13b: Compliance status for completed assignments needing attention
*For any* assignment with status "completed" and score band "Needs Attention", the compliance status should be "Non-Compliant".
**Validates: Requirements 4.3**

### Property 14: At Risk compliance status
*For any* incomplete assignment with a deadline between 1 and 7 days in the future, the compliance status should be "At Risk".
**Validates: Requirements 4.3**

### Property 15: Non-Compliant status for overdue
*For any* incomplete assignment with a deadline in the past, the compliance status should be "Non-Compliant".
**Validates: Requirements 4.4**

### Property 16: Compliance considers deadline and progress
*For any* assignment, the compliance status calculation should consider both the deadline field and the progress_percentage field.
**Validates: Requirements 4.5**

### Property 17: Attention score calculation consistency
*For any* online course assignment, the attention score should be calculated using the same simulated attention calculation method from CourseOnlineReportController.
**Validates: Requirements 5.3**

### Property 18: Quiz score aggregation
*For any* user, the quiz score should include both regular quiz attempts from quiz_attempts table and module quiz results from module_quiz_results table.
**Validates: Requirements 5.4**

### Property 19: Course type identification
*For any* assignment, the course_type field should be "traditional" if from Course model or "online" if from CourseOnline model.
**Validates: Requirements 5.5**

### Property 20: Numeric export formatting
*For any* numeric value in the Excel export, the cell type should be numeric (not text).
**Validates: Requirements 7.1**

### Property 21: Date export formatting
*For any* date value in the Excel export, the formatted string should match the pattern "MM/DD/YYYY".
**Validates: Requirements 7.2**

### Property 22: Percentage export formatting
*For any* percentage value in the Excel export, the formatted string should include the "%" symbol.
**Validates: Requirements 7.3**

### Property 23: UTF-8 encoding in export
*For any* string containing special characters (non-ASCII), the Excel export should preserve those characters correctly using UTF-8 encoding.
**Validates: Requirements 7.5**

## Error Handling

### Data Retrieval Errors

- If database queries fail, log the error and return an empty result set with an error message
- If a specific course or user is not found, skip that record and continue processing
- If relationships are missing (e.g., user has no department), use default values ("N/A")

### Calculation Errors

- If learning score calculation fails due to invalid data, use 0 as the score
- If attention score cannot be calculated, use 65 as default (consistent with CourseOnlineReportController)
- If quiz score cannot be calculated, use 0 as default
- Ensure all calculated values are clamped to valid ranges (0-100 for scores, 0+ for days)

### Export Errors

- If Excel generation fails, return a 500 error with a user-friendly message
- If no data matches the filters, generate an empty Excel file with headers only
- If file download fails, log the error and show an error notification to the user

### Validation Errors

- Validate filter inputs before applying them
- Ensure date ranges are valid (date_from <= date_to)
- Ensure numeric filters are positive integers
- Return validation errors with clear messages

## Testing Strategy

### Unit Testing

The following components should have unit tests:

1. **LearningScoreCalculator**
   - Test learning score calculation with various input combinations
   - Test attention score retrieval and defaults
   - Test quiz score aggregation
   - Test edge cases (zero values, missing data)

2. **CourseProgressService**
   - Test score band determination for boundary values (70, 85)
   - Test compliance status calculation for various deadline scenarios
   - Test days overdue calculation
   - Test filter application logic

3. **ExcelExportService**
   - Test worksheet creation with correct names
   - Test data formatting (numbers, dates, percentages)
   - Test UTF-8 encoding
   - Test empty data handling

### Property-Based Testing

Property-based tests will be implemented using **Pest PHP** with the **pest-plugin-faker** for generating random test data. Each test will run a minimum of 100 iterations.

Each property-based test MUST be tagged with a comment referencing the correctness property in this design document using the format: `**Feature: user-course-progress-report, Property {number}: {property_text}**`

The following properties should be tested:

1. **Property 2**: Learning score formula correctness
2. **Property 3**: Score band classification boundaries
3. **Property 4**: Days overdue calculation accuracy
4. **Property 5-9**: Filter logic correctness
5. **Property 10-11**: Export sheet segregation
6. **Property 12**: Filename format validation
7. **Property 13-16**: Compliance status logic
8. **Property 18**: Quiz score aggregation
9. **Property 20-23**: Export formatting

### Integration Testing

Integration tests should verify:

1. End-to-end data flow from database to report display
2. Filter combinations work correctly together
3. Export generates valid Excel files that can be opened
4. Data consistency between traditional and online courses
5. Performance with large datasets (1000+ assignments)

### Manual Testing

Manual testing should cover:

1. UI/UX of the report page
2. Filter interactions and responsiveness
3. Excel file opens correctly in Microsoft Excel and Google Sheets
4. Data accuracy by comparing with source tables
5. Edge cases like users with no courses, courses with no users

## Performance Considerations

### Database Optimization

- Use eager loading for relationships (user, department, course)
- Add indexes on frequently filtered columns (department_id, status, assigned_at)
- Use pagination to limit result set size (15-20 records per page)
- Cache filter options (departments, courses) for 1 hour

### Query Optimization

- Combine traditional and online course queries using UNION
- Use raw SQL for complex aggregations (learning sessions, quiz scores)
- Limit joins to necessary relationships only
- Use select() to retrieve only required columns

### Export Optimization

- Stream Excel generation for large datasets (>1000 records)
- Process data in chunks to avoid memory issues
- Use queue jobs for exports exceeding 5000 records
- Implement progress tracking for long-running exports

### Caching Strategy

- Cache calculated learning scores for 15 minutes
- Cache attention scores for 30 minutes
- Cache quiz scores for 1 hour
- Invalidate caches when underlying data changes

## Security Considerations

- Ensure only administrators can access the report (middleware check)
- Validate and sanitize all filter inputs
- Prevent SQL injection through parameterized queries
- Limit export file size to prevent DoS attacks
- Log all export operations for audit trail
- Implement rate limiting on export endpoint (max 10 exports per hour per user)

## Dependencies

- **Laravel Framework**: 10.x or higher
- **Inertia.js**: For rendering the report page
- **PhpSpreadsheet**: For Excel file generation (already in use)
- **Pest PHP**: For property-based testing
- **Carbon**: For date manipulation (already in use)

## Migration Path

1. Create new controller and service classes
2. Add new route for the report page and export endpoint
3. Create Inertia page component for the UI
4. Implement data aggregation logic
5. Implement Excel export functionality
6. Add tests (unit, property-based, integration)
7. Deploy to staging for testing
8. Deploy to production with feature flag

## Future Enhancements

- Add chart visualizations for learning score distribution
- Implement scheduled email reports
- Add comparison view (month-over-month, year-over-year)
- Support additional export formats (PDF, CSV)
- Add drill-down capability to view individual user details
- Implement custom report templates
- Add predictive analytics for at-risk learners
