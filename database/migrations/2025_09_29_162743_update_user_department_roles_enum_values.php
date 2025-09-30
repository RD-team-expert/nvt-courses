<?php
// database/migrations/2025_09_29_162743_update_user_department_roles_enum_values.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: FIRST expand the enum to include both old and new values
        DB::statement("ALTER TABLE user_department_roles MODIFY COLUMN role_type ENUM('direct_manager', 'project_manager', 'department_head', 'senior_manager', 'team_lead', 'director', 'supervisor')");

        // Step 2: THEN update existing records to use the new enum values
        DB::table('user_department_roles')
            ->where('role_type', 'department_head')
            ->update(['role_type' => 'director']);

        DB::table('user_department_roles')
            ->where('role_type', 'team_lead')
            ->update(['role_type' => 'supervisor']);

        // Step 3: FINALLY set the enum to only the values we want
        DB::statement("ALTER TABLE user_department_roles MODIFY COLUMN role_type ENUM('director', 'senior_manager', 'direct_manager', 'project_manager', 'supervisor')");
    }

    public function down(): void
    {
        // Expand enum to include old values
        DB::statement("ALTER TABLE user_department_roles MODIFY COLUMN role_type ENUM('direct_manager', 'project_manager', 'department_head', 'senior_manager', 'team_lead', 'director', 'supervisor')");

        // Revert the data changes
        DB::table('user_department_roles')
            ->where('role_type', 'director')
            ->update(['role_type' => 'department_head']);

        DB::table('user_department_roles')
            ->where('role_type', 'supervisor')
            ->update(['role_type' => 'team_lead']);

        // Revert to original enum values
        DB::statement("ALTER TABLE user_department_roles MODIFY COLUMN role_type ENUM('direct_manager', 'project_manager', 'department_head', 'senior_manager', 'team_lead')");
    }
};
