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
            // Add processed_by column to track who created the payment
            // For admin bookings: user_id (e.g., A001, S001)
            // For guest bookings via PayMongo: BookingID (e.g., B001)
            // Do not reference removed PaymentIMG column; add processed_by as nullable
            $table->string('processed_by', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('processed_by');
        });
    }
};
