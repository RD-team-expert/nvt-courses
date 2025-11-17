<?php

namespace App\Services;

use App\Models\LearningSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriveKeyManager
{
    /**
     * ✅ CHANGED: Added $shouldIncrement parameter
     * Get an available API key with the least active users
     *
     * @param bool $shouldIncrement Whether to increment active_users count
     * @return array|null Key data or null if none available
     */
    public function getAvailableKey(bool $shouldIncrement = false): ?array
    {
        try {
            // 🔒 Lock the database while we find and assign a key
            $key = DB::transaction(function () use ($shouldIncrement) {

                // Find the best available key
                $key = DB::table('drive_key_tracker')
                    ->where('is_active', true)
                    ->whereRaw('active_users < max_users')
                    ->orderBy('active_users', 'asc')
                    ->orderBy('last_used_at', 'asc')
                    ->lockForUpdate()
                    ->first();

                if (!$key) {

                    return null;
                }

                // ✅ CHANGED: Only increment if requested
                if ($shouldIncrement) {
                    DB::table('drive_key_tracker')
                        ->where('id', $key->id)
                        ->increment('active_users');


                }

                // Update last used time
                DB::table('drive_key_tracker')
                    ->where('id', $key->id)
                    ->update(['last_used_at' => now()]);

                return $key;
            });

            if (!$key) {
                return null;
            }

            // ✅ CHANGED: Adjust active_users based on whether we incremented
            $currentActiveUsers = $shouldIncrement ? $key->active_users + 1 : $key->active_users;

            // 📊 Log the assignment

            return [
                'id' => $key->id,
                'key_name' => $key->key_name,
                'api_key' => env($key->key_name),
                'active_users' => $currentActiveUsers,
                'max_users' => $key->max_users,
            ];

        } catch (\Exception $e) {

            return null;
        }
    }

    /**
     * Release an API key when user stops watching
     */
    public function releaseKey(int $keyId): void
    {
        try {
            DB::transaction(function () use ($keyId) {
                $updated = DB::table('drive_key_tracker')
                    ->where('id', $keyId)
                    ->where('active_users', '>', 0)
                    ->decrement('active_users');

                if ($updated) {
                    $key = DB::table('drive_key_tracker')->find($keyId);

                } else {

                }
            });

        } catch (\Exception $e) {

        }
    }

    // ✅ Keep all other methods unchanged (getKeyStatus, disableKey, enableKey, cleanupExpiredSessions)

    public function getKeyStatus(): array
    {
        try {
            $keys = DB::table('drive_key_tracker')
                ->select('id', 'key_name', 'active_users', 'max_users', 'is_active', 'last_used_at')
                ->orderBy('id')
                ->get();

            return $keys->map(function ($key) {
                return [
                    'id' => $key->id,
                    'key_name' => $key->key_name,
                    'active_users' => $key->active_users,
                    'max_users' => $key->max_users,
                    'is_active' => (bool) $key->is_active,
                    'utilization' => $key->max_users > 0
                        ? round(($key->active_users / $key->max_users) * 100, 1)
                        : 0,
                    'last_used_at' => $key->last_used_at,
                    'status' => $this->getKeyStatusLabel($key),
                ];
            })->toArray();

        } catch (\Exception $e) {

            return [];
        }
    }

    private function getKeyStatusLabel($key): string
    {
        if (!$key->is_active) {
            return 'Disabled';
        }

        $utilization = $key->max_users > 0
            ? ($key->active_users / $key->max_users) * 100
            : 0;

        if ($utilization >= 100) {
            return 'Full';
        } elseif ($utilization >= 75) {
            return 'High Load';
        } elseif ($utilization >= 50) {
            return 'Medium Load';
        } elseif ($utilization > 0) {
            return 'Active';
        } else {
            return 'Available';
        }
    }

    public function disableKey(int $keyId): bool
    {
        try {
            $updated = DB::table('drive_key_tracker')
                ->where('id', $keyId)
                ->update(['is_active' => false]);

            if ($updated) {
                return true;
            }
            return false;

        } catch (\Exception $e) {

            return false;
        }
    }

    public function enableKey(int $keyId): bool
    {
        try {
            $updated = DB::table('drive_key_tracker')
                ->where('id', $keyId)
                ->update(['is_active' => true]);

            if ($updated) {
                return true;
            }
            return false;

        } catch (\Exception $e) {

            return false;
        }
    }

    public function cleanupExpiredSessions(): void
    {
        $timeout = now()->subSeconds(30);

        $expiredSessions = LearningSession::whereNull('session_end')
            ->where('last_heartbeat', '<', $timeout)
            ->get();

        foreach ($expiredSessions as $session) {
            if ($session->api_key_id) {
                $this->releaseKey($session->api_key_id);
            }

            $session->session_end = now();
            $session->save();

        }
    }
    /**
 * ✅ NEW: Increment active_users for a specific key
 * Used when session starts (user presses play)
 */
public function incrementActiveUsers(int $keyId): void
{
    try {
        DB::table('drive_key_tracker')
            ->where('id', $keyId)
            ->increment('active_users');

        $key = DB::table('drive_key_tracker')->find($keyId);



    } catch (\Exception $e) {

    }
}

}
