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
        Schema::table('evaluation_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluation_configs', 'applies_to')) {
                $table->enum('applies_to', ['regular', 'online', 'both'])
                    ->default('both')
                    ->after('max_score');  // ✅ Changed from 'description' to 'max_score'
            }
        });

        // Add index if it doesn't exist
        if (!$this->indexExists('evaluation_configs', 'idx_evaluation_configs_applies_to')) {
            Schema::table('evaluation_configs', function (Blueprint $table) {
                $table->index('applies_to', 'idx_evaluation_configs_applies_to');
            });
        }

        // Backfill existing configs to 'both' for backward compatibility
        DB::table('evaluation_configs')
            ->where(function($query) {
                $query->whereNull('applies_to')
                    ->orWhere('applies_to', '');
            })
            ->update(['applies_to' => 'both']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_configs', function (Blueprint $table) {
            if ($this->indexExists('evaluation_configs', 'idx_evaluation_configs_applies_to')) {
                $table->dropIndex('idx_evaluation_configs_applies_to');
            }

            if (Schema::hasColumn('evaluation_configs', 'applies_to')) {
                $table->dropColumn('applies_to');
            }
        });
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
