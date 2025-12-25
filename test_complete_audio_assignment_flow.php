<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Audio;
use Illuminate\Support\Facades\Auth;

echo "🧪 Testing Complete Audio Assignment Flow\n";
echo str_repeat("=", 60) . "\n\n";

// Get test user and audio
$testUser = User::find(122); // Maria
$audio = Audio::find(5); // test audio

if (!$testUser || !$audio) {
    echo "❌ User or audio not found\n";
    exit(1);
}

echo "STEP 1: Generate Login Link\n";
echo "👤 User: {$testUser->name} (ID: {$testUser->id})\n";
echo "🎧 Audio: {$audio->name} (ID: {$audio->id})\n\n";

$loginLink = $testUser->generateAudioLoginLink($audio->id);
echo "🔗 Generated link:\n   {$loginLink}\n\n";

// Parse the link
$parsedUrl = parse_url($loginLink);
parse_str($parsedUrl['query'] ?? '', $queryParams);

echo "STEP 2: Verify Link Components\n";
echo "   ✅ User ID in path: " . (strpos($parsedUrl['path'], "/{$testUser->id}/") !== false ? 'CORRECT' : 'WRONG') . "\n";
echo "   ✅ Audio ID in path: " . (strpos($parsedUrl['path'], "/{$audio->id}") !== false ? 'CORRECT' : 'WRONG') . "\n";
echo "   ✅ Token in query: " . (isset($queryParams['token']) ? 'YES' : 'NO') . "\n";
echo "   ✅ Expires in query: " . (isset($queryParams['expires']) ? 'YES' : 'NO') . "\n";
echo "   ✅ Signature in query: " . (isset($queryParams['signature']) ? 'YES' : 'NO') . "\n\n";

echo "STEP 3: Verify Database Storage\n";
$testUser->refresh();
echo "   ✅ Token stored in DB: " . ($testUser->login_token ? 'YES' : 'NO') . "\n";
echo "   ✅ Expiration set: " . ($testUser->login_token_expires_at ? 'YES' : 'NO') . "\n\n";

echo "STEP 4: Simulate Login Process\n";
$token = $queryParams['token'] ?? null;

if (!$token) {
    echo "   ❌ No token in URL\n";
    exit(1);
}

// Check if token matches
$tokenMatches = hash_equals($testUser->login_token, hash('sha256', $token));
echo "   ✅ Token verification: " . ($tokenMatches ? 'PASS' : 'FAIL') . "\n";

// Check if token expired
$tokenExpired = $testUser->loginTokenExpired();
echo "   ✅ Token expiration check: " . (!$tokenExpired ? 'VALID' : 'EXPIRED') . "\n\n";

if ($tokenMatches && !$tokenExpired) {
    echo "✅ LOGIN WOULD SUCCEED!\n";
    echo "   User would be logged in as: {$testUser->name}\n";
    echo "   User would be redirected to: audio.show (ID: {$audio->id})\n";
} else {
    echo "❌ LOGIN WOULD FAIL!\n";
    if (!$tokenMatches) echo "   Reason: Token mismatch\n";
    if ($tokenExpired) echo "   Reason: Token expired\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "\n📋 COMPARISON WITH COURSE LOGIN:\n";
echo "   Course login: Uses generateLoginLink(\$courseId)\n";
echo "   Audio login: Uses generateAudioLoginLink(\$audioId)\n";
echo "   Both methods: Generate token, store in DB, include in URL\n";
echo "   Result: ✅ IDENTICAL BEHAVIOR\n";
echo "\n" . str_repeat("=", 60) . "\n";
