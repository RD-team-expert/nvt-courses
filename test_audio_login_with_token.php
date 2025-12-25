<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Audio;

echo "🧪 Testing Audio Login Link with Token\n";
echo str_repeat("=", 60) . "\n\n";

// Get test user and audio
$user = User::find(122); // Maria
$audio = Audio::find(5); // test audio

if (!$user || !$audio) {
    echo "❌ User or audio not found\n";
    exit(1);
}

echo "👤 User: {$user->name} (ID: {$user->id})\n";
echo "📧 Email: {$user->email}\n";
echo "🎧 Audio: {$audio->name} (ID: {$audio->id})\n\n";

// Generate login link using the User model method
echo "🔗 Generating login link...\n";
$loginLink = $user->generateAudioLoginLink($audio->id);

echo "✅ Link generated:\n";
echo "   {$loginLink}\n\n";

// Parse the URL
$parsedUrl = parse_url($loginLink);
parse_str($parsedUrl['query'] ?? '', $queryParams);

echo "📋 URL Components:\n";
echo "   Path: {$parsedUrl['path']}\n";
echo "   Has token: " . (isset($queryParams['token']) ? '✅ YES' : '❌ NO') . "\n";
echo "   Has expires: " . (isset($queryParams['expires']) ? '✅ YES' : '❌ NO') . "\n";
echo "   Has signature: " . (isset($queryParams['signature']) ? '✅ YES' : '❌ NO') . "\n\n";

// Check database
$user->refresh();
echo "💾 Database Check:\n";
echo "   Token stored: " . ($user->login_token ? '✅ YES' : '❌ NO') . "\n";
echo "   Token expires at: " . ($user->login_token_expires_at ? $user->login_token_expires_at : 'Not set') . "\n\n";

// Extract user and audio IDs from path
if (preg_match('#/login/audio-token/(\d+)/(\d+)#', $parsedUrl['path'], $matches)) {
    $extractedUserId = $matches[1];
    $extractedAudioId = $matches[2];
    
    echo "🎯 Path Parameters:\n";
    echo "   User ID: {$extractedUserId} " . ($extractedUserId == $user->id ? '✅' : '❌') . "\n";
    echo "   Audio ID: {$extractedAudioId} " . ($extractedAudioId == $audio->id ? '✅' : '❌') . "\n\n";
}

if (isset($queryParams['token']) && $user->login_token) {
    echo "✅ Login link is COMPLETE and should work!\n";
} else {
    echo "❌ Login link is MISSING token or database entry!\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
