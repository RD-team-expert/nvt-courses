<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAttentionScores extends Command
{
    /**
     * The name and signature of the console command.
     * Run with: php artisan sessions:recalculate-scores
     * Run with dry-run first: php artisan sessions:recalculate-scores --dry-run
     */
    protected $signature = 'sessions:recalculate-scores
                            {--dry-run : Show what would change without saving}
                            {--limit= : Only process this many sessions (for testing)}';

    protected $description = 'Recalculate attention scores and suspicious flags for all learning sessions using the new scoring rules';

    // ============================================================
    // SCORING CONSTANTS — match exactly what the controller uses
    // ============================================================
    private const WATCH_SCORE_100   = 50;
    private const WATCH_SCORE_75    = 40;
    private const WATCH_SCORE_50    = 30;
    private const WATCH_SCORE_25    = 20;
    private const WATCH_SCORE_LOW   = 0;

    private const SESSION_END_BONUS = 10;

    private const COMPLETION_SCORE_99 = 40;
    private const COMPLETION_SCORE_80 = 30;
    private const COMPLETION_SCORE_50 = 20;

    private const SKIP_PENALTY      = 30;

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $limit    = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('');
        $this->info('========================================');
        $this->info(' Recalculate Attention Scores');
        $this->info('========================================');

        if ($isDryRun) {
            $this->warn('  ⚠️  DRY RUN MODE — nothing will be saved');
        }

        $this->info('');

        // ----------------------------------------------------------------
        // Load all sessions with the data we need
        // ----------------------------------------------------------------
        $query = DB::table('learning_sessions')
            ->select(
                'id',
                'session_start',
                'session_end',
                'active_playback_time',
                'video_completion_percentage',
                'video_skip_count',
                'seek_count',
                'pause_count',
                'video_replay_count',
                'content_id',
                'attention_score',          // old value — for comparison
                'is_suspicious_activity'    // old value — for comparison
            );

        if ($limit) {
            $query->limit($limit);
        }

        $sessions = $query->get();

        $total        = $sessions->count();
        $changed      = 0;
        $unchanged    = 0;
        $errors       = 0;

        $this->info("  Found {$total} sessions to process");
        $this->info('');

        // Pre-load all video durations in ONE query (avoids N+1)
        $contentIds = $sessions->pluck('content_id')->filter()->unique()->values();
        $videoDurationsMap = DB::table('module_content')
            ->whereIn('id', $contentIds)
            ->pluck('duration', 'id')   // duration in SECONDS keyed by content_id
            ->map(fn($seconds) => $seconds ? $seconds / 60 : null)
            ->toArray();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($sessions as $session) {
            try {
                $videoDurationMinutes = $videoDurationsMap[$session->content_id] ?? null;

                $result = $this->calculateNewScore($session, $videoDurationMinutes);

                $newScore      = $result['score'];
                $newSuspicious = $result['is_suspicious'];

                $oldScore      = (int) ($session->attention_score ?? 0);
                $oldSuspicious = (bool) ($session->is_suspicious_activity ?? false);

                $scoreChanged      = $newScore      !== $oldScore;
                $suspiciousChanged = $newSuspicious !== $oldSuspicious;

                if ($scoreChanged || $suspiciousChanged) {
                    $changed++;

                    if ($isDryRun) {
                        // In dry-run mode, just log what would change
                        $this->newLine();
                        $this->line(sprintf(
                            "  Session #%d → score: %d→%d | suspicious: %s→%s",
                            $session->id,
                            $oldScore,
                            $newScore,
                            $oldSuspicious ? 'YES' : 'NO',
                            $newSuspicious ? 'YES' : 'NO'
                        ));
                    } else {
                        // Save new values to database
                        DB::table('learning_sessions')
                            ->where('id', $session->id)
                            ->update([
                                'attention_score'       => $newScore,
                                'is_suspicious_activity' => $newSuspicious,
                                'updated_at'            => now(),
                            ]);
                    }
                } else {
                    $unchanged++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("  ❌ Error on session #{$session->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // ----------------------------------------------------------------
        // Summary
        // ----------------------------------------------------------------
        $this->info('========================================');
        $this->info(' Summary');
        $this->info('========================================');
        $this->info("  Total sessions processed : {$total}");
        $this->info("  Scores changed           : {$changed}");
        $this->info("  Scores unchanged         : {$unchanged}");

        if ($errors > 0) {
            $this->warn("  Errors                   : {$errors}");
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('  This was a DRY RUN. Run without --dry-run to apply changes.');
        } else {
            $this->newLine();
            $this->info('  ✅ All scores updated successfully.');
        }

        $this->info('');

        return Command::SUCCESS;
    }

    // ============================================================
    // NEW SCORING LOGIC
    // Mirrors calculateSimulatedAttentionScore() in the controller
    // but self-contained so it has no external dependencies
    // ============================================================
    private function calculateNewScore(object $session, ?float $videoDurationMinutes): array
    {
        $details     = [];
        $isSuspicious = false;
        $score       = 0;

        // ----------------------------------------------------------
        // STEP 1: Determine active playback minutes
        // Priority: active_playback_time (seconds) → session timestamps → 0
        // ----------------------------------------------------------
        $activePlaybackMinutes = 0;

        if (!empty($session->active_playback_time) && $session->active_playback_time > 0) {
            $activePlaybackMinutes = $session->active_playback_time / 60;
        } elseif ($session->session_start && $session->session_end) {
            try {
                $start   = new \DateTime($session->session_start);
                $end     = new \DateTime($session->session_end);
                $minutes = ($start->diff($end)->days * 24 * 60)
                         + ($start->diff($end)->h * 60)
                         + $start->diff($end)->i;

                if ($minutes >= 1 && $minutes <= 180) {
                    $activePlaybackMinutes = $minutes;
                }
            } catch (\Exception $e) {
                // leave at 0
            }
        }

        // ----------------------------------------------------------
        // STEP 1C: Completion-only fallback (no time data at all)
        // ----------------------------------------------------------
        if ($activePlaybackMinutes <= 0) {
            $completionPct = (float) ($session->video_completion_percentage ?? 0);

            if ($completionPct >= 95) {
                return ['score' => 60, 'is_suspicious' => false, 'details' => ['High completion, no time data (+60)']];
            } elseif ($completionPct >= 80) {
                return ['score' => 50, 'is_suspicious' => false, 'details' => ['Good completion, no time data (+50)']];
            } elseif ($completionPct >= 60) {
                return ['score' => 40, 'is_suspicious' => false, 'details' => ['Moderate completion, no time data (+40)']];
            } elseif ($completionPct > 0) {
                return ['score' => 25, 'is_suspicious' => true,  'details' => ['Low completion, no time data (+25)']];
            }

            return ['score' => 0, 'is_suspicious' => true, 'details' => ['No playback time or completion data']];
        }

        // ----------------------------------------------------------
        // STEP 2: Allowed time window = video duration × 2
        // ----------------------------------------------------------
        $allowedTimeMinutes  = $videoDurationMinutes ? ($videoDurationMinutes * 2) : 90;
        $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;

        // ----------------------------------------------------------
        // STEP 3: Watch percentage scoring (up to +40)
        // ----------------------------------------------------------
        $watchPct = (float) ($session->video_completion_percentage ?? 0);

        if (!$isWithinAllowedTime) {
            // Exceeded 2× video length → suspicious, no watch score
            $isSuspicious = true;
            $details[]    = 'Exceeded allowed time window (2× video length) — SUSPICIOUS (+0)';
        } elseif ($watchPct >= 100) {
            $score    += self::WATCH_SCORE_100;
            $details[] = 'Watched 100% of video (+' . self::WATCH_SCORE_100 . ')';
        } elseif ($watchPct >= 75) {
            $score    += self::WATCH_SCORE_75;
            $details[] = 'Watched 75–99% of video (+' . self::WATCH_SCORE_75 . ')';
        } elseif ($watchPct >= 50) {
            $score    += self::WATCH_SCORE_50;
            $details[] = 'Watched 50–74% of video (+' . self::WATCH_SCORE_50 . ')';
        } elseif ($watchPct >= 25) {
            $score    += self::WATCH_SCORE_25;
            $details[] = 'Watched 25–49% of video (+' . self::WATCH_SCORE_25 . ')';
        } else {
            $details[] = 'Watched less than 25% of video (+0)';
        }

        // ----------------------------------------------------------
        // STEP 4: Session completed bonus (+5)
        // ----------------------------------------------------------
        if ($session->session_end) {
            $score    += self::SESSION_END_BONUS;
            $details[] = 'Session completed (+' . self::SESSION_END_BONUS . ')';
        }

        // NOTE: Pause and replay bonuses REMOVED per client spec.

        // ----------------------------------------------------------
        // STEP 5: Video completion bonus (up to +35)
        // ----------------------------------------------------------
        $completionPct = (float) ($session->video_completion_percentage ?? 0);

        if ($completionPct >= 99) {
            $score    += self::COMPLETION_SCORE_99;
            $details[] = 'Full video completion (+' . self::COMPLETION_SCORE_99 . ')';
        } elseif ($completionPct >= 80) {
            $score    += self::COMPLETION_SCORE_80;
            $details[] = 'High video completion (+' . self::COMPLETION_SCORE_80 . ')';
        } elseif ($completionPct >= 50) {
            $score    += self::COMPLETION_SCORE_50;
            $details[] = 'Moderate video completion (+' . self::COMPLETION_SCORE_50 . ')';
        }
        // below 50% → +0 (no bonus)

        // ----------------------------------------------------------
        // STEP 6: Skip forward PENALTY (-30 + suspicious)
        // ----------------------------------------------------------
        $skipCount = (int) ($session->video_skip_count ?? 0);

        if ($skipCount >= 1) {
            $score       -= self::SKIP_PENALTY;
            $isSuspicious = true;
            $details[]    = 'PENALTY: Skip forward detected (-' . self::SKIP_PENALTY . ')';
        }

        // NOTE: "score < 30 = suspicious" rule REMOVED per client spec.

        $score = max(0, min(100, $score));

        return [
            'score'        => $score,
            'is_suspicious' => $isSuspicious,
            'details'      => $details,
        ];
    }
}