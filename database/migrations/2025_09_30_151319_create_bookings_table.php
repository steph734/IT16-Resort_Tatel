<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates bookings table with final structure.
     * Added: ActualCheckInTime, ActualCheckOutTime, ExcessFee
     * Removed: NumOfSeniors, PaymentID (moved to payments table)
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('BookingID', 10)->primary();

            // Foreign Keys
            $table->string('GuestID', 10);
            $table->string('PackageID', 10);

            // Booking Details
            $table->date('BookingDate');
            $table->date('CheckInDate');
            $table->timestamp('ActualCheckInTime')->nullable();
            $table->date('CheckOutDate');
            $table->timestamp('ActualCheckOutTime')->nullable();
            $table->string('BookingStatus', 30);
            $table->integer('Pax');
            $table->integer('NumOfChild')->nullable();
            $table->decimal('ExcessFee', 10, 2)->default(0);
            $table->integer('NumOfSeniors')->nullable();
            $table->integer('NumOfAdults')->nullable();
            $table->decimal('senior_discount', 10, 2)->default(0);
            $table->integer('actual_seniors_at_checkout')->default(0);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('GuestID')->references('GuestID')->on('guests')->onDelete('cascade');
            $table->foreign('PackageID')->references('PackageID')->on('packages')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
