<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_availabilities', function (Blueprint $table) {
            // Add sessions column after capacity
            $table->integer('sessions')->default(1)->after('capacity')->comment('Number of sessions available');
        });

        // ✅ Initialize sessions = capacity for existing records
        DB::table('course_availabilities')->update(['sessions' => DB::raw('capacity')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_availabilities', function (Blueprint $table) {
            $table->dropColumn('sessions');
        });
    }
};
