import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const coursesIndexDuration = new Trend('courses_index_duration');
const courseShowDuration = new Trend('course_show_duration');
const progressApiDuration = new Trend('progress_api_duration');
const quizIndexDuration = new Trend('quiz_index_duration');
const totalRequests = new Counter('total_requests');

// Test configuration
export const options = {
    scenarios: {
        // Smoke test - quick verification
        smoke: {
            executor: 'constant-vus',
            vus: 1,
            duration: '30s',
            startTime: '0s',
            tags: { test_type: 'smoke' },
        },
        // Load test - normal traffic
        load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 10 },  // Ramp up to 10 users
                { duration: '3m', target: 10 },  // Stay at 10 users
                { duration: '1m', target: 20 },  // Ramp up to 20 users
                { duration: '3m', target: 20 },  // Stay at 20 users
                { duration: '1m', target: 0 },   // Ramp down
            ],
            startTime: '30s',
            tags: { test_type: 'load' },
        },
        // Stress test - high traffic
        stress: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: 30 },
                { duration: '1m', target: 50 },
                { duration: '30s', target: 0 },
            ],
            startTime: '9m30s',
            tags: { test_type: 'stress' },
        },
    },
    thresholds: {
        // Response time thresholds
        'http_req_duration': ['p(95)<2000', 'p(99)<5000'],  // 95% under 2s, 99% under 5s
        'courses_index_duration': ['p(95)<1500'],           // Courses index under 1.5s
        'course_show_duration': ['p(95)<1000'],             // Course show under 1s
        'progress_api_duration': ['p(95)<500'],             // API under 500ms
        'errors': ['rate<0.1'],                             // Error rate under 10%
    },
};

// Configuration - Update these values for your environment
const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const USERNAME = __ENV.TEST_USER || 'test@example.com';
const PASSWORD = __ENV.TEST_PASS || 'password';

// Store session data
let authToken = null;
let xsrfToken = null;
let sessionCookie = null;
let courseIds = [];

// Setup function - runs once before tests
export function setup() {
    console.log(`Testing against: ${BASE_URL}`);
    
    // Get CSRF token first
    const csrfRes = http.get(`${BASE_URL}/sanctum/csrf-cookie`, {
        headers: { 'Accept': 'application/json' },
    });
    
    // Extract cookies
    const cookies = csrfRes.cookies;
    
    // Login
    const loginPayload = JSON.stringify({
        email: USERNAME,
        password: PASSWORD,
    });
    
    const loginRes = http.post(`${BASE_URL}/login`, loginPayload, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    });
    
    if (loginRes.status !== 200 && loginRes.status !== 302) {
        console.warn(`Login might have issues. Status: ${loginRes.status}`);
    }
    
    return {
        cookies: loginRes.cookies,
    };
}

// Main test function
export default function(data) {
    const cookieJar = http.cookieJar();
    
    // Set cookies from setup
    if (data && data.cookies) {
        for (const [name, values] of Object.entries(data.cookies)) {
            if (values && values.length > 0) {
                cookieJar.set(BASE_URL, name, values[0].value);
            }
        }
    }

    group('Courses Online Index', () => {
        const startTime = Date.now();
        
        const res = http.get(`${BASE_URL}/courses-online`, {
            headers: {
                'Accept': 'text/html,application/xhtml+xml',
            },
            tags: { name: 'courses_index' },
        });
        
        const duration = Date.now() - startTime;
        coursesIndexDuration.add(duration);
        totalRequests.add(1);
        
        const success = check(res, {
            'courses index status 200': (r) => r.status === 200,
            'courses index has content': (r) => r.body && r.body.length > 0,
            'courses index under 2s': (r) => r.timings.duration < 2000,
            'no server error': (r) => r.status < 500,
        });
        
        errorRate.add(!success);
        
        // Extract course IDs from response for subsequent tests
        const courseMatches = res.body.match(/courses-online\/(\d+)/g);
        if (courseMatches) {
            courseIds = [...new Set(courseMatches.map(m => m.split('/').pop()))].slice(0, 5);
        }
    });
    
    sleep(1);

    // Test individual course pages
    group('Course Show Page', () => {
        if (courseIds.length > 0) {
            const courseId = courseIds[Math.floor(Math.random() * courseIds.length)];
            const startTime = Date.now();
            
            const res = http.get(`${BASE_URL}/courses-online/${courseId}`, {
                headers: {
                    'Accept': 'text/html,application/xhtml+xml',
                },
                tags: { name: 'course_show' },
            });
            
            const duration = Date.now() - startTime;
            courseShowDuration.add(duration);
            totalRequests.add(1);
            
            const success = check(res, {
                'course show status 200 or 302': (r) => r.status === 200 || r.status === 302,
                'course show under 1.5s': (r) => r.timings.duration < 1500,
                'no server error': (r) => r.status < 500,
            });
            
            errorRate.add(!success);
        }
    });
    
    sleep(0.5);

    // Test progress API
    group('Progress API', () => {
        if (courseIds.length > 0) {
            const courseId = courseIds[Math.floor(Math.random() * courseIds.length)];
            const startTime = Date.now();
            
            const res = http.get(`${BASE_URL}/api/courses-online/${courseId}/user-progress`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                tags: { name: 'progress_api' },
            });
            
            const duration = Date.now() - startTime;
            progressApiDuration.add(duration);
            totalRequests.add(1);
            
            const success = check(res, {
                'progress API status 200': (r) => r.status === 200,
                'progress API is JSON': (r) => {
                    try {
                        JSON.parse(r.body);
                        return true;
                    } catch {
                        return false;
                    }
                },
                'progress API under 500ms': (r) => r.timings.duration < 500,
                'no server error': (r) => r.status < 500,
            });
            
            errorRate.add(!success);
        }
    });
    
    sleep(0.5);

    // Test quiz index
    group('Quiz Index', () => {
        const startTime = Date.now();
        
        const res = http.get(`${BASE_URL}/quizzes`, {
            headers: {
                'Accept': 'text/html,application/xhtml+xml',
            },
            tags: { name: 'quiz_index' },
        });
        
        const duration = Date.now() - startTime;
        quizIndexDuration.add(duration);
        totalRequests.add(1);
        
        const success = check(res, {
            'quiz index status 200': (r) => r.status === 200,
            'quiz index under 1s': (r) => r.timings.duration < 1000,
            'no server error': (r) => r.status < 500,
        });
        
        errorRate.add(!success);
    });
    
    sleep(1);
}

// Teardown function
export function teardown(data) {
    console.log('Load test completed');
}

// Handle summary
export function handleSummary(data) {
    const summary = {
        timestamp: new Date().toISOString(),
        metrics: {
            total_requests: data.metrics.total_requests?.values?.count || 0,
            error_rate: (data.metrics.errors?.values?.rate || 0) * 100,
            http_req_duration: {
                avg: data.metrics.http_req_duration?.values?.avg || 0,
                p95: data.metrics.http_req_duration?.values['p(95)'] || 0,
                p99: data.metrics.http_req_duration?.values['p(99)'] || 0,
            },
            courses_index: {
                avg: data.metrics.courses_index_duration?.values?.avg || 0,
                p95: data.metrics.courses_index_duration?.values['p(95)'] || 0,
            },
            course_show: {
                avg: data.metrics.course_show_duration?.values?.avg || 0,
                p95: data.metrics.course_show_duration?.values['p(95)'] || 0,
            },
            progress_api: {
                avg: data.metrics.progress_api_duration?.values?.avg || 0,
                p95: data.metrics.progress_api_duration?.values['p(95)'] || 0,
            },
        },
        thresholds: data.thresholds,
    };
    
    return {
        'tests/k6/n1-fix-results.json': JSON.stringify(summary, null, 2),
        stdout: textSummary(data, { indent: '  ', enableColors: true }),
    };
}

function textSummary(data, options) {
    const lines = [];
    lines.push('\n');
    lines.push('╔══════════════════════════════════════════════════════════════╗');
    lines.push('║           N+1 Query Fix Performance Test Results             ║');
    lines.push('╠══════════════════════════════════════════════════════════════╣');
    lines.push('║                                                              ║');
    
    const errorRate = (data.metrics.errors?.values?.rate || 0) * 100;
    lines.push(`║  Error Rate: ${errorRate.toFixed(2)}%                                        ║`);
    
    const avgDuration = data.metrics.http_req_duration?.values?.avg || 0;
    const p95Duration = data.metrics.http_req_duration?.values['p(95)'] || 0;
    lines.push(`║  Avg Response Time: ${avgDuration.toFixed(0)}ms                              ║`);
    lines.push(`║  P95 Response Time: ${p95Duration.toFixed(0)}ms                              ║`);
    
    lines.push('║                                                              ║');
    lines.push('║  Endpoint Performance:                                       ║');
    
    const coursesAvg = data.metrics.courses_index_duration?.values?.avg || 0;
    lines.push(`║    - Courses Index: ${coursesAvg.toFixed(0)}ms avg                           ║`);
    
    const showAvg = data.metrics.course_show_duration?.values?.avg || 0;
    lines.push(`║    - Course Show: ${showAvg.toFixed(0)}ms avg                              ║`);
    
    const apiAvg = data.metrics.progress_api_duration?.values?.avg || 0;
    lines.push(`║    - Progress API: ${apiAvg.toFixed(0)}ms avg                              ║`);
    
    lines.push('║                                                              ║');
    lines.push('╚══════════════════════════════════════════════════════════════╝');
    lines.push('\n');
    
    return lines.join('\n');
}
