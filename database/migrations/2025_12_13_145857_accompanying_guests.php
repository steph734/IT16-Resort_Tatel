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
        Schema::create('accompanying_guests', function (Blueprint $table) {
            // Legacy-style primary key used elsewhere in the app
            $table->string('AccompanyingID', 10)->primary();

            // Reference to bookings table (BookingID)
            $table->string('BookingID', 10)->index();

            // Guest details
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('gender', 20);
            $table->string('guest_type', 50);

            $table->timestamps();

            // Add foreign key constraint if bookings table exists with BookingID primary key
            if (Schema::hasTable('bookings')) {
                $table->foreign('BookingID')->references('BookingID')->on('bookings')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accompanying_guests');
    }
};