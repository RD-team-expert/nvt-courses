# Manager Email Notification Diagnostic Results

## Executive Summary

**Issue**: Managers are not receiving email notifications when courses are assigned to their team members.

**Root Cause Identified**: 
- **43 users (30.3%)** do not have managers assigned in the UserDepartmentRole table
- **2 users (1.4%)** do not have departments assigned
- Only **14 managers** would receive emails out of potentially more

## Detailed Findings

### 1. Users Without Departments (2 users)

These users cannot have managers assigned because they lack department assignments:

| ID  | Name      | Email                     |
|-----|-----------|---------------------------|
| 1   | Test User | test@example.com          |
| 189 | Chris     | chris.dunham@pnefoods.com |

**Action Required**: Assign these users to appropriate departments.

### 2. Users Without Managers (43 users)

These users have departments but no managers assigned in UserDepartmentRole:

**Sample of affected users**:
- Jane (Swaida Amrany Marketing)
- Emma (Swaida Amrany Marketing)
- Ned (NVT Marketing)
- Bryan (Maintenance Team)
- Alina (NVT Marketing)
- Austin (Project Managers)
- ... and 37 more

**Action Required**: Assign managers to these users in the UserDepartmentRole table with `is_primary=1`.

### 3. Current Manager Email Coverage

**Statistics**:
- Total users: 142
- Users with managers: 97 (68.3%)
- Users without managers: 45 (31.7%)
- Manager emails that would be sent: 14

**Managers currently receiving notifications**:

| Manager Name | Email                | Team Member Count |
|--------------|----------------------|-------------------|
| Joey         | joey@pnefoods.com    | 31                |
| Alen         | alen@pnefoods.com    | 9                 |
| John         | john@1ftt.com        | 9                 |
| Kain         | kain@pnehomes.com    | 3                 |
| Dan          | dan@pnefoods.com     | 6                 |
| Jaden        | Jaden@pneunited.com  | 5                 |
| Arya         | arya@pnehomes.com    | 5                 |
| Bia          | athena@pnehomes.com  | 3                 |
| Nathan       | nathan@1ftt.com      | 3                 |
| Estefan      | estefan@pnefoods.com | 4                 |
| Zeina        | zeina@1ftt.com       | 5                 |
| Gabriel      | gabriel@1ftt.com     | 5                 |
| Lana         | Lana@1ftt.com        | 5                 |
| Nas          | nas@pneunited.com    | 4                 |

## Recommendations

### Priority 1: Assign Managers to Users

**Problem**: 43 users don't have managers assigned, so no manager notifications are sent for them.

**Solution Options**:

#### Option A: Use the Bulk Assignment Command (Easiest - Recommended)
```bash
# Interactive wizard to assign managers department by department
php artisan managers:assign --bulk
```

This command will:
- Show you all users without managers grouped by department
- Let you select a manager for each department
- Assign the manager to all users in that department at once
- Provide a summary of assignments made

#### Option B: Assign Individual Managers via Command
```bash
# Assign a specific manager to a specific user
php artisan managers:assign --user-id=123 --manager-id=456

# With custom role type
php artisan managers:assign --user-id=123 --manager-id=456 --role-type=supervisor
```

#### Option C: Use the ManagerHierarchyService Programmatically
```php
use App\Services\ManagerHierarchyService;

$managerService = app(ManagerHierarchyService::class);

// Assign a manager to a user
$managerService->assignManager(
    userId: $userId,           // The employee's user ID
    managerId: $managerId,     // The manager's user ID
    roleType: 'direct_manager', // Role type
    departmentId: $departmentId // Optional, uses user's department if null
);
```

#### Option D: Create UserDepartmentRole Records Manually
```sql
INSERT INTO user_department_roles (
    user_id,           -- Manager's user ID
    department_id,     -- Department ID
    role_type,         -- 'direct_manager', 'supervisor', etc.
    is_primary,        -- Set to 1 for primary manager
    start_date,        -- Current date
    created_at,
    updated_at
) VALUES (
    ?, ?, 'direct_manager', 1, NOW(), NOW(), NOW()
);
```

### Priority 2: Assign Departments to Users

**Problem**: 2 users don't have departments, preventing manager assignment.

**Solution**:
```sql
UPDATE users 
SET department_id = ? 
WHERE id IN (1, 189);
```

### Priority 3: Verify Manager Relationships

**Check**: Ensure all UserDepartmentRole records have:
- `is_primary = 1` for primary managers
- `end_date IS NULL` or `end_date > NOW()` for active relationships

```sql
-- Check for inactive manager relationships
SELECT * FROM user_department_roles 
WHERE end_date IS NOT NULL AND end_date <= NOW();

-- Check for non-primary managers
SELECT * FROM user_department_roles 
WHERE is_primary = 0 OR is_primary IS NULL;
```

## How to Run the Diagnostic Again

After making changes, run the diagnostic command to verify:

```bash
php artisan diagnose:manager-emails
```

## Technical Details

### How Manager Notifications Work

1. When a course is assigned to users, the system calls `ManagerHierarchyService->getDirectManagersForUser()`
2. This service queries the `user_department_roles` table for:
   - Active relationships (no end_date or future end_date)
   - Primary managers (is_primary = 1)
   - In the user's department
3. For each unique manager found, one email is sent with all their team members listed

### Why Some Managers Aren't Receiving Emails

The diagnostic shows that **only 68.3% of users have managers assigned**. This means:
- When courses are assigned to the 43 users without managers, **no manager emails are sent**
- Managers are only notified about their assigned team members
- Users without manager relationships are "invisible" to the notification system

## Next Steps

1. **Immediate**: Review the list of 43 users without managers
2. **Assign managers** using one of the methods above
3. **Re-run diagnostic** to verify all users have managers
4. **Test** by assigning a course to a user and checking if their manager receives an email
5. **Monitor** the application logs for any email delivery errors

## Available Commands

### Diagnostic Command
```bash
php artisan diagnose:manager-emails
```
Runs a comprehensive diagnostic to identify users without departments or managers.

### Bulk Manager Assignment
```bash
php artisan managers:assign --bulk
```
Interactive wizard to assign managers to users department by department.

### Single Manager Assignment
```bash
php artisan managers:assign --user-id=123 --manager-id=456
```
Assign a specific manager to a specific user.

## Files Created

- `app/Console/Commands/DiagnoseManagerEmails.php` - Diagnostic command
- `app/Console/Commands/AssignManagersToUsers.php` - Manager assignment command
- `tests/Feature/ManagerEmailNotificationTest.php` - PHPUnit test version
- This report: `MANAGER_EMAIL_DIAGNOSTIC_RESULTS.md`

## Support

If you need help assigning managers or have questions about the UserDepartmentRole structure, refer to:
- `app/Services/ManagerHierarchyService.php` - Manager assignment methods
- `app/Models/UserDepartmentRole.php` - Model definition
- `app/Listeners/SendCourseOnlineAssignmentNotifications.php` - Email notification logic
