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
    Schema::create('podcast_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->text('excerpt')->nullable();
        $table->longText('description')->nullable();

        // Polymorphic — links to either Video or Audio model
        $table->nullableMorphs('mediable');

        $table->string('thumbnail_path')->nullable();
        $table->enum('status', ['draft', 'published'])->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->json('tags')->nullable();
        $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('podcast_posts');
}
};
