<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = $app->make('db');

$user = $db->table('users')->where('name', 'Harry2')->first();

if (!$user) {
    echo json_encode(['error' => 'USER_NOT_FOUND']);
    exit(0);
}

$sessions = $db->table('learning_sessions')
    ->where('user_id', $user->id)
    ->select(
        'id',
        'content_id',
        'session_start',
        'session_end',
        'total_duration_minutes',
        'active_playback_time',
        'video_completion_percentage',
        'video_watch_time',
        'video_total_duration',
        'video_skip_count',
        'seek_count',
        'pause_count',
        'video_replay_count',
        'attention_score',
        'cheating_score',
        'is_within_allowed_time',
        'is_suspicious_activity'
    )
    ->orderByDesc('session_start')
    ->limit(10)
    ->get();

echo json_encode(['user' => $user, 'sessions' => $sessions], JSON_PRETTY_PRINT);
