<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('module_content')->onDelete('cascade');
            $table->string('task_title', 255);
            $table->text('task_description');
            $table->enum('task_type', ['quiz', 'assignment', 'discussion', 'reflection'])->default('quiz');
            $table->json('task_data')->nullable()->comment('Task configuration (quiz questions, assignment instructions, etc.)');
            $table->decimal('passing_score', 5, 2)->nullable()->comment('Minimum score required to pass');
            $table->integer('max_attempts')->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('content_id');
            $table->index(['task_type', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_tasks');
    }
};
