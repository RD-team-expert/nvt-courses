/**
 * k6 Load Test: Video Streaming Endpoint
 * 
 * Tests the local video streaming under load with:
 * - Concurrent users streaming videos
 * - Byte-range requests (video seeking)
 * 
 * HOW TO GET YOUR SESSION COOKIE:
 * 1. Login to your app in browser
 * 2. Open DevTools (F12) → Application tab → Cookies
 * 3. Copy the full cookie string (laravel_session=eyJ...)
 * 4. Set it: $env:SESSION_COOKIE = "laravel_session=YOUR_VALUE"
 * 
 * Run:
 * $env:VIDEO_ID = "1"
 * $env:SESSION_COOKIE = "laravel_session=YOUR_COOKIE_VALUE"
 * k6 run tests/k6/video-stream-load-test.js
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');
const byteRangeTime = new Trend('byte_range_response_time');

// Configuration
const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const VIDEO_ID = __ENV.VIDEO_ID || '1';

// ⚠️ REDUCED LOAD for local testing with php artisan serve
// php artisan serve is single-threaded, can only handle ~5-10 concurrent requests
export const options = {
    stages: [
        { duration: '10s', target: 3 },   // Ramp up to 3 users
        { duration: '30s', target: 5 },   // Ramp up to 5 users
        { duration: '30s', target: 5 },   // Stay at 5 users
        { duration: '10s', target: 10 },  // Light spike to 10 users
        { duration: '20s', target: 10 },  // Stay at 10 users
        { duration: '10s', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<5000'],  // 95% of requests under 5s (relaxed for local)
        errors: ['rate<0.3'],                // Error rate under 30% (relaxed for local)
    },
};

// Session cookie - MUST BE SET!
const SESSION_COOKIE = __ENV.SESSION_COOKIE || '';

export function setup() {
    console.log(`\n========================================`);
    console.log(`Testing video streaming at ${BASE_URL}`);
    console.log(`Video ID: ${VIDEO_ID}`);

    if (!SESSION_COOKIE || SESSION_COOKIE === '' || SESSION_COOKIE === 'laravel_session=YOUR_SESSION_COOKIE_HERE') {
        console.log(`\n⚠️  WARNING: SESSION_COOKIE not set!`);
        console.log(`   You will see redirect errors to /login`);
        console.log(`\n   To fix, run:`);
        console.log(`   $env:SESSION_COOKIE = "laravel_session=YOUR_ACTUAL_COOKIE"`);
        console.log(`========================================\n`);
    } else {
        console.log(`Session Cookie: Set ✓`);
    }

    // Quick connectivity test
    const res = http.get(`${BASE_URL}/video/stream/${VIDEO_ID}`, {
        headers: { 'Cookie': SESSION_COOKIE },
        timeout: '15s',
        redirects: 0,  // Don't follow redirects
    });

    if (res.status === 302 || res.status === 301) {
        console.log(`\n❌ REDIRECT detected (status ${res.status})`);
        console.log(`   You need to set a valid SESSION_COOKIE`);
        console.log(`   Location: ${res.headers['Location']}`);
    } else if (res.status === 200 || res.status === 206) {
        console.log(`✓ Video accessible (status ${res.status})`);
    } else {
        console.log(`⚠️ Unexpected status: ${res.status}`);
    }

    console.log(`========================================\n`);

    return { videoId: VIDEO_ID };
}

export default function (data) {
    const headers = {
        'Cookie': SESSION_COOKIE,
    };

    // Test 1: Full video request (initial load)
    const fullRes = http.get(`${BASE_URL}/video/stream/${data.videoId}`, {
        headers: headers,
        timeout: '30s',
        redirects: 0,  // Don't follow redirects - we want to detect auth failures
    });

    const isRedirect = fullRes.status === 302 || fullRes.status === 301;

    check(fullRes, {
        'not redirected to login': (r) => !isRedirect,
        'full request status is 200': (r) => r.status === 200,
        'full request has content-type': (r) => r.headers['Content-Type']?.includes('video'),
        'full request has accept-ranges': (r) => r.headers['Accept-Ranges'] === 'bytes',
    });

    errorRate.add(fullRes.status !== 200);
    responseTime.add(fullRes.timings.duration);

    // If redirected, skip the rest (auth failed)
    if (isRedirect) {
        sleep(2);
        return;
    }

    sleep(1);

    // Test 2: Byte-range request (simulating video seek)
    const rangeHeaders = {
        ...headers,
        'Range': 'bytes=0-1048575', // First 1MB
    };

    const rangeRes = http.get(`${BASE_URL}/video/stream/${data.videoId}`, {
        headers: rangeHeaders,
        timeout: '10s',
        redirects: 0,
    });

    check(rangeRes, {
        'range request status is 206': (r) => r.status === 206,
        'range request has content-range': (r) => r.headers['Content-Range'] !== undefined,
    });

    errorRate.add(rangeRes.status !== 206);
    byteRangeTime.add(rangeRes.timings.duration);

    sleep(1);

    // Test 3: Random seek position
    const randomStart = Math.floor(Math.random() * 5000000); // 0-5MB
    const randomEnd = randomStart + 512000; // 512KB chunk

    const seekRes = http.get(`${BASE_URL}/video/stream/${data.videoId}`, {
        headers: {
            ...headers,
            'Range': `bytes=${randomStart}-${randomEnd}`,
        },
        timeout: '10s',
        redirects: 0,
    });

    check(seekRes, {
        'seek request successful': (r) => r.status === 206 || r.status === 416,
    });

    if (seekRes.status !== 206 && seekRes.status !== 416) {
        errorRate.add(1);
    }

    sleep(Math.random() * 2 + 1); // Random sleep 1-3 seconds
}

export function teardown(data) {
    console.log('\n========================================');
    console.log('Video streaming load test completed');
    console.log('========================================\n');
}
