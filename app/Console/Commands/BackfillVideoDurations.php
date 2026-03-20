<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVideoDurations extends Command
{
    protected $signature = 'content:backfill-durations
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Copy video duration from videos table into module_content.duration for all video content rows that are missing it';

    public function handle(): int
    {
        $this->info('Checking for video content rows with missing duration...');

        // Count how many rows need fixing
        $count = DB::table('module_content')
            ->join('videos', 'module_content.video_id', '=', 'videos.id')
            ->where('module_content.content_type', 'video')
            ->whereNotNull('module_content.video_id')
            ->where(function ($q) {
                $q->whereNull('module_content.duration')
                  ->orWhere('module_content.duration', 0);
            })
            ->count();

        if ($count === 0) {
            $this->info('✅ Nothing to fix — all video content rows already have a duration.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} video content row(s) with missing or zero duration.");

        // Dry run: show the rows that would be updated
        if ($this->option('dry-run')) {
            $rows = DB::table('module_content')
                ->join('videos', 'module_content.video_id', '=', 'videos.id')
                ->where('module_content.content_type', 'video')
                ->whereNotNull('module_content.video_id')
                ->where(function ($q) {
                    $q->whereNull('module_content.duration')
                      ->orWhere('module_content.duration', 0);
                })
                ->select([
                    'module_content.id as content_id',
                    'module_content.title',
                    'module_content.duration as current_duration',
                    'videos.id as video_id',
                    'videos.name as video_name',
                    'videos.duration as video_duration',
                ])
                ->get();

            $this->table(
                ['Content ID', 'Title', 'Current Duration', 'Video ID', 'Video Name', 'Will Set Duration To'],
                $rows->map(fn($r) => [
                    $r->content_id,
                    $r->title,
                    $r->current_duration ?? 'NULL',
                    $r->video_id,
                    $r->video_name,
                    $r->video_duration ?? 'NULL (video has no duration either!)',
                ])
            );

            $this->info('Dry run complete. Run without --dry-run to apply changes.');
            return self::SUCCESS;
        }

        // Confirm before running in production
        if (!$this->confirm("Update {$count} row(s) now?", true)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        // Run the update
        $updated = DB::statement("
            UPDATE module_content mc
            JOIN videos v ON mc.video_id = v.id
            SET mc.duration = v.duration
            WHERE mc.content_type = 'video'
              AND mc.video_id IS NOT NULL
              AND (mc.duration IS NULL OR mc.duration = 0)
        ");

        // Verify result
        $remaining = DB::table('module_content')
            ->join('videos', 'module_content.video_id', '=', 'videos.id')
            ->where('module_content.content_type', 'video')
            ->whereNotNull('module_content.video_id')
            ->where(function ($q) {
                $q->whereNull('module_content.duration')
                  ->orWhere('module_content.duration', 0);
            })
            ->count();

        if ($remaining === 0) {
            $this->info("✅ Done — {$count} row(s) updated successfully.");
        } else {
            $this->warn("⚠️  Updated rows, but {$remaining} row(s) still have no duration.");
            $this->warn('   These videos likely have NULL duration in the videos table itself.');
            $this->warn('   Check: SELECT id, name, duration FROM videos WHERE duration IS NULL;');
        }

        return self::SUCCESS;
    }
}