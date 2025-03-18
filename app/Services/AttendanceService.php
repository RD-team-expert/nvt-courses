<?php

namespace App\Services;

use App\Models\Clocking;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Update an attendance record
     */
    public function updateAttendance(Clocking $attendance, array $data): Clocking
    {
        // Calculate duration if both clock_in and clock_out are provided
        if (isset($data['clock_in']) && isset($data['clock_out'])) {
            $clockIn = Carbon::parse($data['clock_in']);
            $clockOut = Carbon::parse($data['clock_out']);
            $data['duration_in_minutes'] = $clockOut->diffInMinutes($clockIn);
        } elseif (isset($data['clock_in']) && $attendance->clock_out) {
            // Recalculate if only clock_in changed but clock_out exists
            $clockIn = Carbon::parse($data['clock_in']);
            $clockOut = Carbon::parse($attendance->clock_out);
            $data['duration_in_minutes'] = $clockOut->diffInMinutes($clockIn);
        } else {
            // If clock_out is null, set duration to null
            $data['duration_in_minutes'] = null;
        }

        $attendance->update($data);
        
        return $attendance;
    }

    /**
     * Delete an attendance record
     */
    public function deleteAttendance(Clocking $attendance): bool
    {
        return $attendance->delete();
    }
}