<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_online', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->integer('estimated_duration')->nullable()->comment('Duration in minutes');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'created_at']);
            $table->index('created_by');
            $table->index('difficulty_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_online');
    }
};
