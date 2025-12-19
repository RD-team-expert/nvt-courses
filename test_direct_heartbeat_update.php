<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING DIRECT DATABASE UPDATE ===\n\n";

// Try to directly update the last_heartbeat field
try {
    echo "Attempting direct update of last_heartbeat field...\n";
    
    $result = DB::table('learning_sessions')
        ->where('id', 4466)
        ->update(['last_heartbeat' => now()]);
    
    echo "Direct update result: {$result} row(s) affected\n";
    
    // Check if it was saved
    $session = DB::table('learning_sessions')->where('id', 4466)->first();
    echo "Last heartbeat after direct update: " . ($session->last_heartbeat ?? 'NULL') . "\n\n";
    
    if ($session->last_heartbeat) {
        echo "✅ Direct database update WORKS!\n";
        echo "The issue is in the LearningSessionService update method.\n";
    } else {
        echo "❌ Direct database update FAILED!\n";
        echo "There's a database-level issue with the last_heartbeat field.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Direct update failed: " . $e->getMessage() . "\n";
}

echo "\n✅ Direct update test completed!\n";