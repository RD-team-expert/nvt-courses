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
        Schema::table('course_online_assignments', function (Blueprint $table) {
            $table->datetime('deadline')->nullable()->after('notification_sent');
            $table->boolean('is_overdue')->default(false)->after('deadline');
            $table->datetime('deadline_notification_sent_at')->nullable()->after('is_overdue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_online_assignments', function (Blueprint $table) {
            //
        });
    }
};
