<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Check and add columns if they don't exist
            if (!Schema::hasColumn('evaluations', 'course_type')) {
                $table->enum('course_type', ['regular', 'online'])
                    ->default('regular')
                    ->after('course_id');
            }

            if (!Schema::hasColumn('evaluations', 'course_online_id')) {
                $table->unsignedBigInteger('course_online_id')
                    ->nullable()
                    ->after('course_type');
            }
        });

        // Add foreign key constraint if it doesn't exist
        if (!$this->foreignKeyExists('evaluations', 'evaluations_course_online_id_foreign')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->foreign('course_online_id')
                    ->references('id')
                    ->on('course_online')  // ✅ CORRECT: singular with underscore
                    ->onDelete('cascade');
            });
        }

        // Add composite index if it doesn't exist
        if (!$this->indexExists('evaluations', 'idx_evaluations_course_type')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->index(['course_type', 'course_id', 'course_online_id'], 'idx_evaluations_course_type');
            });
        }

        // Backfill existing records to 'regular' type
        DB::table('evaluations')
            ->where(function($query) {
                $query->whereNull('course_type')
                    ->orWhere('course_type', '');
            })
            ->update(['course_type' => 'regular']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if ($this->foreignKeyExists('evaluations', 'evaluations_course_online_id_foreign')) {
                $table->dropForeign(['course_online_id']);
            }

            if ($this->indexExists('evaluations', 'idx_evaluations_course_type')) {
                $table->dropIndex('idx_evaluations_course_type');
            }

            if (Schema::hasColumn('evaluations', 'course_online_id')) {
                $table->dropColumn('course_online_id');
            }

            if (Schema::hasColumn('evaluations', 'course_type')) {
                $table->dropColumn('course_type');
            }
        });
    }

    /**
     * Check if a foreign key exists
     */
    private function foreignKeyExists(string $table, string $key): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $exists = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = ?
        ", [$dbName, $table, $key]);

        return $exists[0]->count > 0;
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $exists = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND INDEX_NAME = ?
        ", [$dbName, $table, $index]);

        return $exists[0]->count > 0;
    }
};
