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
            $table->time('session_time_shift_2')->nullable()->after('session_time');
            $table->time('session_time_shift_3')->nullable()->after('session_time_shift_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_availabilities', function (Blueprint $table) {
            $table->dropColumn(['session_time_shift_2', 'session_time_shift_3']);
        });
    }
};
