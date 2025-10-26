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
        Schema::table('quizzes', function (Blueprint $table) {
            // FIRST: Make course_id nullable to support online courses
            $table->unsignedBigInteger('course_id')->nullable()->change();

            // === ONLINE COURSE SUPPORT ===
            $table->unsignedBigInteger('course_online_id')->nullable()->after('course_id');

            // === DEADLINE FIELDS ===
            $table->timestamp('deadline')->nullable()->after('description');
            $table->boolean('has_deadline')->default(false)->after('deadline');
            $table->boolean('enforce_deadline')->default(true)->after('has_deadline');
            $table->integer('time_limit_minutes')->nullable()->after('enforce_deadline');
            $table->boolean('allows_extensions')->default(false)->after('time_limit_minutes');

            // === INDEXES FOR PERFORMANCE ===
            $table->index('course_online_id', 'quizzes_course_online_id_index');
            $table->index(['has_deadline', 'deadline'], 'quizzes_deadline_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('quizzes_course_online_id_index');
            $table->dropIndex('quizzes_deadline_index');

            // Drop columns
            $table->dropColumn([
                'course_online_id',
                'deadline',
                'has_deadline',
                'enforce_deadline',
                'time_limit_minutes',
                'allows_extensions'
            ]);

            // Restore course_id as not nullable (if needed)
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }
};
