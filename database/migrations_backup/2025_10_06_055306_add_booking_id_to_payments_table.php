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
        Schema::table('payments', function (Blueprint $table) {
            // Add BookingID column only if it doesn't exist
            if (!Schema::hasColumn('payments', 'BookingID')) {
                $table->string('BookingID', 10)->nullable()->after('PaymentID');

                // Add foreign key constraint only if the column was just added
                $table->foreign('BookingID')
                    ->references('BookingID')
                    ->on('bookings')
                    ->onDelete('cascade');
            }

            // Add TotalAmount column if it doesn't exist
            if (!Schema::hasColumn('payments', 'TotalAmount')) {
                $table->decimal('TotalAmount', 20, 2)->nullable()->after('Amount');
            }

            // Add PaymentIMG column if it doesn't exist
            if (!Schema::hasColumn('payments', 'PaymentIMG')) {
                $table->string('PaymentIMG')->nullable()->after('PaymentPurpose');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign key if it exists
            if (Schema::hasColumn('payments', 'BookingID')) {
                $table->dropForeign(['BookingID']);
                $table->dropColumn('BookingID');
            }

            if (Schema::hasColumn('payments', 'TotalAmount')) {
                $table->dropColumn('TotalAmount');
            }

            if (Schema::hasColumn('payments', 'PaymentIMG')) {
                $table->dropColumn('PaymentIMG');
            }
        });
    }
};
