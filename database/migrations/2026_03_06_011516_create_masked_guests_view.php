<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW guests_masked AS
            SELECT

                /* Masked GuestID: first char visible + * middle + last 3 chars visible */
                CASE
                    WHEN GuestID IS NULL THEN NULL
                    WHEN LENGTH(GuestID) <= 4 THEN 
                        CONCAT(
                            LEFT(GuestID, 1),
                            REPEAT('*', GREATEST(LENGTH(GuestID) - 1, 0))
                        )
                    ELSE 
                        CONCAT(
                            LEFT(GuestID, 1),
                            REPEAT('*', LENGTH(GuestID) - 4),
                            RIGHT(GuestID, 3)
                        )
                END AS masked_GuestID,

                /* Masked email: first char + *** + @domain */
                CASE
                    WHEN email IS NULL OR email = '' THEN NULL
                    WHEN LOCATE('@', email) = 0 THEN CONCAT(LEFT(email, 2), '***')
                    ELSE CONCAT(
                        LEFT(email, 1),
                        REPEAT('*', GREATEST(LOCATE('@', email) - 2, 3)),
                        SUBSTRING(email, LOCATE('@', email))
                    )
                END AS masked_email,

                /* Masked phone: only first 2 digits visible (09) + * for the rest */
                CASE
                    WHEN phone IS NULL OR phone = '' THEN NULL
                    WHEN LENGTH(phone) <= 2 THEN REPEAT('*', LENGTH(phone))
                    ELSE CONCAT(
                        LEFT(phone, 2),
                        REPEAT('*', LENGTH(phone) - 2)
                    )
                END AS masked_phone,

                /* Unmasked / visible columns */
                fname,
                mname,
                lname,
                address,
                contactable,

                created_at,
                updated_at

            FROM guests;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS guests_masked;');
    }
};