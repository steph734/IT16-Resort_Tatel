<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safety: null out any processed_by / voided_by values that do not match an existing users.user_id
        DB::statement("UPDATE transactions t LEFT JOIN users u ON t.processed_by = u.user_id SET t.processed_by = NULL WHERE u.user_id IS NULL");
        DB::statement("UPDATE transactions t LEFT JOIN users u ON t.voided_by = u.user_id SET t.voided_by = NULL WHERE u.user_id IS NULL");

        // Ensure guest_id values reference existing guests; if not, set to NULL
        DB::statement("UPDATE transactions t LEFT JOIN guests g ON t.guest_id = g.GuestID SET t.guest_id = NULL WHERE g.GuestID IS NULL");

        // Modify column lengths to match referenced PKs using raw SQL
        // processed_by / voided_by must match users.user_id (VARCHAR(8))
        DB::statement("ALTER TABLE transactions MODIFY processed_by VARCHAR(8) NULL");
        DB::statement("ALTER TABLE transactions MODIFY voided_by VARCHAR(8) NULL");

        // guest_id should match guests.GuestID (VARCHAR(10))
        DB::statement("ALTER TABLE transactions MODIFY guest_id VARCHAR(10) NULL");

        // Add foreign key constraints if they don't already exist (avoid duplicate-key errors)
        $existing = function ($tableName, $column, $referencedTable) {
            $rows = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?", [$tableName, $column, $referencedTable]);
            return count($rows) > 0;
        };

        if (!$existing('transactions', 'processed_by', 'users')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('processed_by')->references('user_id')->on('users')->onDelete('set null');
            });
        }

        if (!$existing('transactions', 'voided_by', 'users')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('voided_by')->references('user_id')->on('users')->onDelete('set null');
            });
        }

        if (!$existing('transactions', 'guest_id', 'guests')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('guest_id')->references('GuestID')->on('guests')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        // Drop foreign keys only if they exist
        $existing = function ($tableName, $column, $referencedTable) {
            $rows = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?", [$tableName, $column, $referencedTable]);
            return count($rows) > 0 ? $rows[0]->CONSTRAINT_NAME : false;
        };

        $constraint = $existing('transactions', 'processed_by', 'users');
        if ($constraint) {
            Schema::table('transactions', function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }

        $constraint = $existing('transactions', 'voided_by', 'users');
        if ($constraint) {
            Schema::table('transactions', function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }

        $constraint = $existing('transactions', 'guest_id', 'guests');
        if ($constraint) {
            Schema::table('transactions', function (Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }

        // Revert column sizes (restore to 50 for guest_id and 50 for processed/voided_by)
        DB::statement("ALTER TABLE transactions MODIFY processed_by VARCHAR(50) NULL");
        DB::statement("ALTER TABLE transactions MODIFY voided_by VARCHAR(50) NULL");
        DB::statement("ALTER TABLE transactions MODIFY guest_id VARCHAR(50) NULL");
    }
};
