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
        Schema::table('course_online', function (Blueprint $table) {
            $table->datetime('deadline')->nullable()->after('created_by');
            $table->boolean('has_deadline')->default(false)->after('deadline');
            $table->string('deadline_type')->default('flexible')->after('has_deadline'); // 'flexible', 'strict'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_online', function (Blueprint $table) {
            $table->dropColumn(['deadline', 'has_deadline', 'deadline_type']);
        });
    }
};
