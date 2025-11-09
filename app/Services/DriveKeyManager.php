<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriveKeyManager
{
    /**
     * Get an available API key with the least active users
     * This is the "traffic cop" that assigns keys to users
     */
    public function getAvailableKey(): ?array
    {
        try {
            // 🔒 Lock the database while we find and assign a key
            // This prevents two users from getting the same key at the exact same time
            $key = DB::transaction(function () {
                
                // Find the best available key:
                // 1. Must be active (is_active = 1)
                // 2. Must have space (active_users < max_users)
                // 3. Pick the one with LEAST active users (fair distribution)
                // 4. If tied, pick the one used longest ago
                $key = DB::table('drive_key_tracker')
                    ->where('is_active', true)
                    ->whereRaw('active_users < max_users')
                    ->orderBy('active_users', 'asc')
                    ->orderBy('last_used_at', 'asc')
                    ->lockForUpdate() // 🔒 Lock this row so no one else can grab it
                    ->first();

                if (!$key) {
                    // All keys are full or disabled!
                    Log::error('❌ All API keys are at capacity or disabled', [
                        'timestamp' => now(),
                        'total_keys' => DB::table('drive_key_tracker')->count(),
                        'active_keys' => DB::table('drive_key_tracker')->where('is_active', true)->count(),
                    ]);
                    return null;
                }

                // ✅ Found a key! Increment its user count
                DB::table('drive_key_tracker')
                    ->where('id', $key->id)
                    ->increment('active_users');

                // Update last used time
                DB::table('drive_key_tracker')
                    ->where('id', $key->id)
                    ->update(['last_used_at' => now()]);

                return $key;
            });

            if (!$key) {
                return null;
            }

            // 📊 Log the assignment for monitoring
            Log::info('✅ API key assigned to user', [
                'key_id' => $key->id,
                'key_name' => $key->key_name,
                'active_users' => $key->active_users + 1, // +1 because we just incremented
                'max_users' => $key->max_users,
                'utilization' => round((($key->active_users + 1) / $key->max_users) * 100, 1) . '%',
                'timestamp' => now(),
            ]);

            // Return the key data to use
            return [
                'id' => $key->id,
                'key_name' => $key->key_name,
                'api_key' => env($key->key_name), // Get actual key from .env
                'active_users' => $key->active_users + 1,
                'max_users' => $key->max_users,
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error getting available API key', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Release an API key when user stops watching
     * This frees up the key for another user
     */
    public function releaseKey(int $keyId): void
    {
        try {
            DB::transaction(function () use ($keyId) {
                // Decrease active user count (but never go below 0)
                $updated = DB::table('drive_key_tracker')
                    ->where('id', $keyId)
                    ->where('active_users', '>', 0)
                    ->decrement('active_users');

                if ($updated) {
                    $key = DB::table('drive_key_tracker')->find($keyId);
                    
                    Log::info('🔓 API key released', [
                        'key_id' => $keyId,
                        'key_name' => $key->key_name ?? 'unknown',
                        'remaining_active_users' => $key->active_users ?? 0,
                        'timestamp' => now(),
                    ]);
                } else {
                    Log::warning('⚠️ Attempted to release key that was already free', [
                        'key_id' => $keyId,
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('❌ Error releasing API key', [
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current status of all keys (for monitoring/debugging)
     */
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
            Log::error('❌ Error getting key status', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Helper: Get human-readable status label
     */
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

    /**
     * Emergency: Disable a specific key (if Google blocks it)
     */
    public function disableKey(int $keyId): bool
    {
        try {
            $updated = DB::table('drive_key_tracker')
                ->where('id', $keyId)
                ->update(['is_active' => false]);

            if ($updated) {
                Log::warning('🚫 API key manually disabled', [
                    'key_id' => $keyId,
                    'timestamp' => now(),
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('❌ Error disabling API key', [
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Re-enable a disabled key
     */
    public function enableKey(int $keyId): bool
    {
        try {
            $updated = DB::table('drive_key_tracker')
                ->where('id', $keyId)
                ->update(['is_active' => true]);

            if ($updated) {
                Log::info('✅ API key re-enabled', [
                    'key_id' => $keyId,
                    'timestamp' => now(),
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('❌ Error enabling API key', [
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
