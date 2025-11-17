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
        Schema::table('evaluation_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluation_histories', 'course_type')) {
                $table->enum('course_type', ['regular', 'online'])
                    ->default('regular')
                    ->after('evaluation_id');
            }

            if (!Schema::hasColumn('evaluation_histories', 'course_online_id')) {
                $table->unsignedBigInteger('course_online_id')
                    ->nullable()
                    ->after('course_type');
            }
        });

        // Add foreign key constraint if it doesn't exist
        if (!$this->foreignKeyExists('evaluation_histories', 'evaluation_histories_course_online_id_foreign')) {
            Schema::table('evaluation_histories', function (Blueprint $table) {
                $table->foreign('course_online_id')
                    ->references('id')
                    ->on('course_online')
                    ->onDelete('cascade');
            });
        }

        // Add index if it doesn't exist
        if (!$this->indexExists('evaluation_histories', 'idx_evaluation_histories_course_type')) {
            Schema::table('evaluation_histories', function (Blueprint $table) {
                $table->index('course_type', 'idx_evaluation_histories_course_type');
            });
        }

        // Backfill existing records
        DB::table('evaluation_histories')
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
        Schema::table('evaluation_histories', function (Blueprint $table) {
            if ($this->foreignKeyExists('evaluation_histories', 'evaluation_histories_course_online_id_foreign')) {
                $table->dropForeign(['course_online_id']);
            }

            if ($this->indexExists('evaluation_histories', 'idx_evaluation_histories_course_type')) {
                $table->dropIndex('idx_evaluation_histories_course_type');
            }

            if (Schema::hasColumn('evaluation_histories', 'course_online_id')) {
                $table->dropColumn('course_online_id');
            }

            if (Schema::hasColumn('evaluation_histories', 'course_type')) {
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
