<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DATABASE ANALYSIS ===\n\n";

$total = DB::table('learning_sessions')->count();
echo "Total Sessions: {$total}\n\n";

$withActivePlayback = DB::table('learning_sessions')->where('active_playback_time', '>', 0)->count();
echo "Sessions with active_playback_time > 0: {$withActivePlayback}\n";

$withSessionEnd = DB::table('learning_sessions')->whereNotNull('session_end')->count();
echo "Sessions with session_end: {$withSessionEnd}\n";

$withVideoDuration = DB::table('learning_sessions as ls')
    ->join('module_content as mc', 'ls.content_id', '=', 'mc.id')
    ->where('mc.duration', '>', 0)
    ->count();
echo "Sessions with video duration > 0: {$withVideoDuration}\n";

$needsBackup = DB::table('learning_sessions')
    ->where('active_playback_time', '=', 0)
    ->whereNull('session_end')
    ->count();
echo "Sessions needing backup calculation: {$needsBackup}\n\n";

echo "=== BREAKDOWN ===\n";
echo "Priority 1 (Active Playback): {$withActivePlayback} sessions\n";
echo "Priority 2 (Session End): " . ($withSessionEnd - $withActivePlayback) . " sessions\n";
echo "Priority 3+ (Backup/Other): {$needsBackup} sessions\n\n";

// Sample sessions needing backup
echo "=== SAMPLE SESSIONS NEEDING BACKUP ===\n";
$samples = DB::table('learning_sessions as ls')
    ->leftJoin('users as u', 'ls.user_id', '=', 'u.id')
    ->leftJoin('course_online as co', 'ls.course_online_id', '=', 'co.id')
    ->where('ls.active_playback_time', '=', 0)
    ->whereNull('ls.session_end')
    ->select('ls.id', 'u.name as user_name', 'co.name as course_name', 'ls.session_start', 'ls.content_id')
    ->limit(5)
    ->get();

foreach ($samples as $sample) {
    echo "\nSession {$sample->id}:\n";
    echo "  User: {$sample->user_name}\n";
    echo "  Course: {$sample->course_name}\n";
    echo "  Start: {$sample->session_start}\n";
    echo "  Content ID: {$sample->content_id}\n";
    
    // Check if has completion data
    $progress = DB::table('user_content_progress')
        ->where('content_id', $sample->content_id)
        ->where('is_completed', true)
        ->whereNotNull('completed_at')
        ->first();
    
    if ($progress) {
        echo "  ✅ Has completion timestamp: {$progress->completed_at}\n";
    } else {
        echo "  ❌ No completion data\n";
    }
}
