<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Audio;
use App\Services\ManagerHierarchyService;

echo "🔍 Debugging Audio Assignment Email Flow\n";
echo str_repeat("=", 60) . "\n\n";

// Check user 132
$userId = 132;
$user = User::with('department')->find($userId);

if (!$user) {
    echo "❌ User {$userId} not found\n";
    exit(1);
}

echo "👤 User Information:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   Role: {$user->role}\n";
echo "   Department: " . ($user->department ? $user->department->name : 'None') . "\n\n";

// Get managers for this user
try {
    $managerService = app(ManagerHierarchyService::class);
    $managers = $managerService->getDirectManagersForUser($user->id);
    
    echo "👥 Managers for this user:\n";
    if (empty($managers)) {
        echo "   No managers found\n";
    } else {
        foreach ($managers as $managerData) {
            $manager = $managerData['manager'];
            echo "   - {$manager->name} (ID: {$manager->id}, Email: {$manager->email})\n";
            echo "     Relationship: {$managerData['relationship']}\n";
            echo "     Level: {$managerData['level']}\n";
            
            if ($manager->id === $user->id) {
                echo "     ⚠️ WARNING: User is their own manager!\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error getting managers: {$e->getMessage()}\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "\n📧 Expected Email Flow:\n";
echo "   1. User notification sent to: {$user->email}\n";
echo "   2. Manager notification(s) sent to: ";

if (empty($managers)) {
    echo "None (no managers)\n";
} else {
    $managerEmails = [];
    foreach ($managers as $managerData) {
        $manager = $managerData['manager'];
        if ($manager->id !== $user->id) {
            $managerEmails[] = $manager->email;
        }
    }
    
    if (empty($managerEmails)) {
        echo "None (user is self-managed)\n";
    } else {
        echo implode(', ', $managerEmails) . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
