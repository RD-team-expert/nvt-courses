/**
 * k6 Load Test: Progress Tracking API
 * 
 * Tests the progress tracking endpoint under load:
 * - Multiple users updating progress simultaneously
 * - Various completion percentages
 * - Rapid progress updates
 * 
 * Run: k6 run tests/k6/progress-tracking-test.js
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Counter, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const progressUpdates = new Counter('progress_updates');
const progressTime = new Trend('progress_response_time');
const completions = new Counter('completions');

// Configuration
const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const CONTENT_ID = __ENV.CONTENT_ID || '1'; // Change to valid content ID

export const options = {
    scenarios: {
        // Constant rate of progress updates
        constant_progress: {
            executor: 'constant-arrival-rate',
            rate: 50,              // 50 requests per second
            timeUnit: '1s',
            duration: '2m',
            preAllocatedVUs: 30,
            maxVUs: 100,
        },
        // Spike test
        spike_test: {
            executor: 'ramping-arrival-rate',
            startRate: 10,
            timeUnit: '1s',
            preAllocatedVUs: 50,
            maxVUs: 200,
            stages: [
                { duration: '30s', target: 10 },
                { duration: '30s', target: 100 }, // Spike!
                { duration: '1m', target: 100 },
                { duration: '30s', target: 10 },
            ],
            startTime: '2m30s', // Start after constant test
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<500'],   // 95% under 500ms
        errors: ['rate<0.05'],               // Error rate under 5%
        progress_response_time: ['avg<300'], // Average under 300ms
    },
};

// Session cookie
const SESSION_COOKIE = __ENV.SESSION_COOKIE || 'laravel_session=YOUR_SESSION_COOKIE_HERE';

function getCsrfToken() {
    const res = http.get(`${BASE_URL}/dashboard`, {
        headers: { 'Cookie': SESSION_COOKIE },
    });

    const match = res.body.match(/name="csrf-token" content="([^"]+)"/);
    return match ? match[1] : '';
}

export function setup() {
    console.log(`Testing progress tracking at ${BASE_URL}`);
    console.log(`Content ID: ${CONTENT_ID}`);

    const csrfToken = getCsrfToken();

    return { contentId: CONTENT_ID, csrfToken: csrfToken };
}

export default function (data) {
    const headers = {
        'Cookie': SESSION_COOKIE,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': data.csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
    };

    // Simulate a video progress update
    const currentPosition = Math.random() * 300; // 0-300 seconds
    const completionPercentage = Math.min(100, Math.random() * 100);
    const watchTime = Math.floor(Math.random() * 10) + 1;

    const payload = JSON.stringify({
        current_position: currentPosition,
        completion_percentage: completionPercentage,
        watch_time: watchTime,
    });

    const res = http.post(
        `${BASE_URL}/content/${data.contentId}/progress`,
        payload,
        { headers: headers, timeout: '5s' }
    );

    const success = check(res, {
        'progress update status 200': (r) => r.status === 200,
        'progress update has success': (r) => {
            try {
                const body = JSON.parse(r.body);
                return body.success === true;
            } catch (e) {
                return false;
            }
        },
    });

    errorRate.add(!success);

    if (success) {
        progressUpdates.add(1);
        progressTime.add(res.timings.duration);

        // Check if marked as completed
        try {
            const body = JSON.parse(res.body);
            if (body.is_completed) {
                completions.add(1);
            }
        } catch (e) { }
    }

    // Small sleep between iterations
    sleep(0.1);
}

export function teardown(data) {
    console.log('Progress tracking load test completed');
}
