<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('learning_sessions', function (Blueprint $table) {
        $table->timestamp('last_heartbeat')->nullable()->after('session_start');
    });
}

public function down()
{
    Schema::table('learning_sessions', function (Blueprint $table) {
        $table->dropColumn('last_heartbeat');
    });
}
};
