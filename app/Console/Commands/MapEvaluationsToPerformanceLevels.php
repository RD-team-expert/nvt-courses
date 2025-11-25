<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Evaluation;
use App\Enums\PerformanceLevel;
use Illuminate\Support\Facades\Storage;

class MapEvaluationsToPerformanceLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluations:map-performance-levels
                            {--dry-run : Do not persist changes, only simulate}
                            {--chunk=500 : Chunk size for processing}
                            {--export= : Path to export flagged CSV (relative to storage/app). Defaults to performance_level_migration/flagged_records.csv}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Map existing evaluations to performance levels using total_score; flag records without score for manual review.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $export = $this->option('export') ?: 'performance_level_migration/flagged_records.csv';

        $this->info('Mapping evaluations to performance levels');
        if ($dryRun) {
            $this->info('Dry run enabled — no database writes will be performed.');
        }

        $storagePath = storage_path('app/' . $export);
        $dir = dirname($storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $csvHandle = fopen($storagePath, 'w');
        fputcsv($csvHandle, ['id', 'user_id', 'total_score', 'incentive_amount', 'reason']);

        $updated = 0;
        $flagged = 0;
        $processed = 0;

        Evaluation::whereNull('performance_level')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($evaluations) use (&$updated, &$flagged, &$processed, $csvHandle, $dryRun) {
                foreach ($evaluations as $evaluation) {
                    $processed++;
                    $score = $evaluation->total_score;
                    $mappedLevel = null;

                    if ($score !== null) {
                        $mappedLevel = PerformanceLevel::getLevelByScore($score);
                    }

                    if ($mappedLevel !== null) {
                        // write range
                        $range = PerformanceLevel::getScoreRangeByLevel($mappedLevel);
                        if (!$dryRun) {
                            $evaluation->performance_level = $mappedLevel;
                            $evaluation->performance_points_min = $range['min'] ?? null;
                            $evaluation->performance_points_max = $range['max'] ?? null;
                            $evaluation->save();
                        }
                        $updated++;
                    } else {
                        // Flag for manual review
                        $flagged++;
                        fputcsv($csvHandle, [
                            $evaluation->id,
                            $evaluation->user_id,
                            $evaluation->total_score,
                            $evaluation->incentive_amount,
                            'no_total_score_or_unmapped',
                        ]);
                    }
                }
            });

        fclose($csvHandle);

        $this->info("Processed: {$processed}");
        $this->info("Updated: {$updated}");
        $this->info("Flagged for review: {$flagged}");
        $this->info("Flagged records exported to storage/app/{$export}");

        return 0;
    }
}
