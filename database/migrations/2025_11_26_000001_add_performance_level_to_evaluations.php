<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance_level and point-range columns; keeps incentive_amount for transition.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluations', 'performance_level')) {
                $table->tinyInteger('performance_level')->nullable()->after('total_score');
            }

            if (!Schema::hasColumn('evaluations', 'performance_points_min')) {
                $table->integer('performance_points_min')->nullable()->after('performance_level');
            }

            if (!Schema::hasColumn('evaluations', 'performance_points_max')) {
                $table->integer('performance_points_max')->nullable()->after('performance_points_min');
            }

            $table->index('performance_level');
        });
    }

    /**
     * Reverse the migrations.
     * Removes the new columns but keeps incentive_amount untouched.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('evaluations', 'performance_points_max')) {
                $table->dropColumn('performance_points_max');
            }
            if (Schema::hasColumn('evaluations', 'performance_points_min')) {
                $table->dropColumn('performance_points_min');
            }
            if (Schema::hasColumn('evaluations', 'performance_level')) {
                $table->dropIndex(['performance_level']);
                $table->dropColumn('performance_level');
            }
        });
    }
};
