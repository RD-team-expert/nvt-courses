# Design Document

## Overview

This design document outlines a comprehensive testing and diagnostic system to identify why managers are not receiving email notifications when courses are assigned to their team members. The solution involves creating a PHPUnit test that validates data integrity, tests the ManagerHierarchyService, and simulates the email notification flow to pinpoint the exact failure points.

## Architecture

The solution follows Laravel's testing architecture and consists of:

1. **Feature Test Class**: A PHPUnit feature test that orchestrates all diagnostic checks
2. **Data Validation Layer**: Methods to verify database relationships and data integrity
3. **Service Testing Layer**: Methods to test the ManagerHierarchyService in isolation
4. **Email Flow Simulation**: Methods to test the notification flow without sending actual emails
5. **Reporting Layer**: Methods to generate human-readable diagnostic reports

### Component Interaction Flow

```
Test Execution
    ↓
Data Validation (Check departments, managers)
    ↓
Service Testing (Test ManagerHierarchyService)
    ↓
Email Flow Simulation (Test notification logic)
    ↓
Report Generation (Output findings)
```

## Components and Interfaces

### 1. ManagerEmailNotificationTest (Feature Test)

**Location**: `tests/Feature/ManagerEmailNotificationTest.php`

**Responsibilities**:
- Orchestrate all diagnostic checks
- Query database for users, departments, and manager relationships
- Test ManagerHierarchyService methods
- Simulate email notification flow
- Generate comprehensive diagnostic reports

**Key Methods**:
```php
public function test_users_have_departments(): void
public function test_users_have_assigned_managers(): void
public function test_manager_hierarchy_service(): void
public function test_email_notification_flow(): void
public function test_comprehensive_diagnostic(): void
```

### 2. ManagerHierarchyService (Existing Service)

**Location**: `app/Services/ManagerHierarchyService.php`

**Interface Used**:
```php
public function getDirectManagersForUser(int $userId): array
public function getManagersForUser(int $userId, array $targetLevels = ['L2', 'L3', 'L4']): array
```

**Returns**: Array of manager data with structure:
```php
[
    'manager' => User,
    'level' => string,
    'relationship' => string,
    'role_type' => string,
    'is_primary' => bool
]
```

### 3. Database Models

**User Model**:
- Relationships: `department()`, `userLevel()`
- Attributes: `id`, `name`, `email`, `department_id`, `user_level_id`

**UserDepartmentRole Model**:
- Relationships: `manager()`, `department()`
- Attributes: `user_id`, `department_id`, `role_type`, `is_primary`, `start_date`, `end_date`
- Scope: `active()` - filters for current active roles

**Department Model**:
- Relationships: `users()`, `managers()`
- Attributes: `id`, `name`, `parent_id`

## Data Models

### Test Result Data Structure

```php
[
    'users_without_departments' => [
        ['id' => int, 'name' => string, 'email' => string],
        ...
    ],
    'users_without_managers' => [
        [
            'id' => int,
            'name' => string,
            'email' => string,
            'department' => string|null,
            'department_id' => int|null
        ],
        ...
    ],
    'manager_lookup_results' => [
        'user_id' => [
            'user_name' => string,
            'department' => string,
            'managers_found' => int,
            'managers' => [
                ['name' => string, 'email' => string, 'level' => string],
                ...
            ]
        ],
        ...
    ],
    'email_simulation_results' => [
        'total_users' => int,
        'users_with_managers' => int,
        'users_without_managers' => int,
        'total_manager_emails' => int,
        'manager_email_list' => [
            'manager_email' => [
                'manager_name' => string,
                'team_members' => [string, ...],
                'team_member_count' => int
            ],
            ...
        ]
    ],
    'summary' => [
        'total_users' => int,
        'users_with_departments' => int,
        'users_without_departments' => int,
        'users_with_managers' => int,
        'users_without_managers' => int,
        'total_managers' => int,
        'issues_found' => bool,
        'recommendations' => [string, ...]
    ]
]
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: User department identification completeness
*For any* set of users in the database, the diagnostic system should identify exactly those users with null or invalid department_id values, with no false positives or false negatives
**Validates: Requirements 1.1, 1.2**

### Property 2: Manager assignment identification completeness
*For any* set of users in the database, the diagnostic system should identify exactly those users without active primary manager relationships in UserDepartmentRole, with no false positives or false negatives
**Validates: Requirements 2.1, 2.2**

### Property 3: Manager lookup correctness
*For any* user, the ManagerHierarchyService.getDirectManagersForUser should return exactly the set of users who have active (end_date is null or in future) and primary manager roles in the user's department, or an empty array if the user has no department or no managers
**Validates: Requirements 3.1, 3.2, 3.3**

### Property 4: Email notification recipient accuracy
*For any* course assignment to a set of users, the set of manager email addresses that should receive notifications should equal the union of all unique managers returned by getDirectManagersForUser for each assigned user
**Validates: Requirements 4.1, 4.2, 4.4**

### Property 5: Report data completeness
*For any* diagnostic test execution, the generated report should include all required fields (user IDs, names, emails, department info, manager info) for every identified issue, and all data should be accurate and match the database state
**Validates: Requirements 1.3, 2.3, 3.4, 4.3, 5.3, 5.5**

### Property 6: Recommendation generation
*For any* diagnostic test execution where issues are found (users without departments or managers), the report should include at least one actionable recommendation for each type of issue discovered
**Validates: Requirements 5.4**

## Error Handling

### Database Query Errors
- **Scenario**: Database connection fails or query errors occur
- **Handling**: Catch exceptions, log error details, mark test as failed with descriptive message
- **Recovery**: Test should continue with remaining checks where possible

### Missing Relationships
- **Scenario**: User has no department or no manager assigned
- **Handling**: Record in diagnostic report, do not throw exception
- **Recovery**: Continue testing other users, aggregate all issues in final report

### Service Method Errors
- **Scenario**: ManagerHierarchyService throws exception
- **Handling**: Catch exception, log error, include in diagnostic report
- **Recovery**: Mark specific user check as failed, continue with other users

### Invalid Data
- **Scenario**: Orphaned records (e.g., department_id references non-existent department)
- **Handling**: Identify and report as data integrity issue
- **Recovery**: Include in recommendations for data cleanup

## Testing Strategy

### Unit Testing Approach

Unit tests will focus on:
- Individual helper methods within the test class (e.g., data collection methods)
- Validation logic for checking data completeness
- Report formatting methods

Example unit tests:
- Test that `collectUsersWithoutDepartments()` correctly identifies users with null department_id
- Test that `collectUsersWithoutManagers()` correctly queries UserDepartmentRole
- Test that report generation methods format data correctly

### Property-Based Testing Approach

Property-based tests will verify:
- **Property 1**: Department assignment completeness - Generate random user samples, verify all have departments or are correctly flagged
- **Property 2**: Manager relationship validity - Generate random manager relationships, verify all active ones are valid
- **Property 3**: Manager lookup consistency - For random users, verify service results match direct database queries
- **Property 4**: Email recipient completeness - For random course assignments, verify all managers are identified
- **Property 5**: Diagnostic report completeness - For random data states, verify all issues are detected

**Property-Based Testing Library**: We will use **Pest PHP** with the **pest-plugin-faker** for property-based testing in PHP. Pest is Laravel's recommended testing framework and provides excellent support for property-based testing patterns.

**Test Configuration**: Each property-based test will run a minimum of 100 iterations to ensure comprehensive coverage across different data scenarios.

**Test Tagging**: Each property-based test will include a comment tag in this format:
- `// Feature: manager-email-notification-test, Property 1: Department assignment completeness`
- `// Feature: manager-email-notification-test, Property 2: Manager relationship validity`
- etc.

### Integration Testing

Integration tests will verify:
- End-to-end flow from course assignment to manager email identification
- Interaction between ManagerHierarchyService and database models
- Email notification flow simulation with Mail facade

### Manual Testing Recommendations

After running automated tests:
1. Review diagnostic report output for specific data issues
2. Manually verify a sample of manager relationships in the database
3. Test actual email sending with a small group of test users
4. Verify email content and recipient lists match expectations

## Implementation Notes

### Database Queries

The test will use Laravel's query builder and Eloquent ORM:

```php
// Find users without departments
User::whereNull('department_id')->get();

// Find users without managers
User::whereDoesntHave('departmentRoles', function($query) {
    $query->where('is_primary', true)
          ->where(function($q) {
              $q->whereNull('end_date')
                ->orWhere('end_date', '>', now());
          });
})->get();
```

### Service Testing

Test the ManagerHierarchyService in isolation:

```php
$managerService = app(ManagerHierarchyService::class);
$managers = $managerService->getDirectManagersForUser($userId);
```

### Email Simulation

Use Laravel's Mail fake to test without sending:

```php
Mail::fake();
// Simulate assignment logic
Mail::assertSent(CourseOnlineAssignmentManagerNotification::class, function ($mail) {
    return $mail->hasTo('manager@example.com');
});
```

### Output Format

The test will output results using PHPUnit's output methods and Laravel's dump/dd helpers for detailed inspection:

```php
$this->info("=== Manager Email Notification Diagnostic Report ===");
$this->info("Total Users: " . $summary['total_users']);
$this->info("Users Without Departments: " . count($usersWithoutDepartments));
// ... more output
```

## Performance Considerations

- **Batch Processing**: Query users in batches if dataset is large (>1000 users)
- **Eager Loading**: Use `with()` to eager load relationships and avoid N+1 queries
- **Query Optimization**: Use `select()` to limit columns retrieved when full models aren't needed
- **Caching**: Consider caching manager lookups during test execution to avoid repeated queries

## Security Considerations

- **Data Privacy**: Test output should not expose sensitive user data in logs
- **Test Isolation**: Use database transactions to ensure test doesn't modify production data
- **Access Control**: Test should only be runnable by administrators with appropriate permissions
