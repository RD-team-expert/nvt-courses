<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Audio;
use App\Events\AudioAssigned;
use Illuminate\Support\Facades\Log;

echo "🧪 Testing Single Email Dispatch\n";
echo str_repeat("=", 60) . "\n\n";

// Get test user and audio
$user = User::where('id', 122)->first(); // Maria
$audio = Audio::find(5); // test audio

if (!$user || !$audio) {
    echo "❌ User or audio not found\n";
    exit(1);
}

echo "👤 User: {$user->name} (ID: {$user->id})\n";
echo "🎧 Audio: {$audio->name} (ID: {$audio->id})\n\n";

echo "📧 Dispatching AudioAssigned event...\n";

// Dispatch the event
AudioAssigned::dispatch(
    $audio,
    $user,
    'http://test-link.com',
    User::find(1), // admin user
    [
        'assignment_type' => 'audio',
        'assignment_id' => 999,
        'skip_manager_notification' => true,
    ]
);

echo "✅ Event dispatched\n";
echo "\n📋 Check the logs to see if the event was handled only ONCE\n";
echo "   Look for: '🔔 AudioAssigned event received'\n";
echo "   Expected: 1 occurrence\n";
echo "   Previous bug: 2 occurrences\n\n";

echo str_repeat("=", 60) . "\n";
