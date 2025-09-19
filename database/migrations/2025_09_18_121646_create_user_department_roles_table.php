<?php
// database/migrations/2025_09_18_120004_create_user_department_roles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_department_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // The manager
            $table->unsignedBigInteger('department_id'); // Department where role applies
            $table->enum('role_type', [
                'direct_manager',
                'project_manager',
                'department_head',
                'senior_manager',
                'team_lead'
            ]);
            $table->unsignedBigInteger('manages_user_id')->nullable(); // User being managed (null for department-wide roles)
            $table->boolean('is_primary')->default(false); // Primary manager flag
            $table->tinyInteger('authority_level')->default(1); // 1=highest, 2=medium, 3=lowest
            $table->date('start_date');
            $table->date('end_date')->nullable(); // NULL = active
            $table->unsignedBigInteger('created_by'); // Admin who created relationship
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('manages_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');

            // Indexes for performance
            $table->index('user_id');
            $table->index('department_id');
            $table->index('manages_user_id');
            $table->index('role_type');
            $table->index(['user_id', 'department_id', 'role_type']);
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_department_roles');
    }
};
