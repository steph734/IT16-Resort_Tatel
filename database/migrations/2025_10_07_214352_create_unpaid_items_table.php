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
        Schema::create('unpaid_items', function (Blueprint $table) {
            $table->string('ItemID')->primary();
            $table->string('BookingID');
            $table->string('ItemName');
            $table->integer('Quantity')->default(1);
            $table->decimal('Price', 10, 2);
            $table->decimal('TotalAmount', 10, 2);
            $table->boolean('IsPaid')->default(false);
            $table->timestamps();
            
            // Foreign key
            $table->foreign('BookingID')->references('BookingID')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unpaid_items');
    }
};
