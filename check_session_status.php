<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING SESSION STATUS ===\n\n";

$session4466 = DB::table('learning_sessions')->where('id', 4466)->first();
echo "Session 4466:\n";
echo "Session End: " . ($session4466->session_end ?? 'NULL') . "\n";
echo "Attention Score: " . ($session4466->attention_score ?? 'NULL') . "\n\n";

$session4461 = DB::table('learning_sessions')->where('id', 4461)->first();
echo "Session 4461:\n";
echo "Session End: " . ($session4461->session_end ?? 'NULL') . "\n";
echo "Attention Score: " . ($session4461->attention_score ?? 'NULL') . "\n\n";

// Check if there are any recent sessions with calculated attention scores
$recentWithScores = DB::table('learning_sessions')
    ->where('created_at', '>=', now()->subDays(1))
    ->whereNotNull('attention_score')
    ->where('attention_score', '>', 0)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentWithScores->isEmpty()) {
    echo "❌ No recent sessions with calculated attention scores found!\n";
    echo "This means sessions are not being ended properly.\n";
} else {
    echo "✅ Found recent sessions with attention scores:\n";
    foreach ($recentWithScores as $session) {
        echo "Session {$session->id}: {$session->attention_score}% (ended: {$session->session_end})\n";
    }
}

echo "\n✅ Status check completed!\n";