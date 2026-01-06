@echo off
echo ==========================================
echo    N+1 Query Fix Test Suite
echo ==========================================
echo.

cd /d "%~dp0..\.."

echo [1/4] Running PHPUnit N+1 Query Tests...
echo ==========================================
call php artisan test --filter="N1QueryFixTest" --testdox
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ❌ PHPUnit N+1 tests failed!
    echo.
) else (
    echo.
    echo ✅ PHPUnit N+1 tests passed!
    echo.
)

echo.
echo [2/4] Running PHPUnit Performance Benchmark Tests...
echo ==========================================
call php artisan test --filter="PerformanceBenchmarkTest" --testdox
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ❌ Performance benchmark tests failed!
    echo.
) else (
    echo.
    echo ✅ Performance benchmark tests passed!
    echo.
)

echo.
echo [3/4] Checking if k6 is available...
echo ==========================================
where k6 >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ⚠️ k6 is not installed. Skipping load tests.
    echo    Install k6 from: https://k6.io/docs/getting-started/installation/
    echo.
    goto :end
)

echo k6 found! Running smoke test...
echo.

echo [4/4] Running k6 Smoke Test...
echo ==========================================
call k6 run tests\k6\smoke-test.js
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ❌ k6 smoke test failed!
    echo.
) else (
    echo.
    echo ✅ k6 smoke test passed!
    echo.
)

:end
echo.
echo ==========================================
echo    Test Suite Complete
echo ==========================================
echo.
pause
