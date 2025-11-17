<?php

namespace App\Services;

use App\Models\Clocking;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClockingService
{
    /**
     * Clock the user in by creating a new Clocking entry.
     */
    public function clockIn(?int $course_id = null): Clocking
    {
        // Add logging for debugging


        // Optionally, ensure user does not have an open clocking session
        $openSession = Clocking::where('user_id', Auth::id())
            ->whereNull('clock_out')
            ->first();

        if ($openSession) {
            // Log the issue


            throw new \Exception('You must clock out before clocking in again.');
        }

        // Create the clocking record
        $clocking = Clocking::create([
            'user_id'   => Auth::id(),
            'course_id' => $course_id,
            'clock_in'  => Carbon::now(),
        ]);

        // Log success


        return $clocking;
    }

    /**
     * Clock the user out by updating the active Clocking entry.
     */
    public function clockOut(int $rating = null, string $comment = null): Clocking
    {
        // Find the active clocking session
        $clocking = Clocking::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->first();

        if (!$clocking) {
            throw new \Exception('No active clocking session found.');
        }

        // Update the clocking record
        $clocking->clock_out = now();
        $clocking->rating = $rating;
        $clocking->comment = $comment;
        $clocking->save();

        // Calculate and save the duration
        $clocking->updateDuration();

        return $clocking;
    }
}
