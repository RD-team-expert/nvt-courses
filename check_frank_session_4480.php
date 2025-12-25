<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$session = DB::table('learning_sessions')->where('id', 4480)->first();

echo "Session 4480 (Frank's session):\n";
echo "active_playback_time: {$session->active_playback_time}\n";
echo "Type: " . gettype($session->active_playback_time) . "\n";

// Get video duration
$content = DB::table('module_content')->where('id', $session->content_id)->first();
if ($content) {
    $videoDurationSeconds = $content->duration;
    $videoDurationMinutes = round($videoDurationSeconds / 60, 2);
    echo "Video duration: {$videoDurationMinutes} minutes ({$videoDurationSeconds} seconds)\n";
    
    echo "\nIf active_playback_time is in SECONDS:\n";
    echo "  {$session->active_playback_time} seconds = " . round($session->active_playback_time / 60, 2) . " minutes\n";
    echo "  Ratio to video: " . round(($session->active_playback_time / 60) / $videoDurationMinutes, 2) . "x\n";
    
    echo "\nIf active_playback_time is in MINUTES:\n";
    echo "  {$session->active_playback_time} minutes\n";
    echo "  Ratio to video: " . round($session->active_playback_time / $videoDurationMinutes, 2) . "x\n";
}

// Check session timestamps
echo "\nSession timestamps:\n";
echo "Start: {$session->session_start}\n";
echo "End: " . ($session->session_end ?? 'NULL') . "\n";

if ($session->session_start && $session->session_end) {
    $start = new DateTime($session->session_start);
    $end = new DateTime($session->session_end);
    $diff = $end->getTimestamp() - $start->getTimestamp();
    $minutes = round($diff / 60, 2);
    echo "Session duration: {$minutes} minutes\n";
}
