<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING FOR ANY RECENT HEARTBEATS ===\n\n";

$recentWithHeartbeat = DB::table('learning_sessions')
    ->whereNotNull('last_heartbeat')
    ->where('created_at', '>=', now()->subDays(1))
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentWithHeartbeat->isEmpty()) {
    echo "❌ NO recent sessions with heartbeats found!\n";
    echo "This confirms the heartbeat system is completely broken.\n\n";
    
    // Check if ANY session EVER had a heartbeat
    $anyHeartbeat = DB::table('learning_sessions')
        ->whereNotNull('last_heartbeat')
        ->count();
    
    echo "Total sessions with heartbeats in entire database: {$anyHeartbeat}\n";
    
} else {
    echo "✅ Found recent sessions with heartbeats:\n";
    foreach ($recentWithHeartbeat as $session) {
        echo "Session {$session->id}: {$session->last_heartbeat}\n";
    }
}

echo "\n🔍 NEXT STEPS:\n";
echo "1. Check browser console for JavaScript errors\n";
echo "2. Check network tab for failed heartbeat requests\n";
echo "3. Check Laravel logs for backend errors\n";
echo "4. Verify CSRF token is working\n";

echo "\n✅ Heartbeat check completed!\n";