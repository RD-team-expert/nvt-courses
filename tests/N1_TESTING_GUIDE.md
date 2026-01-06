# N+1 Query Fix Testing Guide

## Overview

This guide explains how to run all tests to verify the N+1 query fixes are working correctly.

## Test Files Created

### 1. PHPUnit Tests

#### N1QueryFixTest (`tests/Feature/N1QueryFixTest.php`)
Tests that verify N+1 query fixes are working:
- `test_courses_online_index_query_count` - Verifies courses index page query count is bounded
- `test_course_show_query_count` - Verifies course show page query count
- `test_content_unlock_check_no_lazy_loading` - Verifies content unlock doesn't lazy load
- `test_content_navigation_no_lazy_loading` - Verifies navigation service doesn't lazy load
- `test_progress_service_no_lazy_loading` - Verifies progress service doesn't lazy load
- `test_learning_session_no_lazy_loading` - Verifies session service doesn't lazy load
- `test_multiple_courses_no_n1` - Tests scaling with multiple courses
- `test_course_progress_query_count` - Tests course progress page
- `test_course_model_accessors_no_lazy_loading` - Tests model accessors
- `test_quiz_index_no_n1` - Tests quiz index page

#### PerformanceBenchmarkTest (`tests/Feature/PerformanceBenchmarkTest.php`)
Performance benchmarking tests:
- `test_courses_index_performance` - Benchmarks response time and query count
- `test_course_show_performance` - Benchmarks course show page
- `test_query_count_scaling` - Verifies query count doesn't scale linearly with data
- `test_api_performance` - Benchmarks API endpoints
- `test_memory_usage` - Tests memory doesn't grow excessively

### 2. k6 Load Tests

#### smoke-test.js (`tests/k6/smoke-test.js`)
Quick verification test:
- 5 iterations with 1 virtual user
- Tests courses index, course show, and progress API
- Pass threshold: 80% checks passing, P95 under 3s

#### n1-query-load-test.js (`tests/k6/n1-query-load-test.js`)
Full load test with multiple scenarios:
- **Smoke**: 1 VU, 30s - Quick verification
- **Load**: Ramp up to 20 VUs over 8 minutes
- **Stress**: Ramp up to 50 VUs

## Running Tests

### PHPUnit Tests

```bash
# Run all N+1 tests
php artisan test --filter="N1QueryFixTest"

# Run performance benchmarks
php artisan test --filter="PerformanceBenchmarkTest"

# Run a specific test
php artisan test --filter="N1QueryFixTest::test_courses_online_index_query_count"
```

### k6 Tests

```bash
# Quick smoke test
k6 run tests/k6/smoke-test.js

# Full load test
k6 run tests/k6/n1-query-load-test.js

# With custom base URL
k6 run -e BASE_URL=http://your-server.com tests/k6/smoke-test.js

# With custom test user
k6 run -e TEST_USER=admin@example.com -e TEST_PASS=password tests/k6/smoke-test.js
```

### Run All Tests (Windows)

```bash
tests\k6\run-all-tests.bat
```

## Expected Results

### N1QueryFixTest
- All 10 tests should pass
- Query counts should be bounded (typically under 40 for index, under 35 for show)
- No lazy loading violations

### PerformanceBenchmarkTest
- Average response times under 500ms for index, under 400ms for show
- Query counts don't scale linearly with data volume
- Memory doesn't increase more than 50MB over 10 requests

### k6 Smoke Test
- 80%+ check pass rate
- P95 under 3000ms
- No 500 errors

## Interpreting Results

### Signs of N+1 Issues
1. Query count increases linearly with data volume
2. Response times grow with more records
3. `LazyLoadingViolationException` errors in logs
4. High query counts (100+) for simple pages

### Signs of Good Performance
1. Bounded query counts regardless of data volume
2. Consistent response times
3. Minimal memory growth
4. P95 response times under 2 seconds

## Troubleshooting

### Test Failures

1. **Database constraint errors**: Check factory definitions match database schema
2. **Lazy loading errors**: A relationship is being accessed without eager loading
3. **High query counts**: Look for missing `with()` statements in queries
4. **Slow response times**: Check for missing indexes or inefficient queries

### k6 Issues

1. **Connection refused**: Make sure `php artisan serve` is running
2. **Authentication issues**: Set TEST_USER and TEST_PASS environment variables
3. **High response times**: Expected in development; focus on query counts

## Files Changed for N+1 Fixes

1. `app/Http/Controllers/User/CourseOnlineController.php`
2. `app/Http/Controllers/User/CourseProgressController.php`
3. `app/Http/Controllers/User/ContentViewController.php`
4. `app/Services/ContentView/ContentNavigationService.php`
5. `app/Services/ContentView/ContentDataService.php`
6. `app/Services/ContentView/ContentProgressService.php`
7. `app/Services/ContentView/ContentAccessService.php`
8. `app/Services/ContentView/LearningSessionService.php`
9. `app/Providers/AppServiceProvider.php` (added `preventLazyLoading`)
