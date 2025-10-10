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
        Schema::table('module_content', function (Blueprint $table) {
            // Add PDF page count field
            $table->integer('pdf_page_count')->nullable()->after('file_size');

            // Add index for better performance when querying PDFs
            $table->index(['content_type', 'pdf_page_count'], 'idx_content_type_pdf_pages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_content', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_content_type_pdf_pages');

            // Drop the column
            $table->dropColumn('pdf_page_count');
        });
    }
};
