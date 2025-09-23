<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->integer('min_score')->unsigned()->nullable(false); // Non-negative integer
            $table->integer('max_score')->unsigned()->nullable(false); // Non-negative integer
            $table->decimal('incentive_amount', 8, 2)->nullable(false); // Decimal with 8 digits, 2 after decimal
            $table->foreignId('evaluation_config_id')->nullable()->constrained('evaluation_configs')->cascadeOnDelete(); // Optional foreign key
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentives');
    }
};
