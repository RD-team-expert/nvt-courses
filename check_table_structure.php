<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MODULE_CONTENT TABLE STRUCTURE ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM module_content');
foreach ($columns as $column) {
    echo "{$column->Field} - {$column->Type}\n";
}

echo "\n=== VIDEO CONTENT DURATION CHECK ===\n\n";

$videos = DB::table('module_content')
    ->where('content_type', 'video')
    ->select('id', 'title', 'duration')
    ->limit(10)
    ->get();

echo "📹 SAMPLE VIDEOS:\n";
echo str_repeat("-", 40) . "\n";

foreach ($videos as $video) {
    echo "ID: {$video->id}\n";
    echo "├─ Title: {$video->title}\n";
    echo "└─ Duration: " . ($video->duration ?? 'NULL') . "\n\n";
}

$totalVideos = DB::table('module_content')->where('content_type', 'video')->count();
$videosWithDuration = DB::table('module_content')
    ->where('content_type', 'video')
    ->whereNotNull('duration')
    ->where('duration', '>', 0)
    ->count();

echo "📊 SUMMARY:\n";
echo str_repeat("-", 20) . "\n";
echo "Total Videos: {$totalVideos}\n";
echo "Videos with Duration: {$videosWithDuration}\n";
echo "Videos WITHOUT Duration: " . ($totalVideos - $videosWithDuration) . "\n";

echo "\n✅ Check completed!\n";