<?php

/**
 * Quick verification script to check email template structure
 */

$userTemplate = file_get_contents('resources/views/emails/audio-assignment-notification.blade.php');
$managerTemplate = file_get_contents('resources/views/emails/audio-assignment-manager-notification.blade.php');

echo "=== User Assignment Email Template Verification ===\n\n";

// Check user template requirements
$userChecks = [
    'Audio name variable' => str_contains($userTemplate, '{{ $audioName }}'),
    'Audio description variable' => str_contains($userTemplate, '{{ $audioDescription }}'),
    'Audio duration variable' => str_contains($userTemplate, '{{ $audioDuration }}'),
    'Login link button' => str_contains($userTemplate, '{{ $loginLink }}'),
    'User name' => str_contains($userTemplate, '{{ $user->name }}'),
    'Assigned by name' => str_contains($userTemplate, '{{ $assignedBy->name }}'),
    'Responsive meta tag' => str_contains($userTemplate, 'viewport'),
    'Styled button' => str_contains($userTemplate, 'cta-button'),
];

foreach ($userChecks as $check => $passed) {
    echo ($passed ? '✓' : '✗') . " {$check}\n";
}

echo "\n=== Manager Notification Email Template Verification ===\n\n";

// Check manager template requirements
$managerChecks = [
    'Team member iteration' => str_contains($managerTemplate, '@foreach($teamMembers'),
    'Team member name' => str_contains($managerTemplate, '{{ $member->name }}'),
    'Team member email' => str_contains($managerTemplate, '{{ $member->email }}'),
    'Audio name variable' => str_contains($managerTemplate, '{{ $audioName }}'),
    'Audio description variable' => str_contains($managerTemplate, '{{ $audioDescription }}'),
    'Audio duration variable' => str_contains($managerTemplate, '{{ $audioDuration }}'),
    'Manager name' => str_contains($managerTemplate, '{{ $manager->name }}'),
    'Assigned by name' => str_contains($managerTemplate, '{{ $assignedBy->name }}'),
    'Assignment date' => str_contains($managerTemplate, 'Assignment Date'),
    'Responsive meta tag' => str_contains($managerTemplate, 'viewport'),
];

foreach ($managerChecks as $check => $passed) {
    echo ($passed ? '✓' : '✗') . " {$check}\n";
}

echo "\n=== Summary ===\n";
$totalChecks = count($userChecks) + count($managerChecks);
$passedChecks = count(array_filter($userChecks)) + count(array_filter($managerChecks));

echo "Passed: {$passedChecks}/{$totalChecks} checks\n";

if ($passedChecks === $totalChecks) {
    echo "\n✓ All email templates meet the requirements!\n";
    exit(0);
} else {
    echo "\n✗ Some checks failed. Please review the templates.\n";
    exit(1);
}
