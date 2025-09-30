<?php
// database/migrations/2025_09_18_120003_add_organizational_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add new organizational fields
            $table->unsignedBigInteger('department_id')->nullable()->after('email');
            $table->unsignedBigInteger('user_level_id')->nullable()->after('department_id');
            $table->string('employee_code', 50)->unique()->nullable()->after('user_level_id');
            $table->date('hire_date')->nullable()->after('employee_code');
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active')->after('hire_date');
            $table->string('phone', 20)->nullable()->after('status');

            // Foreign key constraints
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('user_level_id')->references('id')->on('user_levels')->onDelete('set null');

            // Indexes for performance
            $table->index('department_id');
            $table->index('user_level_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['department_id']);
            $table->dropForeign(['user_level_id']);

            // Drop columns
            $table->dropColumn([
                'department_id',
                'user_level_id',
                'employee_code',
                'hire_date',
                'status',
                'phone'
            ]);
        });
    }
};
