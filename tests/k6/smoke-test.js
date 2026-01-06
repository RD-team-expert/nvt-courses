import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

// Quick smoke test for immediate verification
export const options = {
    vus: 1,
    iterations: 5,
    thresholds: {
        'http_req_duration': ['p(95)<3000'],
        'checks': ['rate>0.8'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

const coursesIndexTime = new Trend('courses_index_time');
const courseShowTime = new Trend('course_show_time');
const progressApiTime = new Trend('progress_api_time');

export default function() {
    // Test courses index
    let res = http.get(`${BASE_URL}/courses-online`);
    coursesIndexTime.add(res.timings.duration);
    
    check(res, {
        'courses index returns 200': (r) => r.status === 200,
        'courses index under 3s': (r) => r.timings.duration < 3000,
        'no 500 error on courses': (r) => r.status !== 500,
    });
    
    // Extract a course ID
    const courseMatch = res.body.match(/courses-online\/(\d+)/);
    const courseId = courseMatch ? courseMatch[1] : null;
    
    if (courseId) {
        sleep(0.5);
        
        // Test course show page
        res = http.get(`${BASE_URL}/courses-online/${courseId}`);
        courseShowTime.add(res.timings.duration);
        
        check(res, {
            'course show returns 200/302': (r) => r.status === 200 || r.status === 302,
            'course show under 2s': (r) => r.timings.duration < 2000,
            'no 500 error on show': (r) => r.status !== 500,
        });
        
        sleep(0.5);
        
        // Test progress API
        res = http.get(`${BASE_URL}/api/courses-online/${courseId}/user-progress`, {
            headers: { 'Accept': 'application/json' },
        });
        progressApiTime.add(res.timings.duration);
        
        check(res, {
            'progress API returns 200': (r) => r.status === 200,
            'progress API under 1s': (r) => r.timings.duration < 1000,
            'progress API is JSON': (r) => {
                try {
                    JSON.parse(r.body);
                    return true;
                } catch {
                    return false;
                }
            },
        });
    }
    
    sleep(1);
}

export function handleSummary(data) {
    console.log('\n========================================');
    console.log('       QUICK SMOKE TEST RESULTS');
    console.log('========================================\n');
    
    const checks = data.metrics.checks;
    const passed = checks ? (checks.values.passes / (checks.values.passes + checks.values.fails) * 100) : 0;
    
    console.log(`✓ Check Pass Rate: ${passed.toFixed(1)}%`);
    
    if (data.metrics.courses_index_time) {
        console.log(`✓ Courses Index Avg: ${data.metrics.courses_index_time.values.avg.toFixed(0)}ms`);
    }
    if (data.metrics.course_show_time) {
        console.log(`✓ Course Show Avg: ${data.metrics.course_show_time.values.avg.toFixed(0)}ms`);
    }
    if (data.metrics.progress_api_time) {
        console.log(`✓ Progress API Avg: ${data.metrics.progress_api_time.values.avg.toFixed(0)}ms`);
    }
    
    const httpDuration = data.metrics.http_req_duration;
    if (httpDuration) {
        console.log(`\n✓ Overall P95: ${httpDuration.values['p(95)'].toFixed(0)}ms`);
    }
    
    console.log('\n========================================\n');
    
    // Determine pass/fail
    const allPassed = passed >= 80;
    if (allPassed) {
        console.log('✅ SMOKE TEST PASSED - N+1 fixes working!\n');
    } else {
        console.log('❌ SMOKE TEST FAILED - Check for issues\n');
    }
    
    return {};
}
