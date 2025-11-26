<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incentives', function (Blueprint $table) {
            // Add performance_level field after incentive_amount for transition
            $table->integer('performance_level')->nullable()->after('incentive_amount')
                ->comment('Reference to PerformanceLevel enum (1=Outstanding, 2=Reliable, 3=Developing, 4=Underperforming)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incentives', function (Blueprint $table) {
            $table->dropColumn('performance_level');
        });
    }
};