<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_online_id')->constrained('course_online')->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('order_number')->default(0);
            $table->integer('estimated_duration')->nullable()->comment('Duration in minutes');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['course_online_id', 'order_number']);
            $table->index(['is_active', 'is_required']);
            $table->unique(['course_online_id', 'order_number'], 'unique_course_module_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
