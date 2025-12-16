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
        // Add indexes for course_assignments table (if they don't exist)
        if (Schema::hasTable('course_assignments')) {
            Schema::table('course_assignments', function (Blueprint $table) {
                try {
                    $table->index(['user_id', 'status'], 'idx_course_assignments_user_status');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                try {
                    $table->index('assigned_at', 'idx_course_assignments_assigned_at');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
            });
        }

        // Add indexes for course_online_assignments table (if they don't exist)
        if (Schema::hasTable('course_online_assignments')) {
            Schema::table('course_online_assignments', function (Blueprint $table) {
                try {
                    $table->index(['user_id', 'status'], 'idx_course_online_assignments_user_status');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                try {
                    $table->index('assigned_at', 'idx_course_online_assignments_assigned_at');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
            });
        }

        // Add index for users table (department_id) if column exists
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->index('department_id', 'idx_users_department_id');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from course_assignments
        if (Schema::hasTable('course_assignments')) {
            Schema::table('course_assignments', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_course_assignments_user_status');
                } catch (\Exception $e) {
                    // Index might not exist, skip
                }
                try {
                    $table->dropIndex('idx_course_assignments_assigned_at');
                } catch (\Exception $e) {
                    // Index might not exist, skip
                }
            });
        }

        // Drop indexes from course_online_assignments
        if (Schema::hasTable('course_online_assignments')) {
            Schema::table('course_online_assignments', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_course_online_assignments_user_status');
                } catch (\Exception $e) {
                    // Index might not exist, skip
                }
                try {
                    $table->dropIndex('idx_course_online_assignments_assigned_at');
                } catch (\Exception $e) {
                    // Index might not exist, skip
                }
            });
        }

        // Drop index from users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_users_department_id');
                } catch (\Exception $e) {
                    // Index might not exist, skip
                }
            });
        }
    }
};
