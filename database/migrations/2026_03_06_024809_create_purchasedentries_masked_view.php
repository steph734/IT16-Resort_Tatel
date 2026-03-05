<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates masked VIEW for purchase_entries table
     */
    public function up(): void
    {
        // MySQL / MariaDB compatible syntax
        DB::statement(/** @lang text */ "
            CREATE OR REPLACE VIEW purchase_entries_masked AS
            SELECT
                CONCAT(
                    LEFT(entry_number, 3),
                    REPEAT('*', GREATEST(LENGTH(entry_number) - 3, 0))
                ) AS entry_number,   -- e.g. PE0******* or PE00******

                purchase_date,

                '***' AS total_amount,   -- fully masked (you can also use NULL)

                vendor_name,

                CASE
                    WHEN receipt_no IS NULL THEN NULL
                    WHEN LENGTH(receipt_no) <= 4 THEN receipt_no
                    ELSE CONCAT('****', RIGHT(receipt_no, 4))
                END AS receipt_no,   -- e.g. ****1234 or full if very short

                notes,
                created_by,
                created_at,
                updated_at

            FROM purchase_entries
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS purchase_entries_masked');
    }
};