<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Audio;
use Illuminate\Support\Facades\URL;

echo "🔍 Testing Audio Login Link Generation\n";
echo str_repeat("=", 60) . "\n\n";

// Get a test user and audio
$user = User::where('role', '!=', 'admin')->first();
$audio = Audio::first();

if (!$user || !$audio) {
    echo "❌ No user or audio found for testing\n";
    exit(1);
}

echo "👤 Test User:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   Email: {$user->email}\n\n";

echo "🎧 Test Audio:\n";
echo "   ID: {$audio->id}\n";
echo "   Name: {$audio->name}\n\n";

// Generate login link
$loginUrl = URL::temporarySignedRoute(
    'auth.audio-token-login',
    now()->addHours(24),
    [
        'user' => $user->id,
        'audio' => $audio->id,
    ]
);

echo "🔗 Generated Login Link:\n";
echo "   {$loginUrl}\n\n";

// Parse the URL to verify parameters
$parsedUrl = parse_url($loginUrl);
$path = $parsedUrl['path'] ?? '';

echo "📋 URL Analysis:\n";
echo "   Path: {$path}\n";

// Extract user and audio IDs from path
if (preg_match('#/login/audio-token/(\d+)/(\d+)#', $path, $matches)) {
    $extractedUserId = $matches[1];
    $extractedAudioId = $matches[2];
    
    echo "   Extracted User ID: {$extractedUserId}\n";
    echo "   Extracted Audio ID: {$extractedAudioId}\n\n";
    
    if ($extractedUserId == $user->id && $extractedAudioId == $audio->id) {
        echo "✅ Link generation is CORRECT!\n";
        echo "   User ID matches: {$user->id} == {$extractedUserId}\n";
        echo "   Audio ID matches: {$audio->id} == {$extractedAudioId}\n";
    } else {
        echo "❌ Link generation is WRONG!\n";
        echo "   Expected User ID: {$user->id}, Got: {$extractedUserId}\n";
        echo "   Expected Audio ID: {$audio->id}, Got: {$extractedAudioId}\n";
    }
} else {
    echo "❌ Could not parse URL path\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
