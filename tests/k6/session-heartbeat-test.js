/**
 * k6 Load Test: Session & Heartbeat API
 * 
 * Tests the session management endpoints under load:
 * - Session start/end lifecycle
 * - Heartbeat calls (every 10 seconds per user)
 * - Multiple concurrent sessions
 * 
 * Run: k6 run tests/k6/session-heartbeat-test.js
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Counter, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const sessionStarts = new Counter('session_starts');
const heartbeats = new Counter('heartbeats');
const sessionEnds = new Counter('session_ends');
const heartbeatTime = new Trend('heartbeat_response_time');

// Configuration
const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const CONTENT_ID = __ENV.CONTENT_ID || '1'; // Change to valid content ID

export const options = {
    scenarios: {
        // Simulate realistic user behavior
        realistic_sessions: {
            executor: 'ramping-vus',
            startVUs: 1,
            stages: [
                { duration: '30s', target: 20 },  // Ramp up
                { duration: '2m', target: 20 },   // Sustain
                { duration: '1m', target: 40 },   // Peak
                { duration: '1m', target: 40 },   // Sustain peak
                { duration: '30s', target: 0 },   // Ramp down
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<1000'],  // 95% under 1s
        errors: ['rate<0.05'],               // Error rate under 5%
    },
};

// Session cookie
const SESSION_COOKIE = __ENV.SESSION_COOKIE || 'laravel_session=YOUR_SESSION_COOKIE_HERE';

// Get CSRF token (required for POST requests)
function getCsrfToken() {
    const res = http.get(`${BASE_URL}/dashboard`, {
        headers: { 'Cookie': SESSION_COOKIE },
    });

    const match = res.body.match(/name="csrf-token" content="([^"]+)"/);
    return match ? match[1] : '';
}

export function setup() {
    console.log(`Testing session/heartbeat at ${BASE_URL}`);
    console.log(`Content ID: ${CONTENT_ID}`);

    const csrfToken = getCsrfToken();
    console.log(`CSRF Token obtained: ${csrfToken ? 'Yes' : 'No'}`);

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

    let sessionId = null;

    group('Session Lifecycle', function () {
        // 1. Start session
        const startPayload = JSON.stringify({
            action: 'start',
            position: 0,
            api_key_id: null,
        });

        const startRes = http.post(
            `${BASE_URL}/content/${data.contentId}/session`,
            startPayload,
            { headers: headers, timeout: '10s' }
        );

        const startSuccess = check(startRes, {
            'session start status 200': (r) => r.status === 200,
            'session start has session_id': (r) => {
                try {
                    const body = JSON.parse(r.body);
                    sessionId = body.session_id;
                    return body.session_id !== undefined;
                } catch (e) {
                    return false;
                }
            },
        });

        errorRate.add(!startSuccess);
        if (startSuccess) sessionStarts.add(1);

        sleep(2);

        // 2. Send multiple heartbeats (simulating 1-3 minutes of watching)
        const heartbeatCount = Math.floor(Math.random() * 10) + 3; // 3-12 heartbeats

        for (let i = 0; i < heartbeatCount; i++) {
            const currentPosition = (i + 1) * 30; // Advance 30 seconds each heartbeat

            const heartbeatPayload = JSON.stringify({
                action: 'heartbeat',
                current_position: currentPosition,
                skip_count: Math.floor(Math.random() * 2),
                seek_count: Math.floor(Math.random() * 3),
                pause_count: Math.floor(Math.random() * 2),
                watch_time: 30,
            });

            const heartbeatRes = http.post(
                `${BASE_URL}/content/${data.contentId}/session`,
                heartbeatPayload,
                { headers: headers, timeout: '5s' }
            );

            const heartbeatSuccess = check(heartbeatRes, {
                'heartbeat status 200': (r) => r.status === 200,
            });

            errorRate.add(!heartbeatSuccess);
            if (heartbeatSuccess) {
                heartbeats.add(1);
                heartbeatTime.add(heartbeatRes.timings.duration);
            }

            // Simulate realistic heartbeat interval (shorter for testing)
            sleep(Math.random() * 3 + 2); // 2-5 seconds in test (10 min in prod)
        }

        // 3. End session
        const endPayload = JSON.stringify({
            action: 'end',
            current_position: heartbeatCount * 30,
            skip_count: 0,
            seek_count: 0,
            pause_count: 0,
            watch_time: 0,
        });

        const endRes = http.post(
            `${BASE_URL}/content/${data.contentId}/session`,
            endPayload,
            { headers: headers, timeout: '5s' }
        );

        const endSuccess = check(endRes, {
            'session end status 200': (r) => r.status === 200,
        });

        errorRate.add(!endSuccess);
        if (endSuccess) sessionEnds.add(1);
    });

    sleep(1);
}

export function teardown(data) {
    console.log('Session/Heartbeat load test completed');
}
