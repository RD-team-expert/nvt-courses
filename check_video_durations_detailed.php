<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DETAILED VIDEO DURATION CHECK ===\n\n";

// Check videos table
echo "📹 VIDEOS TABLE:\n";
echo str_repeat("-", 40) . "\n";

$videos = DB::table('videos')
    ->select('id', 'name', 'duration', 'created_at')
    ->limit(10)
    ->get();

foreach ($videos as $video) {
    echo "Video ID: {$video->id}\n";
    echo "├─ Name: {$video->name}\n";
    echo "├─ Duration: " . ($video->duration ?? 'NULL') . " seconds\n";
    echo "└─ Created: {$video->created_at}\n\n";
}

$totalVideos = DB::table('videos')->count();
$videosWithDuration = DB::table('videos')
    ->whereNotNull('duration')
    ->where('duration', '>', 0)
    ->count();

echo "📊 VIDEOS SUMMARY:\n";
echo "Total Videos: {$totalVideos}\n";
echo "Videos with Duration: {$videosWithDuration}\n";
echo "Videos WITHOUT Duration: " . ($totalVideos - $videosWithDuration) . "\n\n";

// Check module_content table
echo "📄 MODULE_CONTENT TABLE:\n";
echo str_repeat("-", 40) . "\n";

$moduleContents = DB::table('module_content')
    ->where('content_type', 'video')
    ->select('id', 'title', 'video_id', 'duration', 'created_at')
    ->limit(10)
    ->get();

foreach ($moduleContents as $content) {
    echo "Content ID: {$content->id}\n";
    echo "├─ Title: {$content->title}\n";
    echo "├─ Video ID: " . ($content->video_id ?? 'NULL') . "\n";
    echo "├─ Duration: " . ($content->duration ?? 'NULL') . " seconds\n";
    echo "└─ Created: {$content->created_at}\n\n";
}

$totalModuleContent = DB::table('module_content')->where('content_type', 'video')->count();
$moduleContentWithDuration = DB::table('module_content')
    ->where('content_type', 'video')
    ->whereNotNull('duration')
    ->where('duration', '>', 0)
    ->count();

echo "📊 MODULE_CONTENT SUMMARY:\n";
echo "Total Video Content: {$totalModuleContent}\n";
echo "Content with Duration: {$moduleContentWithDuration}\n";
echo "Content WITHOUT Duration: " . ($totalModuleContent - $moduleContentWithDuration) . "\n\n";

// Check relationship between videos and module_content
echo "🔗 VIDEO-CONTENT RELATIONSHIP CHECK:\n";
echo str_repeat("-", 40) . "\n";

$relationships = DB::table('module_content as mc')
    ->join('videos as v', 'mc.video_id', '=', 'v.id')
    ->where('mc.content_type', 'video')
    ->select('mc.id as content_id', 'mc.title', 'mc.duration as content_duration', 
             'v.id as video_id', 'v.name', 'v.duration as video_duration')
    ->limit(10)
    ->get();

foreach ($relationships as $rel) {
    echo "Content: {$rel->title} (ID: {$rel->content_id})\n";
    echo "├─ Video: {$rel->name} (ID: {$rel->video_id})\n";
    echo "├─ Video Duration: " . ($rel->video_duration ?? 'NULL') . " seconds\n";
    echo "├─ Content Duration: " . ($rel->content_duration ?? 'NULL') . " seconds\n";
    echo "└─ Match: " . (($rel->video_duration == $rel->content_duration) ? '✅ YES' : '❌ NO') . "\n\n";
}

echo "✅ Check completed!\n";