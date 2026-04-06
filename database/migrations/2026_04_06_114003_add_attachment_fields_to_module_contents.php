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
            $table->string('attachment_path', 500)->nullable()->after('pdf_page_count');
            $table->string('attachment_name', 255)->nullable()->after('attachment_path');
            $table->string('attachment_extension', 10)->nullable()->after('attachment_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_content', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_extension']);
        });
    }
};
