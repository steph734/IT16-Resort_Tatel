<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW payments_masked AS
            SELECT
                PaymentID,
                BookingID,
                PaymentDate,

                -- Masked Amount: first 2 chars + dynamic * + last 3 chars (e.g. 12***4.56, 9.***9.99, -4***5.30)
                CONCAT(
                    LEFT(CAST(Amount AS CHAR), 2),
                    REPEAT('*', GREATEST(0, CHAR_LENGTH(CAST(ROUND(ABS(Amount), 2) AS CHAR)) - 5)),
                    RIGHT(CAST(ROUND(Amount, 2) AS CHAR), 3)
                ) AS masked_amount,

                -- Same logic for TotalAmount
                CONCAT(
                    LEFT(CAST(TotalAmount AS CHAR), 2),
                    REPEAT('*', GREATEST(0, CHAR_LENGTH(CAST(ROUND(ABS(TotalAmount), 2) AS CHAR)) - 5)),
                    RIGHT(CAST(ROUND(TotalAmount, 2) AS CHAR), 3)
                ) AS masked_total_amount

                -- Add more masked amount columns here if needed, using the same pattern
                -- , masked_refund_amount = CONCAT( ... same as above but with RefundAmount ... )

                -- Uncomment/include other columns as needed
                -- , PaymentMethod
                -- , PaymentStatus
                -- , PaymentPurpose
                -- , ReferenceNumber          -- consider masking: CONCAT('****', RIGHT(ReferenceNumber, 4))

                , created_at
                , updated_at
            FROM payments;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS payments_masked;');
    }
};