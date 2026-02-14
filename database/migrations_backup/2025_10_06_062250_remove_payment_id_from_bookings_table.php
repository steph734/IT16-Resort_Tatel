<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Just drop the column if it exists
            if (Schema::hasColumn('bookings', 'PaymentID')) {
                $table->dropColumn('PaymentID');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add PaymentID back if needed for rollback
            $table->string('PaymentID', 10)->nullable()->after('GuestID');
        });
    }
};
