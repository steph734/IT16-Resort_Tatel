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
            CREATE OR REPLACE VIEW masked_users AS
            SELECT
                user_id,
                -- Name masking: first 2 letters + *** + last letter (adjust as needed)
                CASE
                    WHEN LENGTH(name) <= 4 THEN name
                    ELSE CONCAT(
                        LEFT(name, 2),
                        REPEAT('*', GREATEST(LENGTH(name) - 3, 0)),
                        RIGHT(name, 1)
                    )
                END AS name,

                -- Email masking: first 2 chars + *** + @domain.com
                CONCAT(
                    LEFT(email, 2),
                    REPEAT('*', GREATEST(LOCATE('@', email) - 3, 0)),
                    SUBSTRING(email, LOCATE('@', email))
                ) AS email,

                role,
                status,
                failed_attempts,          -- usually ok to show count (not the reason)
                lock_level,
                created_at,
                updated_at
                -- Deliberately excluded: password, locked_until, last_failed_login, remember_token
            FROM users;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS masked_users;');
    }
};