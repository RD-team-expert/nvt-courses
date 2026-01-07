<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = $app->make('db');

$sessions = $db->table('learning_sessions')->select('id','session_start','session_end','active_playback_time','total_duration_minutes','content_id')->get();

$totalMinutes = 0;
$count = 0;
foreach ($sessions as $s) {
    $minutes = 0;
    if ($s->active_playback_time && $s->active_playback_time > 0) {
        $minutes = $s->active_playback_time / 60;
    } elseif ($s->session_start && $s->session_end) {
        $start = new DateTime($s->session_start);
        $end = new DateTime($s->session_end);
        $diff = $end->getTimestamp() - $start->getTimestamp();
        $minutes = max(0, floor($diff / 60));
    } elseif ($s->total_duration_minutes) {
        $minutes = $s->total_duration_minutes;
    }

    if ($minutes > 0) {
        $totalMinutes += $minutes;
        $count++;
    }
}

$avg = $count > 0 ? round($totalMinutes / $count, 1) : 0;
echo json_encode(['count'=>$count,'avg_minutes'=>$avg,'total_minutes'=>$totalMinutes], JSON_PRETTY_PRINT);
