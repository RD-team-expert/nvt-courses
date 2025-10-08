<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('module_tasks')->onDelete('cascade');
            $table->foreignId('content_id')->constrained('module_content')->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('is_passed')->default(false);
            $table->json('submission_data')->nullable()->comment('User submission data');
            $table->timestamp('completed_at')->useCurrent();
            $table->integer('time_spent')->nullable()->comment('Time spent in seconds');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'task_id']);
            $table->index(['task_id', 'is_passed']);
            $table->index('content_id');
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_task_completions');
    }
};
