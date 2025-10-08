<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_availabilities', function (Blueprint $table) {
            // Add SET field for multiple days selection
            $table->set('days_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable()->after('notes');

            // Add duration in weeks
            $table->integer('duration_weeks')->nullable()->after('days_of_week');

            // Add session time (optional)
            $table->time('session_time')->nullable()->after('duration_weeks');

            // Add session duration in minutes (default 60 minutes)
            $table->integer('session_duration_minutes')->default(60)->after('session_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_availabilities', function (Blueprint $table) {
            $table->dropColumn([
                'days_of_week',
                'duration_weeks',
                'session_time',
                'session_duration_minutes'
            ]);
        });
    }
};
