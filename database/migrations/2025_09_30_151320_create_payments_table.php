<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates payments table with final structure.
     * Added: ReferenceNumber, NameOnAccount, AccountNumber
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->string('PaymentID', 10)->primary();
            $table->string('BookingID', 10); // FK to bookings
            $table->date('PaymentDate');
            $table->decimal('Amount', 20, 2);
            $table->decimal('TotalAmount', 20, 2)->nullable();
            $table->string('PaymentMethod', 30);
            $table->string('PaymentStatus', 30);
            $table->string('PaymentPurpose', 100);
            $table->string('ReferenceNumber', 100)->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('BookingID')
                  ->references('BookingID')
                  ->on('bookings')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
