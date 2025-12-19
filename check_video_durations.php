<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VIDEO CONTENT DURATION CHECK ===\n\n";

$videos = DB::table('module_content')
    ->where('content_type', 'video')
    ->select('id', 'title', 'duration', 'file_url')
    ->limit(10)
    ->get();

echo "📹 SAMPLE VIDEOS:\n";
echo str_repeat("-", 40) . "\n";

foreach ($videos as $video) {
    echo "ID: {$video->id}\n";
    echo "├─ Title: {$video->title}\n";
    echo "├─ Duration: " . ($video->duration ?? 'NULL') . "\n";
    echo "└─ File URL: " . ($video->file_url ? 'EXISTS' : 'NULL') . "\n\n";
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

if ($videosWithDuration > 0) {
    echo "\n✅ Some videos have duration data!\n";
    
    $videosWithDurationSample = DB::table('module_content')
        ->where('content_type', 'video')
        ->whereNotNull('duration')
        ->where('duration', '>', 0)
        ->select('id', 'title', 'duration')
        ->limit(5)
        ->get();
    
    echo "\n📹 VIDEOS WITH DURATION:\n";
    foreach ($videosWithDurationSample as $video) {
        echo "ID: {$video->id} - {$video->title} - {$video->duration}s (" . round($video->duration/60, 1) . "m)\n";
    }
} else {
    echo "\n❌ NO videos have duration data!\n";
    echo "This is why attention scores are defaulting to ~50%\n";
}

echo "\n✅ Check completed!\n";