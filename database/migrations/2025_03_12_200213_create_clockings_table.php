<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clockings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // If clocking is tied to a specific course, uncomment below:
            // $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->tinyInteger('rating')->nullable()->comment('1-5 rating');
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clockings');
    }
};
