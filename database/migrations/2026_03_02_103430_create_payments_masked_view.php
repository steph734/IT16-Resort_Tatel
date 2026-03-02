<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW payments_masked AS
            SELECT
                PaymentID,
                BookingID,
                PaymentDate,
                -- Mask amount fields (example: show first 2 digits + *** + decimals)
                CONCAT(
                    LEFT(FORMAT(Amount, 2), 2),
                    REPEAT('*', LENGTH(FORMAT(Amount, 2)) - 5),
                    RIGHT(FORMAT(Amount, 2), 3)
                ) AS masked_amount,

                CONCAT(
                    LEFT(FORMAT(TotalAmount, 2), 2),
                    REPEAT('*', LENGTH(FORMAT(TotalAmount, 2)) - 5),
                    RIGHT(FORMAT(TotalAmount, 2), 3)
                ) AS masked_total_amount,

                -- Repeat similar pattern for other amount fields...
                -- PaymentMethod,
                -- PaymentStatus,
                -- PaymentPurpose,
                -- ReferenceNumber,   ← you can also mask this here if wanted
                created_at,
                updated_at
            FROM payments;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS payments_masked;');
    }
};