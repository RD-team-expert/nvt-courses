<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING MODULE CONTENT DURATIONS ===\n\n";

// Get all video content that has NULL duration but has a linked video with duration
$contentToFix = DB::table('module_content as mc')
    ->join('videos as v', 'mc.video_id', '=', 'v.id')
    ->where('mc.content_type', 'video')
    ->whereNull('mc.duration')
    ->whereNotNull('v.duration')
    ->where('v.duration', '>', 0)
    ->select('mc.id as content_id', 'mc.title', 'v.id as video_id', 'v.name as video_name', 'v.duration')
    ->get();

if ($contentToFix->isEmpty()) {
    echo "✅ No content needs fixing!\n";
    exit;
}

echo "🔧 FOUND " . count($contentToFix) . " CONTENT ITEMS TO FIX:\n";
echo str_repeat("-", 50) . "\n";

foreach ($contentToFix as $content) {
    echo "Content: {$content->title} (ID: {$content->content_id})\n";
    echo "├─ Video: {$content->video_name} (ID: {$content->video_id})\n";
    echo "└─ Will set duration to: {$content->duration} seconds\n\n";
}

echo "🚀 APPLYING FIXES...\n";
echo str_repeat("-", 20) . "\n";

$fixedCount = 0;

foreach ($contentToFix as $content) {
    try {
        $updated = DB::table('module_content')
            ->where('id', $content->content_id)
            ->update(['duration' => $content->duration]);
        
        if ($updated) {
            echo "✅ Fixed: {$content->title} -> {$content->duration}s\n";
            $fixedCount++;
        } else {
            echo "❌ Failed: {$content->title}\n";
        }
    } catch (Exception $e) {
        echo "❌ Error fixing {$content->title}: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 SUMMARY:\n";
echo str_repeat("-", 15) . "\n";
echo "Total items processed: " . count($contentToFix) . "\n";
echo "Successfully fixed: {$fixedCount}\n";
echo "Failed: " . (count($contentToFix) - $fixedCount) . "\n";

// Verify the fix
echo "\n🔍 VERIFICATION:\n";
echo str_repeat("-", 15) . "\n";

$verificationResults = DB::table('module_content as mc')
    ->join('videos as v', 'mc.video_id', '=', 'v.id')
    ->where('mc.content_type', 'video')
    ->select('mc.id as content_id', 'mc.title', 'mc.duration as content_duration', 'v.duration as video_duration')
    ->limit(5)
    ->get();

foreach ($verificationResults as $result) {
    $match = ($result->content_duration == $result->video_duration) ? '✅' : '❌';
    echo "{$match} {$result->title}: Content={$result->content_duration}s, Video={$result->video_duration}s\n";
}

echo "\n✅ Fix completed! Now test the attention scores again.\n";