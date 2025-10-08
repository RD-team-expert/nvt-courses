<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_online_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_online_id')->constrained('course_online')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed'])->default('assigned');
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->foreignId('current_module_id')->nullable()->constrained('course_modules')->onDelete('set null');
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['course_online_id', 'status']);
            $table->index('assigned_by');
            $table->index('current_module_id');
            $table->unique(['course_online_id', 'user_id'], 'unique_course_user_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_online_assignments');
    }
};
