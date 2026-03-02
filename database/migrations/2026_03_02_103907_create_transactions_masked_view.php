<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW transactions_masked AS
            SELECT
                transaction_id,
                transaction_type,
                
                -- Mask reference_id: keep prefix up to last '-', then '******'
                -- Example: 'TXN-BPY001' → 'TXN-******'
                -- If no dash → mask almost everything after first few chars
                CASE 
                    WHEN LOCATE('-', REVERSE(reference_id)) > 0 THEN 
                        CONCAT(
                            LEFT(reference_id, LENGTH(reference_id) - LOCATE('-', REVERSE(reference_id))),
                            '******'
                        )
                    ELSE 
                        CONCAT(LEFT(reference_id, 4), '******')
                END AS masked_reference_id,

                transaction_date,
                
                CONCAT(
                    LEFT(FLOOR(amount), 2),
                    REPEAT('*', GREATEST(LENGTH(FLOOR(amount)) - 2, 0)),
                    '.',
                    LPAD(RIGHT(ROUND(amount * 100), 2), 2, '0')
                ) AS masked_amount,

                payment_method,
                payment_status,
                purpose,

                CONCAT(
                    LEFT(booking_id, 3),
                    REPEAT('*', GREATEST(LENGTH(booking_id) - 6, 0)),
                    IF(LENGTH(booking_id) > 6, RIGHT(booking_id, 3), '')
                ) AS masked_booking_id,

                CONCAT(
                    LEFT(guest_id, 2),
                    REPEAT('*', GREATEST(LENGTH(guest_id) - 4, 0)),
                    IF(LENGTH(guest_id) > 4, RIGHT(guest_id, 2), '')
                ) AS masked_guest_id,

                rental_id,
                customer_name,

                CONCAT(
                    LEFT(customer_email, 1),
                    REPEAT('*', GREATEST(LOCATE('@', customer_email) - 2, 3)),
                    SUBSTRING(customer_email, LOCATE('@', customer_email))
                ) AS masked_customer_email,

                customer_phone,
                processed_by,
                processor_name,
                amount_received,
                change_amount,

                -- metadata: one-way hash (SHA-256 hex) – closest native to bcrypt
                -- Real bcrypt requires UDF plugin or app-level handling
                UPPER(TO_BASE64(SHA2(COALESCE(metadata, '{}'), 256))) AS hashed_metadata,

                reference_number,
                notes,
                is_voided,
                voided_at,
                voided_by,
                void_reason,
                created_at,
                updated_at

            FROM transactions;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS transactions_masked;');
    }
};