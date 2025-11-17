<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Expand enum to include new values
        DB::statement("
            ALTER TABLE `user_department_roles` 
            MODIFY COLUMN `role_type` ENUM(
                'director',
                'senior_manager',
                'direct_manager',
                'project_manager',
                'supervisor',
                'president',
                'business_owner'
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new values (only if no records use them)
        DB::statement("
            ALTER TABLE `user_department_roles` 
            MODIFY COLUMN `role_type` ENUM(
                'director',
                'senior_manager',
                'direct_manager',
                'project_manager',
                'supervisor'
            )
        ");
    }
};
